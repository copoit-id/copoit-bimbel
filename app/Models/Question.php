<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TryoutDetail;
use App\Models\QuestionOption;
use App\Models\UserAnswerDetail;

class Question extends Model
{
    use HasFactory;

    protected $table = 'questions';
    protected $guarded = ['question_id'];
    protected $primaryKey = 'question_id';

    protected $casts = [
        'default_weight' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function tryoutDetail()
    {
        return $this->belongsTo(TryoutDetail::class, 'tryout_detail_id', 'tryout_detail_id');
    }

    public function questionOptions()
    {
        return $this->hasMany(QuestionOption::class, 'question_id', 'question_id');
    }

    public function correctOption()
    {
        return $this->hasOne(QuestionOption::class, 'question_id', 'question_id')->where('is_correct', true);
    }

    public function userAnswerDetails()
    {
        return $this->hasMany(UserAnswerDetail::class, 'question_id', 'question_id');
    }

    // Helper method to get score for a specific option
    public function getScoreForOption($optionKey)
    {
        $option = $this->options()->where('option_key', $optionKey)->first();
        return $option ? $option->weight : 0;
    }

    // Check if question is multiple choice
    public function isMultipleChoice()
    {
        return $this->question_type === 'multiple_choice';
    }

    // Check if question is essay
    public function isEssay()
    {
        return $this->question_type === 'essay';
    }
}
