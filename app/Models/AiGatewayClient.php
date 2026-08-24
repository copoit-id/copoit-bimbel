<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGatewayClient extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'monthly_token_limit' => 'integer',
        'free_token_limit' => 'integer',
        'free_chat_limit' => 'integer',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
        'slug',
        'base_url',
        'api_key_hash',
        'monthly_token_limit',
        'free_token_limit',
        'free_chat_limit',
        'is_active',
        'last_used_at',
    ];

    public function usageLogs()
    {
        return $this->hasMany(AiGatewayUsageLog::class);
    }
}
