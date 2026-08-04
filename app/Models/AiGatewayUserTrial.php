<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGatewayUserTrial extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'tokens_used' => 'integer',
        'chats_used' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(AiGatewayClient::class, 'ai_gateway_client_id');
    }
}
