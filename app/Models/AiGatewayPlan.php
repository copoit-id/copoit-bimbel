<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiGatewayPlan extends Model
{
    protected $guarded = ['id'];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(AiGatewaySubscription::class, 'ai_gateway_plan_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AiGatewayTransaction::class, 'ai_gateway_plan_id');
    }
}
