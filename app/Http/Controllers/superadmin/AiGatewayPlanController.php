<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\AiGatewayPlan;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AiGatewayPlanController extends Controller
{
    public function index(Request $request): View
    {
        $scopes = AiGatewayPlan::scopes();
        $activeScope = $request->query('scope', AiGatewayPlan::SCOPE_LEARNING_TOOLS);
        abort_unless(array_key_exists($activeScope, $scopes), 404);

        return view('super-admin.ai-gateway-plans.index', [
            'plans' => AiGatewayPlan::query()
                ->where('scope', $activeScope)
                ->withCount(['subscriptions', 'transactions'])
                ->latest()
                ->get(),
            'scopes' => $scopes,
            'activeScope' => $activeScope,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['scope'] ??= AiGatewayPlan::SCOPE_LEARNING_TOOLS;
        $data = $this->normalizeScopeDefaults($data);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(5));
        $data['is_active'] = $request->boolean('is_active');
        AiGatewayPlan::create($data);

        return back()->with('success', 'Paket AI berhasil dibuat.');
    }

    public function update(Request $request, AiGatewayPlan $aiGatewayPlan): RedirectResponse
    {
        $data = $this->validated($request);
        $data['scope'] ??= $aiGatewayPlan->scope;
        $data = $this->normalizeScopeDefaults($data);
        $data['is_active'] = $request->boolean('is_active');
        $aiGatewayPlan->update($data);

        return back()->with('success', 'Paket AI berhasil diperbarui.');
    }

    public function destroy(AiGatewayPlan $aiGatewayPlan): RedirectResponse
    {
        if ($aiGatewayPlan->subscriptions()->exists() || $aiGatewayPlan->transactions()->exists()) {
            return $this->archive($aiGatewayPlan);
        }

        try {
            $aiGatewayPlan->delete();
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? null) !== '23000') {
                throw $exception;
            }

            return $this->archive($aiGatewayPlan);
        }

        return back()->with('success', 'Paket AI berhasil dihapus.');
    }

    private function archive(AiGatewayPlan $aiGatewayPlan): RedirectResponse
    {
        $aiGatewayPlan->update(['is_active' => false]);

        return back()->with(
            'success',
            'Paket berhasil dinonaktifkan. Riwayat klaim dan transaksi tetap disimpan agar akses peserta yang sudah aktif tidak berubah. Paket tidak lagi tersedia untuk pembelian atau klaim baru, dan dapat diaktifkan kembali kapan saja.'
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'scope' => ['nullable', 'in:'.implode(',', array_keys(AiGatewayPlan::scopes()))],
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0'],
            'token_limit' => ['required', 'integer', 'min:1'],
            'chat_limit' => ['required', 'integer', 'min:0'],
            'duration_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ], [
            'token_limit.min' => 'Limit token minimal 1 agar biaya paket tetap terkendali.',
        ]);
    }

    private function normalizeScopeDefaults(array $data): array
    {
        if (($data['scope'] ?? null) === AiGatewayPlan::SCOPE_ADMIN_QUESTION_GENERATOR) {
            $data['duration_days'] = 0;
        }

        return $data;
    }
}
