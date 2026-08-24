<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillInvoicePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_invoice_id',
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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillInvoice::class, 'bill_invoice_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
