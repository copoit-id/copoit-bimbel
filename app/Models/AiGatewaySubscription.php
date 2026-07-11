<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGatewaySubscription extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function plan()
    {
        return $this->belongsTo(AiGatewayPlan::class, 'ai_gateway_plan_id');
    }
}
