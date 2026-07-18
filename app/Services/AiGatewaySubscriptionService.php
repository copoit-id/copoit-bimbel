<?php

namespace App\Services;

use App\Models\AiGatewayClient;
use App\Models\AiGatewayPlan;
use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AiGatewaySubscriptionService
{
    public function __construct(
        private readonly AiGatewayTelegramNotificationService $telegramNotificationService,
    ) {}

    public function activateTransaction(
        AiGatewayTransaction $transaction,
        string $confirmationSource = 'provider'
    ): ?AiGatewaySubscription {
        return DB::transaction(function () use ($transaction, $confirmationSource): ?AiGatewaySubscription {
            // Reload under a row lock so cancellation and a provider callback
            // cannot both settle the same invoice.
            $transaction = AiGatewayTransaction::query()
                ->lockForUpdate()
                ->find($transaction->id);

            if (! $transaction) {
                return null;
            }

            if ($transaction->status === 'paid') {
                $activatedSubscriptionId = data_get($transaction->details, 'activated_subscription_id');

                return $activatedSubscriptionId
                    ? AiGatewaySubscription::with('plan')->find($activatedSubscriptionId)
                    : AiGatewaySubscription::with('plan')->find($transaction->ai_gateway_subscription_id);
            }

            // A cancelled, expired, or failed invoice must remain terminal even if
            // the payment provider delivers a late callback.
            if ($transaction->status !== 'pending') {
                return null;
            }

            $pendingSubscription = AiGatewaySubscription::with('plan')->find($transaction->ai_gateway_subscription_id);
            if (! $pendingSubscription) {
                return null;
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
                $activeSubscription = $pendingSubscription;
            }

            $details = is_array($transaction->details) ? $transaction->details : [];
            $details['confirmation_source'] = $confirmationSource;
            $details['confirmed_at'] = now()->toDateTimeString();
            $details['activated_subscription_id'] = $activeSubscription->id;
            $transaction->update([
                'status' => 'paid',
                'paid_at' => now(),
                'details' => $details,
            ]);

            DB::afterCommit(fn () => $this->telegramNotificationService->notifyPurchase($transaction->id));

            return $activeSubscription->fresh('plan');
        });
    }

    public function claimFreePlan(AiGatewayClient $client, AiGatewayPlan $plan, array $customer): array
    {
        if (! $plan->isFree()) {
            throw new InvalidArgumentException('Paket ini bukan paket gratis.');
        }

        $externalUserId = trim((string) $customer['external_user_id']);
        $claimKey = hash('sha256', $client->id.'|'.$plan->id.'|'.$externalUserId);

        return DB::transaction(function () use ($client, $plan, $customer, $externalUserId, $claimKey) {
            $claimSubscription = AiGatewaySubscription::query()
                ->where('free_claim_key', $claimKey)
                ->first();

            if ($claimSubscription) {
                return $this->freeClaimResult($claimSubscription, true);
            }

            $claimSubscription = AiGatewaySubscription::query()->firstOrCreate(
                ['free_claim_key' => $claimKey],
                [
                    'ai_gateway_client_id' => $client->id,
                    'ai_gateway_plan_id' => $plan->id,
                    'token_limit' => $plan->token_limit,
                    'chat_limit' => $plan->chat_limit,
                    'external_user_id' => $externalUserId,
                    'external_user_name' => $customer['customer_name'] ?? null,
                    'external_user_email' => $customer['customer_email'] ?? null,
                    'status' => 'pending',
                ]
            );

            if (! $claimSubscription->wasRecentlyCreated) {
                return $this->freeClaimResult($claimSubscription, true);
            }

            $transaction = AiGatewayTransaction::query()->create([
                'ai_gateway_client_id' => $client->id,
                'ai_gateway_plan_id' => $plan->id,
                'ai_gateway_subscription_id' => $claimSubscription->id,
                'external_id' => $this->externalId($client, $claimSubscription),
                'provider' => 'free_claim',
                'amount' => 0,
                'status' => 'pending',
                'details' => [
                    'claimed_at' => now()->toISOString(),
                ],
            ]);
            $activeSubscription = $this->activateTransaction($transaction, 'free_claim');

            return [
                'subscription' => $activeSubscription,
                'transaction' => $transaction->fresh(),
                'already_claimed' => false,
            ];
        });
    }

    private function freeClaimResult(AiGatewaySubscription $claimSubscription, bool $alreadyClaimed): array
    {
        $transaction = $claimSubscription->transactions()->latest()->first();
        $activeSubscriptionId = data_get($transaction?->details, 'activated_subscription_id');
        $activeSubscription = $activeSubscriptionId
            ? AiGatewaySubscription::with('plan')->find($activeSubscriptionId)
            : null;

        if (! $activeSubscription && $claimSubscription->status === 'active') {
            $activeSubscription = $claimSubscription->loadMissing('plan');
        }

        return [
            'subscription' => $activeSubscription ?: $claimSubscription->loadMissing('plan'),
            'transaction' => $transaction,
            'already_claimed' => $alreadyClaimed,
        ];
    }

    private function externalId(AiGatewayClient $client, AiGatewaySubscription $subscription): string
    {
        return 'AIGW-FREE-'.$client->id.'-'.$subscription->id.'-'.Str::upper(Str::random(8));
    }
}
