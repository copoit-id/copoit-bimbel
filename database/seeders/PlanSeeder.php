<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Services\PlanQuotaService;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // Trial Plan (Default for new projects)
        Plan::firstOrCreate(
            ['slug' => 'trial'],
            [
                'name' => 'Trial',
                'description' => 'Plan trial untuk project baru. Berlaku 14 hari.',
                'price' => 0,
                'duration_days' => 14,
                'is_active' => true,
                'max_packages' => 2,
                'max_users' => 20,
                'max_question_banks' => 3,
                'essay_ai_enabled' => true,
                'essay_ai_monthly_limit' => 100,
                'is_default' => true,
                'is_trial' => true,
                'trial_duration_days' => 14,
            ]
        );

        // Basic Plan (Free)
        Plan::firstOrCreate(
            ['slug' => 'basic'],
            [
                'name' => 'Basic',
                'description' => 'Plan gratis dengan fitur dasar.',
                'price' => 0,
                'duration_days' => 0, // Lifetime
                'is_active' => true,
                'max_packages' => 3,
                'max_users' => 50,
                'max_question_banks' => 5,
                'essay_ai_enabled' => false,
                'essay_ai_monthly_limit' => 0,
                'is_default' => false,
                'is_trial' => false,
                'trial_duration_days' => 0,
            ]
        );

        // Pro Plan
        Plan::firstOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'description' => 'Plan profesional dengan fitur lengkap.',
                'price' => 299000,
                'duration_days' => 30,
                'is_active' => true,
                'max_packages' => 10,
                'max_users' => 500,
                'max_question_banks' => 20,
                'essay_ai_enabled' => true,
                'essay_ai_monthly_limit' => 1000,
                'is_default' => false,
                'is_trial' => false,
                'trial_duration_days' => 0,
            ]
        );

        // Enterprise Plan
        Plan::firstOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'description' => 'Plan enterprise dengan fitur unlimited.',
                'price' => 999000,
                'duration_days' => 30,
                'is_active' => true,
                'max_packages' => -1, // Unlimited
                'max_users' => -1, // Unlimited
                'max_question_banks' => -1, // Unlimited
                'essay_ai_enabled' => true,
                'essay_ai_monthly_limit' => 10000,
                'is_default' => false,
                'is_trial' => false,
                'trial_duration_days' => 0,
            ]
        );

        // Auto-setup default subscription untuk project ini
        // Hanya jika belum ada subscription
        if (!PlanQuotaService::hasActiveSubscription()) {
            PlanQuotaService::setupDefaultSubscription();
            $this->command->info('Default subscription created for this project.');
        }
    }
}
