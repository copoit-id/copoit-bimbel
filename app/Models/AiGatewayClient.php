<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AiGatewayClient extends Model { protected $guarded=['id']; public function usageLogs(){ return $this->hasMany(AiGatewayUsageLog::class); } }
