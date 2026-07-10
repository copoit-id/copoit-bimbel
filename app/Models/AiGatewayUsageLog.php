<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AiGatewayUsageLog extends Model { protected $guarded=['id']; public function client(){ return $this->belongsTo(AiGatewayClient::class,'ai_gateway_client_id'); } }
