<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EssayCorrectionJob extends Model
{
    use HasFactory;

    protected $table = 'essay_correction_jobs';

    protected $fillable = [
        // Basic info
        'tryout_id',
        'user_id',
        'user_answer_id', // Per attempt token
        'job_type',
        'status',
        
        // AI Service related
        'ai_job_id',
        'method',
        'threshold',
        'callback_url',
        
        // Processing counts
        'total_essays',
        'processed_essays',
        'correct_count',
        'incorrect_count',
        'estimated_time_seconds',
        
        // Results from AI
        'total_similarity_score',
        'processing_time_ms',
        'error_message',
        
        // Timestamps
        'queued_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'queued_at' => 'datetime',
        'threshold' => 'float',
        'total_similarity_score' => 'float',
    ];

    public function tryout()
    {
        return $this->belongsTo(Tryout::class, 'tryout_id', 'tryout_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function userAnswer()
    {
        return $this->belongsTo(UserAnswer::class, 'user_answer_id', 'user_answer_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->whereIn('status', ['processing', 'queued']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Menunggu',
            'queued' => 'Antrian',
            'processing' => 'Diproses',
            'completed' => 'Selesai',
            'failed' => 'Gagal',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'amber',
            'queued' => 'blue',
            'processing' => 'indigo',
            'completed' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }

    public function getMethodLabelAttribute(): string
    {
        return match($this->method) {
            'semantic' => 'Semantic',
            'jaccard' => 'Jaccard',
            'overlap' => 'Overlap',
            'tfidf' => 'TF-IDF',
            'local' => 'Lokal',
            default => $this->method,
        };
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_essays === 0) {
            return 0;
        }
        return (int) round(($this->processed_essays / $this->total_essays) * 100);
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at && !$this->queued_at) {
            return null;
        }
        $start = $this->started_at ?? $this->queued_at;
        $end = $this->completed_at ?? now();
        $diff = $start->diff($end);
        
        if ($diff->h > 0) {
            return $diff->h . 'j ' . $diff->i . 'm';
        }
        if ($diff->i > 0) {
            return $diff->i . 'm ' . $diff->s . 'd';
        }
        return $diff->s . 'd';
    }

    public function getAiStatusBadgeAttribute(): string
    {
        if ($this->ai_job_id) {
            return '<span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">AI: ' . substr($this->ai_job_id, 0, 8) . '...</span>';
        }
        return '<span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Lokal</span>';
    }
}
