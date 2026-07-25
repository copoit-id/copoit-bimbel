<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\AiGatewayPlan;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AiQuestionGeneratorBillingController extends Controller
{
    public function index(Request $request): View
    {
        $plans = [];
        $status = [];
        $error = null;

        try {
            $plans = $this->gatewayRequest('get', 'plans', ['scope' => AiGatewayPlan::SCOPE_ADMIN_QUESTION_GENERATOR])->json() ?? [];
            $status = $this->gatewayRequest('get', 'subscription', [
                'scope' => AiGatewayPlan::SCOPE_ADMIN_QUESTION_GENERATOR,
                'external_user_id' => (string) $request->user()->getAuthIdentifier(),
            ])->json() ?? [];
        } catch (\Throwable) {
            $error = 'Informasi paket AI Generator Soal belum dapat dimuat. Pastikan gateway AI sudah dikonfigurasi.';
        }

        return view('admin.pages.question-bank.ai-generator-quota', compact('plans', 'status', 'error'));
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate(['plan_id' => ['required', 'integer']]);
        $user = $request->user();
        $returnUrl = route('admin.question-generator.quota.index');

        try {
            $response = $this->gatewayRequest('post', 'checkout', [
                'plan_id' => $data['plan_id'],
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
}
