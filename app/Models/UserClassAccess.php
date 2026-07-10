<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserClassAccess extends Model
{
    use HasFactory;

    protected $table = 'user_class_access';
    protected $primaryKey = 'user_class_access_id';
    protected $guarded = ['user_class_access_id'];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id', 'class_id');
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeDirectAccess($query)
    {
        return $query->where('access_source', 'direct');
    }

    public function getIsActiveAttribute(): bool
    {
        return is_null($this->expires_at) || $this->expires_at->isFuture();
    }
}
