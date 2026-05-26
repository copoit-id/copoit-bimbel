<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndividualPurchase extends Model
{
    protected $fillable = [
        'user_id',
        'purchasable_type',
        'purchasable_id',
        'price',
        'admin_fee',
        'total_amount',
        'payment_method',
        'status',
        'transaction_id',
        'payment_details',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'price' => 'decimal',
        'admin_fee' => 'decimal',
        'total_amount' => 'decimal',
        'payment_details' => 'array',
        'approved_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function purchasable()
    {
        return $this->morphTo();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('purchasable_type', $type);
    }
}
