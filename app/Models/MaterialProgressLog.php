<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialProgressLog extends Model
{
    use HasFactory;

    protected $table = 'material_progress_logs';
    protected $primaryKey = 'log_id';
    protected $guarded = ['log_id'];

    protected $casts = [
        'metadata' => 'array',
        'progress_seconds' => 'integer',
    ];

    /**
     * Relasi ke user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke material
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /**
     * Scope: By user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: By material
     */
    public function scopeByMaterial($query, int $materialId)
    {
        return $query->where('material_id', $materialId);
    }

    /**
     * Scope: By event type
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope: Latest first
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get event type label attribute
     */
    public function getEventTypeLabelAttribute(): string
    {
        return match ($this->event_type) {
            'started' => 'Dimulai',
            'paused' => 'Dijeda',
            'resumed' => 'Dilanjutkan',
            'completed' => 'Selesai',
            'viewed' => 'Dilihat',
            default => 'Unknown',
        };
    }
}
