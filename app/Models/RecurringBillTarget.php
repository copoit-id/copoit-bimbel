<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringBillTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'recurring_bill_id',
        'user_id',
        'class_id',
    ];

    public function recurringBill()
    {
        return $this->belongsTo(RecurringBill::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id', 'class_id');
    }
}
