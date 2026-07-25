<?php

namespace App\Services;

use App\Models\AiGatewayClient;
use App\Models\AiGatewayPlan;
use App\Models\AiGatewaySubscription;
use App\Models\AiGatewayUsageLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminQuestionGeneratorQuotaService
{
    public function summary(User $user): ?array
    {
        $subscription = $this->subscriptionFor($user);

        if (! $subscription) {
            return null;
        }

        $tokenLimit = (int) ($subscription->token_limit ?: $subscription->plan?->token_limit ?: 0);

        return [
            'plan_name' => $subscription->plan?->name,
            'token_limit' => $tokenLimit,
            'tokens_used' => (int) $subscription->tokens_used,
            'remaining_tokens' => max(0, $tokenLimit - (int) $subscription->tokens_used),
            'ends_at' => $subscription->ends_at,
        ];
    }

    public function ensureAvailable(User $user): void
    {
        if (! $this->subscriptionFor($user)) {
            throw new RuntimeException('Kuota AI Generator Soal belum tersedia. Beli paket atau minta Super Admin menambahkan kuota terlebih dahulu.');
        }
    }

    /** @param array{input:int,output:int,total:int} $usage */
    public function consume(User $user, array $usage, string $provider, string $model): array
    {
        return DB::transaction(function () use ($user, $usage, $provider, $model): array {
            $client = $this->client();
            if (! $client) {
                throw new RuntimeException('Gateway AI Generator Soal belum dikonfigurasi.');
            }

            $subscription = AiGatewaySubscription::query()
                ->with('plan')
                ->where('ai_gateway_client_id', $client->id)
                ->where('external_user_id', (string) $user->getAuthIdentifier())
                ->where('scope', AiGatewayPlan::SCOPE_ADMIN_QUESTION_GENERATOR)
                ->where('status', 'active')
                ->notExpired()
                ->whereHas('transactions', fn ($query) => $query->where('status', 'paid'))
                ->orderBy('ends_at')
                ->lockForUpdate()
                ->get()
                ->first(fn (AiGatewaySubscription $item) => $item->hasRemainingQuota());

            if (! $subscription) {
                throw new RuntimeException('Kuota AI Generator Soal sudah habis. Silakan beli paket baru.');
            }

            $tokenLimit = (int) ($subscription->token_limit ?: $subscription->plan?->token_limit ?: 0);
            $remaining = max(0, $tokenLimit - (int) $subscription->tokens_used);
            $total = max(1, (int) ($usage['total'] ?? 0));
            $charged = min($total, $remaining);

            $subscription->increment('tokens_used', $charged);
            $subscription->refresh();

            AiGatewayUsageLog::query()->create([
                'ai_gateway_client_id' => $client->id,
                'external_user_id' => (string) $user->getAuthIdentifier(),
                'external_user_name' => $user->name,
                'external_user_email' => $user->email,
                'origin_base_url' => rtrim((string) config('app.url'), '/'),
                'feature' => 'admin_question_generator',
                'provider' => $provider,
                'model' => $model,
                'input_tokens' => min(max(0, (int) ($usage['input'] ?? 0)), $charged),
                'output_tokens' => max(0, $charged - min(max(0, (int) ($usage['input'] ?? 0)), $charged)),
                'total_tokens' => $charged,
                'response_time_ms' => null,
            ]);

            return [
                'plan_name' => $subscription->plan?->name,
                'token_limit' => $tokenLimit,
                'tokens_used' => (int) $subscription->tokens_used,
                'remaining_tokens' => max(0, $tokenLimit - (int) $subscription->tokens_used),
                'charged_tokens' => $charged,
            ];
        });
    }

    private function subscriptionFor(User $user): ?AiGatewaySubscription
    {
        $client = $this->client();
        if (! $client) {
            return null;
        }

        return AiGatewaySubscription::query()
            ->with('plan')
            ->where('ai_gateway_client_id', $client->id)
            ->where('external_user_id', (string) $user->getAuthIdentifier())
            ->where('scope', AiGatewayPlan::SCOPE_ADMIN_QUESTION_GENERATOR)
            ->where('status', 'active')
            ->notExpired()
            ->whereHas('transactions', fn ($query) => $query->where('status', 'paid'))
            ->orderBy('ends_at')
            ->get()
            ->first(fn (AiGatewaySubscription $item) => $item->hasRemainingQuota());
    }

    private function client(): ?AiGatewayClient
    {
        $key = trim((string) config('services.ai_gateway.key'));

        if ($key === '') {
            return null;
        }

        return AiGatewayClient::query()
            ->where('api_key_hash', hash('sha256', $key))
            ->where('is_active', true)
            ->first();
    }
}
