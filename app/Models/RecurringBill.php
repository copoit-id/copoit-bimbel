<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'amount',
        'frequency',
        'start_date',
        'end_date',
        'due_day',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:0',
        'start_date' => 'date',
        'end_date' => 'date',
        'due_day' => 'integer',
        'is_active' => 'boolean',
    ];

    public function targets()
    {
        return $this->hasMany(RecurringBillTarget::class);
    }

    public function invoices()
    {
        return $this->hasMany(BillInvoice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
