<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AiGatewayTokenClientService
{
    /**
     * @param  array<int, int|string>  $userIds
     * @return Collection<string, array{token_limit: int, tokens_used: int, remaining_tokens: int}>
     */
    public function summaries(array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        try {
            $response = $this->gatewayRequest('token-summaries', [
                'external_user_ids' => array_map('strval', $userIds),
            ]);
            $response->throw();
        } catch (\Throwable $exception) {
            report($exception);

            return collect();
        }

        return collect($response->json('summaries', []))
            ->map(fn (array $summary): array => [
                'token_limit' => (int) ($summary['token_limit'] ?? 0),
                'tokens_used' => (int) ($summary['tokens_used'] ?? 0),
                'remaining_tokens' => (int) ($summary['remaining_tokens'] ?? 0),
            ]);
    }

    /**
     * @return array{subscription_id: int, previous_limit: int, new_limit: int, added_tokens: int}
     */
    public function addTokens(User $user, User $actor, int $tokens, string $reason): array
    {
        if ($user->role !== 'user') {
            throw new RuntimeException('Token AI hanya dapat ditambahkan ke akun peserta.');
        }

        try {
            $response = $this->gatewayRequest('tokens', [
                'external_user_id' => (string) $user->getKey(),
                'tokens' => $tokens,
                'reason' => $reason,
                'actor_user_id' => (string) $actor->getKey(),
                'actor_name' => (string) $actor->name,
                'actor_email' => (string) $actor->email,
                'origin_base_url' => url('/'),
            ]);
            $response->throw();
        } catch (\Throwable $exception) {
            report($exception);
            $message = isset($response) ? trim((string) $response->json('message')) : '';

            throw new RuntimeException(
                $message !== '' ? $message : 'Gagal menghubungi AI Gateway. Silakan coba lagi.',
                previous: $exception
            );
        }

        return [
            'subscription_id' => (int) $response->json('subscription_id'),
            'previous_limit' => (int) $response->json('previous_limit'),
            'new_limit' => (int) $response->json('new_limit'),
            'added_tokens' => (int) $response->json('added_tokens'),
        ];
    }

    private function gatewayRequest(string $endpoint, array $data): Response
    {
        $url = rtrim((string) config('services.ai_gateway.url'), '/');
        $baseUrl = Str::beforeLast($url, '/discussion');
        $apiKey = trim((string) config('services.ai_gateway.key'));

        if ($baseUrl === '' || $apiKey === '') {
            throw new RuntimeException('Gateway AI belum dikonfigurasi.');
        }

        return Http::acceptJson()
            ->timeout(15)
            ->withHeaders(['X-AI-Gateway-Key' => $apiKey])
            ->post("{$baseUrl}/subscription/{$endpoint}", $data);
    }
}
