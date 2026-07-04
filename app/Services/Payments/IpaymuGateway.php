<?php

namespace App\Services\Payments;

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

        if (!$apiKey || !$va) {
            return [
                'success' => false,
                'message' => 'Credential iPaymu belum dikonfigurasi.',
            ];
        }

        $transactionId = 'IPAYMU-' . $package->package_id . '-' . Auth::id() . '-' . time();
        $amount = (int) round($discountData['payable_amount']);
        $uniqueCode = $this->paymentUniqueCodeFor($amount);
        $totalAmount = $amount + ($uniqueCode ?? 0);
        $user = Auth::user();

        $payload = [
            'account' => $va,
            'product' => [Str::limit($package->name, 80, '')],
            'qty' => [1],
            'price' => [$totalAmount],
            'description' => ['Pembelian ' . $package->name],
            'notifyUrl' => route('webhook.ipaymu'),
            'returnUrl' => route('user.package.payment.success'),
            'cancelUrl' => route('user.package.payment.failed'),
            'buyerName' => $user?->name,
            'buyerEmail' => $user?->email,
            'buyerPhone' => $this->buyerPhone($user),
            'referenceId' => $transactionId,
            'expired' => 24,
            'expiredType' => 'hours',
        ];

        try {
            $response = $this->post('/payment', $payload);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => $response->json('Message') ?: $response->json('message') ?: 'Gagal membuat pembayaran iPaymu.',
                ];
            }

            $data = $response->json();
            if (!$this->isSuccessResponse($data)) {
                return [
                    'success' => false,
                    'message' => $data['Message'] ?? $data['message'] ?? 'iPaymu menolak pembuatan pembayaran.',
                ];
            }

            $responseData = $data['Data'] ?? $data['data'] ?? [];
            $redirectUrl = $responseData['Url']
                ?? $responseData['url']
                ?? $responseData['PaymentUrl']
                ?? $responseData['payment_url']
                ?? null;

            if (!$redirectUrl) {
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
                'message' => 'Error koneksi ke iPaymu: ' . $e->getMessage(),
            ];
        }
    }

    private function paymentUniqueCodeFor(int $amount): ?int
    {
        if ($amount <= 0 || !(bool) config('client.branding.payment_unique_code_enabled', true)) {
            return null;
        }

        return Payment::generateManualUniqueCode();
    }

    public function handleWebhook(Request $request): ?Payment
    {
        $referenceId = (string) ($request->input('reference_id')
            ?: $request->input('referenceId')
            ?: $request->input('reference')
            ?: $request->input('merchant_ref')
            ?: '');

        $payment = $referenceId !== ''
            ? Payment::where('transaction_id', $referenceId)->first()
            : null;

        if (!$payment) {
            $transactionId = (string) ($request->input('trx_id')
                ?: $request->input('transaction_id')
                ?: $request->input('sid')
                ?: '');

            $payment = $transactionId !== ''
                ? Payment::where('payment_method', 'ipaymu')
                    ->where('payment_details', 'like', '%' . $transactionId . '%')
                    ->first()
                : null;
        }

        if (!$payment) {
            return null;
        }

        $status = Str::lower((string) ($request->input('status')
            ?: $request->input('status_code')
            ?: $request->input('transaction_status')
            ?: ''));

        $details = json_decode($payment->payment_details ?? '{}', true);
        $details['ipaymu_webhook'] = $request->all();

        if (in_array($status, ['berhasil', 'success', 'paid', 'settlement', '1'], true)) {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => Carbon::now(),
                'payment_details' => json_encode($details),
            ]);

            return $payment->fresh();
        }

        if (in_array($status, ['expired', 'expire', 'cancel', 'cancelled', 'failed', 'failure', '0', '2'], true)) {
            $payment->update([
                'status' => in_array($status, ['expired', 'expire'], true)
                    ? Payment::STATUS_EXPIRED
                    : Payment::STATUS_FAILED,
                'payment_details' => json_encode($details),
            ]);

            return $payment->fresh();
        }

        $payment->update(['payment_details' => json_encode($details)]);

        return $payment->fresh();
    }

    public function checkTransaction(Payment $payment): array
    {
        if ($payment->payment_method !== 'ipaymu') {
            throw new RuntimeException('Payment bukan iPaymu.');
        }

        $details = json_decode($payment->payment_details ?? '{}', true);
        $ipaymuTransactionId = $details['ipaymu_transaction_id'] ?? null;

        if (!$ipaymuTransactionId) {
            return [
                'success' => false,
                'message' => 'Transaction ID iPaymu tidak ditemukan.',
            ];
        }

        $response = $this->post('/transaction', [
            'transactionId' => $ipaymuTransactionId,
        ]);

        return [
            'success' => $response->successful(),
            'data' => $response->json(),
        ];
    }

    private function post(string $path, array $payload)
    {
        $apiKey = (string) config('services.ipaymu.api_key');
        $va = (string) config('services.ipaymu.va');
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $requestBody = strtolower(hash('sha256', $body));
        $stringToSign = 'POST:' . $va . ':' . $requestBody . ':' . $apiKey;
        $signature = hash_hmac('sha256', $stringToSign, $apiKey);

        return Http::acceptJson()
            ->asJson()
            ->timeout(30)
            ->withHeaders([
                'va' => $va,
                'signature' => $signature,
                'timestamp' => now()->format('YmdHis'),
            ])
            ->post(rtrim((string) config('services.ipaymu.base_url'), '/') . '/' . ltrim($path, '/'), $payload);
    }

    private function isSuccessResponse(array $data): bool
    {
        $status = (string) ($data['Status'] ?? $data['status'] ?? '');

        return in_array($status, ['200', '201', 'success', 'Success'], true);
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
