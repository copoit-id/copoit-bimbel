<?php

namespace App\Services;

use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayTransaction;

class AiGatewaySubscriptionService
{
    public function activateTransaction(AiGatewayTransaction $transaction): void
    {
        if ($transaction->status === 'paid') {
            return;
        }

        $pendingSubscription = AiGatewaySubscription::with('plan')->find($transaction->ai_gateway_subscription_id);
        if (!$pendingSubscription) {
            return;
        }

        $tokenCredit = max(1, (int) ($pendingSubscription->token_limit ?: $transaction->plan?->token_limit ?: 0));
        $durationDays = max(1, (int) ($transaction->plan?->duration_days ?? 30));
        $activeSubscription = AiGatewaySubscription::query()
            ->where('ai_gateway_client_id', $pendingSubscription->ai_gateway_client_id)
            ->where('external_user_id', $pendingSubscription->external_user_id)
            ->where('ai_gateway_plan_id', $pendingSubscription->ai_gateway_plan_id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest()
            ->first();

        if ($activeSubscription) {
            $currentLimit = (int) ($activeSubscription->token_limit ?: $activeSubscription->plan?->token_limit ?: 0);
            $activeSubscription->update([
                'token_limit' => $currentLimit + $tokenCredit,
                'ends_at' => $activeSubscription->ends_at->copy()->addDays($durationDays),
            ]);
            $pendingSubscription->update(['status' => 'merged']);
        } else {
            $pendingSubscription->update([
                'status' => 'active',
                'token_limit' => $tokenCredit,
                'starts_at' => now(),
                'ends_at' => now()->addDays($durationDays),
            ]);
        }

        $transaction->update(['status' => 'paid', 'paid_at' => now()]);
    }
}
