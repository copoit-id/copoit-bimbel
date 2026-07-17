<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiGatewayPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'token_limit',
        'chat_limit',
        'duration_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'token_limit' => 'integer',
            'chat_limit' => 'integer',
            'duration_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(AiGatewaySubscription::class, 'ai_gateway_plan_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AiGatewayTransaction::class, 'ai_gateway_plan_id');
    }

    public function isFree(): bool
    {
        return $this->price === 0;
    }
}
