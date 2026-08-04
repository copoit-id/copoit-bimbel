<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGatewayTokenAdjustment extends Model
{
    protected $fillable = [
        'ai_gateway_client_id',
        'ai_gateway_subscription_id',
        'external_user_id',
        'tokens_added',
        'previous_token_limit',
        'new_token_limit',
        'reason',
        'actor_user_id',
        'actor_name',
        'actor_email',
        'origin_base_url',
    ];

    protected function casts(): array
    {
        return [
            'tokens_added' => 'integer',
            'previous_token_limit' => 'integer',
            'new_token_limit' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(AiGatewayClient::class, 'ai_gateway_client_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(AiGatewaySubscription::class, 'ai_gateway_subscription_id');
    }
}
