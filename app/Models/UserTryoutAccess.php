<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTryoutAccess extends Model
{
    use HasFactory;

    protected $table = 'user_tryout_access';
    protected $primaryKey = 'user_tryout_access_id';
    protected $guarded = ['user_tryout_access_id'];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'progress_percentage' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tryout(): BelongsTo
    {
        return $this->belongsTo(Tryout::class, 'tryout_id');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTryout($query, int $tryoutId)
    {
        return $query->where('tryout_id', $tryoutId);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeFromPackage($query, ?int $packageId = null)
    {
        $query->where('access_source', 'package');
        if ($packageId) {
            $query->where('source_id', $packageId);
        }
        return $query;
    }

    public function scopeDirectAccess($query)
    {
        return $query->where('access_source', 'direct');
    }

    public function getIsActiveAttribute(): bool
    {
        return is_null($this->expires_at) || $this->expires_at->isFuture();
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsInProgressAttribute(): bool
    {
        return $this->status === 'in_progress';
    }
}
