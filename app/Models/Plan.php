<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'duration_days',
        'is_active',
        'max_packages',
        'max_users',
        'max_question_banks',
        'max_tryouts',
        'essay_ai_enabled',
        'essay_ai_monthly_limit',
        'is_default',
        'is_trial',
        'trial_duration_days',
        'features_json',
    ];

    protected $casts = [
        'price' => 'decimal:0',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_trial' => 'boolean',
        'essay_ai_enabled' => 'boolean',
        'features_json' => 'array',
        'max_packages' => 'integer',
        'max_users' => 'integer',
        'max_question_banks' => 'integer',
        'max_tryouts' => 'integer',
        'essay_ai_monthly_limit' => 'integer',
        'trial_duration_days' => 'integer',
        'duration_days' => 'integer',
    ];

    // Relationships
    public function subscriptions()
    {
        return $this->hasMany(ClientPlanSubscription::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeTrial($query)
    {
        return $query->where('is_trial', true);
    }

    // Helpers
    public function isUnlimitedPackages(): bool
    {
        return $this->max_packages === -1;
    }

    public function isUnlimitedUsers(): bool
    {
        return $this->max_users === -1;
    }

    public function isUnlimitedQuestionBanks(): bool
    {
        return $this->max_question_banks === -1;
    }

    public function isUnlimitedTryouts(): bool
    {
        return $this->max_tryouts === -1;
    }

    public function isUnlimitedEssayAI(): bool
    {
        return $this->essay_ai_enabled && $this->essay_ai_monthly_limit === 0;
    }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->price == 0) {
            return 'Gratis';
        }
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getDurationTextAttribute(): string
    {
        if ($this->duration_days === 0) {
            return 'Lifetime';
        }
        return $this->duration_days . ' Hari';
    }

    public function getMaxPackagesTextAttribute(): string
    {
        return $this->isUnlimitedPackages() ? 'Unlimited' : $this->max_packages;
    }

    public function getMaxUsersTextAttribute(): string
    {
        return $this->isUnlimitedUsers() ? 'Unlimited' : $this->max_users;
    }

    public function getMaxQuestionBanksTextAttribute(): string
    {
        return $this->isUnlimitedQuestionBanks() ? 'Unlimited' : $this->max_question_banks;
    }

    public function getMaxTryoutsTextAttribute(): string
    {
        return $this->isUnlimitedTryouts() ? 'Unlimited' : $this->max_tryouts;
    }

    public function getEssayAILimitTextAttribute(): string
    {
        if (!$this->essay_ai_enabled) {
            return 'Disabled';
        }
        return $this->isUnlimitedEssayAI() ? 'Unlimited' : number_format($this->essay_ai_monthly_limit) . '/bln';
    }
}
