<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModelPricing extends Model
{
    protected $fillable = [
        'provider',
        'model',
        'input_per_million_usd',
        'output_per_million_usd',
        'usd_to_idr',
        'is_active',
    ];

    protected $casts = [
        'input_per_million_usd' => 'decimal:6',
        'output_per_million_usd' => 'decimal:6',
        'usd_to_idr' => 'decimal:4',
        'is_active' => 'boolean',
    ];
}
