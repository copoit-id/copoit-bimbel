<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TryoutDetail;
use App\Models\QuestionOption;
use App\Models\UserAnswerDetail;
use App\Services\TutorContentVisibilityService;
use Illuminate\Database\Eloquent\Builder;

class Question extends Model
{
    use HasFactory;

    protected $table = 'questions';
    protected $guarded = ['question_id'];
    protected $primaryKey = 'question_id';

    protected $casts = [
        'default_weight' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'essay_score_correct' => 'decimal:2',
        'essay_score_wrong' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tutor-content-owner', function (Builder $query): void {
            if (! app(TutorContentVisibilityService::class)->shouldScopeToOwner(auth()->user())) {
                return;
            }

            $query->whereHas('tryoutDetail');
        });
    }

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
        return in_array($this->question_type, ['multiple_choice', 'multiple_answer'], true);
    }

    // Check if question is essay
    public function isEssay()
    {
        return $this->question_type === 'essay';
    }

    public function isShortAnswer()
    {
        return in_array($this->question_type, ['short_answer', 'essay']);
    }

    public function isMatching()
    {
        return $this->question_type === 'matching';
    }

    public function requiresAudioAnswer()
    {
        return $this->question_type === 'audio';
    }

    // Essay scoring helpers
    public function getEssayScoreCorrect(): float
    {
        return (float) ($this->essay_score_correct ?? $this->default_weight ?? 1);
    }

    public function getEssayScoreWrong(): float
    {
        return (float) ($this->essay_score_wrong ?? 0);
    }

    public function isEssayScoringRange(): bool
    {
        return $this->essay_scoring_mode === 'range';
    }

    public function isEssayScoringFull(): bool
    {
        return $this->essay_scoring_mode === 'full';
    }

    /**
     * Calculate essay score based on similarity and scoring mode
     * 
     * @param float $similarity 0.0 - 1.0
     * @return float Calculated score
     */
    public function calculateEssayScore(float $similarity): float
    {
        $scoreCorrect = $this->getEssayScoreCorrect();
        $scoreWrong = $this->getEssayScoreWrong();

        if ($this->isEssayScoringRange()) {
            // Range mode: score proportional to similarity
            // similarity 1.0 = score_correct, similarity 0 = score_wrong
            return $scoreWrong + (($scoreCorrect - $scoreWrong) * $similarity);
        }

        // Full mode: binary scoring
        return $similarity >= 0.6 ? $scoreCorrect : $scoreWrong;
    }
}
