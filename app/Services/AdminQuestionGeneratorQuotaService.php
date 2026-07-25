<?php

namespace App\Services;

use App\Models\AiGatewayPlan;
use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AdminQuestionGeneratorQuotaService
{
    public function summary(User $user): ?array
    {
        $subscription = $this->subscriptionFor($user);

        return $subscription ? $this->summaryFromSubscription($subscription) : null;
    }

    public function ensureAvailable(User $user): void
    {
        if (! $this->summary($user)) {
            throw new RuntimeException('Kuota AI Generator Soal belum tersedia. Beli paket atau minta Super Admin menambahkan kuota terlebih dahulu.');
        }
    }

    /** @param array{input:int,output:int,total:int} $usage */
    public function consume(User $user, array $usage, string $provider, string $model): array
    {
        $payload = $this->gatewayRequest('post', 'question-generator/consume', [
            'external_user_id' => (string) $user->getAuthIdentifier(),
            'external_user_name' => (string) $user->name,
            'external_user_email' => (string) $user->email,
            'origin_base_url' => rtrim((string) config('app.url'), '/'),
            'provider' => $provider,
            'model' => $model,
            'usage' => [
                'input' => max(0, (int) ($usage['input'] ?? 0)),
                'output' => max(0, (int) ($usage['output'] ?? 0)),
                'total' => max(0, (int) ($usage['total'] ?? 0)),
            ],
        ]);

        $summary = $this->summaryFromSubscription((array) data_get($payload, 'subscription', []));

        return [
            ...$summary,
            'charged_tokens' => (int) data_get($payload, 'charged_tokens', 0),
        ];
    }

    /**
     * Convert the internal token quota to a conservative, customer-facing
     * estimate. A generated multiple-choice question uses roughly 1,000
     * combined prompt and output tokens; references and long explanations
     * make the lower end of the range more likely.
     *
     * @return array{min: int, max: int, label: string}
     */
    public function questionEstimate(int $tokens): array
    {
        $maximum = max(0, (int) floor($tokens / 1000));
        $minimum = $maximum === 0 ? 0 : max(1, (int) floor($maximum * 0.8));

        return [
            'min' => $minimum,
            'max' => $maximum,
            'label' => $minimum === $maximum
                ? number_format($maximum, 0, ',', '.')
                : number_format($minimum, 0, ',', '.').'–'.number_format($maximum, 0, ',', '.'),
        ];
    }

    /** @return array<string, mixed>|null */
    private function subscriptionFor(User $user): ?array
    {
        $payload = $this->gatewayRequest('get', 'subscription', [
            'scope' => AiGatewayPlan::SCOPE_ADMIN_QUESTION_GENERATOR,
            'external_user_id' => (string) $user->getAuthIdentifier(),
        ]);

        return collect(data_get($payload, 'subscriptions', []))
            ->filter(fn ($subscription): bool => is_array($subscription))
            ->first(function (array $subscription): bool {
                $tokenLimit = (int) data_get($subscription, 'token_limit', data_get($subscription, 'plan.token_limit', 0));

                return $tokenLimit > (int) data_get($subscription, 'tokens_used', 0);
            });
    }

    /** @param array<string, mixed> $subscription */
    private function summaryFromSubscription(array $subscription): array
    {
        $tokenLimit = (int) data_get($subscription, 'token_limit', data_get($subscription, 'plan.token_limit', 0));
        $tokensUsed = (int) data_get($subscription, 'tokens_used', 0);
        $remainingTokens = max(0, $tokenLimit - $tokensUsed);

        return [
            'plan_name' => data_get($subscription, 'plan.name'),
            'token_limit' => $tokenLimit,
            'tokens_used' => $tokensUsed,
            'remaining_tokens' => $remainingTokens,
            'remaining_question_estimate' => $this->questionEstimate($remainingTokens),
            'ends_at' => data_get($subscription, 'ends_at'),
        ];
    }

    /** @return array<string, mixed> */
    private function gatewayRequest(string $method, string $endpoint, array $data = []): array
    {
        $discussionUrl = rtrim((string) config('services.ai_gateway.url'), '/');
        $baseUrl = Str::beforeLast($discussionUrl, '/discussion');
        $key = trim((string) config('services.ai_gateway.key'));

        if ($baseUrl === '' || $key === '') {
            throw new RuntimeException('Gateway AI Generator Soal belum dikonfigurasi.');
        }

        try {
            return Http::acceptJson()
                ->timeout(15)
                ->withHeaders(['X-AI-Gateway-Key' => $key])
                ->{$method}("{$baseUrl}/{$endpoint}", $data)
                ->throw()
                ->json() ?? [];
        } catch (RequestException $exception) {
            $message = trim(strip_tags((string) $exception->response?->json('message')));

            throw new RuntimeException($message !== '' ? $message : 'Gateway AI Generator Soal tidak dapat dihubungi.');
        }
    }
}
