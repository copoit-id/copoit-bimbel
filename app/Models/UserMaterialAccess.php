<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMaterialAccess extends Model
{
    use HasFactory;

    protected $table = 'user_material_access';
    protected $primaryKey = 'user_material_access_id';
    protected $guarded = ['user_material_access_id'];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percentage' => 'integer',
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
     * Scope: By status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: In progress
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope: Completed
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: From package
     */
    public function scopeFromPackage($query, ?int $packageId = null)
    {
        $query->where('access_source', 'package');
        
        if ($packageId) {
            $query->where('source_id', $packageId);
        }
        
        return $query;
    }

    /**
     * Scope: Direct access
     */
    public function scopeDirectAccess($query)
    {
        return $query->where('access_source', 'direct');
    }

    /**
     * Check if material is completed
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if material is in progress
     */
    public function getIsInProgressAttribute(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Get progress badge attribute
     */
    public function getProgressBadgeAttribute(): string
    {
        return match ($this->status) {
            'completed' => '<span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Selesai</span>',
            'in_progress' => '<span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">Sedang Dipelajari</span>',
            default => '<span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Belum Dimulai</span>',
        };
    }

    /**
     * Update progress
     */
    public function updateProgress(int $percentage): void
    {
        $this->progress_percentage = min(100, max(0, $percentage));
        
        if ($this->progress_percentage >= 90) {
            $this->status = 'completed';
            $this->completed_at = $this->completed_at ?? now();
        } elseif ($this->progress_percentage > 0) {
            $this->status = 'in_progress';
            $this->started_at = $this->started_at ?? now();
        }
        
        $this->save();
    }

    /**
     * Mark as started
     */
    public function markAsStarted(): void
    {
        if ($this->status === 'not_started') {
            $this->status = 'in_progress';
            $this->started_at = now();
            $this->save();
        }
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): void
    {
        $this->status = 'completed';
        $this->progress_percentage = 100;
        $this->completed_at = now();
        $this->save();
    }
}
