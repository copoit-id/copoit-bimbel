<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProctoringSnapshot extends Model
{
    protected $fillable = [
        'user_id',
        'tryout_id',
        'user_answer_id',
        'attempt_token',
        'type',
        'file_path',
        'mime_type',
        'file_size',
        'captured_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tryout(): BelongsTo
    {
        return $this->belongsTo(Tryout::class, 'tryout_id', 'tryout_id');
    }

    public function userAnswer(): BelongsTo
    {
        return $this->belongsTo(UserAnswer::class, 'user_answer_id', 'user_answer_id');
    }
}
