<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_schedule_id',
        'mode',
        'open_minutes_before',
        'close_minutes_after',
        'allow_admin_override',
    ];

    protected $casts = [
        'open_minutes_before' => 'integer',
        'close_minutes_after' => 'integer',
        'allow_admin_override' => 'boolean',
    ];

    public function schedule()
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }
}
