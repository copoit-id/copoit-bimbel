<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\User;
use App\Models\UserPackageAcces;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PackagePaymentInstallmentService
{
    public function record(
        Payment $payment,
        int $amount,
        string $paymentMethod,
        ?string $notes,
        ?User $recordedBy,
    ): Payment {
        return DB::transaction(function () use ($payment, $amount, $paymentMethod, $notes, $recordedBy): Payment {
            $payment = Payment::query()
                ->with('package')
                ->lockForUpdate()
                ->findOrFail($payment->payment_id);

            if (in_array($payment->status, [Payment::STATUS_SUCCESS, Payment::STATUS_FAILED, Payment::STATUS_EXPIRED], true)) {
                throw ValidationException::withMessages([
                    'amount' => 'Pembayaran ini tidak dapat menerima cicilan lagi.',
                ]);
            }

            $paidAmount = (int) PaymentInstallment::query()
                ->where('payment_id', $payment->payment_id)
                ->sum('amount');
            $remainingAmount = max(0, (int) $payment->total_amount - $paidAmount);

            if ($amount > $remainingAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal cicilan melebihi sisa pembayaran Rp '.number_format($remainingAmount, 0, ',', '.').'.',
                ]);
            }

            PaymentInstallment::query()->create([
                'payment_id' => $payment->payment_id,
                'receipt_number' => $this->receiptNumber(),
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'paid_at' => now(),
                'paid_by' => $recordedBy?->id,
            ]);

            $isPaid = $paidAmount + $amount >= (int) $payment->total_amount;
            $payment->update([
                'status' => $isPaid ? Payment::STATUS_SUCCESS : Payment::STATUS_PARTIAL,
                'paid_at' => $isPaid ? now() : null,
                'confirmed_by' => $recordedBy?->id,
                'confirmed_at' => now(),
            ]);

            if ($isPaid) {
                $this->activatePackageAccess($payment, $recordedBy);
                app(AffiliateService::class)->recordCommission($payment);
            }

            return $payment->fresh(['installments.paidBy', 'user', 'package']);
        });
    }

    private function activatePackageAccess(Payment $payment, ?User $recordedBy): void
    {
        $package = $payment->package ?? Package::query()->find($payment->package_id);
        if (! $package) {
            throw ValidationException::withMessages([
                'amount' => 'Paket pembayaran tidak ditemukan.',
            ]);
        }

        $existingAccess = UserPackageAcces::query()
            ->where('user_id', $payment->user_id)
            ->where('package_id', $payment->package_id)
            ->first();

        if ($existingAccess?->is_active) {
            return;
        }

        $startDate = now();
        UserPackageAcces::query()->updateOrCreate(
            [
                'user_id' => $payment->user_id,
                'package_id' => $payment->package_id,
            ],
            [
                'start_date' => $startDate,
                'end_date' => PurchaseAccessDuration::expiresAt($package, $startDate),
                'status' => 'active',
                'payment_amount' => $payment->total_amount,
                'payment_status' => 'paid',
                'notes' => 'Lunas melalui cicilan pembayaran '.$payment->transaction_id,
                'created_by' => $recordedBy?->id,
            ],
        );
    }

    private function receiptNumber(): string
    {
        do {
            $receiptNumber = 'PAY-INST-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (PaymentInstallment::query()->where('receipt_number', $receiptNumber)->exists());

        return $receiptNumber;
    }
}
