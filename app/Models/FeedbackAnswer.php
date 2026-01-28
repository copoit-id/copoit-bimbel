<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackAnswer extends Model
{
    use HasFactory;

    protected $table = 'feedback_answers';
    protected $primaryKey = 'feedback_answer_id';
    protected $guarded = ['feedback_answer_id'];

    protected $casts = [
        'score' => 'integer',
    ];

    public function submission()
    {
        return $this->belongsTo(FeedbackSubmission::class, 'feedback_submission_id', 'feedback_submission_id');
    }

    public function question()
    {
        return $this->belongsTo(FeedbackQuestion::class, 'feedback_question_id', 'feedback_question_id');
    }
}
