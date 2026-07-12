<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\AiGatewayPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiGatewayPlanController extends Controller
{
    public function index()
    {
        return view('super-admin.ai-gateway-plans.index', [
            'plans' => AiGatewayPlan::query()->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(5));
        $data['is_active'] = $request->boolean('is_active');
        AiGatewayPlan::create($data);

        return back()->with('success', 'Paket AI berhasil dibuat.');
    }

    public function update(Request $request, AiGatewayPlan $aiGatewayPlan)
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');
        $aiGatewayPlan->update($data);

        return back()->with('success', 'Paket AI berhasil diperbarui.');
    }

    public function destroy(AiGatewayPlan $aiGatewayPlan)
    {
        $aiGatewayPlan->delete();

        return back()->with('success', 'Paket AI berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0'],
            'token_limit' => ['required', 'integer', 'min:1'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);
    }
}
