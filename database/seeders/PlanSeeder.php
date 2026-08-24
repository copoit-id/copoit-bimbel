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
                'duration_days' => 0,
                'is_active' => true,
                'max_packages' => 2,
                'max_users' => 20,
                'max_question_banks' => -1, // Unlimited
                'max_tryouts' => 2,
                'essay_ai_enabled' => true,
                'essay_ai_monthly_limit' => 10,
                'is_default' => true,
                'is_trial' => true,
                'trial_duration_days' => 14,
            ]
        );

        // Langganan - Paket S
        Plan::firstOrCreate(
            ['slug' => 'langganan-paket-s'],
            [
                'name' => 'Langganan - Paket S',
                'description' => 'Paket S - 50 Users, 10 Tryout, 10 Paket',
                'price' => 99000,
                'duration_days' => 0,
                'is_active' => true,
                'max_packages' => 10,
                'max_users' => 50,
                'max_question_banks' => -1, // Unlimited
                'max_tryouts' => 10,
                'essay_ai_enabled' => true,
                'essay_ai_monthly_limit' => 10,
                'is_default' => false,
                'is_trial' => false,
                'trial_duration_days' => 0,
            ]
        );

        // Langganan - Paket M
        Plan::firstOrCreate(
            ['slug' => 'langganan-paket-m'],
            [
                'name' => 'Langganan - Paket M',
                'description' => 'Paket M - 100 Users, 15 Tryout, 20 Paket',
                'price' => 149000,
                'duration_days' => 0,
                'is_active' => true,
                'max_packages' => 20,
                'max_users' => 100,
                'max_question_banks' => -1, // Unlimited
                'max_tryouts' => 15,
                'essay_ai_enabled' => true,
                'essay_ai_monthly_limit' => 20,
                'is_default' => false,
                'is_trial' => false,
                'trial_duration_days' => 0,
            ]
        );

        // Langganan - Paket L
        Plan::firstOrCreate(
            ['slug' => 'langganan-paket-l'],
            [
                'name' => 'Langganan - Paket L',
                'description' => 'Paket L - 300 Users, 20 Tryout, 30 Paket',
                'price' => 249000,
                'duration_days' => 0,
                'is_active' => true,
                'max_packages' => 30,
                'max_users' => 300,
                'max_question_banks' => -1, // Unlimited
                'max_tryouts' => 20,
                'essay_ai_enabled' => true,
                'essay_ai_monthly_limit' => 30,
                'is_default' => false,
                'is_trial' => false,
                'trial_duration_days' => 0,
            ]
        );

        // Langganan - Paket XL
        Plan::firstOrCreate(
            ['slug' => 'langganan-paket-xl'],
            [
                'name' => 'Langganan - Paket XL',
                'description' => 'Paket XL - 400 Users, 40 Tryout, 40 Paket',
                'price' => 349000,
                'duration_days' => 0,
                'is_active' => true,
                'max_packages' => 40,
                'max_users' => 400,
                'max_question_banks' => -1, // Unlimited
                'max_tryouts' => 40,
                'essay_ai_enabled' => true,
                'essay_ai_monthly_limit' => 40,
                'is_default' => false,
                'is_trial' => false,
                'trial_duration_days' => 0,
            ]
        );

        // Langganan - Paket X2L
        Plan::firstOrCreate(
            ['slug' => 'langganan-paket-x2l'],
            [
                'name' => 'Langganan - Paket X2L',
                'description' => 'Paket X2L - 800 Users, 50 Tryout, 50 Paket',
                'price' => 599000,
                'duration_days' => 0,
                'is_active' => true,
                'max_packages' => 50,
                'max_users' => 800,
                'max_question_banks' => -1, // Unlimited
                'max_tryouts' => 50,
                'essay_ai_enabled' => true,
                'essay_ai_monthly_limit' => 50,
                'is_default' => false,
                'is_trial' => false,
                'trial_duration_days' => 0,
            ]
        );

        // Langganan - Paket X3L (Unlimited)
        Plan::firstOrCreate(
            ['slug' => 'langganan-paket-x3l'],
            [
                'name' => 'Langganan - Paket X3L',
                'description' => 'Paket X3L - Unlimited Users, Unlimited Tryout, Unlimited Paket',
                'price' => 999000,
                'duration_days' => 0,
                'is_active' => true,
                'max_packages' => -1, // Unlimited
                'max_users' => -1, // Unlimited
                'max_question_banks' => -1, // Unlimited
                'max_tryouts' => -1, // Unlimited
                'essay_ai_enabled' => true,
                'essay_ai_monthly_limit' => 0, // 0 = Unlimited
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

        $this->command->info('Plan seeding completed successfully!');
    }
}
