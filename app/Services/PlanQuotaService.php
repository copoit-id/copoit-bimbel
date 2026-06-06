<?php

namespace App\Services;

use App\Models\ClientPlanSubscription;
use App\Models\Package;
use App\Models\User;
use App\Models\QuestionBank;
use App\Models\Plan;

class PlanQuotaService
{
    public const DEFAULT_PROCTORING_SETTINGS = [
        'enable_anti_copy' => true,
        'enable_tab_switch_detection' => true,
        'enable_webcam_check' => false,
        'enable_screen_check' => false,
    ];

    /**
     * Get active subscription for this project (single client)
     */
    public static function getCurrentSubscription(): ?ClientPlanSubscription
    {
        return ClientPlanSubscription::getCurrent();
    }

    /**
     * Get active plan for this project
     */
    public static function getCurrentPlan(): ?Plan
    {
        $subscription = self::getCurrentSubscription();
        return $subscription?->plan;
    }

    public static function getDefaultProctoringSettings(): array
    {
        $features = self::getCurrentPlan()?->features_json ?? [];
        $settings = $features['proctoring_defaults'] ?? [];

        return array_merge(
            self::DEFAULT_PROCTORING_SETTINGS,
            array_intersect_key(array_map('boolval', (array) $settings), self::DEFAULT_PROCTORING_SETTINGS)
        );
    }

    public static function proctoringSettingsFromRequest($request): array
    {
        $availableSettings = self::getDefaultProctoringSettings();
        $resolvedSettings = [];

        foreach ($availableSettings as $field => $isAvailable) {
            $resolvedSettings[$field] = $isAvailable
                ? $request->boolean($field, self::DEFAULT_PROCTORING_SETTINGS[$field])
                : false;
        }

        return $resolvedSettings;
    }

    /**
     * Check if this project has an active subscription
     */
    public static function hasActiveSubscription(): bool
    {
        return self::getCurrentSubscription() !== null;
    }

