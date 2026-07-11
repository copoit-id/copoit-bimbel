<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGatewayTransaction extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['details' => 'array', 'paid_at' => 'datetime'];

    public function plan()
    {
        return $this->belongsTo(AiGatewayPlan::class, 'ai_gateway_plan_id');
    }

    public function subscription()
    {
        return $this->belongsTo(AiGatewaySubscription::class, 'ai_gateway_subscription_id');
    }
}
