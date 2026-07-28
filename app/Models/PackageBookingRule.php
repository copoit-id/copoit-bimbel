<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackageBookingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'class_id',
        'is_enabled',
        'session_quota',
        'duration_minutes',
        'min_notice_hours',
        'max_advance_days',
        'cancellation_hours',
        'allow_custom_time',
        'allow_all_tutors',
        'delivery_mode',
        'learning_mode',
        'min_participants',
        'max_participants',
        'default_location',
        'payment_deadline_hours',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'allow_custom_time' => 'boolean',
        'allow_all_tutors' => 'boolean',
        'session_quota' => 'integer',
        'duration_minutes' => 'integer',
        'min_notice_hours' => 'integer',
        'max_advance_days' => 'integer',
        'cancellation_hours' => 'integer',
        'min_participants' => 'integer',
        'max_participants' => 'integer',
        'payment_deadline_hours' => 'integer',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }

    public function bookingClass(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id', 'class_id');
    }

    public function tutors(): BelongsToMany
    {
        return $this->belongsToMany(
            Tentor::class,
            'package_booking_rule_tentor',
            'package_booking_rule_id',
            'tentor_id'
        )->withTimestamps();
    }

    public function requests(): HasMany
    {
        return $this->hasMany(
            ScheduleBookingRequest::class,
            'package_id',
            'package_id'
        );
    }

    public function priceTiers(): HasMany
    {
        return $this->hasMany(PackageBookingPriceTier::class)
            ->orderBy('participant_count');
    }

    public function cohorts(): HasMany
    {
        return $this->hasMany(BookingCohort::class);
    }
}
