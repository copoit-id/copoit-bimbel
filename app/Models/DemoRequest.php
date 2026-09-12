<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoRequest extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'origin_institution',
        'request_note',
        'status',
        'reviewed_at',
        'approved_by',
        'approved_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_admin_id');
    }
}
