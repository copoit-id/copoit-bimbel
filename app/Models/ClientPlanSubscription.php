<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ClientPlanSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'status',
        'starts_at',
        'expires_at',
        'essay_ai_used_this_month',
        'essay_ai_reset_at',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'essay_ai_reset_at' => 'datetime',
        'essay_ai_used_this_month' => 'integer',
    ];

    // Relationships
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    // Singleton pattern untuk single client
    public static function getCurrent(): ?self
    {
        return self::with('plan')
            ->whereIn('status', ['active', 'trial'])
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trial')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    // Status Check
    public function isActive(): bool
    {
        if ($this->status === 'suspended') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return in_array($this->status, ['active', 'trial']);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial';
    }

    // Essay AI
    public function canUseEssayAI(): bool
    {
        if (!$this->plan->essay_ai_enabled) {
            return false;
        }

        // Check if need reset
        if ($this->essay_ai_reset_at && $this->essay_ai_reset_at->isPast()) {
            $this->resetEssayAICounter();
        }

        // Check limit
        if ($this->plan->isUnlimitedEssayAI()) {
            return true;
        }

        return $this->essay_ai_used_this_month < $this->plan->essay_ai_monthly_limit;
    }

    public function getRemainingEssayAIQuota(): int
    {
        if (!$this->plan->essay_ai_enabled) {
            return 0;
        }

        if ($this->plan->isUnlimitedEssayAI()) {
            return -1; // Unlimited
        }

        return max(0, $this->plan->essay_ai_monthly_limit - $this->essay_ai_used_this_month);
    }

    public function incrementEssayAIUsage(int $count = 1): void
    {
        $this->increment('essay_ai_used_this_month', $count);
    }

    public function resetEssayAICounter(): void
    {
        $this->update([
            'essay_ai_used_this_month' => 0,
            'essay_ai_reset_at' => Carbon::now()->addMonth(),
        ]);
    }

    public function getEssayAIResetDate(): ?Carbon
    {
        return $this->essay_ai_reset_at;
    }

    // Getters for limits
    public function getMaxPackages(): int
    {
        return $this->plan->max_packages;
    }

    public function getMaxUsers(): int
    {
        return $this->plan->max_users;
    }

    public function getMaxQuestionBanks(): int
    {
        return $this->plan->max_question_banks;
    }
}
