<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\AiDiscussionUsageLog;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiGatewaySubscriptionController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $parameters = ['tool' => 'quota'];

        if (in_array($request->query('payment'), ['success', 'failed'], true)) {
            $parameters['payment'] = $request->query('payment');
        }

        return redirect()->route('user.ai-learning.index', $parameters);
    }

    public function dashboardData(Request $request): array
    {
        $user = $request->user();
        $plans = [];
        $subscription = null;
        $subscriptions = [];
        $trial = null;
        $pendingPayment = null;
        $claimedFreePlanIds = [];
        $gatewayError = null;

        try {
            $plans = $this->gatewayRequest('get', 'plans')->json() ?? [];
            $status = $this->gatewayRequest('get', 'subscription', [
                'external_user_id' => (string) $user->getAuthIdentifier(),
            ])->json();
            $subscription = data_get($status, 'subscription');
            $subscriptions = data_get($status, 'subscriptions', $subscription ? [$subscription] : []);
            $trial = data_get($status, 'trial');
            $pendingPayment = data_get($status, 'pending_payment');
            $claimedFreePlanIds = array_map('intval', data_get($status, 'claimed_free_plan_ids', []));
            if ($subscription) {
                $request->session()->forget('ai_gateway_pending_payment');
            }
        } catch (\Throwable) {
            $gatewayError = 'Informasi paket AI sementara tidak dapat dimuat. Silakan coba lagi.';
        }
        $pendingPayment ??= $request->session()->get('ai_gateway_pending_payment');

        $usageLogsQuery = AiDiscussionUsageLog::query()
            ->where('user_id', $user->id);
        $usageLogs = $usageLogsQuery->latest()->paginate(20)->withQueryString();

        return compact('plans', 'subscription', 'subscriptions', 'trial', 'pendingPayment', 'claimedFreePlanIds', 'usageLogs', 'gatewayError');
    }

    public function checkout(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer'],
            'return_url' => ['nullable', 'string', 'max:2048'],
            'combined_checkout' => ['nullable', 'boolean'],
        ]);
        $user = $request->user();
        $returnUrl = $this->safeReturnUrl($request);

        try {
            $response = $this->gatewayRequest('post', 'checkout', [
                'plan_id' => $data['plan_id'],
                'external_user_id' => (string) $user->getAuthIdentifier(),
                'customer_name' => (string) $user->name,
                'customer_email' => (string) $user->email,
                'success_redirect_url' => $this->withPaymentStatus($returnUrl, 'success'),
                'failure_redirect_url' => $this->withPaymentStatus($returnUrl, 'failed'),
            ]);
            $response->throw();
            $activated = (bool) $response->json('activated');
            $alreadyClaimed = (bool) $response->json('already_claimed');
            $invoiceUrl = (string) $response->json('invoice_url');

            if ($activated || $alreadyClaimed) {
                $message = $alreadyClaimed
                    ? 'Paket gratis ini sudah pernah diklaim oleh akun Anda.'
                    : 'Paket Pembahasan AI gratis berhasil diklaim dan langsung aktif.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'activated' => $activated,
                        'already_claimed' => $alreadyClaimed,
                        'invoice_url' => null,
                        'message' => $message,
                    ]);
                }

                return redirect()->to($returnUrl)->with('success', $message);
            }

            if ($invoiceUrl === '') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invoice Pembahasan AI tidak tersedia. Silakan coba lagi.',
                    ], 422);
                }

                return back()->with('error', 'Invoice gateway tidak tersedia.');
            }

            $pendingPayment = [
                'plan_name' => 'AI',
                'invoice_url' => $invoiceUrl,
                'expires_at' => now()->addDay()->toIso8601String(),
            ];

            $request->session()->put('ai_gateway_pending_payment', $pendingPayment);

            if ($request->boolean('combined_checkout')) {
                $request->session()->put('ai_gateway_combined_checkout', [
                    ...$pendingPayment,
                    'return_url' => $returnUrl,
                    'product_transaction_id' => null,
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'invoice_url' => $invoiceUrl,
                ]);
            }

            return redirect()->away($invoiceUrl);
        } catch (RequestException $exception) {
            report($exception);
            $message = $this->gatewayErrorMessage($exception);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        } catch (\Throwable $exception) {
            report($exception);
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat pembayaran paket AI. Silakan coba lagi.',
                ], 422);
            }

            return back()->with('error', 'Gagal membuat pembayaran paket AI. Silakan coba lagi.');
        }
    }

    private function gatewayErrorMessage(RequestException $exception): string
    {
        $message = trim(strip_tags((string) $exception->response?->json('message')));
        $message = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $message) ?? '';

        return $message !== ''
            ? Str::limit($message, 180)
            : 'Gagal membuat pembayaran paket AI. Silakan coba lagi.';
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

    private function safeReturnUrl(Request $request): string
    {
        $returnUrl = trim((string) $request->input('return_url', ''));
        $appUrl = url('/');

        if ($returnUrl !== '' && $this->hasSameOrigin($returnUrl, $appUrl)) {
            return $returnUrl;
        }

        return route('user.ai-gateway.index');
    }

    private function withPaymentStatus(string $url, string $status): string
    {
        return $url.(str_contains($url, '?') ? '&' : '?').'payment='.$status;
    }

    private function hasSameOrigin(string $url, string $appUrl): bool
    {
        $candidate = parse_url($url);
        $application = parse_url($appUrl);

        return is_array($candidate)
            && is_array($application)
            && strtolower((string) ($candidate['scheme'] ?? '')) === strtolower((string) ($application['scheme'] ?? ''))
            && strtolower((string) ($candidate['host'] ?? '')) === strtolower((string) ($application['host'] ?? ''))
            && (int) ($candidate['port'] ?? 0) === (int) ($application['port'] ?? 0);
    }
}
