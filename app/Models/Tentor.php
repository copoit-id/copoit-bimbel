<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tentor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'expertise',
        'bio',
        'profile_photo_path',
        'education',
        'experience_years',
        'experience',
        'certifications',
        'teaching_method',
        'is_active',
        'honor_per_attendance',
        'user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'honor_per_attendance' => 'decimal:0',
        'experience_years' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassModel::class, 'tentor_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'tentor_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'tentor_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(TutorAttendance::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(TutorPayroll::class);
    }

    public function packageRates(): HasMany
    {
        return $this->hasMany(TutorPackageRate::class);
    }

    public function chatConversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class, 'tentor_id');
    }

    public function bookingRules(): BelongsToMany
    {
        return $this->belongsToMany(
            PackageBookingRule::class,
            'package_booking_rule_tentor',
            'tentor_id',
            'package_booking_rule_id'
        )->withTimestamps();
    }

    public function bookingRequests(): HasMany
    {
        return $this->hasMany(ScheduleBookingRequest::class, 'tentor_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(TutorReview::class, 'tentor_id');
    }

    public function visibleReviews(): HasMany
    {
        return $this->reviews()->visible();
    }

    public function studentFeedback(): HasMany
    {
        return $this->hasMany(StudentFeedback::class);
    }

    public function studentProgressReports(): HasMany
    {
        return $this->hasMany(StudentProgressReport::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $query) use ($term) {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('expertise', 'like', "%{$term}%");
        });
    }
}
