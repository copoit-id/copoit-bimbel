<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TesKoranResult extends Model
{
    protected $table = 'tes_koran_results';

    protected $fillable = [
        'tes_koran_id',
        'user_id',
        'attempt_token',
        'total_correct',
        'total_wrong',
        'total_skipped',
        'column_scores',
        'speed_score',
        'accuracy_score',
        'stability_score',
        'stability_status',
        'final_result',
        'started_at',
        'finished_at',
        'status',
    ];

    protected $casts = [
        'column_scores' => 'array',
        'total_correct' => 'integer',
        'total_wrong' => 'integer',
        'total_skipped' => 'integer',
        'speed_score' => 'decimal:2',
        'accuracy_score' => 'decimal:2',
        'stability_score' => 'decimal:2',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function tesKoran(): BelongsTo
    {
        return $this->belongsTo(TesKoran::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
