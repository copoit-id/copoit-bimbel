<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\ClientPlanSubscription;
use App\Models\Plan;
use App\Services\PlanQuotaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PlanManagementController extends Controller
{
    /**
     * Show current plan and usage for this project
     */
    public function index()
    {
        $currentSubscription = PlanQuotaService::getCurrentSubscription();
        $usageStats = PlanQuotaService::getUsageStats();
        $plans = Plan::active()->orderBy('price')->get();

        return view('super-admin.plan-management.index', compact(
            'currentSubscription',
            'usageStats',
            'plans'
        ));
    }

    /**
     * Show form to change/upgrade plan
     */
    public function changeForm()
    {
        $currentSubscription = PlanQuotaService::getCurrentSubscription();
        $plans = Plan::active()->orderBy('price')->get();

        return view('super-admin.plan-management.change', compact(
            'currentSubscription',
            'plans'
        ));
    }

    /**
     * Assign/change plan for this project
     */
    public function assign(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'status' => 'required|in:active,trial,suspended',
            'starts_at' => 'required|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'notes' => 'nullable|string',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        PlanQuotaService::assignPlan($plan->id, [
            'status' => $validated['status'],
            'starts_at' => Carbon::parse($validated['starts_at']),
            'expires_at' => $validated['expires_at'] ? Carbon::parse($validated['expires_at']) : null,
            'notes' => $validated['notes'],
        ]);

        return redirect()->route('super-admin.plan-management.index')
            ->with('success', "Plan berhasil diubah ke {$plan->name}.");
    }

    /**
     * Update current subscription
     */
    public function updateSubscription(Request $request, ClientPlanSubscription $subscription)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,trial,expired,suspended',
            'starts_at' => 'required|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'notes' => 'nullable|string',
        ]);

        $subscription->update([
            'status' => $validated['status'],
            'starts_at' => Carbon::parse($validated['starts_at']),
            'expires_at' => $validated['expires_at'] ? Carbon::parse($validated['expires_at']) : null,
            'notes' => $validated['notes'],
        ]);

        return redirect()->route('super-admin.plan-management.index')
            ->with('success', 'Subscription berhasil diperbarui.');
    }

    /**
     * Reset essay AI counter
     */
    public function resetEssayCounter()
    {
        $subscription = PlanQuotaService::getCurrentSubscription();

        if ($subscription) {
            $subscription->resetEssayAICounter();
            return redirect()->back()->with('success', 'Counter Essay AI berhasil direset.');
        }

        return redirect()->back()->with('error', 'Tidak ada subscription aktif.');
    }
}
