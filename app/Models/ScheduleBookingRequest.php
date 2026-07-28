<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ScheduleBookingRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COUNTER_PROPOSED = 'counter_proposed';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'package_id',
        'user_package_access_id',
        'tentor_id',
        'requested_start_at',
        'requested_end_at',
        'scheduled_start_at',
        'scheduled_end_at',
        'status',
        'student_notes',
        'tutor_notes',
        'class_schedule_id',
        'class_session_id',
        'responded_by',
        'responded_at',
        'cancelled_at',
        'slot_key',
    ];

    protected $casts = [
        'requested_start_at' => 'datetime',
        'requested_end_at' => 'datetime',
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'responded_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }

    public function packageAccess(): BelongsTo
    {
        return $this->belongsTo(
            UserPackageAcces::class,
            'user_package_access_id',
            'user_package_access_id'
        );
    }

    public function tentor(): BelongsTo
    {
        return $this->belongsTo(Tentor::class, 'tentor_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function review(): HasOne
    {
        return $this->hasOne(
            TutorReview::class,
            'schedule_booking_request_id'
        );
    }

    public function scopeConsumesQuota(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_APPROVED,
            self::STATUS_COMPLETED,
        ]);
    }

    public function scopeAwaitingResponse(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_COUNTER_PROPOSED,
        ]);
    }
}
