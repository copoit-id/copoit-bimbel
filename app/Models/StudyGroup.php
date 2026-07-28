<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyGroup extends Model
{
    use HasFactory;

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'name',
        'tentor_id',
        'description',
        'is_active',
        'package_id',
        'package_booking_rule_id',
        'package_booking_price_tier_id',
        'organizer_user_id',
        'invite_code',
        'target_participants',
        'unit_price_snapshot',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'target_participants' => 'integer',
        'unit_price_snapshot' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function tentor(): BelongsTo
    {
        return $this->belongsTo(Tentor::class, 'tentor_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'study_group_user')
            ->withPivot([
                'role',
                'status',
                'bill_invoice_id',
                'user_package_access_id',
                'unit_price_snapshot',
                'paid_at',
            ])
            ->withTimestamps();
    }

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
        return $this->belongsTo(PackageBookingPriceTier::class, 'package_booking_price_tier_id');
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(StudyGroupMember::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public function bookingRequests(): HasMany
    {
        return $this->hasMany(ScheduleBookingRequest::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(StudentFeedback::class);
    }

    public function progressReports(): HasMany
    {
        return $this->hasMany(StudentProgressReport::class);
    }
}
