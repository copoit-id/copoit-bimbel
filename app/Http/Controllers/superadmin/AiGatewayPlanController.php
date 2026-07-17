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
    public function index(): View
    {
        return view('super-admin.ai-gateway-plans.index', [
            'plans' => AiGatewayPlan::query()
                ->withCount(['subscriptions', 'transactions'])
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(5));
        $data['is_active'] = $request->boolean('is_active');
        AiGatewayPlan::create($data);

        return back()->with('success', 'Paket AI berhasil dibuat.');
    }

    public function update(Request $request, AiGatewayPlan $aiGatewayPlan): RedirectResponse
    {
        $data = $this->validated($request);
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
            'warning',
            'Paket pernah digunakan sehingga riwayatnya tidak dapat dihapus. Paket telah dinonaktifkan dan tidak lagi tersedia untuk pembelian baru.'
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0'],
            'token_limit' => ['required', 'integer', 'min:1'],
            'chat_limit' => ['required', 'integer', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:0', 'max:3650'],
        ]);
    }
}
