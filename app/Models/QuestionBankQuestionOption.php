<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionBankQuestionOption extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'weight' => 'decimal:2',
        'is_correct' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(QuestionBankQuestion::class, 'question_bank_question_id');
    }
}
