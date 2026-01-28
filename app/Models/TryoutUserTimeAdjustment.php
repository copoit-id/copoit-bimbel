<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TryoutUserTimeAdjustment extends Model
{
    use HasFactory;

    protected $table = 'tryout_user_time_adjustments';
    protected $primaryKey = 'tryout_user_time_id';
    protected $guarded = ['tryout_user_time_id'];

    protected $casts = [
        'extra_minutes' => 'integer',
    ];

    public function tryout()
    {
        return $this->belongsTo(Tryout::class, 'tryout_id', 'tryout_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
