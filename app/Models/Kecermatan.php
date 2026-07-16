<?php

namespace App\Models;

use App\Models\Concerns\HasIndividualPricing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Legacy model for Kecermatan purchases.
 *
 * Existing individual_purchases records use this class as their polymorphic
 * type, so it must remain available while those records exist.
 */
class Kecermatan extends Model
{
    use HasIndividualPricing;

    protected $table = 'kecermatans';

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

    public function individualPurchases(): MorphMany
    {
        return $this->morphMany(IndividualPurchase::class, 'purchasable');
    }
}
