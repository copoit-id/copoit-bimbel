<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionBankQuestion extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'default_weight' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function bank()
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function options()
    {
        return $this->hasMany(QuestionBankQuestionOption::class, 'question_bank_question_id')->orderBy('position');
    }
}
