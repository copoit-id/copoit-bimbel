<?php

namespace App\Providers;

use App\Services\PlanQuotaService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class PlanQuotaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Share plan quota data ke semua view admin
        View::composer('admin.*', function ($view) {
            $view->with('planQuota', [
                'package' => PlanQuotaService::canCreatePackage(),
                'user' => PlanQuotaService::canRegisterUser(),
                'question_bank' => PlanQuotaService::canCreateQuestionBank(),
                'essay_ai' => PlanQuotaService::canUseEssayAI(),
                'stats' => PlanQuotaService::getUsageStats(),
            ]);
        });
    }
}
