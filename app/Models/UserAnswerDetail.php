<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAnswerDetail extends Model
{
    use HasFactory;

    protected $table = 'user_answer_details';
    protected $primaryKey = 'user_answer_detail_id';

    protected $fillable = [
        'user_answer_id',
        'question_id',
        'question_option_id', // Sesuai dengan migration
        'is_correct',
        'answered_at'
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'answered_at' => 'datetime'
    ];

    public function userAnswer()
    {
        return $this->belongsTo(UserAnswer::class, 'user_answer_id', 'user_answer_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }

    public function questionOption()
    {
        return $this->belongsTo(QuestionOption::class, 'question_option_id', 'question_option_id');
    }
}
