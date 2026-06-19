<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function recurringBill()
    {
        return $this->belongsTo(RecurringBill::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
