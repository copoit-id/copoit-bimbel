<?php

namespace App\Services;

use App\Models\AffiliateCommission;
use App\Models\AffiliateSetting;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Str;

class AffiliateService
{
    public function ensureCode(User $user): string
    {
        if (!empty($user->affiliate_code)) {
            return $user->affiliate_code;
        }

        do {
            $code = Str::upper(Str::random(8));
        } while (User::query()->where('affiliate_code', $code)->exists());

        $user->forceFill(['affiliate_code' => $code])->save();

        return $code;
    }

    public function referrerFromCode(?string $code): ?User
    {
        $code = Str::upper(trim((string) $code));
        if ($code === '') {
            return null;
        }

        return User::query()->where('affiliate_code', $code)->first();
    }

    public function recordCommission(Payment $payment): ?AffiliateCommission
    {
        $payment->loadMissing('user');
        $buyer = $payment->user;

        if (!$buyer || !$buyer->referred_by_user_id || $buyer->referred_by_user_id === $buyer->id) {
            return null;
        }

        if ($payment->status !== Payment::STATUS_SUCCESS) {
            return null;
        }

        if (AffiliateCommission::query()->where('payment_id', $payment->payment_id)->exists()) {
            return null;
        }

        $setting = AffiliateSetting::current();
        if (!$setting->is_active) {
            return null;
        }

        $baseAmount = (int) ($payment->amount ?? $payment->total_amount ?? 0);
        $commissionAmount = $setting->calculateCommission($baseAmount);
        if ($commissionAmount <= 0) {
            return null;
        }

        return AffiliateCommission::create([
            'affiliate_user_id' => $buyer->referred_by_user_id,
            'referred_user_id' => $buyer->id,
            'payment_id' => $payment->payment_id,
            'package_id' => $payment->package_id,
            'commission_type' => $setting->commission_type,
            'commission_value' => $setting->commission_value,
            'base_amount' => $baseAmount,
            'commission_amount' => $commissionAmount,
            'status' => AffiliateCommission::STATUS_PENDING,
        ]);
    }
}
