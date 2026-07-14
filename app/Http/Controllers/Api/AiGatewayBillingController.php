<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiGatewayClient;
use App\Models\AiGatewayPlan;
use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayTransaction;
use App\Models\AiGatewayUserTrial;
use App\Services\AiGatewaySubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiGatewayBillingController extends Controller
{
    public function __construct(private AiGatewaySubscriptionService $subscriptionService)
    {
    }

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
        $gateway = strtolower((string) config('services.payment_gateway', 'xendit'));
        if (! in_array($gateway, ['xendit', 'midtrans'], true)) {
            return response()->json(['message' => 'Gateway pembayaran AI belum mendukung metode pembayaran utama yang dipilih.'], 422);
        }

        $s = AiGatewaySubscription::create(['ai_gateway_client_id' => $c->id, 'ai_gateway_plan_id' => $p->id, 'token_limit' => $p->token_limit, 'chat_limit' => $p->chat_limit, 'external_user_id' => $d['external_user_id'], 'external_user_name' => $d['customer_name'], 'external_user_email' => $d['customer_email'], 'status' => 'pending']);
        $id = 'AIGW-'.$c->id.'-'.$s->id.'-'.Str::upper(Str::random(8));
        $payment = $gateway === 'midtrans'
            ? $this->createMidtransCheckout($p, $id, $d, $successRedirectUrl, $failureRedirectUrl)
            : $this->createXenditCheckout($p, $id, $d, $successRedirectUrl, $failureRedirectUrl);

        if (! ($payment['success'] ?? false)) {
            $s->delete();

            return response()->json(['message' => $payment['message'] ?? 'Gagal membuat pembayaran Pembahasan AI.'], 422);
        }

        AiGatewayTransaction::create(['ai_gateway_client_id' => $c->id, 'ai_gateway_plan_id' => $p->id, 'ai_gateway_subscription_id' => $s->id, 'external_id' => $id, 'provider' => $gateway, 'provider_invoice_id' => $payment['provider_id'] ?? null, 'amount' => $p->price, 'status' => 'pending', 'details' => $payment['details']]);

        return ['invoice_url' => $payment['url'], 'external_id' => $id];
    }

    private function createXenditCheckout(AiGatewayPlan $plan, string $externalId, array $customer, ?string $successRedirectUrl, ?string $failureRedirectUrl): array
    {
        $secretKey = (string) config('services.xendit.secret_key');
        if ($secretKey === '') {
            return ['success' => false, 'message' => 'Credential Xendit belum dikonfigurasi.'];
        }

        $response = Http::withBasicAuth($secretKey, '')
            ->acceptJson()
            ->post(rtrim((string) config('services.xendit.base_url'), '/').'/v2/invoices', [
                'external_id' => $externalId,
                'amount' => $plan->price,
                'description' => 'Paket AI Gateway: '.$plan->name,
                'invoice_duration' => 86400,
                'success_redirect_url' => $successRedirectUrl,
                'failure_redirect_url' => $failureRedirectUrl,
                'customer' => ['given_names' => $customer['customer_name'], 'email' => $customer['customer_email']],
            ]);

        if (! $response->successful()) {
            Log::warning('AI gateway invoice creation rejected by Xendit.', [
                'status' => $response->status(),
                'provider_code' => trim((string) data_get($response->json(), 'error_code', 'unknown')),
                'provider_message' => Str::limit(trim(strip_tags((string) data_get($response->json(), 'message', ''))), 300),
                'external_id' => $externalId,
                'amount' => $plan->price,
            ]);

            return ['success' => false, 'message' => 'Layanan pembayaran Xendit menolak pembuatan tagihan.'];
        }

        $payload = $response->json();
        $url = (string) data_get($payload, 'invoice_url');

        return $url !== ''
            ? ['success' => true, 'url' => $url, 'provider_id' => data_get($payload, 'id'), 'details' => $payload]
            : ['success' => false, 'message' => 'Xendit tidak mengembalikan tautan pembayaran.'];
    }

    private function createMidtransCheckout(AiGatewayPlan $plan, string $externalId, array $customer, ?string $successRedirectUrl, ?string $failureRedirectUrl): array
    {
        $serverKey = (string) config('services.midtrans.server_key');
        if ($serverKey === '') {
            return ['success' => false, 'message' => 'Credential Midtrans belum dikonfigurasi.'];
        }

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->post(config('services.midtrans.snap_url'), [
                'transaction_details' => ['order_id' => $externalId, 'gross_amount' => $plan->price],
                'item_details' => [[
                    'id' => (string) $plan->id,
                    'price' => $plan->price,
                    'quantity' => 1,
                    'name' => Str::limit('Paket AI: '.$plan->name, 50, ''),
                ]],
                'customer_details' => ['first_name' => $customer['customer_name'], 'email' => $customer['customer_email']],
                'callbacks' => array_filter([
                    'finish' => $successRedirectUrl,
                    'error' => $failureRedirectUrl,
                ]),
            ]);

        if (! $response->successful()) {
            Log::warning('AI gateway payment creation rejected by Midtrans.', [
                'status' => $response->status(),
                'provider_code' => trim((string) data_get($response->json(), 'status_code', 'unknown')),
                'provider_message' => Str::limit(trim(strip_tags((string) data_get($response->json(), 'status_message', data_get($response->json(), 'error_messages.0', '')))), 300),
                'external_id' => $externalId,
                'amount' => $plan->price,
            ]);

            return ['success' => false, 'message' => 'Layanan pembayaran Midtrans menolak pembuatan tagihan.'];
        }

        $payload = $response->json();
        $url = (string) data_get($payload, 'redirect_url');

        return $url !== ''
            ? ['success' => true, 'url' => $url, 'details' => $payload]
            : ['success' => false, 'message' => 'Midtrans tidak mengembalikan tautan pembayaran.'];
    }

    public function webhook(Request $r)
    {
        $webhookToken = (string) config('services.xendit.webhook_token', '');
        $callbackToken = (string) $r->header('X-CALLBACK-TOKEN', '');

        if ($webhookToken === '' || $callbackToken === '' || ! hash_equals($webhookToken, $callbackToken)) {
            return response()->json(['message' => 'Invalid callback token'], 401);
        }

        $t = AiGatewayTransaction::where('external_id', $r->input('external_id'))
            ->where('provider', 'xendit')
            ->firstOrFail();
        if ($r->input('status') === 'PAID' && $t->status === 'pending') {
            $this->subscriptionService->activateTransaction($t);
        } elseif (in_array($r->input('status'), ['EXPIRED', 'FAILED'])) {
            $t->update(['status' => strtolower($r->input('status'))]);
        }

        return ['message' => 'OK'];
    }

    private function syncLatestPendingPayment(AiGatewayClient $client, string $externalUserId): void
    {
        $transaction = AiGatewayTransaction::query()
            ->where('ai_gateway_client_id', $client->id)
            ->where('status', 'pending')
            ->whereHas('subscription', fn ($query) => $query->where('external_user_id', $externalUserId))
            ->latest()
            ->first();

        if (!$transaction || $transaction->provider !== 'xendit' || blank($transaction->provider_invoice_id)) {
            return;
        }

        try {
            $http = Http::withBasicAuth((string) config('services.xendit.secret_key'), '')
                ->timeout(10)
                ->acceptJson();
            $baseUrl = rtrim((string) config('services.xendit.base_url'), '/');
            $response = $http->get($baseUrl . '/v2/invoices/' . $transaction->provider_invoice_id);
            $invoice = $response->successful() ? $response->json() : null;

            if (!is_array($invoice) || blank($invoice['status'] ?? null)) {
                $lookup = $http->get($baseUrl . '/v2/invoices', ['external_id' => $transaction->external_id]);
                $invoice = data_get($lookup->json(), 'data.0', $lookup->json());
            }

            if (strtoupper((string) data_get($invoice, 'status')) === 'PAID') {
                $this->subscriptionService->activateTransaction($transaction);
            }
        } catch (\Throwable $exception) {
            report($exception);
            // Webhook tetap menjadi jalur utama; sinkronisasi ini hanya fallback saat status dibuka user.
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
