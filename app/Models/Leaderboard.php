<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Tryout;

class Leaderboard extends Model
{
    protected $table = 'leaderboards';
    protected $guarded = ['leaderboard_id'];
    protected $primaryKey = 'leaderboard_id';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function tryout()
    {
        return $this->belongsTo(Tryout::class, 'tryout_id');
    }
}
