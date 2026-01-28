<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackSubmission extends Model
{
    use HasFactory;

    protected $table = 'feedback_submissions';
    protected $primaryKey = 'feedback_submission_id';
    protected $guarded = ['feedback_submission_id'];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function tryout()
    {
        return $this->belongsTo(Tryout::class, 'tryout_id', 'tryout_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function answers()
    {
        return $this->hasMany(FeedbackAnswer::class, 'feedback_submission_id', 'feedback_submission_id');
    }
}
