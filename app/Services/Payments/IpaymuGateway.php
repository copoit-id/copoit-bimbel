<?php

namespace App\Services\Payments;

use App\Models\IndividualPurchase;
use App\Models\Package;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class IpaymuGateway
{
    public function createPackagePayment(Package $package, array $discountData): array
    {
        $apiKey = config('services.ipaymu.api_key');
        $va = config('services.ipaymu.va');

        if (! $apiKey || ! $va) {
            return [
                'success' => false,
                'message' => 'Credential iPaymu belum dikonfigurasi.',
            ];
        }

        $transactionId = 'IPAYMU-'.$package->package_id.'-'.Auth::id().'-'.time();
        $amount = (int) round($discountData['payable_amount']);
        $uniqueCode = $this->paymentUniqueCodeFor($amount);
        $totalAmount = $amount + ($uniqueCode ?? 0);
        $user = Auth::user();

        $payload = $this->cleanPayload([
            'account' => $va,
            'product' => [Str::limit($package->name, 80, '')],
            'qty' => [1],
            'price' => [$totalAmount],
            'description' => ['Pembelian '.$package->name],
            'notifyUrl' => route('webhook.ipaymu'),
            'returnUrl' => route('user.package.payment.success', ['transaction_id' => $transactionId]),
            'cancelUrl' => route('user.package.payment.failed', ['transaction_id' => $transactionId]),
            'buyerName' => $user?->name,
            'buyerEmail' => $this->buyerEmail($user),
            'buyerPhone' => $this->buyerPhone($user),
            'referenceId' => $transactionId,
            'expired' => 24,
            'expiredType' => 'hours',
        ]);

        try {
            $response = $this->post('/api/v2/payment', $payload);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => $this->responseMessage($response->json() ?: [], 'Gagal membuat pembayaran iPaymu.', $response->status()),
                ];
            }

            $data = $response->json();
            if (! $this->isSuccessResponse($data)) {
                return [
                    'success' => false,
                    'message' => $this->responseMessage($data, 'iPaymu menolak pembuatan pembayaran.'),
                ];
            }

            $responseData = $data['Data'] ?? $data['data'] ?? [];
            $redirectUrl = $responseData['Url']
                ?? $responseData['url']
                ?? $responseData['PaymentUrl']
                ?? $responseData['payment_url']
                ?? null;

            if (! $redirectUrl) {
                return [
                    'success' => false,
                    'message' => 'iPaymu tidak mengembalikan URL pembayaran.',
                ];
            }

            Payment::create([
                'transaction_id' => $transactionId,
                'user_id' => Auth::id(),
                'package_id' => $package->package_id,
                'discount_id' => $discountData['discount']?->id,
                'discount_code' => $discountData['discount_code'],
                'original_amount' => (int) $package->price,
                'discount_amount' => $discountData['discount_amount'],
                'amount' => $amount,
                'admin_fee' => 0,
                'unique_code' => $uniqueCode,
                'unique_code_date' => $uniqueCode ? now()->toDateString() : null,
                'total_amount' => $totalAmount,
                'status' => Payment::STATUS_PENDING,
                'payment_method' => 'ipaymu',
                'payment_details' => json_encode([
                    'session_id' => $responseData['SessionID'] ?? $responseData['sessionId'] ?? $responseData['session_id'] ?? null,
                    'ipaymu_transaction_id' => $responseData['TransactionId'] ?? $responseData['transactionId'] ?? $responseData['trx_id'] ?? null,
                    'redirect_url' => $redirectUrl,
                    'gateway_base_url' => rtrim((string) config('services.ipaymu.base_url'), '/'),
                    'external_id' => $transactionId,
                    'base_amount' => (int) $package->price,
                    'payable_amount' => $amount,
                    'unique_code' => $uniqueCode,
                    'discount_code' => $discountData['discount_code'],
                    'discount_amount' => $discountData['discount_amount'],
                    'response' => $responseData,
                ]),
            ]);

            return [
                'success' => true,
                'redirect_url' => $redirectUrl,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error koneksi ke iPaymu: '.$e->getMessage(),
            ];
        }
    }

    public function createIndividualPurchasePayment(IndividualPurchase $purchase, object $item, string $type): array
    {
        $apiKey = config('services.ipaymu.api_key');
        $va = config('services.ipaymu.va');

        if (! $apiKey || ! $va) {
            return [
                'success' => false,
                'message' => 'Credential iPaymu belum dikonfigurasi.',
            ];
        }

        $transactionId = $purchase->transaction_id;
        $amount = (int) $purchase->total_amount;
        $user = Auth::user();
        $itemName = $item->title ?? $item->name ?? 'Item';

        $payload = $this->cleanPayload([
            'account' => $va,
            'product' => [Str::limit($itemName, 80, '')],
            'qty' => [1],
            'price' => [$amount],
            'description' => ['Pembelian '.$itemName],
            'notifyUrl' => route('webhook.ipaymu'),
            'returnUrl' => route('user.package.payment.success', ['transaction_id' => $transactionId]),
            'cancelUrl' => route('user.package.payment.failed', ['transaction_id' => $transactionId]),
            'buyerName' => $user?->name,
            'buyerEmail' => $this->buyerEmail($user),
            'buyerPhone' => $this->buyerPhone($user),
            'referenceId' => $transactionId,
            'expired' => 24,
            'expiredType' => 'hours',
        ]);

        try {
            $response = $this->post('/api/v2/payment', $payload);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => $this->responseMessage($response->json() ?: [], 'Gagal membuat pembayaran iPaymu.', $response->status()),
                ];
            }

            $data = $response->json();
            if (! $this->isSuccessResponse($data)) {
                return [
                    'success' => false,
                    'message' => $this->responseMessage($data, 'iPaymu menolak pembuatan pembayaran.'),
                ];
            }

            $responseData = $data['Data'] ?? $data['data'] ?? [];
            $redirectUrl = $responseData['Url']
                ?? $responseData['url']
                ?? $responseData['PaymentUrl']
                ?? $responseData['payment_url']
                ?? null;

            if (! $redirectUrl) {
                return [
                    'success' => false,
                    'message' => 'iPaymu tidak mengembalikan URL pembayaran.',
                ];
            }

            $details = $this->detailsArray($purchase->payment_details);
            $purchase->update([
                'payment_details' => array_merge($details, [
                    'session_id' => $responseData['SessionID'] ?? $responseData['sessionId'] ?? $responseData['session_id'] ?? null,
                    'ipaymu_transaction_id' => $responseData['TransactionId'] ?? $responseData['transactionId'] ?? $responseData['trx_id'] ?? null,
                    'redirect_url' => $redirectUrl,
                    'gateway_base_url' => rtrim((string) config('services.ipaymu.base_url'), '/'),
                    'external_id' => $transactionId,
                    'expires_at' => now()->addDay()->toDateTimeString(),
                    'response' => $responseData,
                    'purchase_type' => $type,
                ]),
            ]);

            return [
                'success' => true,
                'redirect_url' => $redirectUrl,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error koneksi ke iPaymu: '.$e->getMessage(),
            ];
        }
    }

    private function paymentUniqueCodeFor(int $amount): ?int
    {
        if ($amount <= 0 || ! (bool) config('client.branding.payment_unique_code_enabled', true)) {
            return null;
        }

        return Payment::generateManualUniqueCode();
    }

    public function handleWebhook(Request $request): Payment|IndividualPurchase|null
    {
        $referenceId = (string) ($request->input('reference_id')
            ?: $request->input('referenceId')
            ?: $request->input('reference')
            ?: $request->input('merchant_ref')
            ?: '');

        $payment = $referenceId !== ''
            ? Payment::where('transaction_id', $referenceId)->first()
            : null;
        $individualPurchase = null;

        if (! $payment && $referenceId !== '') {
            $individualPurchase = IndividualPurchase::where('transaction_id', $referenceId)->first();
        }

        if (! $payment && ! $individualPurchase) {
            $transactionId = (string) ($request->input('trx_id')
                ?: $request->input('transaction_id')
                ?: $request->input('sid')
                ?: '');

            if ($transactionId !== '') {
                $payment = Payment::where('payment_method', 'ipaymu')
                    ->where('payment_details', 'like', '%'.$transactionId.'%')
                    ->first();

                if (! $payment) {
                    $individualPurchase = IndividualPurchase::where('payment_method', 'ipaymu')
                        ->where('payment_details', 'like', '%'.$transactionId.'%')
                        ->first();
                }
            }
        }

        $payable = $payment ?: $individualPurchase;

        if (! $payable) {
            return null;
        }

        // Do not trust the inbound notification as proof of payment. Verify the
        // transaction with iPaymu using the merchant credential before changing
        // any local payment or access status.
        if ($payable instanceof Payment) {
            $this->checkTransaction($payable);
        } else {
            $this->checkIndividualTransaction($payable);
        }

        return $payable->fresh();
    }

    public function checkTransaction(Payment $payment): array
    {
        if ($payment->payment_method !== 'ipaymu') {
            throw new RuntimeException('Payment bukan iPaymu.');
        }

        $details = $this->detailsArray($payment->payment_details);
        $lookupPayloads = $this->transactionLookupPayloads($details);

        if (empty($lookupPayloads)) {
            return [
                'success' => false,
                'message' => 'ID transaksi/session iPaymu tidak ditemukan.',
            ];
        }

        $successful = false;
        $paid = false;
        $failed = false;
        $payload = [];
        $attempts = [];

        foreach ($lookupPayloads as $lookupPayload) {
            $response = $this->post('/api/v2/transaction', $lookupPayload);
            $currentPayload = $response->json() ?: [];

            $attempts[] = [
                'request' => $lookupPayload,
                'http_status' => $response->status(),
                'response' => $currentPayload,
            ];

            $successful = $successful || $response->successful();
            $payload = $currentPayload;

            if ($response->successful() && $this->isPaidTransactionPayload($currentPayload)) {
                $paid = true;
                break;
            }

            if ($response->successful() && $this->isFailedTransactionPayload($currentPayload)) {
                $failed = true;
            }
        }

        $details['ipaymu_check'] = $payload;
        $details['ipaymu_check_attempts'] = $attempts;
        $details['ipaymu_checked_at'] = now()->toDateTimeString();

        if ($paid) {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => $payment->paid_at ?: Carbon::now(),
                'payment_details' => json_encode($details),
            ]);
        } elseif ($failed) {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'payment_details' => json_encode($details),
            ]);
        } else {
            $payment->update(['payment_details' => json_encode($details)]);
        }

        return [
            'success' => $successful,
            'paid' => $paid,
            'data' => $payload,
            'attempts' => $attempts,
        ];
    }

    public function checkIndividualTransaction(IndividualPurchase $purchase): array
    {
        if ($purchase->payment_method !== 'ipaymu') {
            throw new RuntimeException('Pembelian bukan iPaymu.');
        }

        $details = $this->detailsArray($purchase->payment_details);
        $lookupPayloads = $this->transactionLookupPayloads($details);

        if (empty($lookupPayloads)) {
            return [
                'success' => false,
                'message' => 'ID transaksi/session iPaymu tidak ditemukan.',
            ];
        }

        $successful = false;
        $paid = false;
        $failed = false;
        $payload = [];
        $attempts = [];

        foreach ($lookupPayloads as $lookupPayload) {
            $response = $this->post('/api/v2/transaction', $lookupPayload);
            $currentPayload = $response->json() ?: [];

            $attempts[] = [
                'request' => $lookupPayload,
                'http_status' => $response->status(),
                'response' => $currentPayload,
            ];

            $successful = $successful || $response->successful();
            $payload = $currentPayload;

            if ($response->successful() && $this->isPaidTransactionPayload($currentPayload)) {
                $paid = true;
                break;
            }

            if ($response->successful() && $this->isFailedTransactionPayload($currentPayload)) {
                $failed = true;
            }
        }

        $details['ipaymu_check'] = $payload;
        $details['ipaymu_check_attempts'] = $attempts;
        $details['ipaymu_checked_at'] = now()->toDateTimeString();

        if ($paid) {
            $purchase->update([
                'status' => IndividualPurchase::STATUS_APPROVED,
                'approved_at' => $purchase->approved_at ?: Carbon::now(),
                'payment_details' => $details,
            ]);
        } elseif ($failed) {
            $details['auto_rejected_reason'] = 'Gateway payment failed or expired.';
            $details['auto_rejected_at'] = now()->toDateTimeString();

            $purchase->update([
                'status' => IndividualPurchase::STATUS_REJECTED,
                'payment_details' => $details,
            ]);
        } else {
            $purchase->update(['payment_details' => $details]);
        }

        return [
            'success' => $successful,
            'paid' => $paid,
            'data' => $payload,
            'attempts' => $attempts,
        ];
    }

    private function detailsArray(mixed $details): array
    {
        if (is_array($details)) {
            return $details;
        }

        return $details ? (json_decode($details, true) ?: []) : [];
    }

    private function transactionLookupPayloads(array $details): array
    {
        $payloads = [];
        $transactionId = $details['ipaymu_transaction_id']
            ?? $details['transaction_id']
            ?? $details['trx_id']
            ?? null;
        $sessionId = $details['session_id']
            ?? $details['sessionId']
            ?? $details['SessionID']
            ?? $this->sessionIdFromRedirectUrl((string) ($details['redirect_url'] ?? ''));
        $referenceId = $details['external_id'] ?? $details['reference_id'] ?? null;

        if ($transactionId) {
            $payloads[] = ['transactionId' => $transactionId];
        }

        if ($sessionId) {
            $payloads[] = ['sessionId' => $sessionId];
            $payloads[] = ['transactionId' => $sessionId];
        }

        if ($referenceId) {
            $payloads[] = ['referenceId' => $referenceId];
            $payloads[] = ['transactionId' => $referenceId];
        }

        return collect($payloads)
            ->unique(fn (array $payload) => json_encode($payload))
            ->values()
            ->all();
    }

    private function sessionIdFromRedirectUrl(string $redirectUrl): ?string
    {
        if ($redirectUrl === '') {
            return null;
        }

        if (preg_match('~#/([^/?#]+)~', $redirectUrl, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function isPaidTransactionPayload(array $payload): bool
    {
        return $this->hasPaidStatusValue($this->statusValues($payload));
    }

    private function isFailedTransactionPayload(array $payload): bool
    {
        return $this->hasFailedStatusValue($this->statusValues($payload));
    }

    public function requestHasPaidStatus(Request $request): bool
    {
        return $this->hasPaidStatusValue($this->statusValues($request->all()));
    }

    private function hasPaidStatusValue(array $values): bool
    {
        return collect($values)
            ->contains(fn (string $status) => in_array($status, [
                'berhasil',
                'sukses',
                'success',
                'paid',
                'settlement',
                'settled',
                'capture',
                'complete',
                'completed',
                'lunas',
                '1',
            ], true));
    }

    private function hasFailedStatusValue(array $values): bool
    {
        return collect($values)
            ->contains(fn (string $status) => in_array($status, [
                'expired',
                'expire',
                'cancel',
                'canceled',
                'cancelled',
                'failed',
                'failure',
                'deny',
                'denied',
                '-1',
                '2',
                '3',
            ], true));
    }

    private function hasExpiredStatusValue(array $values): bool
    {
        return collect($values)
            ->contains(fn (string $status) => in_array($status, ['expired', 'expire', '2'], true));
    }

    private function statusValues(array $payload): array
    {
        $values = [];
        $statusKeys = [
            'status',
            'status_code',
            'statuscode',
            'transaction_status',
            'transactionstatus',
            'payment_status',
            'paymentstatus',
            'status_trx',
            'statustrx',
            'trx_status',
            'trxstatus',
            'payment_status_desc',
            'paymentstatusdesc',
        ];

        array_walk_recursive($payload, function ($value, $key) use (&$values, $statusKeys) {
            if (in_array(strtolower((string) $key), $statusKeys, true)) {
                $values[] = Str::lower(trim((string) $value));
            }
        });

        return $values;
    }

    private function post(string $path, array $payload)
    {
        $apiKey = (string) config('services.ipaymu.api_key');
        $va = (string) config('services.ipaymu.va');
        // iPaymu memakai JSON.stringify pada contoh resminya. Kirim body tersebut
        // apa adanya agar signature dan URL callback memiliki format yang sama.
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $requestBody = strtolower(hash('sha256', $body));
        $stringToSign = 'POST:'.$va.':'.$requestBody.':'.$apiKey;
        $signature = hash_hmac('sha256', $stringToSign, $apiKey);

        return Http::acceptJson()
            ->withBody($body, 'application/json')
            ->timeout(30)
            ->withHeaders([
                'va' => $va,
                'signature' => $signature,
                'timestamp' => now()->format('YmdHis'),
            ])
            ->post(rtrim((string) config('services.ipaymu.base_url'), '/').'/'.ltrim($path, '/'));
    }

    private function isSuccessResponse(array $data): bool
    {
        $status = (string) ($data['Status'] ?? $data['status'] ?? '');

        return in_array($status, ['200', '201', 'success', 'Success'], true);
    }

    private function responseMessage(array $payload, string $fallback, ?int $httpStatus = null): string
    {
        $message = $payload['Message']
            ?? $payload['message']
            ?? $payload['StatusDesc']
            ?? $payload['statusDesc']
            ?? $payload['Description']
            ?? $payload['description']
            ?? $payload['Data']['Message']
            ?? $payload['data']['message']
            ?? null;

        if (is_array($message)) {
            $message = collect($message)->flatten()->filter()->implode(' ');
        }

        $message = trim((string) $message);

        if ($message === '') {
            $message = $fallback;
        }

        return $httpStatus ? "HTTP {$httpStatus}: {$message}" : $message;
    }

    private function cleanPayload(array $payload): array
    {
        return array_filter($payload, fn ($value) => $value !== null && $value !== '');
    }

    private function buyerEmail($user): ?string
    {
        $email = Str::lower(trim((string) ($user?->email ?? '')));

        if ($email === '') {
            return null;
        }

        if ($this->userLooksLikeMerchant($user)) {
            return null;
        }

        $merchantEmails = collect([
            config('client.branding.smtp_email'),
            config('client.branding.smtp_notification_email'),
            config('client.branding.footer_email'),
            config('mail.from.address'),
            config('seeders.super_admin.email'),
            config('seeders.prod_admin.email'),
        ])
            ->filter()
            ->map(fn ($value) => Str::lower(trim((string) $value)))
            ->all();

        return in_array($email, $merchantEmails, true) ? null : $email;
    }

    private function userLooksLikeMerchant($user): bool
    {
        if (! $user) {
            return false;
        }

        $role = Str::lower((string) ($user->role ?? ''));

        if (in_array($role, ['admin', 'super_admin', 'superadmin'], true)) {
            return true;
        }

        return method_exists($user, 'isAdmin') && $user->isAdmin();
    }

    private function buyerPhone($user): string
    {
        $phone = $user?->phone
            ?? $user?->no_hp
            ?? $user?->whatsapp
            ?? $user?->nomor_hp
            ?? null;

        return preg_replace('/\D+/', '', (string) $phone) ?: '081000000000';
    }
}
