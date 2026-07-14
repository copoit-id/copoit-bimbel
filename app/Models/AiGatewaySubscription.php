<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGatewaySubscription extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'token_limit' => 'integer', 'chat_limit' => 'integer', 'tokens_used' => 'integer', 'chats_used' => 'integer'];

    public function plan()
    {
        return $this->belongsTo(AiGatewayPlan::class, 'ai_gateway_plan_id');
    }

    public function client()
    {
        return $this->belongsTo(AiGatewayClient::class, 'ai_gateway_client_id');
    }
}
