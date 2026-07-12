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
        $trial = null;
        $pendingPayment = null;
        $gatewayError = null;

        try {
            $plans = $this->gatewayRequest('get', 'plans')->json() ?? [];
            $status = $this->gatewayRequest('get', 'subscription', [
                'external_user_id' => (string) $user->getAuthIdentifier(),
            ])->json();
            $subscription = data_get($status, 'subscription');
            $trial = data_get($status, 'trial');
            $pendingPayment = data_get($status, 'pending_payment');
        } catch (\Throwable) {
            $gatewayError = 'Informasi paket AI sementara tidak dapat dimuat. Silakan coba lagi.';
        }

        $usageLogs = AiDiscussionUsageLog::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('user.pages.ai-gateway.index', compact('plans', 'subscription', 'trial', 'pendingPayment', 'usageLogs', 'gatewayError'));
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate(['plan_id' => ['required', 'integer']]);
        $user = $request->user();

        try {
            $response = $this->gatewayRequest('post', 'checkout', [
                'plan_id' => $data['plan_id'],
                'external_user_id' => (string) $user->getAuthIdentifier(),
                'customer_name' => (string) $user->name,
                'customer_email' => (string) $user->email,
                'success_redirect_url' => route('user.ai-gateway.index', ['payment' => 'success']),
                'failure_redirect_url' => route('user.ai-gateway.index', ['payment' => 'failed']),
            ]);
            $response->throw();
            $invoiceUrl = (string) $response->json('invoice_url');

            if ($invoiceUrl === '') {
                return back()->with('error', 'Invoice gateway tidak tersedia.');
            }

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
}
