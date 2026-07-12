<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\AiDiscussionUsageLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AiGatewaySubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $plans = [];
        $subscription = null;
        $subscriptions = [];
        $trial = null;
        $pendingPayment = null;
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
            if ($subscription) {
                $request->session()->forget('ai_gateway_pending_payment');
            }
        } catch (\Throwable) {
            $gatewayError = 'Informasi paket AI sementara tidak dapat dimuat. Silakan coba lagi.';
        }
        $pendingPayment ??= $request->session()->get('ai_gateway_pending_payment');

        $usageLogsQuery = AiDiscussionUsageLog::query()
            ->where('user_id', $user->id);
        $usageTokenTotal = (int) (clone $usageLogsQuery)->sum('total_tokens');
        $usageLogs = $usageLogsQuery->latest()->paginate(20)->withQueryString();

        return view('user.pages.ai-gateway.index', compact('plans', 'subscription', 'subscriptions', 'trial', 'pendingPayment', 'usageLogs', 'usageTokenTotal', 'gatewayError'));
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer'],
            'return_url' => ['nullable', 'string', 'max:2048'],
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
            $invoiceUrl = (string) $response->json('invoice_url');

            if ($invoiceUrl === '') {
                return back()->with('error', 'Invoice gateway tidak tersedia.');
            }

            $request->session()->put('ai_gateway_pending_payment', [
                'plan_name' => 'AI',
                'invoice_url' => $invoiceUrl,
                'expires_at' => now()->addDay()->toIso8601String(),
            ]);

            return redirect()->away($invoiceUrl);
        } catch (\Throwable) {
            return back()->with('error', 'Gagal membuat pembayaran paket AI. Silakan coba lagi.');
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

    private function safeReturnUrl(Request $request): string
    {
        $returnUrl = (string) $request->input('return_url', '');

        if ($returnUrl !== '' && Str::startsWith($returnUrl, url('/'))) {
            return $returnUrl;
        }

        return route('user.ai-gateway.index');
    }

    private function withPaymentStatus(string $url, string $status): string
    {
        return $url.(str_contains($url, '?') ? '&' : '?').'payment='.$status;
    }
}
