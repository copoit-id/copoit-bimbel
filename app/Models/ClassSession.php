<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_schedule_id',
        'class_id',
        'session_date',
        'start_at',
        'end_at',
        'status',
        'meeting_url',
        'location',
        'notes',
    ];

    protected $casts = [
        'session_date' => 'date',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function schedule()
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id', 'class_id');
    }

    public function attendances()
    {
        return $this->hasMany(ClassAttendance::class);
    }
}
