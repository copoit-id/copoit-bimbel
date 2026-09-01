<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminTourProgress extends Model
{
    protected $table = 'admin_tour_progress';

    protected $primaryKey = 'admin_tour_progress_id';

    protected $fillable = [
        'user_id',
        'tour_key',
        'tour_version',
        'status',
        'current_step_id',
        'completed_at',
        'skipped_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'skipped_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
