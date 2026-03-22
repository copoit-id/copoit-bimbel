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
                'max_question_banks' => 3,
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
                'description' => 'Paket S - 100 Packages, 100 Users, 10 Essay AI/bulan',
                'price' => 99000,
                'duration_days' => 0,
                'is_active' => true,
                'max_packages' => 100,
                'max_users' => 100,
                'max_question_banks' => -1, // Unlimited
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
                'description' => 'Paket M - 200 Packages, 100 Users, 20 Essay AI/bulan',
                'price' => 149000,
                'duration_days' => 0,
                'is_active' => true,
                'max_packages' => 200,
                'max_users' => 100,
                'max_question_banks' => -1, // Unlimited
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
                'description' => 'Paket L - 300 Packages, 200 Users, 30 Essay AI/bulan',
                'price' => 249000,
                'duration_days' => 0,
                'is_active' => true,
                'max_packages' => 300,
                'max_users' => 200,
                'max_question_banks' => -1, // Unlimited
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
                'description' => 'Paket XL - 400 Packages, 300 Users, 40 Essay AI/bulan',
                'price' => 349000,
                'duration_days' => 0,
                'is_active' => true,
                'max_packages' => 400,
                'max_users' => 300,
                'max_question_banks' => -1, // Unlimited
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
                'description' => 'Paket X2L - 800 Packages, 600 Users, 50 Essay AI/bulan',
                'price' => 599000,
                'duration_days' => 0,
                'is_active' => true,
                'max_packages' => 800,
                'max_users' => 600,
                'max_question_banks' => -1, // Unlimited
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
                'description' => 'Paket X3L - Unlimited Packages, Unlimited Users, Unlimited Essay AI',
                'price' => 999000,
                'duration_days' => 0,
                'is_active' => true,
                'max_packages' => -1, // Unlimited
                'max_users' => -1, // Unlimited
                'max_question_banks' => -1, // Unlimited
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
