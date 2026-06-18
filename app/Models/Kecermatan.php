<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Kecermatan extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'price',
        'is_for_sale',
        'is_displayed',
        'is_active',
        'access_duration_value',
        'access_duration_unit',
    ];

    protected $casts = [
        'price' => 'decimal:0',
        'is_for_sale' => 'boolean',
        'is_displayed' => 'boolean',
        'is_active' => 'boolean',
        'access_duration_value' => 'integer',
    ];

    public function columns(): HasMany
    {
        return $this->hasMany(KecermatanColumn::class)->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(KecermatanAttempt::class);
    }

    public function detailPackages(): MorphMany
    {
        return $this->morphMany(DetailPackage::class, 'detailable');
    }

    public function individualPurchases(): MorphMany
    {
        return $this->morphMany(IndividualPurchase::class, 'purchasable');
    }

    public function packages(): BelongsToMany
    {
        return $this->morphToMany(Package::class, 'detailable', 'detail_packages', 'detailable_id', 'package_id');
    }

    public function canUserAccess(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        $hasPackageAccess = $this->packages()
            ->whereHas('userAccess', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('status', 'active')
                    ->where(function ($query) {
                        $query->whereNull('end_date')->orWhere('end_date', '>', now());
                    });
            })
            ->exists();

        if ($hasPackageAccess) {
            return true;
        }

        return $this->hasUserPurchased($userId);
    }

    public function hasUserPurchased(int $userId): bool
    {
        return IndividualPurchase::where('user_id', $userId)
            ->where('purchasable_type', self::class)
            ->where('purchasable_id', $this->id)
            ->where('status', IndividualPurchase::STATUS_APPROVED)
            ->where(function ($query) {
                $query->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            })
            ->exists();
    }

    public function hasPendingPurchase(int $userId): bool
    {
        return IndividualPurchase::where('user_id', $userId)
            ->where('purchasable_type', self::class)
            ->where('purchasable_id', $this->id)
            ->where('status', IndividualPurchase::STATUS_PENDING)
            ->exists();
    }

    public function typeLabel(): string
    {
        return $this->type === 'kecermatan_tni' ? 'Kecermatan TNI' : 'Kecermatan POLRI';
    }
}
