<?php

namespace App\Services;

use App\Models\AiGatewayPlan;
use App\Models\AiGatewayTransaction;
use App\Models\ClientProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class AiGatewayPaymentService
{
    public function __construct(private AiGatewaySubscriptionService $subscriptionService) {}

    public function createCheckout(AiGatewayPlan $plan, string $externalId, array $customer, ?string $successUrl, ?string $failureUrl): array
    {
        $settings = $this->settings();

        return match ($settings['gateway']) {
            'xendit' => $this->createXendit($settings, $plan, $externalId, $customer, $successUrl, $failureUrl),
            'midtrans' => $this->createMidtrans($settings, $plan, $externalId, $customer, $successUrl, $failureUrl),
            'ipaymu' => $this->createIpaymu($settings, $plan, $externalId, $customer, $successUrl, $failureUrl),
            'interactive_qris' => $this->createInteractiveQris($settings, $plan, $externalId),
            default => throw new RuntimeException('Gateway pembayaran AI tidak valid.'),
        };
    }

    public function synchronize(AiGatewayTransaction $transaction): AiGatewayTransaction
    {
        if ($transaction->status !== 'pending') {
            return $transaction;
        }

        try {
            $settings = $this->settings();
        } catch (\Throwable $exception) {
            report($exception);

            return $transaction;
        }
        $details = is_array($transaction->details) ? $transaction->details : [];

        try {
            match ($transaction->provider) {
                'xendit' => $this->syncXendit($transaction, $settings, $details),
                'midtrans' => $this->syncMidtrans($transaction, $settings),
                'ipaymu' => $this->syncIpaymu($transaction, $settings, $details),
                'interactive_qris' => $this->syncInteractiveQris($transaction, $settings, $details),
                default => null,
            };
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $transaction->fresh(['subscription']) ?? $transaction;
    }

    public function handleXenditWebhook(array $payload, ?string $callbackToken): bool
    {
        $settings = $this->settings();
        $expectedToken = (string) ($settings['xendit_webhook_token'] ?? '');

        if ($expectedToken === '' || $callbackToken === null || ! hash_equals($expectedToken, $callbackToken)) {
            return false;
        }

        $transaction = AiGatewayTransaction::query()
            ->where('external_id', $payload['external_id'] ?? null)
            ->where('provider', 'xendit')
            ->first();

        if (! $transaction) {
            return true;
        }

        $this->applyProviderStatus($transaction, (string) ($payload['status'] ?? ''));

        return true;
    }

    public function handleMidtransWebhook(array $payload): bool
    {
        $settings = $this->settings();
        $orderId = (string) ($payload['order_id'] ?? '');
        $signature = (string) ($payload['signature_key'] ?? '');
        $expected = hash('sha512', $orderId.($payload['status_code'] ?? '').($payload['gross_amount'] ?? '').($settings['midtrans_server_key'] ?? ''));

        if ($orderId === '' || $signature === '' || ! hash_equals($expected, $signature)) {
            return false;
        }

        $transaction = AiGatewayTransaction::query()->where('external_id', $orderId)->where('provider', 'midtrans')->first();
        if (! $transaction) {
            return true;
        }

        $status = (string) ($payload['transaction_status'] ?? '');
        if ($status === 'capture' && ($payload['fraud_status'] ?? null) === 'challenge') {
            return true;
        }
        $this->applyProviderStatus($transaction, $status);

        return true;
    }

    private function createXendit(array $settings, AiGatewayPlan $plan, string $externalId, array $customer, ?string $successUrl, ?string $failureUrl): array
    {
        $secret = (string) ($settings['xendit_secret_key'] ?? '');
        if ($secret === '') {
            return $this->failure('Credential Xendit AI Gateway belum diatur oleh Super Admin.');
        }

        $response = Http::withBasicAuth($secret, '')
            ->acceptJson()
            ->post('https://api.xendit.co/v2/invoices', [
                'external_id' => $externalId,
                'amount' => $plan->price,
                'description' => 'Paket AI Gateway: '.$plan->name,
                'invoice_duration' => 86400,
                'success_redirect_url' => $successUrl,
                'failure_redirect_url' => $failureUrl,
                'customer' => ['given_names' => $customer['customer_name'], 'email' => $customer['customer_email']],
            ]);

        $payload = $response->json() ?: [];

        return $response->successful() && filled($payload['invoice_url'] ?? null)
            ? $this->success('xendit', $payload['invoice_url'], $payload, $payload['id'] ?? null)
            : $this->failure('Xendit menolak pembuatan invoice AI.');
    }

    private function createMidtrans(array $settings, AiGatewayPlan $plan, string $externalId, array $customer, ?string $successUrl, ?string $failureUrl): array
    {
        $serverKey = (string) ($settings['midtrans_server_key'] ?? '');
        if ($serverKey === '') {
            return $this->failure('Credential Midtrans AI Gateway belum diatur oleh Super Admin.');
        }

        $url = ($settings['mode'] ?? 'sandbox') === 'production'
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->post($url, [
                'transaction_details' => ['order_id' => $externalId, 'gross_amount' => $plan->price],
                'item_details' => [['id' => (string) $plan->id, 'price' => $plan->price, 'quantity' => 1, 'name' => Str::limit('Paket AI: '.$plan->name, 50, '')]],
                'customer_details' => ['first_name' => $customer['customer_name'], 'email' => $customer['customer_email']],
                'callbacks' => array_filter(['finish' => $successUrl, 'error' => $failureUrl]),
            ]);
        $payload = $response->json() ?: [];

        return $response->successful() && filled($payload['redirect_url'] ?? null)
            ? $this->success('midtrans', $payload['redirect_url'], $payload)
            : $this->failure('Midtrans menolak pembuatan pembayaran AI.');
    }

    private function createIpaymu(array $settings, AiGatewayPlan $plan, string $externalId, array $customer, ?string $successUrl, ?string $failureUrl): array
    {
        if (blank($settings['ipaymu_api_key'] ?? null) || blank($settings['ipaymu_va'] ?? null)) {
            return $this->failure('Credential iPaymu AI Gateway belum diatur oleh Super Admin.');
        }

        $payload = array_filter([
            'account' => $settings['ipaymu_va'],
            'product' => [Str::limit('Paket AI: '.$plan->name, 80, '')],
            'qty' => [1],
            'price' => [(int) $plan->price],
            'description' => ['Pembelian paket AI Gateway'],
            'notifyUrl' => route('webhook.ai-gateway.ipaymu'),
            'returnUrl' => $successUrl,
            'cancelUrl' => $failureUrl,
            'buyerName' => $customer['customer_name'],
            'buyerEmail' => $customer['customer_email'],
            'referenceId' => $externalId,
            'expired' => 24,
            'expiredType' => 'hours',
        ], fn ($value) => $value !== null && $value !== '');
        $response = $this->ipaymuPost($settings, '/api/v2/payment', $payload);
        $body = $response->json() ?: [];
        $data = $body['Data'] ?? $body['data'] ?? [];
        $url = $data['Url'] ?? $data['url'] ?? $data['PaymentUrl'] ?? $data['payment_url'] ?? null;

        if ($response->successful() && $this->ipaymuSuccess($body) && filled($url)) {
            return $this->success('ipaymu', $url, [
                'redirect_url' => $url,
                'session_id' => $data['SessionID'] ?? $data['sessionId'] ?? null,
                'ipaymu_transaction_id' => $data['TransactionId'] ?? $data['transactionId'] ?? null,
                'external_id' => $externalId,
                'response' => $data,
            ]);
        }

        $message = $this->ipaymuResponseMessage($body, 'iPaymu menolak pembuatan pembayaran AI.', $response->status());
        Log::warning('AI Gateway iPaymu invoice creation rejected.', [
            'external_id' => $externalId,
            'http_status' => $response->status(),
            'provider_status' => $body['Status'] ?? $body['status'] ?? null,
            'provider_message' => $message,
        ]);

        return $this->failure($message);
    }

    private function createInteractiveQris(array $settings, AiGatewayPlan $plan, string $externalId): array
    {
        if (blank($settings['interactive_qris_api_key'] ?? null) || blank($settings['interactive_qris_mid'] ?? null)) {
            return $this->failure('Credential InterActive QRIS AI Gateway belum diatur oleh Super Admin.');
        }

        $response = Http::timeout(20)->get('https://qris.interactive.co.id/restapi/qris/show_qris.php', [
            'do' => 'create-invoice',
            'apikey' => $settings['interactive_qris_api_key'],
            'mID' => $settings['interactive_qris_mid'],
            'cliTrxNumber' => $externalId,
            'cliTrxAmount' => (int) $plan->price,
            'useTip' => ($settings['interactive_qris_use_tip'] ?? false) ? 'yes' : 'no',
        ]);
        $payload = $response->json() ?: [];
        $data = $payload['data'] ?? [];

        return $response->successful() && ($payload['status'] ?? null) === 'success' && filled($data['qris_content'] ?? null)
            ? $this->success('interactive_qris', route('ai-gateway-payments.qris.show', $externalId), [
                'redirect_url' => route('ai-gateway-payments.qris.show', $externalId),
                'qris_content' => $data['qris_content'],
                'qris_invoiceid' => $data['qris_invoiceid'] ?? null,
                'qris_request_date' => $data['qris_request_date'] ?? now()->toDateTimeString(),
                'expires_at' => now()->addMinutes(30)->toIso8601String(),
            ])
            : $this->failure('InterActive QRIS menolak pembuatan invoice AI.');
    }

    private function syncXendit(AiGatewayTransaction $transaction, array $settings, array $details): void
    {
        if (blank($details['id'] ?? $details['invoice_id'] ?? $transaction->provider_invoice_id)) {
            return;
        }
        $invoiceId = $details['id'] ?? $details['invoice_id'] ?? $transaction->provider_invoice_id;
        $response = Http::withBasicAuth((string) $settings['xendit_secret_key'], '')
            ->acceptJson()->get('https://api.xendit.co/v2/invoices/'.$invoiceId);
        if ($response->successful()) {
            $this->applyProviderStatus($transaction, (string) data_get($response->json(), 'status'));
        }
    }

    private function syncMidtrans(AiGatewayTransaction $transaction, array $settings): void
    {
        $base = ($settings['mode'] ?? 'sandbox') === 'production' ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        $response = Http::withBasicAuth((string) $settings['midtrans_server_key'], '')
            ->acceptJson()->get($base.'/v2/'.$transaction->external_id.'/status');
        if ($response->successful()) {
            $this->applyProviderStatus($transaction, (string) data_get($response->json(), 'transaction_status'));
        }
    }

    private function syncIpaymu(AiGatewayTransaction $transaction, array $settings, array $details): void
    {
        $lookup = $details['ipaymu_transaction_id'] ?? $details['session_id'] ?? $transaction->external_id;
        $response = $this->ipaymuPost($settings, '/api/v2/transaction', ['transactionId' => $lookup]);
        $payload = $response->json() ?: [];
        $status = Str::lower(json_encode($payload) ?: '');
        if (Str::contains($status, ['"paid"', '"success"', '"settlement"', '"lunas"'])) {
            $this->subscriptionService->activateTransaction($transaction);
        } elseif (Str::contains($status, ['"expired"', '"failed"', '"cancel"'])) {
            $this->markFailed($transaction, Str::contains($status, 'expired') ? 'expired' : 'failed');
        }
    }

    private function syncInteractiveQris(AiGatewayTransaction $transaction, array $settings, array $details): void
    {
        if (blank($details['qris_invoiceid'] ?? null)) {
            return;
        }
        $response = Http::timeout(20)->get('https://qris.interactive.co.id/restapi/qris/checkpaid_qris.php', [
            'do' => 'checkStatus',
            'apikey' => $settings['interactive_qris_api_key'],
            'mID' => $settings['interactive_qris_mid'],
            'invid' => $details['qris_invoiceid'],
            'trxvalue' => (int) $transaction->amount,
            'trxdate' => Carbon::parse($details['qris_request_date'] ?? $transaction->created_at)->toDateString(),
        ]);
        if (Str::lower((string) data_get($response->json(), 'data.qris_status')) === 'paid') {
            $this->subscriptionService->activateTransaction($transaction);
        } elseif (filled($details['expires_at'] ?? null) && now()->gte($details['expires_at'])) {
            $this->markFailed($transaction, 'expired');
        }
    }

    private function applyProviderStatus(AiGatewayTransaction $transaction, string $status): void
    {
        $status = Str::lower($status);
        if (in_array($status, ['paid', 'settlement', 'capture'], true)) {
            $this->subscriptionService->activateTransaction($transaction);
        } elseif (in_array($status, ['expired', 'expire'], true)) {
            $this->markFailed($transaction, 'expired');
        } elseif (in_array($status, ['failed', 'cancel', 'deny', 'failure'], true)) {
            $this->markFailed($transaction, 'failed');
        }
    }

    private function markFailed(AiGatewayTransaction $transaction, string $status): void
    {
        $transaction->update(['status' => $status]);
        $transaction->subscription?->update(['status' => $status]);
    }

    private function ipaymuPost(array $settings, string $path, array $payload)
    {
        // iPaymu memakai JSON.stringify pada contoh resminya. Kirim body tersebut
        // apa adanya agar signature dan URL callback memiliki format yang sama.
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $apiKey = (string) $settings['ipaymu_api_key'];
        $va = (string) $settings['ipaymu_va'];
        $signature = hash_hmac('sha256', 'POST:'.$va.':'.strtolower(hash('sha256', $body)).':'.$apiKey, $apiKey);
        $base = ($settings['mode'] ?? 'sandbox') === 'production' ? 'https://my.ipaymu.com' : 'https://sandbox.ipaymu.com';

        return Http::acceptJson()->withBody($body, 'application/json')->timeout(30)->withHeaders([
            'va' => $va,
            'signature' => $signature,
            'timestamp' => now()->format('YmdHis'),
        ])->post($base.'/'.ltrim($path, '/'));
    }

    private function settings(): array
    {
        $settings = ClientProfile::query()->first()?->ai_gateway_payment_settings;
        if (! is_array($settings) || blank($settings['gateway'] ?? null)) {
            throw new RuntimeException('Payment gateway AI belum diatur oleh Super Admin.');
        }

        return $settings;
    }

    private function success(string $provider, string $url, array $details, ?string $providerId = null): array
    {
        return ['success' => true, 'provider' => $provider, 'url' => $url, 'provider_id' => $providerId, 'details' => $details];
    }

    private function failure(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }

    private function ipaymuSuccess(array $payload): bool
    {
        return in_array((string) ($payload['Status'] ?? $payload['status'] ?? ''), ['200', '201', 'success', 'Success'], true);
    }

    private function ipaymuResponseMessage(array $payload, string $fallback, int $httpStatus): string
    {
        $message = data_get($payload, 'Message')
            ?? data_get($payload, 'message')
            ?? data_get($payload, 'StatusDesc')
            ?? data_get($payload, 'statusDesc')
            ?? data_get($payload, 'Description')
            ?? data_get($payload, 'description')
            ?? data_get($payload, 'Data.Message')
            ?? data_get($payload, 'data.message');

        if (is_array($message)) {
            $message = collect($message)->flatten()->filter()->implode(' ');
        }

        $message = trim(strip_tags((string) $message));

        return $message !== ''
            ? 'iPaymu: '.Str::limit($message, 180)
            : "iPaymu menolak pembuatan pembayaran AI (HTTP {$httpStatus}).";
    }
}
