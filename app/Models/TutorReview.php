<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_booking_request_id',
        'user_id',
        'tentor_id',
        'rating',
        'comment',
        'is_visible',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_visible' => 'boolean',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(
            ScheduleBookingRequest::class,
            'schedule_booking_request_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tentor(): BelongsTo
    {
        return $this->belongsTo(Tentor::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }
}
