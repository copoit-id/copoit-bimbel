<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageBookingPriceTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_booking_rule_id',
        'participant_count',
        'price_per_person',
    ];

    protected $casts = [
        'participant_count' => 'integer',
        'price_per_person' => 'integer',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(PackageBookingRule::class, 'package_booking_rule_id');
    }
}
