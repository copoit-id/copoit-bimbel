<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiGatewayPlan extends Model
{
    public const SCOPE_LEARNING_TOOLS = 'learning_tools';

    public const SCOPE_ADMIN_QUESTION_GENERATOR = 'admin_question_generator';

    protected $fillable = [
        'name',
        'slug',
        'scope',
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

    public static function scopes(): array
    {
        return [
            self::SCOPE_LEARNING_TOOLS => 'AI Learning Tools',
            self::SCOPE_ADMIN_QUESTION_GENERATOR => 'Generate Soal Admin',
        ];
    }
}
