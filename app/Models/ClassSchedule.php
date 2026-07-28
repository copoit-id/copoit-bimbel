<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'study_group_id',
        'tentor_id',
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

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id', 'class_id');
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(
            Package::class,
            'detail_packages',
            'detailable_id',
            'package_id',
            'id',
            'package_id'
        )->wherePivot('detailable_type', $this->getMorphClass());
    }

    public function detailPackages(): MorphMany
    {
        return $this->morphMany(DetailPackage::class, 'detailable');
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function tentor(): BelongsTo
    {
        return $this->belongsTo(Tentor::class, 'tentor_id');
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

    protected static function booted(): void
    {
        static::deleting(function (ClassSchedule $schedule): void {
            $schedule->detailPackages()->delete();
        });
    }
}
