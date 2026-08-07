<?php

namespace App\Models;

use App\Models\Concerns\HasIndividualPricing;
use App\Services\TutorContentVisibilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tryout extends Model
{
    use HasFactory;
    use HasIndividualPricing;

    protected $table = 'tryouts';
    protected $primaryKey = 'tryout_id';
    protected $guarded = ['tryout_id'];

    protected $casts = [
        'is_certification' => 'boolean',
        'is_toefl' => 'boolean',
        'is_irt' => 'boolean',
        'material_category_id' => 'integer',
        'scoring_method' => 'string',
        'is_active' => 'boolean',
        'is_for_sale' => 'boolean',
        'is_displayed' => 'boolean',
        'type_price' => 'string',
        'show_discussion' => 'boolean',
        'show_leaderboard' => 'boolean',
        'section_break_duration' => 'integer',
        'max_attempts' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'results_release_at' => 'datetime',
        'results_released_at' => 'datetime',
        'results_reset_at' => 'datetime',
        'assessment_type' => 'string',
        'answer_persistence_mode' => 'string',
        'subtest_display_mode' => 'string',
        'user_card_display' => 'string',
        'enable_anti_copy' => 'boolean',
        'enable_tab_switch_detection' => 'boolean',
        'enable_webcam_check' => 'boolean',
        'enable_screen_check' => 'boolean',
        'price' => 'decimal:0',
        'access_duration_value' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tutor-content-owner', function (Builder $query): void {
            $user = auth()->user();

            if (! app(TutorContentVisibilityService::class)->shouldScopeToOwner($user)) {
                return;
            }

            $query->where($query->qualifyColumn('created_by'), $user->id);
        });
    }

    public function requiresIrtScoring(): bool
    {
        return $this->scoring_method === 'irt_utbk'
            || $this->scoring_method === 'irt'
            || $this->is_irt;
    }

    public function hasReleasedUtbk(): bool
    {
        if (! $this->results_released_at) {
            return false;
        }

        if (! $this->results_reset_at) {
            return true;
        }

        return $this->results_reset_at->lt($this->results_released_at);
    }

    public function canReleaseUtbk(): bool
    {
        return $this->requiresIrtScoring() && ! $this->hasReleasedUtbk();
    }

    public function tryoutDetails()
    {
        return $this->hasMany(TryoutDetail::class, 'tryout_id', 'tryout_id');
    }

    public function materialCategory()
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id', 'category_id');
    }

    // Polymorphic relationship untuk detail packages
    public function detailPackages()
    {
        return $this->morphMany(DetailPackage::class, 'detailable');
    }

    // Many-to-many relationship dengan packages melalui detail_packages
    public function packages()
    {
        return $this->morphToMany(Package::class, 'detailable', 'detail_packages', 'detailable_id', 'package_id');
    }

    // Add missing userAnswers relationship
    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class, 'tryout_id', 'tryout_id');
    }

    public function feedbackQuestions()
    {
        return $this->hasMany(FeedbackQuestion::class, 'tryout_id', 'tryout_id');
    }

    public function feedbackSubmissions()
    {
        return $this->hasMany(FeedbackSubmission::class, 'tryout_id', 'tryout_id');
    }

    public function proctoringSnapshots()
    {
        return $this->hasMany(ProctoringSnapshot::class, 'tryout_id', 'tryout_id');
    }

    /**
     * Relasi ke user access (individual)
     */
    public function userAccess()
    {
        return $this->hasMany(UserTryoutAccess::class, 'tryout_id', 'tryout_id');
    }

    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'class_assessments', 'tryout_id', 'class_id')
            ->withPivot('assessment_type')
            ->withTimestamps();
    }

    // Helper method untuk mendapatkan total soal
    public function getTotalQuestionsAttribute()
    {
        return $this->tryoutDetails()->withCount('questions')->get()->sum('questions_count');
    }

    // Helper method untuk mendapatkan total durasi
    public function getTotalDurationAttribute()
    {
        return $this->tryoutDetails()->sum('duration');
    }

    public function getAttemptLimitLabelAttribute(): string
    {
        $maxAttempts = (int) ($this->max_attempts ?? 0);

        return $maxAttempts > 0 ? $maxAttempts.' kali' : 'Tidak dibatasi';
    }

    public function completedAttemptCountForUser(int $userId): int
    {
        $finalStatuses = ['completed', 'pending_release'];

        if ($this->relationLoaded('userAnswers')) {
            return $this->userAnswers
                ->where('user_id', $userId)
                ->whereIn('status', $finalStatuses)
                ->pluck('attempt_token')
                ->filter()
                ->unique()
                ->count();
        }

        return $this->userAnswers()
            ->where('user_id', $userId)
            ->whereIn('status', $finalStatuses)
            ->distinct('attempt_token')
            ->count('attempt_token');
    }

    public function hasInProgressAttemptForUser(int $userId): bool
    {
        if ($this->relationLoaded('userAnswers')) {
            return $this->userAnswers
                ->where('user_id', $userId)
                ->where('status', 'in_progress')
                ->isNotEmpty();
        }

        return $this->userAnswers()
            ->where('user_id', $userId)
            ->where('status', 'in_progress')
            ->exists();
    }

    public function remainingAttemptsForUser(int $userId): ?int
    {
        $maxAttempts = (int) ($this->max_attempts ?? 0);

        if ($maxAttempts <= 0) {
            return null;
        }

        return max(0, $maxAttempts - $this->completedAttemptCountForUser($userId));
    }

    public function hasReachedAttemptLimitForUser(int $userId): bool
    {
        $maxAttempts = (int) ($this->max_attempts ?? 0);

        return $maxAttempts > 0 && $this->completedAttemptCountForUser($userId) >= $maxAttempts;
    }

    /**
     * Check if user can access this tryout (via any method)
     */
    public function canUserAccess(int $userId): bool
    {
        // Check via package
        $hasPackageAccess = $this->packages()
            ->whereHas('userAccess', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->where('status', 'active')
                  ->where(function ($q) {
                      $q->whereNull('end_date')->orWhere('end_date', '>', now());
                  });
            })
            ->exists();

        if ($hasPackageAccess) {
            return true;
        }

        // Check via direct user access
        $hasDirectAccess = $this->userAccess()
            ->where('user_id', $userId)
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

        // Check via individual purchase.
        if ($this->isIndividuallyAvailable()) {
            $hasIndividualPurchase = \App\Models\IndividualPurchase::where('user_id', $userId)
                ->where('purchasable_type', self::class)
                ->where('purchasable_id', $this->tryout_id)
                ->where('status', 'approved')
                ->where(function ($query) {
                    $query->whereNull('access_expires_at')
                        ->orWhere('access_expires_at', '>', now());
                })
                ->exists();

            if ($hasIndividualPurchase) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has purchased this individually
     */
    public function hasUserPurchased(int $userId): bool
    {
        return \App\Models\IndividualPurchase::where('user_id', $userId)
            ->where('purchasable_type', self::class)
            ->where('purchasable_id', $this->tryout_id)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Check if user has pending purchase for this
     */
    public function hasPendingPurchase(int $userId): bool
    {
        return \App\Models\IndividualPurchase::where('user_id', $userId)
            ->where('purchasable_type', self::class)
            ->where('purchasable_id', $this->tryout_id)
            ->where('status', 'pending')
            ->exists();
    }
}
