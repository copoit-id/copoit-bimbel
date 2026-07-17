<?php

namespace App\Services;

use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayTransaction;

class AiGatewaySubscriptionService
{
    public function activateTransaction(AiGatewayTransaction $transaction, string $confirmationSource = 'provider'): void
    {
        if ($transaction->status === 'paid') {
            return;
        }

        $pendingSubscription = AiGatewaySubscription::with('plan')->find($transaction->ai_gateway_subscription_id);
        if (!$pendingSubscription) {
            return;
        }

        $tokenCredit = max(1, (int) ($pendingSubscription->token_limit ?: $transaction->plan?->token_limit ?: 0));
        $chatCredit = max(0, (int) ($pendingSubscription->chat_limit ?: $transaction->plan?->chat_limit ?: 0));
        $durationDays = max(0, (int) ($transaction->plan?->duration_days ?? 30));
        $activeSubscription = AiGatewaySubscription::query()
            ->where('ai_gateway_client_id', $pendingSubscription->ai_gateway_client_id)
            ->where('external_user_id', $pendingSubscription->external_user_id)
            ->where('ai_gateway_plan_id', $pendingSubscription->ai_gateway_plan_id)
            ->where('status', 'active')
            ->notExpired()
            ->latest()
            ->first();

        if ($activeSubscription) {
            $currentLimit = (int) ($activeSubscription->token_limit ?: $activeSubscription->plan?->token_limit ?: 0);
            $updates = [
                'token_limit' => $currentLimit + $tokenCredit,
                'ends_at' => $durationDays === 0 || $activeSubscription->ends_at === null
                    ? null
                    : $activeSubscription->ends_at->copy()->addDays($durationDays),
            ];
            if ($chatCredit > 0) {
                $currentChatLimit = (int) ($activeSubscription->chat_limit ?: $activeSubscription->plan?->chat_limit ?: 0);
                $updates['chat_limit'] = $currentChatLimit + $chatCredit;
            }
            $activeSubscription->update($updates);
            $pendingSubscription->update(['status' => 'merged']);
        } else {
            $pendingSubscription->update([
                'status' => 'active',
                'token_limit' => $tokenCredit,
                'chat_limit' => $chatCredit,
                'starts_at' => now(),
                'ends_at' => $durationDays > 0 ? now()->addDays($durationDays) : null,
            ]);
        }

        $details = is_array($transaction->details) ? $transaction->details : [];
        $details['confirmation_source'] = $confirmationSource;
        $details['confirmed_at'] = now()->toDateTimeString();
        $transaction->update(['status' => 'paid', 'paid_at' => now(), 'details' => $details]);
    }
}
