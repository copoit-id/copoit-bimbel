<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    private const URL_LIKE_PATTERN = '~(?:https?://\\S+|www\\.\\S+|\\b[a-z0-9][a-z0-9-]{1,61}\\.[a-z]{2,}(?:\\.[a-z]{2,})?\\b)~i';

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected ?array $effectivePermissionSlugs = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [
        'id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'admin_expires_at' => 'datetime',
        ];
    }

    public static function containsUrlLike(string $value): bool
    {
        return (bool) preg_match(self::URL_LIKE_PATTERN, $value);
    }

    public static function sanitizeName(string $name): string
    {
        $name = preg_replace('/[\\x00-\\x1F\\x7F]/', '', $name);
        $name = trim(preg_replace('/\\s+/', ' ', $name));

        if ($name === '') {
            return '';
        }

        $name = preg_replace(self::URL_LIKE_PATTERN, '', $name);
        $name = trim(preg_replace('/\\s+/', ' ', $name));

        return $name;
    }

    public static function obfuscateUrlLike(string $value): string
    {
        return preg_replace_callback(self::URL_LIKE_PATTERN, function ($matches) {
            return str_replace(['.', ':', '/'], ['[.]', '[:]', '[/]'], $matches[0]);
        }, $value);
    }

    public function getSafeNameForEmailAttribute(): string
    {
        $name = (string) ($this->attributes['name'] ?? '');
        if ($name === '') {
            return '';
        }

        return self::obfuscateUrlLike($name);
    }

    public function getLeaderboardMajorChoicesDisplayAttribute(): ?string
    {
        $primaryDestination = $this->participant_destination_display_name;
        $secondDestination = $this->second_participant_destination_display_name;

        $choices = array_values(array_filter([
            $primaryDestination ?: trim((string) ($this->attributes['major_choice_1'] ?? '')),
            $secondDestination ?: trim((string) ($this->attributes['major_choice_2'] ?? '')),
        ], static fn (mixed $choice): bool => $choice !== ''));

        return $choices === [] ? null : implode(' / ', array_values(array_unique($choices)));
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = self::sanitizeName((string) $value);
    }

    // Relationships
    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class, 'user_id', 'id');
    }

    public function userPackageAccess()
    {
        return $this->hasMany(UserPackageAcces::class, 'user_id', 'id');
    }

    public function scheduleBookingRequests()
    {
        return $this->hasMany(ScheduleBookingRequest::class, 'user_id');
    }

    public function tutorReviews()
    {
        return $this->hasMany(TutorReview::class, 'user_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'user_id', 'id');
    }

    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }

    public function affiliateCommissions()
    {
        return $this->hasMany(AffiliateCommission::class, 'affiliate_user_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'parent_student', 'parent_id', 'child_id')
            ->withPivot(['relationship', 'receive_notifications'])
            ->withTimestamps();
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'parent_student', 'child_id', 'parent_id')
            ->withPivot(['relationship', 'receive_notifications'])
            ->withTimestamps();
    }

    public function tentorProfile(): HasOne
    {
        return $this->hasOne(Tentor::class, 'user_id');
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class, 'user_id', 'id');
    }

    public function materialAccess()
    {
        return $this->hasMany(UserMaterialAccess::class, 'user_id');
    }

    public function classAccess()
    {
        return $this->hasMany(UserClassAccess::class, 'user_id');
    }

    public function studentChatConversations()
    {
        return $this->hasMany(ChatConversation::class, 'student_user_id');
    }

    public function tutorChatConversations()
    {
        return $this->hasMany(ChatConversation::class, 'tutor_user_id');
    }

    public function materialProgressLogs()
    {
        return $this->hasMany(MaterialProgressLog::class, 'user_id');
    }

    public function billInvoices()
    {
        return $this->hasMany(BillInvoice::class, 'user_id');
    }

    public function classAttendances()
    {
        return $this->hasMany(ClassAttendance::class, 'user_id');
    }

    public function studyGroups(): BelongsToMany
    {
        return $this->belongsToMany(StudyGroup::class, 'study_group_user')
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

    public function studyGroupMembers(): HasMany
    {
        return $this->hasMany(StudyGroupMember::class);
    }

    public function organizedStudyGroups(): HasMany
    {
        return $this->hasMany(StudyGroup::class, 'organizer_user_id');
    }

    public function studentFeedback(): HasMany
    {
        return $this->hasMany(StudentFeedback::class);
    }

    public function studentProgressReports(): HasMany
    {
        return $this->hasMany(StudentProgressReport::class);
    }

    public function participantDestinationCategory(): BelongsTo
    {
        return $this->belongsTo(ParticipantDestinationCategory::class, 'participant_destination_category_id');
    }

    public function secondParticipantDestinationCategory(): BelongsTo
    {
        return $this->belongsTo(ParticipantDestinationCategory::class, 'second_participant_destination_category_id');
    }

    public function getParticipantDestinationDisplayNameAttribute(): ?string
    {
        if ($this->participantDestinationCategory) {
            return $this->participantDestinationCategory->display_name;
        }

        $institutionName = trim((string) ($this->participant_destination_institution_name ?? ''));
        $programName = trim((string) ($this->participant_destination_program_name ?? ''));

        if ($institutionName === '' && $programName === '') {
            return null;
        }

        return $programName !== ''
            ? $institutionName.' - '.$programName
            : $institutionName;
    }

    public function getSecondParticipantDestinationDisplayNameAttribute(): ?string
    {
        if ($this->secondParticipantDestinationCategory) {
            return $this->secondParticipantDestinationCategory->display_name;
        }

        $institutionName = trim((string) ($this->second_participant_destination_institution_name ?? ''));
        $programName = trim((string) ($this->second_participant_destination_program_name ?? ''));

        if ($institutionName === '' && $programName === '') {
            return null;
        }

        return $programName !== ''
            ? $institutionName.' - '.$programName
            : $institutionName;
    }

    public function getParticipantDestinationFilterKeyAttribute(): ?string
    {
        if ($this->participantDestinationCategory) {
            return 'db:'.$this->participantDestinationCategory->id;
        }

        $source = trim((string) ($this->participant_destination_source ?? ''));
        $externalId = trim((string) ($this->participant_destination_external_id ?? ''));

        if ($source === '' || $externalId === '') {
            return null;
        }

        return $source.':'.$externalId;
    }

    // Helper methods
    public function hasActivePackageAccess($packageId)
    {
        return $this->userPackageAccess()
            ->where('package_id', $packageId)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            })
            ->exists();
    }

    public function getCompletedTryoutsCount()
    {
        return $this->userAnswers()
            ->where('status', 'completed')
            ->count();
    }

    public function getAverageScore()
    {
        return $this->userAnswers()
            ->where('status', 'completed')
            ->whereNotNull('score')
            ->avg('score') ?? 0;
    }

    public function isAdmin(): bool
    {
        return $this->canAccessAdminPanel();
    }

    public function canAccessAdminPanel(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! empty($this->getEffectivePermissionSlugs())) {
            return true;
        }

        // Backward compatibility for legacy users that might not have role pivot synced yet.
        return in_array($this->role, ['admin', 'admin_demo'], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isDemoAdmin(): bool
    {
        return $this->role === 'admin_demo';
    }

    public function isTutor(): bool
    {
        return $this->role === 'tutor';
    }

    public function isParent(): bool
    {
        return $this->role === 'parent';
    }

    public function hasPermission(string $feature, string $action): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Allow admin landing page once user is recognized as admin-panel user.
        if ($feature === 'dashboard' && $action === 'view' && $this->canAccessAdminPanel()) {
            return true;
        }

        $slug = $feature.'.'.$action;

        return in_array($slug, $this->getEffectivePermissionSlugs(), true);
    }

    public function getEffectivePermissionSlugs(): array
    {
        if ($this->effectivePermissionSlugs !== null) {
            return $this->effectivePermissionSlugs;
        }

        $this->loadMissing('roles.permissions');

        $slugs = $this->roles
            ->flatMap(function ($role) {
                return $role->permissions->pluck('slug');
            })
            ->unique()
            ->values()
            ->all();

        if (empty($slugs) && ! empty($this->role)) {
            $fallbackRole = Role::query()
                ->where('slug', $this->role)
                ->with('permissions:id,slug')
                ->first();

            if ($fallbackRole) {
                $slugs = $fallbackRole->permissions
                    ->pluck('slug')
                    ->unique()
                    ->values()
                    ->all();
            }
        }

        $this->effectivePermissionSlugs = $slugs;

        return $this->effectivePermissionSlugs;
    }

    // Material access helpers
    public function hasMaterialAccess(int $materialId): bool
    {
        return $this->materialAccess()
            ->where('material_id', $materialId)
            ->where('status', '!=', 'not_started')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function canAccessMaterial(int $materialId): bool
    {
        $material = Material::find($materialId);
        if ($material && $material->canUserAccess($this->id)) {
            return true;
        }

        // Check direct access
        $hasDirectAccess = $this->materialAccess()
            ->where('material_id', $materialId)
            ->where('access_source', 'direct')
            ->whereIn('access_type', ['free', 'purchased', 'paid'])
            ->where('status', '!=', 'not_started')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($hasDirectAccess) {
            return true;
        }

        // Check access via package - menggunakan query builder untuk menghindari masalah relasi
        $activePackageIds = $this->userPackageAccess()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            })
            ->pluck('package_id')
            ->toArray();

        return \DB::table('detail_packages')
            ->where('detailable_type', Material::class)
            ->where('detailable_id', $materialId)
            ->whereIn('package_id', $activePackageIds)
            ->exists();
    }

    public function getMaterialProgress(int $materialId): ?UserMaterialAccess
    {
        return $this->materialAccess()
            ->where('material_id', $materialId)
            ->first();
    }

    /**
     * Relasi ke tryout access (individual)
     */
    public function tryoutAccess()
    {
        return $this->hasMany(UserTryoutAccess::class, 'user_id');
    }

    /**
     * Cek apakah user bisa akses tryout (direct atau via package)
     */
    public function canAccessTryout(int $tryoutId): bool
    {
        $tryout = Tryout::find($tryoutId);
        if ($tryout && $tryout->canUserAccess($this->id)) {
            return true;
        }

        // Check direct access
        $hasDirectAccess = $this->tryoutAccess()
            ->where('tryout_id', $tryoutId)
            ->where('access_source', 'direct')
            ->whereIn('access_type', ['free', 'purchased', 'paid'])
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($hasDirectAccess) {
            return true;
        }

        // Check access via package
        $activePackageIds = $this->userPackageAccess()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            })
            ->pluck('package_id')
            ->toArray();

        return \DB::table('detail_packages')
            ->where('detailable_id', $tryoutId)
            ->where('detailable_type', Tryout::class)
            ->whereIn('package_id', $activePackageIds)
            ->exists();
    }
}
