<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGatewayTransaction extends Model
{
    protected $fillable = [
        'ai_gateway_client_id',
        'ai_gateway_plan_id',
        'ai_gateway_subscription_id',
        'external_id',
        'provider',
        'provider_invoice_id',
        'amount',
        'status',
        'details',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'details' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(AiGatewayPlan::class, 'ai_gateway_plan_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(AiGatewaySubscription::class, 'ai_gateway_subscription_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(AiGatewayClient::class, 'ai_gateway_client_id');
    }
}
