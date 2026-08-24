<?php

namespace App\Services;

use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayTokenAdjustment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AiGatewayTokenService
{
    /**
     * @return array{subscription_id: int, previous_limit: int, new_limit: int, added_tokens: int}
     */
    public function addTokens(
        AiGatewaySubscription $subscription,
        int $tokens,
        array $audit = []
    ): array {
        if ($tokens < 1) {
            throw new RuntimeException('Jumlah token harus lebih dari 0.');
        }

        return DB::transaction(function () use ($subscription, $tokens, $audit): array {
            $subscription = AiGatewaySubscription::query()
                ->with(['plan', 'client'])
                ->whereKey($subscription->id)
                ->where('status', 'active')
                ->notExpired()
                ->whereHas('transactions', fn ($query) => $query->where('status', 'paid'))
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                throw new RuntimeException('User belum memiliki paket AI aktif. Minta user membeli atau klaim paket terlebih dahulu.');
            }

            $previousLimit = (int) ($subscription->token_limit ?: $subscription->plan?->token_limit ?: 0);
            $newLimit = $previousLimit + $tokens;

            $subscription->update(['token_limit' => $newLimit]);
            AiGatewayTokenAdjustment::query()->create([
                'ai_gateway_client_id' => $subscription->ai_gateway_client_id,
                'ai_gateway_subscription_id' => $subscription->id,
                'external_user_id' => $subscription->external_user_id,
                'tokens_added' => $tokens,
                'previous_token_limit' => $previousLimit,
                'new_token_limit' => $newLimit,
                'reason' => trim((string) ($audit['reason'] ?? 'Penambahan token oleh super admin')),
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
