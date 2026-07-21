<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'recurring_bill_id',
        'user_id',
        'invoice_number',
        'title',
        'amount',
        'period_start',
        'period_end',
        'due_date',
        'status',
        'paid_at',
        'paid_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:0',
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function recurringBill(): BelongsTo
    {
        return $this->belongsTo(RecurringBill::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillInvoicePayment::class)->orderByDesc('paid_at');
    }

    public function getPaidAmountAttribute(): int
    {
        if (array_key_exists('paid_amount', $this->attributes)) {
            return (int) $this->attributes['paid_amount'];
        }

        if ($this->relationLoaded('payments')) {
            return (int) $this->payments->sum('amount');
        }

        return (int) $this->payments()->sum('amount');
    }

    public function getRemainingAmountAttribute(): int
    {
        return max(0, (int) $this->amount - $this->paid_amount);
    }
}