    /**
     * Check if client can create a new package
     */
    public static function canCreatePackage(): array
    {
        $subscription = self::getCurrentSubscription();

        if (!$subscription) {
            return [
                'allowed' => false,
                'reason' => 'Tidak ada subscription aktif',
                'current' => 0,
                'limit' => 0,
            ];
        }

        $plan = $subscription->plan;
        $maxPackages = $plan->max_packages;

        // -1 means unlimited
        if ($maxPackages === -1) {
            return [
                'allowed' => true,
                'reason' => null,
                'current' => Package::count(),
                'limit' => -1,
            ];
        }

        $currentPackages = Package::count();

        if ($currentPackages >= $maxPackages) {
            return [
                'allowed' => false,
                'reason' => "Anda telah mencapai limit package ({$maxPackages}). Upgrade plan untuk menambah lebih banyak package.",
                'current' => $currentPackages,
                'limit' => $maxPackages,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'current' => $currentPackages,
            'limit' => $maxPackages,
        ];
    }

    /**
     * Check if client can register a new user
     */
    public static function canRegisterUser(): array
    {
        $subscription = self::getCurrentSubscription();

        if (!$subscription) {
            return [
                'allowed' => false,
                'reason' => 'Tidak ada subscription aktif',
                'current' => 0,
                'limit' => 0,
            ];
        }

        $plan = $subscription->plan;
        $maxUsers = $plan->max_users;

        // -1 means unlimited
        if ($maxUsers === -1) {
            return [
                'allowed' => true,
                'reason' => null,
                'current' => User::where('role', 'user')->count(),
                'limit' => -1,
            ];
        }

        $currentUsers = User::where('role', 'user')->count();

        if ($currentUsers >= $maxUsers) {
            return [
                'allowed' => false,
                'reason' => "Anda telah mencapai limit user ({$maxUsers}). Upgrade plan untuk menambah lebih banyak user.",
                'current' => $currentUsers,
                'limit' => $maxUsers,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'current' => $currentUsers,
            'limit' => $maxUsers,
        ];
    }

    /**
     * Check if client can create a new question bank
     */
    public static function canCreateQuestionBank(): array
    {
        $subscription = self::getCurrentSubscription();

        if (!$subscription) {
            return [
                'allowed' => false,
                'reason' => 'Tidak ada subscription aktif',
                'current' => 0,
                'limit' => 0,
            ];
        }

        $plan = $subscription->plan;
        $maxQB = $plan->max_question_banks;

        // -1 means unlimited
        if ($maxQB === -1) {
            return [
                'allowed' => true,
                'reason' => null,
                'current' => QuestionBank::count(),
                'limit' => -1,
            ];
        }

        $currentQB = QuestionBank::count();

        if ($currentQB >= $maxQB) {
            return [
                'allowed' => false,
                'reason' => "Anda telah mencapai limit bank soal ({$maxQB}). Upgrade plan untuk menambah lebih banyak bank soal.",
                'current' => $currentQB,
                'limit' => $maxQB,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'current' => $currentQB,
            'limit' => $maxQB,
        ];
    }

    /**
     * Check if client can use Essay AI feature
     */
    public static function canUseEssayAI(): array
    {
        $subscription = self::getCurrentSubscription();

        if (!$subscription) {
            return [
                'allowed' => false,
                'enabled' => false,
                'reason' => 'Tidak ada subscription aktif',
                'current' => 0,
                'limit' => 0,
                'reset_at' => null,
            ];
        }

        $plan = $subscription->plan;

        // Check if feature is enabled
        if (!$plan->essay_ai_enabled) {
            return [
                'allowed' => false,
                'enabled' => false,
                'reason' => 'Fitur Essay AI tidak tersedia di plan Anda. Upgrade ke Plan Pro atau Enterprise.',
                'current' => 0,
                'limit' => 0,
                'reset_at' => null,
            ];
        }

        // Check if need reset
        if ($subscription->essay_ai_reset_at && $subscription->essay_ai_reset_at->isPast()) {
            $subscription->resetEssayAICounter();
        }

        // 0 means unlimited
        if ($plan->essay_ai_monthly_limit === 0) {
            return [
                'allowed' => true,
                'enabled' => true,
                'reason' => null,
                'current' => $subscription->essay_ai_used_this_month,
                'limit' => 0, // unlimited
                'reset_at' => $subscription->essay_ai_reset_at,
            ];
        }

        $used = $subscription->essay_ai_used_this_month;
        $limit = $plan->essay_ai_monthly_limit;

        if ($used >= $limit) {
            return [
                'allowed' => false,
                'enabled' => true,
                'reason' => "Quota Essay AI bulan ini telah habis ({$used}/{$limit}). Akan direset pada " . $subscription->essay_ai_reset_at?->format('d M Y') . '.',
                'current' => $used,
                'limit' => $limit,
                'reset_at' => $subscription->essay_ai_reset_at,
            ];
        }

        return [
            'allowed' => true,
            'enabled' => true,
            'reason' => null,
            'current' => $used,
            'limit' => $limit,
            'reset_at' => $subscription->essay_ai_reset_at,
        ];
    }

    /**
     * Record Essay AI usage
     */
    public static function recordEssayAIUsage(int $count = 1, ?int $userId = null, ?int $jobId = null): void
    {
        $subscription = self::getCurrentSubscription();

        if (!$subscription) {
            return;
        }

        // Increment counter
        $subscription->incrementEssayAIUsage($count);

        // Create log
        \App\Models\EssayAIUsageLog::create([
            'user_id' => $userId,
            'essay_correction_job_id' => $jobId,
            'essays_count' => $count,
            'used_at' => now(),
        ]);
    }

    /**
     * Get usage stats for this project
     */
    public static function getUsageStats(): array
    {
        $subscription = self::getCurrentSubscription();

        if (!$subscription) {
            return [
                'has_subscription' => false,
                'plan' => null,
                'packages' => ['used' => 0, 'limit' => 0, 'percentage' => 0, 'unlimited' => false],
                'users' => ['used' => 0, 'limit' => 0, 'percentage' => 0, 'unlimited' => false],
                'question_banks' => ['used' => 0, 'limit' => 0, 'percentage' => 0, 'unlimited' => false],
                'essay_ai' => ['used' => 0, 'limit' => 0, 'percentage' => 0, 'unlimited' => false, 'enabled' => false],
            ];
        }

        $plan = $subscription->plan;

        $packageCheck = self::canCreatePackage();
        $userCheck = self::canRegisterUser();
        $qbCheck = self::canCreateQuestionBank();
        $essayCheck = self::canUseEssayAI();

        return [
            'has_subscription' => true,
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'is_trial' => $subscription->isTrial(),
                'expires_at' => $subscription->expires_at,
            ],
            'packages' => [
                'used' => $packageCheck['current'],
                'limit' => $packageCheck['limit'],
                'percentage' => $packageCheck['limit'] > 0 ? round(($packageCheck['current'] / $packageCheck['limit']) * 100) : 0,
                'unlimited' => $packageCheck['limit'] === -1,
            ],
            'users' => [
                'used' => $userCheck['current'],
                'limit' => $userCheck['limit'],
                'percentage' => $userCheck['limit'] > 0 ? round(($userCheck['current'] / $userCheck['limit']) * 100) : 0,
                'unlimited' => $userCheck['limit'] === -1,
            ],
            'question_banks' => [
                'used' => $qbCheck['current'],
                'limit' => $qbCheck['limit'],
                'percentage' => $qbCheck['limit'] > 0 ? round(($qbCheck['current'] / $qbCheck['limit']) * 100) : 0,
                'unlimited' => $qbCheck['limit'] === -1,
            ],
            'essay_ai' => [
                'used' => $essayCheck['current'],
                'limit' => $essayCheck['limit'],
                'percentage' => $essayCheck['limit'] > 0 ? round(($essayCheck['current'] / $essayCheck['limit']) * 100) : 0,
                'unlimited' => $essayCheck['limit'] === 0 && $essayCheck['enabled'],
                'enabled' => $essayCheck['enabled'],
                'reset_at' => $essayCheck['reset_at'],
            ],
        ];
    }

    /**
     * Get remaining quota for a specific feature
     */
    public static function getRemainingQuota(string $feature): int
    {
        $subscription = self::getCurrentSubscription();

        if (!$subscription) {
            return 0;
        }

        $plan = $subscription->plan;

        return match ($feature) {
            'packages' => $plan->isUnlimitedPackages() ? -1 : max(0, $plan->max_packages - Package::count()),
            'users' => $plan->isUnlimitedUsers() ? -1 : max(0, $plan->max_users - User::where('role', 'user')->count()),
            'question_banks' => $plan->isUnlimitedQuestionBanks() ? -1 : max(0, $plan->max_question_banks - QuestionBank::count()),
            'essay_ai' => $subscription->getRemainingEssayAIQuota(),
            default => 0,
        };
    }

    /**
     * Assign plan to this project (used by Super Admin or during setup)
     */
    public static function assignPlan(int $planId, array $data = []): ClientPlanSubscription
    {
        $plan = Plan::findOrFail($planId);

        // Deactivate previous subscriptions
        ClientPlanSubscription::where('status', '!=', 'expired')
            ->update(['status' => 'expired']);

        // Calculate dates
        $startsAt = $data['starts_at'] ?? now();
        $expiresAt = null;

        if ($plan->duration_days > 0) {
            $expiresAt = $startsAt->copy()->addDays($plan->duration_days);
        }

        // Create new subscription
        $subscription = ClientPlanSubscription::create([
            'plan_id' => $planId,
            'status' => $data['status'] ?? 'active',
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'essay_ai_used_this_month' => 0,
            'essay_ai_reset_at' => now()->addMonth(),
            'notes' => $data['notes'] ?? null,
        ]);

        return $subscription;
    }

    /**
     * Get or create default plan for new setup
     */
    public static function getDefaultPlan(): ?Plan
    {
        return Plan::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Setup default subscription for this project
     */
    public static function setupDefaultSubscription(): ?ClientPlanSubscription
    {
        $defaultPlan = self::getDefaultPlan();

        if (!$defaultPlan) {
            return null;
        }

        return self::assignPlan($defaultPlan->id, [
            'status' => $defaultPlan->is_trial ? 'trial' : 'active',
        ]);
    }
}
