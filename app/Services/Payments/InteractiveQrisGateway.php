<?php

namespace App\Services\Payments;

use App\Models\Package;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InteractiveQrisGateway
{
    public function createPackagePayment(Package $package, array $discountData): array
    {
        $apiKey = config('services.interactive_qris.api_key');
        $merchantId = config('services.interactive_qris.mid');

        if (!$apiKey || !$merchantId) {
            return [
                'success' => false,
                'message' => 'Credential InterActive QRIS belum dikonfigurasi.',
            ];
        }

        $transactionId = 'QRIS-' . $package->package_id . '-' . Auth::id() . '-' . time();
        $amount = (int) round($discountData['payable_amount']);

        try {
            $response = Http::timeout(20)->get($this->endpoint('show_qris.php'), [
                'do' => 'create-invoice',
                'apikey' => $apiKey,
                'mID' => $merchantId,
                'cliTrxNumber' => $transactionId,
                'cliTrxAmount' => $amount,
                'useTip' => config('services.interactive_qris.use_tip', false) ? 'yes' : 'no',
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Gagal membuat invoice InterActive QRIS.',
                ];
            }

            $payload = $response->json();
            if (($payload['status'] ?? null) !== 'success') {
                return [
                    'success' => false,
                    'message' => $payload['data']['qris_status'] ?? 'InterActive QRIS menolak pembuatan invoice.',
                ];
            }

            $data = $payload['data'] ?? [];
            $requestDate = Carbon::parse($data['qris_request_date'] ?? now());

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
                'total_amount' => $amount,
                'status' => Payment::STATUS_PENDING,
                'payment_method' => 'interactive_qris',
                'payment_details' => json_encode([
                    'qris_content' => $data['qris_content'] ?? null,
                    'qris_request_date' => $requestDate->toDateTimeString(),
                    'qris_invoiceid' => $data['qris_invoiceid'] ?? null,
                    'qris_nmid' => $data['qris_nmid'] ?? null,
                    'expires_at' => $requestDate->copy()->addMinutes(30)->toDateTimeString(),
                    'external_id' => $transactionId,
                    'base_amount' => (int) $package->price,
                    'discount_code' => $discountData['discount_code'],
                    'discount_amount' => $discountData['discount_amount'],
                ]),
            ]);

            return [
                'success' => true,
                'redirect_url' => route('user.package.payment.qris.show', $transactionId),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error koneksi ke InterActive QRIS: ' . $e->getMessage(),
            ];
        }
    }

    public function checkPayment(Payment $payment): array
    {
        if ($payment->payment_method !== 'interactive_qris') {
            throw new RuntimeException('Payment bukan InterActive QRIS.');
        }

        $apiKey = config('services.interactive_qris.api_key');
        $merchantId = config('services.interactive_qris.mid');
        $details = json_decode($payment->payment_details ?? '{}', true);
        $invoiceId = $details['qris_invoiceid'] ?? null;
        $transactionDate = Carbon::parse($details['qris_request_date'] ?? $payment->created_at)->toDateString();

        if (!$apiKey || !$merchantId) {
            return [
                'success' => false,
                'message' => 'Credential InterActive QRIS belum dikonfigurasi.',
            ];
        }

        if (!$invoiceId) {
            return [
                'success' => false,
                'message' => 'Invoice ID QRIS tidak ditemukan.',
            ];
        }

        $response = Http::timeout(20)->get($this->endpoint('checkpaid_qris.php'), [
            'do' => 'checkStatus',
            'apikey' => $apiKey,
            'mID' => $merchantId,
            'invid' => $invoiceId,
            'trxvalue' => (int) $payment->total_amount,
            'trxdate' => $transactionDate,
        ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => 'Gagal mengecek status InterActive QRIS.',
            ];
        }

        $payload = $response->json();
        $status = $payload['data']['qris_status'] ?? null;

        if (($payload['status'] ?? null) === 'success' && $status === 'paid') {
            $details['qris_paid_status'] = $payload['data'] ?? [];
            $details['qris_api_version_code'] = $payload['qris_api_version_code'] ?? null;

            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => now(),
                'payment_details' => json_encode($details),
            ]);

            return [
                'success' => true,
                'paid' => true,
                'message' => 'Pembayaran QRIS berhasil dikonfirmasi.',
                'data' => $payload,
            ];
        }

        if ($this->isExpired($payment)) {
            $payment->update(['status' => Payment::STATUS_EXPIRED]);

            return [
                'success' => true,
                'paid' => false,
                'expired' => true,
                'message' => 'QRIS sudah kedaluwarsa. Silakan buat pembayaran ulang.',
                'data' => $payload,
            ];
        }

        return [
            'success' => true,
            'paid' => false,
            'expired' => false,
            'message' => 'Pembayaran belum ditemukan. Coba cek lagi setelah beberapa saat.',
            'data' => $payload,
        ];
    }

    public function isExpired(Payment $payment): bool
    {
        $details = json_decode($payment->payment_details ?? '{}', true);
        $expiresAt = $details['expires_at'] ?? null;

        return $expiresAt ? Carbon::parse($expiresAt)->isPast() : $payment->created_at->addMinutes(30)->isPast();
    }

    private function endpoint(string $path): string
    {
        return rtrim(config('services.interactive_qris.base_url', 'https://qris.interactive.co.id/restapi/qris'), '/')
            . '/'
            . ltrim($path, '/');
    }
}
