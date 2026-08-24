<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiLearningArtifact extends Model
{
    protected $fillable = [
        'user_id',
        'tryout_id',
        'question_id',
        'attempt_token',
        'source_type',
        'source_label',
        'tool',
        'title',
        'payload',
        'provider',
        'model',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'saved_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'total_tokens' => 'integer',
        'saved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tryout(): BelongsTo
    {
        return $this->belongsTo(Tryout::class, 'tryout_id', 'tryout_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }
}
