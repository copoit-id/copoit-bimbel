<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EssayAIUsageLog extends Model
{
    use HasFactory;

    protected $table = 'essay_ai_usage_logs';

    protected $fillable = [
        'user_id',
        'essay_correction_job_id',
        'essays_count',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'essays_count' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function essayCorrectionJob()
    {
        return $this->belongsTo(EssayCorrectionJob::class);
    }

    // Scopes
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('used_at', now()->month)
            ->whereYear('used_at', now()->year);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('used_at', [$startDate, $endDate]);
    }
}
