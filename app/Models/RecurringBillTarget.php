<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringBillTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'recurring_bill_id',
        'user_id',
        'class_id',
        'package_id',
        'study_group_id',
    ];

    public function recurringBill(): BelongsTo
    {
        return $this->belongsTo(RecurringBill::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id', 'class_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }
}
