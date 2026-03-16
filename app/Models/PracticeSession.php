<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use App\Models\PracticeStudySession;

class PracticeSession extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'last_answered_at' => 'datetime',
        'study_started_at' => 'datetime',
        'session_start' => 'datetime',
        'flagged_questions' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PracticeAnswer::class);
    }

    public function studySessions(): HasMany
    {
        return $this->hasMany(PracticeStudySession::class);
    }

    public function activeStudySession(): HasOne
    {
        return $this->hasOne(PracticeStudySession::class, 'id', 'active_study_session_id');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
