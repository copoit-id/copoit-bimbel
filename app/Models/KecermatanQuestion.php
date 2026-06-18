<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KecermatanQuestion extends Model
{
    protected $fillable = [
        'kecermatan_column_id',
        'sort_order',
        'payload',
        'correct_answer',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'payload' => 'array',
    ];

    public function column(): BelongsTo
    {
        return $this->belongsTo(KecermatanColumn::class, 'kecermatan_column_id');
    }
}
