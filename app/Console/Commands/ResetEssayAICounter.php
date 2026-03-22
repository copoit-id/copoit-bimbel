<?php

namespace App\Console\Commands;

use App\Models\ClientPlanSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ResetEssayAICounter extends Command
{
    protected $signature = 'plan:reset-essay-counter {--client= : Specific client_profile_id to reset}';
    protected $description = 'Reset Essay AI usage counter for all active subscriptions or specific client';

    public function handle(): int
    {
        $clientId = $this->option('client');

        if ($clientId) {
            // Reset specific client
            $subscription = ClientPlanSubscription::where('client_profile_id', $clientId)
                ->whereIn('status', ['active', 'trial'])
                ->first();

            if ($subscription) {
                $subscription->resetEssayAICounter();
                $this->info("Essay AI counter reset for client {$clientId}.");
                return 0;
            }

            $this->warn("No active subscription found for client {$clientId}.");
            return 1;
        }

        // Reset all active subscriptions that have passed reset date
        $subscriptions = ClientPlanSubscription::whereIn('status', ['active', 'trial'])
            ->where(function ($query) {
                $query->whereNull('essay_ai_reset_at')
                    ->orWhere('essay_ai_reset_at', '<=', now());
            })
            ->get();

        $count = 0;
        foreach ($subscriptions as $subscription) {
            $subscription->resetEssayAICounter();
            $count++;
        }

        $this->info("Essay AI counter reset for {$count} subscriptions.");
        
        return 0;
    }
}
