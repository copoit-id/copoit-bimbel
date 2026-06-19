<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'title',
        'schedule_type',
        'frequency',
        'day_of_week',
        'day_of_month',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'meeting_url',
        'location',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'is_active' => 'boolean',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id', 'class_id');
    }

    public function sessions()
    {
        return $this->hasMany(ClassSession::class);
    }

    public function attendanceSetting()
    {
        return $this->hasOne(AttendanceSetting::class);
    }

    public function destinationCategories()
    {
        return $this->belongsToMany(
            ParticipantDestinationCategory::class,
            'class_schedule_destination_categories',
            'class_schedule_id',
            'participant_destination_category_id'
        )->withTimestamps();
    }
}
