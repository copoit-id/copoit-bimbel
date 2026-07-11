<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiDiscussionUsageLog extends Model
{
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tryout()
    {
        return $this->belongsTo(Tryout::class, 'tryout_id', 'tryout_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }
}
