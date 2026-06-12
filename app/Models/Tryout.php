<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tryout extends Model
{
    use HasFactory;

    protected $table = 'tryouts';
    protected $primaryKey = 'tryout_id';
    protected $guarded = ['tryout_id'];

    protected $casts = [
        'is_certification' => 'boolean',
        'is_toefl' => 'boolean',
        'is_irt' => 'boolean',
        'is_active' => 'boolean',
        'is_for_sale' => 'boolean',
        'is_displayed' => 'boolean',
        'section_break_duration' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'results_release_at' => 'datetime',
        'results_released_at' => 'datetime',
        'results_reset_at' => 'datetime',
        'assessment_type' => 'string',
        'answer_persistence_mode' => 'string',
        'subtest_display_mode' => 'string',
        'enable_anti_copy' => 'boolean',
        'enable_tab_switch_detection' => 'boolean',
        'enable_webcam_check' => 'boolean',
        'enable_screen_check' => 'boolean',
        'price' => 'decimal:0',
    ];

    public function requiresIrtScoring(): bool
    {
        return $this->type_tryout === 'utbk_full' && $this->is_irt;
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
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($hasDirectAccess) {
            return true;
        }

        // Check via individual purchase (if tryout has price > 0)
        if ($this->price > 0) {
            $hasIndividualPurchase = \App\Models\IndividualPurchase::where('user_id', $userId)
                ->where('purchasable_type', self::class)
                ->where('purchasable_id', $this->tryout_id)
                ->where('status', 'approved')
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
