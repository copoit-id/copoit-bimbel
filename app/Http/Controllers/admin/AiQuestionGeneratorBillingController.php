<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\AiGatewayPlan;
use App\Services\AdminQuestionGeneratorQuotaService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AiQuestionGeneratorBillingController extends Controller
{
    public function __construct(
        private readonly AdminQuestionGeneratorQuotaService $quotaService
    ) {}

    public function index(Request $request): View
    {
        $plans = [];
        $status = [];
        $quotaOverview = $this->emptyQuotaOverview();
        $error = null;

        try {
            $plans = $this->gatewayRequest('get', 'plans', ['scope' => AiGatewayPlan::SCOPE_ADMIN_QUESTION_GENERATOR])->json() ?? [];
            $status = $this->gatewayRequest('get', 'subscription', [
                'scope' => AiGatewayPlan::SCOPE_ADMIN_QUESTION_GENERATOR,
                // Kredit AI dipakai bersama oleh AI Learning Tools dan
                // Generator Soal; scope hanya membedakan katalog paket.
                'include_all_scopes' => true,
                'external_user_id' => (string) $request->user()->getAuthIdentifier(),
            ])->json() ?? [];

            $plans = collect($plans)
                ->map(function (array $plan): array {
                    $plan['question_estimate'] = $this->quotaService->questionEstimate(
                        (int) ($plan['token_limit'] ?? 0)
                    );

                    return $plan;
                })
                ->values()
                ->all();

            $quotaOverview = $this->quotaOverview($status);
        } catch (\Throwable) {
            $error = 'Informasi paket AI Generator Soal belum dapat dimuat. Pastikan gateway AI sudah dikonfigurasi.';
        }

        return view('admin.pages.question-bank.ai-generator-quota', compact('plans', 'status', 'quotaOverview', 'error'));
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate(['plan_id' => ['required', 'integer']]);
        $user = $request->user();
        $returnUrl = route('admin.question-generator.quota.index');

        try {
            $response = $this->gatewayRequest('post', 'checkout', [
                'plan_id' => $data['plan_id'],
                'scope' => AiGatewayPlan::SCOPE_ADMIN_QUESTION_GENERATOR,
                'external_user_id' => (string) $user->getAuthIdentifier(),
                'customer_name' => (string) $user->name,
                'customer_email' => (string) $user->email,
                'success_redirect_url' => $returnUrl.'?payment=success',
                'failure_redirect_url' => $returnUrl.'?payment=failed',
            ]);
            $response->throw();

            if ($response->json('activated') || $response->json('already_claimed')) {
                return redirect()->route('admin.question-generator.quota.index')
                    ->with('success', $response->json('already_claimed') ? 'Paket gratis sudah pernah diklaim.' : 'Kuota AI Generator Soal berhasil aktif.');
            }

            $invoiceUrl = trim((string) $response->json('invoice_url'));
            if ($invoiceUrl !== '') {
                return redirect()->away($invoiceUrl);
            }

            return back()->with('error', 'Invoice AI Generator Soal tidak tersedia.');
        } catch (RequestException $exception) {
            report($exception);

            return back()->with('error', $this->errorMessage($exception));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Gagal membuat pembayaran AI Generator Soal.');
        }
    }

    private function gatewayRequest(string $method, string $endpoint, array $data = [])
    {
        $url = rtrim((string) config('services.ai_gateway.url'), '/');
        $baseUrl = Str::beforeLast($url, '/discussion');

        if ($baseUrl === '' || blank(config('services.ai_gateway.key'))) {
            throw new \RuntimeException('Gateway AI belum dikonfigurasi.');
        }

        return Http::acceptJson()
            ->timeout(15)
            ->withHeaders(['X-AI-Gateway-Key' => config('services.ai_gateway.key')])
            ->{$method}("{$baseUrl}/{$endpoint}", $data);
    }

    private function errorMessage(RequestException $exception): string
    {
        $message = trim(strip_tags((string) $exception->response?->json('message')));

        return $message !== ''
            ? Str::limit($message, 180)
            : 'Gagal membuat pembayaran AI Generator Soal.';
    }

    /** @param array<string, mixed> $status
     *  @return array{token_limit: int, tokens_used: int, remaining_tokens: int, usage_percentage: int, usage_percentage_label: string, progress_class: string, token_limit_label: string, tokens_used_label: string, remaining_tokens_label: string, active_package_count: int, question_estimate: array{min: int, max: int, label: string}}
     */
    private function quotaOverview(array $status): array
    {
        $subscriptions = collect(data_get($status, 'subscriptions', []))
            ->filter(fn (mixed $subscription): bool => is_array($subscription));
        $tokenLimit = (int) $subscriptions->sum(
            fn (array $subscription): int => max(0, (int) data_get($subscription, 'token_limit', data_get($subscription, 'plan.token_limit', 0)))
        );
        $tokensUsed = min($tokenLimit, max(0, (int) $subscriptions->sum(
            fn (array $subscription): int => max(0, (int) data_get($subscription, 'tokens_used', 0))
        )));
        $remainingTokens = max(0, $tokenLimit - $tokensUsed);
        $usagePercentage = $tokenLimit > 0
            ? min(100, (int) round(($tokensUsed / $tokenLimit) * 100))
            : 0;

        return [
            'token_limit' => $tokenLimit,
            'tokens_used' => $tokensUsed,
            'remaining_tokens' => $remainingTokens,
            'usage_percentage' => $usagePercentage,
            'usage_percentage_label' => $usagePercentage.'%',
            'progress_class' => $usagePercentage >= 90
                ? 'bg-red-500'
                : ($usagePercentage >= 70 ? 'bg-amber-500' : 'bg-primary'),
            'token_limit_label' => number_format($tokenLimit, 0, ',', '.'),
            'tokens_used_label' => number_format($tokensUsed, 0, ',', '.'),
            'remaining_tokens_label' => number_format($remainingTokens, 0, ',', '.'),
            'active_package_count' => $subscriptions->count(),
            'question_estimate' => $this->quotaService->questionEstimate($remainingTokens),
        ];
    }

    /** @return array<string, mixed> */
    private function emptyQuotaOverview(): array
    {
        return $this->quotaOverview([]);
    }
}
