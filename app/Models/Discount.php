<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

class Discount extends Model
{
    public const TYPE_VOUCHER = 'voucher';
    public const TYPE_PACKAGE_TRYOUT = 'package_tryout';

    protected $guarded = [];

    protected $casts = [
        'discount_value' => 'decimal:0',
        'max_discount_amount' => 'decimal:0',
        'min_purchase_amount' => 'decimal:0',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'applicable_package_ids' => 'array',
        'applicable_tryout_ids' => 'array',
        'applicable_material_ids' => 'array',
        'applicable_tes_koran_ids' => 'array',
        'applicable_purchase_types' => 'array',
    ];

    public static function normalizeCode(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        return $code !== '' ? $code : null;
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function individualPurchases(): HasMany
    {
        return $this->hasMany(IndividualPurchase::class);
    }

    public function tryout(): BelongsTo
    {
        return $this->belongsTo(Tryout::class, 'tryout_id', 'tryout_id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopePublicAvailable(Builder $query): Builder
    {
        return $query->available()
            ->where('application_type', self::TYPE_VOUCHER)
            ->where('is_public', true);
    }

    public function scopeVoucher(Builder $query): Builder
    {
        return $query->where('application_type', self::TYPE_VOUCHER);
    }

    public function scopeAutomaticTryout(Builder $query): Builder
    {
        return $query->where('application_type', self::TYPE_PACKAGE_TRYOUT)
            ->whereNotNull('tryout_id');
    }

    public function scopeAutomaticAvailable(Builder $query): Builder
    {
        return $query->automaticTryout()->available();
    }

    public function calculateDiscountAmount(int $amount): int
    {
        if ($amount <= 0) {
            return 0;
        }

        if ($this->discount_type === 'fixed') {
            return min($amount, (int) $this->discount_value);
        }

        $discount = (int) floor($amount * ((float) $this->discount_value / 100));

        if ($this->max_discount_amount !== null) {
            $discount = min($discount, (int) $this->max_discount_amount);
        }

        return min($amount, max(0, $discount));
    }

    public function validationErrorFor(int $amount, int $userId, ?int $packageId = null, ?string $purchaseType = null, ?int $purchaseItemId = null): ?string
    {
        $now = Carbon::now();

        if (!$this->is_active) {
            return 'Kode diskon tidak aktif.';
        }

        if ($this->starts_at && $this->starts_at->gt($now)) {
            return 'Kode diskon belum bisa digunakan.';
        }

        if ($this->ends_at && $this->ends_at->lt($now)) {
            return 'Kode diskon sudah kedaluwarsa.';
        }

        if ($amount < (int) $this->min_purchase_amount) {
            return 'Minimal pembelian untuk kode diskon ini belum terpenuhi.';
        }

        if ($purchaseType !== null && !$this->appliesToPurchaseType($purchaseType)) {
            return 'Kode diskon tidak berlaku untuk jenis pembelian ini.';
        }

        if ($purchaseType !== null && !$this->appliesToPurchaseTarget($purchaseType, $purchaseItemId ?? $packageId)) {
            return 'Kode diskon tidak berlaku untuk item ini.';
        }

        $paymentUsedStatuses = [Payment::STATUS_PENDING, Payment::STATUS_SUCCESS];
        $individualUsedStatuses = [IndividualPurchase::STATUS_PENDING, IndividualPurchase::STATUS_APPROVED];

        if ($this->usage_limit !== null) {
            $totalUsed = $this->payments()->whereIn('status', $paymentUsedStatuses)->count()
                + $this->individualPurchases()->whereIn('status', $individualUsedStatuses)->count();

            if ($totalUsed >= (int) $this->usage_limit) {
                return 'Kuota kode diskon sudah habis.';
            }
        }

        if ($this->per_user_limit !== null) {
            $userUsed = $this->payments()
                ->where('user_id', $userId)
                ->whereIn('status', $paymentUsedStatuses)
                ->count();

            $userUsed += $this->individualPurchases()
                ->where('user_id', $userId)
                ->whereIn('status', $individualUsedStatuses)
                ->count();

            if ($userUsed >= (int) $this->per_user_limit) {
                return 'Kode diskon sudah mencapai batas pemakaian akun ini.';
            }
        }

        return null;
    }

    public function appliesToPackage(int $packageId): bool
    {
        $ids = $this->applicable_package_ids;

        if (empty($ids)) {
            return true;
        }

        return in_array($packageId, array_map('intval', $ids), true);
    }

    public function appliesToPurchaseTarget(string $type, ?int $itemId): bool
    {
        if ($itemId === null) {
            return true;
        }

        $ids = match ($type) {
            'package' => $this->applicable_package_ids,
            'tryout' => $this->applicable_tryout_ids,
            'material' => $this->applicable_material_ids,
            'tes_koran' => $this->applicable_tes_koran_ids,
            default => null,
        };

        if (empty($ids)) {
            return true;
        }

        return in_array($itemId, array_map('intval', $ids), true);
    }

    public function appliesToPurchaseType(string $type): bool
    {
        $types = $this->applicable_purchase_types;

        if (empty($types)) {
            return true;
        }

        return in_array($type, $types, true);
    }

    public function getFormattedValueAttribute(): string
    {
        if ($this->discount_type === 'fixed') {
            return 'Rp ' . number_format((float) $this->discount_value, 0, ',', '.');
        }

        return rtrim(rtrim(number_format((float) $this->discount_value, 2, ',', '.'), '0'), ',') . '%';
    }
}
