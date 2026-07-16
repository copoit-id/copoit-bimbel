<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGatewayUsageLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'input_per_million_usd' => 'decimal:6',
        'output_per_million_usd' => 'decimal:6',
        'usd_to_idr' => 'decimal:4',
        'input_cost_idr' => 'decimal:6',
        'output_cost_idr' => 'decimal:6',
    ];

    public function client()
    {
        return $this->belongsTo(AiGatewayClient::class, 'ai_gateway_client_id');
    }
}
