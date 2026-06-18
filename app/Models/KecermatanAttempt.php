<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KecermatanAttempt extends Model
{
    protected $fillable = [
        'kecermatan_id',
        'kecermatan_column_id',
        'user_id',
        'attempt_token',
        'status',
        'started_at',
        'finished_at',
        'correct_answers',
        'wrong_answers',
        'unanswered',
        'score',
        'answers',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'correct_answers' => 'integer',
        'wrong_answers' => 'integer',
        'unanswered' => 'integer',
        'score' => 'decimal:2',
        'answers' => 'array',
    ];

    public function kecermatan(): BelongsTo
    {
        return $this->belongsTo(Kecermatan::class);
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(KecermatanColumn::class, 'kecermatan_column_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
