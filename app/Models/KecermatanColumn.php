<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KecermatanColumn extends Model
{
    protected $fillable = [
        'kecermatan_id',
        'name',
        'sort_order',
        'duration_seconds',
        'questions_count',
        'column_type',
        'references',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'duration_seconds' => 'integer',
        'questions_count' => 'integer',
        'references' => 'array',
    ];

    public function kecermatan(): BelongsTo
    {
        return $this->belongsTo(Kecermatan::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(KecermatanQuestion::class)->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(KecermatanAttempt::class);
    }
}
