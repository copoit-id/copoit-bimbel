<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiGatewaySubscription extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'token_limit' => 'integer', 'chat_limit' => 'integer', 'tokens_used' => 'integer', 'chats_used' => 'integer'];

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
        });
    }

    public function plan()
    {
        return $this->belongsTo(AiGatewayPlan::class, 'ai_gateway_plan_id');
    }

    public function client()
    {
        return $this->belongsTo(AiGatewayClient::class, 'ai_gateway_client_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AiGatewayTransaction::class, 'ai_gateway_subscription_id');
    }
}
