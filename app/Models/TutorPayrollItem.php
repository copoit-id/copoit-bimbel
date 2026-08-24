<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorPayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tutor_payroll_id',
        'tutor_attendance_id',
        'class_session_id',
        'package_id',
        'session_date',
        'description',
        'amount',
    ];

    protected $casts = [
        'session_date' => 'date',
        'amount' => 'decimal:0',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(TutorPayroll::class, 'tutor_payroll_id');
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(TutorAttendance::class, 'tutor_attendance_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }
}
