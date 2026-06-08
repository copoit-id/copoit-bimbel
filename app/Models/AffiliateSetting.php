<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'invitee_discount_enabled' => 'boolean',
        'commission_value' => 'decimal:0',
        'invitee_discount_value' => 'decimal:0',
        'invitee_max_discount_amount' => 'decimal:0',
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'is_active' => true,
            'commission_type' => 'percent',
            'commission_value' => 10,
            'invitee_discount_enabled' => false,
            'invitee_discount_type' => 'percent',
            'invitee_discount_value' => 0,
        ]);
    }

    public function calculateCommission(int $amount): int
    {
        if ($amount <= 0 || !$this->is_active) {
            return 0;
        }

        if ($this->commission_type === 'fixed') {
            return min($amount, (int) $this->commission_value);
        }

        return max(0, (int) floor($amount * ((float) $this->commission_value / 100)));
    }

    public function calculateInviteeDiscount(int $amount): int
    {
        if ($amount <= 0 || !$this->is_active || !$this->invitee_discount_enabled) {
            return 0;
        }

        if ($this->invitee_discount_type === 'fixed') {
            return min($amount, (int) $this->invitee_discount_value);
        }

        $discount = (int) floor($amount * ((float) $this->invitee_discount_value / 100));

        if ($this->invitee_max_discount_amount !== null) {
            $discount = min($discount, (int) $this->invitee_max_discount_amount);
        }

        return min($amount, max(0, $discount));
    }
}
