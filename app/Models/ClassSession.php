<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_schedule_id',
        'class_id',
        'tentor_id',
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

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id', 'class_id');
    }

    public function tentor(): BelongsTo
    {
        return $this->belongsTo(Tentor::class, 'tentor_id');
    }

    public function attendances()
    {
        return $this->hasMany(ClassAttendance::class);
    }
}
