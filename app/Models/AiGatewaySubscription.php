<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiGatewaySubscription extends Model
{
    protected $hidden = [
        'free_claim_key',
    ];

    protected $fillable = [
        'ai_gateway_client_id',
        'ai_gateway_plan_id',
        'token_limit',
        'chat_limit',
        'external_user_id',
        'external_user_name',
        'external_user_email',
        'free_claim_key',
        'status',
        'starts_at',
        'ends_at',
        'tokens_used',
        'chats_used',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'token_limit' => 'integer',
            'chat_limit' => 'integer',
            'tokens_used' => 'integer',
            'chats_used' => 'integer',
        ];
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
        });
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(AiGatewayPlan::class, 'ai_gateway_plan_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(AiGatewayClient::class, 'ai_gateway_client_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AiGatewayTransaction::class, 'ai_gateway_subscription_id');
    }

    public function hasRemainingQuota(): bool
    {
        $tokenLimit = (int) ($this->token_limit ?: $this->plan?->token_limit ?: 0);
        $chatLimit = (int) ($this->chat_limit ?: $this->plan?->chat_limit ?: 0);

        return $tokenLimit > $this->tokens_used
            && ($chatLimit === 0 || $chatLimit > $this->chats_used);
    }
}
