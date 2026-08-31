<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAssistantMessage extends Model
{
    protected $fillable = [
        'user_id',
        'portal',
        'question_hash',
        'question_token_hashes',
        'question_text',
        'answer_text',
        'answer_type',
        'source',
        'confidence',
        'usage_total',
        'context_hash',
    ];

    protected $casts = [
        'question_token_hashes' => 'array',
        'question_text' => 'encrypted',
        'answer_text' => 'encrypted',
        'usage_total' => 'integer',
    ];
}
