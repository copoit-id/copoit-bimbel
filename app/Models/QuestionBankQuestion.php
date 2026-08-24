<?php

namespace App\Models;

use App\Services\TutorContentVisibilityService;
use Illuminate\Database\Eloquent\Builder;
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

    protected static function booted(): void
    {
        static::addGlobalScope('tutor-content-owner', function (Builder $query): void {
            if (! app(TutorContentVisibilityService::class)->shouldScopeToOwner(auth()->user())) {
                return;
            }

            $query->whereHas('bank');
        });
    }

    public function bank()
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function options()
    {
        return $this->hasMany(QuestionBankQuestionOption::class, 'question_bank_question_id')->orderBy('position');
    }
}
