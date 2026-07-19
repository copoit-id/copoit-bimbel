<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiGatewayClient;
use App\Models\AiGatewayPlan;
use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayTransaction;
use App\Models\AiGatewayUserTrial;
use App\Services\AiGatewayPaymentService;
use App\Services\AiGatewaySubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiGatewayBillingController extends Controller
{
    public function __construct(
        private AiGatewaySubscriptionService $subscriptionService,
        private AiGatewayPaymentService $paymentService,
    ) {}

    public function plans(): JsonResponse
    {
        $plans = AiGatewayPlan::query()
            ->where('is_active', true)
            ->where('token_limit', '>', 0)
            ->orderBy('price')
            ->orderBy('id')
            ->get()
            ->map(fn (AiGatewayPlan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'price' => $plan->price,
                'is_free' => $plan->isFree(),
                'token_limit' => $plan->token_limit,
                'chat_limit' => $plan->chat_limit,
                'duration_days' => $plan->duration_days,
            ]);

        return response()->json($plans);
    }

    public function status(Request $request): JsonResponse
    {
        $client = $this->client($request);
        $externalUserId = trim((string) $request->validate([
            'external_user_id' => 'required|string|max:120',
        ])['external_user_id']);
        $this->syncLatestPendingPayment($client, $externalUserId);
        $subscriptions = AiGatewaySubscription::with('plan')
            ->where('ai_gateway_client_id', $client->id)
            ->where('external_user_id', $externalUserId)
            ->where('status', 'active')
            ->notExpired()
            ->whereHas('transactions', fn ($query) => $query->where('status', 'paid'))
            ->latest()
            ->get()
            ->each(fn (AiGatewaySubscription $subscription) => $subscription->setAttribute('payment_confirmed', true));
        $subscription = $subscriptions->first();
        $trial = AiGatewayUserTrial::where('ai_gateway_client_id', $client->id)
            ->where('external_user_id', $externalUserId)
            ->first();
        $pendingPayment = AiGatewayTransaction::query()
            ->with(['plan:id,name', 'subscription:id,external_user_id'])
            ->where('ai_gateway_client_id', $client->id)
            ->where('status', 'pending')
            ->where('created_at', '>', now()->subDay())
            ->whereHas('subscription', fn ($query) => $query->where('external_user_id', $externalUserId))
            ->latest()
            ->first();
        $claimedFreePlanIds = AiGatewaySubscription::query()
            ->where('ai_gateway_client_id', $client->id)
            ->where('external_user_id', $externalUserId)
            ->where('status', 'active')
            ->notExpired()
            ->whereNotNull('free_claim_key')
            ->whereHas('transactions', fn ($query) => $query->where('status', 'paid'))
            ->pluck('ai_gateway_plan_id')
            ->unique()
            ->values();

        return response()->json([
            'project' => $client->name,
            'subscription' => $subscription,
            'subscriptions' => $subscriptions,
            'pending_payment' => $pendingPayment ? [
                'plan_id' => $pendingPayment->ai_gateway_plan_id,
                'subscription_id' => $pendingPayment->ai_gateway_subscription_id,
                'plan_name' => $pendingPayment->plan?->name,
                'invoice_url' => $this->paymentUrl($pendingPayment),
                'expires_at' => $pendingPayment->created_at?->copy()->addDay()->toIso8601String(),
            ] : null,
            'trial' => [
                'available' => $client->free_token_limit > 0 || $client->free_chat_limit > 0,
                'token_limit' => $client->free_token_limit,
                'chat_limit' => $client->free_chat_limit,
                'tokens_used' => $trial?->tokens_used ?? 0,
                'chats_used' => $trial?->chats_used ?? 0,
            ],
            'claimed_free_plan_ids' => $claimedFreePlanIds,
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $client = $this->client($request);
        $data = $request->validate([
            'plan_id' => 'required|integer|exists:ai_gateway_plans,id',
            'external_user_id' => 'required|string|max:120',
            'customer_name' => 'nullable|string|max:100',
            'customer_email' => 'nullable|email',
            'success_redirect_url' => 'nullable|url|max:2048',
            'failure_redirect_url' => 'nullable|url|max:2048',
        ]);
        $data['external_user_id'] = trim($data['external_user_id']);
        $successRedirectUrl = $this->allowedRedirectUrl($client, $data['success_redirect_url'] ?? null);
        $failureRedirectUrl = $this->allowedRedirectUrl($client, $data['failure_redirect_url'] ?? null);
        $plan = AiGatewayPlan::where('is_active', true)
            ->where('token_limit', '>', 0)
            ->findOrFail($data['plan_id']);

        if ($plan->isFree()) {
            $claim = $this->subscriptionService->claimFreePlan($client, $plan, $data);
            $subscription = $claim['subscription'];
            $transaction = $claim['transaction'];
            $isActive = $subscription?->status === 'active'
                && ($subscription->ends_at === null || $subscription->ends_at->isFuture())
                && $subscription->hasRemainingQuota();

            return response()->json([
                'message' => $claim['already_claimed']
                    ? 'Paket gratis ini sudah pernah diklaim.'
                    : 'Paket gratis berhasil diklaim dan langsung aktif.',
                'activated' => $isActive,
                'claimed' => ! $claim['already_claimed'],
                'already_claimed' => $claim['already_claimed'],
                'invoice_url' => null,
                'external_id' => $transaction?->external_id,
                'subscription' => $subscription,
            ]);
        }

        $request->validate([
            'customer_name' => 'required|string|max:100',
            'customer_email' => 'required|email',
        ]);

        $this->syncLatestPendingPayment($client, $data['external_user_id']);
        $pendingTransaction = AiGatewayTransaction::query()
            ->with('subscription')
            ->where('ai_gateway_client_id', $client->id)
            ->where('ai_gateway_plan_id', $plan->id)
            ->where('status', 'pending')
            ->where('created_at', '>', now()->subDay())
            ->whereHas('subscription', fn ($query) => $query->where('external_user_id', $data['external_user_id']))
            ->latest()
            ->first();

        if ($pendingTransaction && filled($this->paymentUrl($pendingTransaction))) {
            return response()->json([
                'activated' => false,
                'claimed' => false,
                'invoice_url' => $this->paymentUrl($pendingTransaction),
                'external_id' => $pendingTransaction->external_id,
                'reused_pending_invoice' => true,
            ]);
        }

        if ($pendingTransaction) {
            $pendingTransaction->update(['status' => 'expired']);
            $pendingTransaction->subscription?->update(['status' => 'expired']);
        }

        $subscription = AiGatewaySubscription::create([
            'ai_gateway_client_id' => $client->id,
            'ai_gateway_plan_id' => $plan->id,
            'token_limit' => $plan->token_limit,
            'chat_limit' => $plan->chat_limit,
            'external_user_id' => $data['external_user_id'],
            'external_user_name' => $data['customer_name'],
            'external_user_email' => $data['customer_email'],
            'status' => 'pending',
        ]);
        $externalId = 'AIGW-'.$client->id.'-'.$subscription->id.'-'.Str::upper(Str::random(8));

        try {
            $payment = $this->paymentService->createCheckout(
                $plan,
                $externalId,
                $data,
                $successRedirectUrl,
                $failureRedirectUrl
            );
        } catch (\Throwable $exception) {
            report($exception);
            $subscription->delete();

            return response()->json(['message' => 'Payment gateway AI belum siap digunakan.'], 422);
        }

        if (! ($payment['success'] ?? false)) {
            $subscription->delete();

            return response()->json([
                'message' => $payment['message'] ?? 'Gagal membuat pembayaran Pembahasan AI.',
            ], 422);
        }

        AiGatewayTransaction::create([
            'ai_gateway_client_id' => $client->id,
            'ai_gateway_plan_id' => $plan->id,
            'ai_gateway_subscription_id' => $subscription->id,
            'external_id' => $externalId,
            'provider' => $payment['provider'],
            'provider_invoice_id' => $payment['provider_id'] ?? null,
            'amount' => $plan->price,
            'status' => 'pending',
            'details' => $payment['details'],
        ]);

        return response()->json([
            'activated' => false,
            'claimed' => false,
            'invoice_url' => $payment['url'],
            'external_id' => $externalId,
        ]);
    }

    /**
     * Invalidate the caller's latest unpaid AI invoice.
     *
     * Provider invoice cancellation is intentionally not assumed here because
     * provider APIs differ. The local transaction is made terminal, so a late
     * provider callback cannot activate a subscription after the user cancels.
     */
    public function cancelPending(Request $request): JsonResponse
    {
        $client = $this->client($request);
        $externalUserId = trim((string) $request->validate([
            'external_user_id' => 'required|string|max:120',
        ])['external_user_id']);

        // Never cancel an invoice which has just been confirmed by the provider.
        $this->syncLatestPendingPayment($client, $externalUserId);

        $cancelled = DB::transaction(function () use ($client, $externalUserId): bool {
            $transaction = AiGatewayTransaction::query()
                ->where('ai_gateway_client_id', $client->id)
                ->where('status', 'pending')
                ->whereHas('subscription', fn ($query) => $query->where('external_user_id', $externalUserId))
                ->latest()
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                return false;
            }

            $details = is_array($transaction->details) ? $transaction->details : [];
            $details['cancelled_at'] = now()->toIso8601String();
            $details['cancellation_source'] = 'client_request';

            $transaction->update([
                'status' => 'cancelled',
                'details' => $details,
            ]);

            AiGatewaySubscription::query()
                ->whereKey($transaction->ai_gateway_subscription_id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            return true;
        });

        if (! $cancelled) {
            return response()->json([
                'message' => 'Tidak ada invoice Pembahasan AI pending yang dapat dibatalkan.',
            ], 422);
        }

        return response()->json(['cancelled' => true]);
    }

    public function webhook(Request $request): JsonResponse
    {
        if (! $this->paymentService->handleXenditWebhook(
            $request->all(),
            $request->header('X-CALLBACK-TOKEN')
        )) {
            return response()->json(['message' => 'Invalid callback token'], 401);
        }

        return response()->json(['message' => 'OK']);
    }

    public function midtransWebhook(Request $request): JsonResponse
    {
        if (! $this->paymentService->handleMidtransWebhook($request->all())) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        return response()->json(['message' => 'OK']);
    }

    public function ipaymuWebhook(Request $request): JsonResponse
    {
        $externalId = (string) ($request->input('reference_id') ?: $request->input('referenceId') ?: '');
        $transaction = $externalId !== ''
            ? AiGatewayTransaction::query()->where('external_id', $externalId)->where('provider', 'ipaymu')->first()
            : null;

        if (! $transaction) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $this->paymentService->synchronize($transaction);

        return response()->json(['message' => 'OK']);
    }

    public function showQrisPayment(string $externalId)
    {
        $transaction = AiGatewayTransaction::query()
            ->where('external_id', $externalId)
            ->where('provider', 'interactive_qris')
            ->firstOrFail();
        $transaction = $this->paymentService->synchronize($transaction);

        return view('ai-gateway.qris-payment', compact('transaction'));
    }

    public function qrisStatus(string $externalId): JsonResponse
    {
        $transaction = AiGatewayTransaction::query()
            ->where('external_id', $externalId)
            ->where('provider', 'interactive_qris')
            ->firstOrFail();
        $transaction = $this->paymentService->synchronize($transaction);

        return response()->json(['status' => $transaction->status]);
    }

    private function client(Request $request): AiGatewayClient
    {
        $apiKey = (string) $request->header('X-AI-Gateway-Key', '');
        abort_if($apiKey === '', 401, 'Gateway key tidak valid.');

        $client = AiGatewayClient::where('api_key_hash', hash('sha256', $apiKey))
            ->where('is_active', true)
            ->first();
        abort_unless($client, 401, 'Gateway key tidak valid.');

        return $client;
    }

    private function syncLatestPendingPayment(AiGatewayClient $client, string $externalUserId): void
    {
        $transaction = AiGatewayTransaction::query()
            ->where('ai_gateway_client_id', $client->id)
            ->where('status', 'pending')
            ->whereHas('subscription', fn ($query) => $query->where('external_user_id', $externalUserId))
            ->latest()
            ->first();

        if ($transaction) {
            $this->paymentService->synchronize($transaction);
        }
    }

    private function paymentUrl(AiGatewayTransaction $transaction): ?string
    {
        return data_get($transaction->details, 'invoice_url')
            ?: data_get($transaction->details, 'redirect_url');
    }

    private function allowedRedirectUrl(AiGatewayClient $client, ?string $redirectUrl): ?string
    {
        if (blank($redirectUrl)) {
            return null;
        }

        $clientUrl = parse_url((string) $client->base_url);
        $targetUrl = parse_url($redirectUrl);
        $sameOrigin = isset($clientUrl['scheme'], $clientUrl['host'], $targetUrl['scheme'], $targetUrl['host'])
            && strtolower($clientUrl['scheme']) === strtolower($targetUrl['scheme'])
            && strtolower($clientUrl['host']) === strtolower($targetUrl['host'])
            && ($clientUrl['port'] ?? null) === ($targetUrl['port'] ?? null);

        abort_unless($sameOrigin, 422, 'URL pengalihan harus berasal dari domain klien yang terdaftar.');

        return $redirectUrl;
    }
}
