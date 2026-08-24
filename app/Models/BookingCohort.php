<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingCohort extends Model
{
    use HasFactory;

    public const STATUS_FORMING = 'forming';

    public const STATUS_READY = 'ready';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'package_id',
        'package_booking_rule_id',
        'package_booking_price_tier_id',
        'organizer_user_id',
        'study_group_id',
        'invite_code',
        'target_participants',
        'unit_price_snapshot',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'target_participants' => 'integer',
        'unit_price_snapshot' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(PackageBookingRule::class, 'package_booking_rule_id');
    }

    public function priceTier(): BelongsTo
    {
        return $this->belongsTo(
            PackageBookingPriceTier::class,
            'package_booking_price_tier_id'
        );
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_user_id');
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(BookingCohortParticipant::class);
    }

    public function bookingRequests(): HasMany
    {
        return $this->hasMany(ScheduleBookingRequest::class);
    }
}
