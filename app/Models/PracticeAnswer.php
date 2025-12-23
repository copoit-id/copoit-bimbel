<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeAnswer extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'answer_json' => 'array',
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(PracticeSession::class, 'practice_session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBankQuestion::class, 'question_bank_question_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(QuestionBankQuestionOption::class, 'question_bank_question_option_id');
    }
}
