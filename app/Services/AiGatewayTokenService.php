<?php

namespace App\Services;

use App\Models\AiGatewayClient;
use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayTokenAdjustment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AiGatewayTokenService
{
    /**
     * @param  array<int, int|string>  $userIds
     * @return Collection<string, array{token_limit: int, tokens_used: int, remaining_tokens: int}>
     */
    public function summaries(AiGatewayClient $client, array $userIds): Collection
    {
        $externalUserIds = collect($userIds)
            ->map(fn (int|string $userId): string => (string) $userId)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($externalUserIds === []) {
            return collect();
        }

        return AiGatewaySubscription::query()
            ->join('ai_gateway_plans', 'ai_gateway_plans.id', '=', 'ai_gateway_subscriptions.ai_gateway_plan_id')
            ->where('ai_gateway_subscriptions.ai_gateway_client_id', $client->id)
            ->whereIn('ai_gateway_subscriptions.external_user_id', $externalUserIds)
            ->where('ai_gateway_subscriptions.status', 'active')
            ->where(function ($query): void {
                $query->whereNull('ai_gateway_subscriptions.ends_at')
                    ->orWhere('ai_gateway_subscriptions.ends_at', '>', now());
            })
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('ai_gateway_transactions')
                    ->whereColumn(
                        'ai_gateway_transactions.ai_gateway_subscription_id',
                        'ai_gateway_subscriptions.id'
                    )
                    ->where('ai_gateway_transactions.status', 'paid');
            })
            ->groupBy('ai_gateway_subscriptions.external_user_id')
            ->selectRaw(
                'ai_gateway_subscriptions.external_user_id,
                SUM(CASE WHEN ai_gateway_subscriptions.token_limit > 0
                    THEN ai_gateway_subscriptions.token_limit
                    ELSE ai_gateway_plans.token_limit END) AS token_limit,
                SUM(ai_gateway_subscriptions.tokens_used) AS tokens_used'
            )
            ->get()
            ->mapWithKeys(function (AiGatewaySubscription $subscription): array {
                $tokenLimit = (int) $subscription->getAttribute('token_limit');
                $tokensUsed = (int) $subscription->getAttribute('tokens_used');

                return [(string) $subscription->external_user_id => [
                    'token_limit' => $tokenLimit,
                    'tokens_used' => $tokensUsed,
                    'remaining_tokens' => max(0, $tokenLimit - $tokensUsed),
                ]];
            });
    }

    /**
     * @return array{subscription_id: int, previous_limit: int, new_limit: int, added_tokens: int}
     */
    public function addTokens(
        AiGatewayClient $client,
        string $externalUserId,
        int $tokens,
        array $audit = []
    ): array {
        if ($tokens < 1) {
            throw new RuntimeException('Jumlah token harus lebih dari 0.');
        }

        $externalUserId = trim($externalUserId);
        if ($externalUserId === '') {
            throw new RuntimeException('User tujuan tidak valid.');
        }

        return DB::transaction(function () use ($client, $externalUserId, $tokens, $audit): array {
            $subscription = AiGatewaySubscription::query()
                ->with('plan')
                ->where('ai_gateway_client_id', $client->id)
                ->where('external_user_id', $externalUserId)
                ->where('status', 'active')
                ->notExpired()
                ->whereHas('transactions', fn ($query) => $query->where('status', 'paid'))
                ->orderByRaw('ends_at IS NULL DESC')
                ->orderByDesc('ends_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                throw new RuntimeException('User belum memiliki paket AI aktif. Minta user membeli atau klaim paket terlebih dahulu.');
            }

            $previousLimit = (int) ($subscription->token_limit ?: $subscription->plan?->token_limit ?: 0);
            $newLimit = $previousLimit + $tokens;

            $subscription->update(['token_limit' => $newLimit]);
            AiGatewayTokenAdjustment::query()->create([
                'ai_gateway_client_id' => $client->id,
                'ai_gateway_subscription_id' => $subscription->id,
                'external_user_id' => $externalUserId,
                'tokens_added' => $tokens,
                'previous_token_limit' => $previousLimit,
                'new_token_limit' => $newLimit,
                'reason' => trim((string) ($audit['reason'] ?? 'Penambahan token oleh admin')),
                'actor_user_id' => filled($audit['actor_user_id'] ?? null)
                    ? (string) $audit['actor_user_id']
                    : null,
                'actor_name' => $audit['actor_name'] ?? null,
                'actor_email' => $audit['actor_email'] ?? null,
                'origin_base_url' => $audit['origin_base_url'] ?? null,
            ]);

            return [
                'subscription_id' => (int) $subscription->id,
                'previous_limit' => $previousLimit,
                'new_limit' => $newLimit,
                'added_tokens' => $tokens,
            ];
        });
    }
}
