<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'receipt_number',
        'amount',
        'payment_method',
        'notes',
        'paid_at',
        'paid_by',
    ];

    protected $casts = [
        'amount' => 'decimal:0',
        'paid_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
