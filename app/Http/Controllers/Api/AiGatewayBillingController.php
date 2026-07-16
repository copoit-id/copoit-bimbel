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
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiGatewayBillingController extends Controller
{
    public function __construct(
        private AiGatewaySubscriptionService $subscriptionService,
        private AiGatewayPaymentService $paymentService,
    ) {}

    private function client(Request $r): AiGatewayClient
    {
        $apiKey = (string) $r->header('X-AI-Gateway-Key', '');
        abort_if($apiKey === '', 401, 'Gateway key tidak valid.');

        $c = AiGatewayClient::where('api_key_hash', hash('sha256', $apiKey))->where('is_active', true)->first();
        abort_unless($c, 401, 'Gateway key tidak valid.');

        return $c;
    }

    public function plans()
    {
        return AiGatewayPlan::query()
            ->where('is_active', true)
            ->where('token_limit', '>', 0)
            ->orderBy('price')
            ->get(['id', 'name', 'slug', 'price', 'token_limit', 'chat_limit', 'duration_days']);
    }

    public function status(Request $r)
    {
        $c = $this->client($r);
        $userId = (string) $r->validate(['external_user_id' => 'required|string|max:120'])['external_user_id'];
        $this->syncLatestPendingPayment($c, $userId);
        $subscriptions = AiGatewaySubscription::with('plan')->where('ai_gateway_client_id', $c->id)->where('external_user_id', $userId)->where('status', 'active')->where('ends_at', '>', now())->latest()->get();
        $s = $subscriptions->first();
        $trial = AiGatewayUserTrial::where('ai_gateway_client_id', $c->id)->where('external_user_id', $userId)->first();
        $pendingPayment = AiGatewayTransaction::query()
            ->with(['plan:id,name', 'subscription:id,external_user_id'])
            ->where('ai_gateway_client_id', $c->id)
            ->where('status', 'pending')
            ->where('created_at', '>', now()->subDay())
            ->whereHas('subscription', fn ($query) => $query->where('external_user_id', $userId))
            ->latest()
            ->first();

        return ['project' => $c->name, 'subscription' => $s, 'subscriptions' => $subscriptions, 'pending_payment' => $pendingPayment ? ['plan_name' => $pendingPayment->plan?->name, 'invoice_url' => $this->paymentUrl($pendingPayment), 'expires_at' => $pendingPayment->created_at?->copy()->addDay()->toIso8601String()] : null, 'trial' => ['available' => $c->free_token_limit > 0 || $c->free_chat_limit > 0, 'token_limit' => $c->free_token_limit, 'chat_limit' => $c->free_chat_limit, 'tokens_used' => $trial?->tokens_used ?? 0, 'chats_used' => $trial?->chats_used ?? 0]];
    }

    public function checkout(Request $r)
    {
        $c = $this->client($r);
        $d = $r->validate(['plan_id' => 'required|integer|exists:ai_gateway_plans,id', 'external_user_id' => 'required|string|max:120', 'customer_name' => 'required|string|max:100', 'customer_email' => 'required|email', 'success_redirect_url' => 'nullable|url|max:2048', 'failure_redirect_url' => 'nullable|url|max:2048']);
        $successRedirectUrl = $this->allowedRedirectUrl($c, $d['success_redirect_url'] ?? null);
        $failureRedirectUrl = $this->allowedRedirectUrl($c, $d['failure_redirect_url'] ?? null);
        $p = AiGatewayPlan::where('is_active', true)->where('token_limit', '>', 0)->findOrFail($d['plan_id']);
        $this->syncLatestPendingPayment($c, $d['external_user_id']);
        $pendingTransaction = AiGatewayTransaction::query()
            ->with('subscription')
            ->where('ai_gateway_client_id', $c->id)
            ->where('ai_gateway_plan_id', $p->id)
            ->where('status', 'pending')
            ->where('created_at', '>', now()->subDay())
            ->whereHas('subscription', fn ($query) => $query->where('external_user_id', $d['external_user_id']))
            ->latest()
            ->first();

        if ($pendingTransaction && filled($this->paymentUrl($pendingTransaction))) {
            return [
                'invoice_url' => $this->paymentUrl($pendingTransaction),
                'external_id' => $pendingTransaction->external_id,
                'reused_pending_invoice' => true,
            ];
        }

        if ($pendingTransaction) {
            $pendingTransaction->update(['status' => 'expired']);
            $pendingTransaction->subscription?->update(['status' => 'expired']);
        }
        $s = AiGatewaySubscription::create(['ai_gateway_client_id' => $c->id, 'ai_gateway_plan_id' => $p->id, 'token_limit' => $p->token_limit, 'chat_limit' => $p->chat_limit, 'external_user_id' => $d['external_user_id'], 'external_user_name' => $d['customer_name'], 'external_user_email' => $d['customer_email'], 'status' => 'pending']);
        $id = 'AIGW-'.$c->id.'-'.$s->id.'-'.Str::upper(Str::random(8));
        try {
            $payment = $this->paymentService->createCheckout($p, $id, $d, $successRedirectUrl, $failureRedirectUrl);
        } catch (\Throwable $exception) {
            report($exception);
            $s->delete();

            return response()->json(['message' => 'Payment gateway AI belum siap digunakan.'], 422);
        }

        if (! ($payment['success'] ?? false)) {
            $s->delete();

            return response()->json(['message' => $payment['message'] ?? 'Gagal membuat pembayaran Pembahasan AI.'], 422);
        }

        AiGatewayTransaction::create(['ai_gateway_client_id' => $c->id, 'ai_gateway_plan_id' => $p->id, 'ai_gateway_subscription_id' => $s->id, 'external_id' => $id, 'provider' => $payment['provider'], 'provider_invoice_id' => $payment['provider_id'] ?? null, 'amount' => $p->price, 'status' => 'pending', 'details' => $payment['details']]);

        return ['invoice_url' => $payment['url'], 'external_id' => $id];
    }

    public function webhook(Request $r)
    {
        if (! $this->paymentService->handleXenditWebhook($r->all(), $r->header('X-CALLBACK-TOKEN'))) {
            return response()->json(['message' => 'Invalid callback token'], 401);
        }

        return ['message' => 'OK'];
    }

    public function midtransWebhook(Request $request)
    {
        if (! $this->paymentService->handleMidtransWebhook($request->all())) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        return ['message' => 'OK'];
    }

    public function ipaymuWebhook(Request $request)
    {
        $externalId = (string) ($request->input('reference_id') ?: $request->input('referenceId') ?: '');
        $transaction = $externalId !== ''
            ? AiGatewayTransaction::query()->where('external_id', $externalId)->where('provider', 'ipaymu')->first()
            : null;

        if (! $transaction) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $this->paymentService->synchronize($transaction);

        return ['message' => 'OK'];
    }

    public function showQrisPayment(string $externalId)
    {
        $transaction = AiGatewayTransaction::query()->where('external_id', $externalId)->where('provider', 'interactive_qris')->firstOrFail();
        $transaction = $this->paymentService->synchronize($transaction);

        return view('ai-gateway.qris-payment', compact('transaction'));
    }

    public function qrisStatus(string $externalId)
    {
        $transaction = AiGatewayTransaction::query()->where('external_id', $externalId)->where('provider', 'interactive_qris')->firstOrFail();
        $transaction = $this->paymentService->synchronize($transaction);

        return response()->json(['status' => $transaction->status]);
    }

    private function syncLatestPendingPayment(AiGatewayClient $client, string $externalUserId): void
    {
        $transaction = AiGatewayTransaction::query()
            ->where('ai_gateway_client_id', $client->id)
            ->where('status', 'pending')
            ->whereHas('subscription', fn ($query) => $query->where('external_user_id', $externalUserId))
            ->latest()
            ->first();

        if (! $transaction) {
            return;
        }

        $this->paymentService->synchronize($transaction);
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
