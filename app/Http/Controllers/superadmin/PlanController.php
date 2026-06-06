<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\PlanQuotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::orderBy('price')->orderBy('name')->get();
        return view('super-admin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('super-admin.plans.create');
    }

    public function store(Request $request)
    {
        // Konversi checkbox dulu untuk validasi yang benar
        $isTrial = $request->boolean('is_trial');
        $isEssayAI = $request->boolean('essay_ai_enabled');
        
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:0',
            'max_packages' => 'required|integer|min:-1',
            'max_users' => 'required|integer|min:-1',
            'max_question_banks' => 'required|integer|min:-1',
            'essay_ai_enabled' => 'boolean',
            'essay_ai_monthly_limit' => 'nullable|integer|min:0',
            'is_trial' => 'boolean',
            'is_default' => 'boolean',
            'trial_duration_days' => 'nullable|integer|min:1',
            'proctoring_defaults' => 'nullable|array',
            'proctoring_defaults.enable_anti_copy' => 'boolean',
            'proctoring_defaults.enable_tab_switch_detection' => 'boolean',
            'proctoring_defaults.enable_webcam_check' => 'boolean',
            'proctoring_defaults.enable_screen_check' => 'boolean',
        ];
        
        // Validasi kondisional manual
        if ($isEssayAI) {
            $rules['essay_ai_monthly_limit'] = 'required|integer|min:0';
        }
        if ($isTrial) {
            $rules['trial_duration_days'] = 'required|integer|min:1';
        }
        
        $validated = $request->validate($rules);

        // Generate slug
        $slug = Str::slug($validated['name'], '_');
        $baseSlug = $slug;
        $suffix = 1;
        while (Plan::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '_' . $suffix;
            $suffix++;
        }

        // Handle checkboxes
        $validated['essay_ai_enabled'] = $isEssayAI;
        $validated['is_trial'] = $isTrial;
        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = true;
        $validated['slug'] = $slug;
        $validated['features_json'] = [
            'proctoring_defaults' => $this->proctoringDefaultsFromRequest($request),
        ];
        unset($validated['proctoring_defaults']);

        // If this is set as default, remove default from others
        if ($validated['is_default']) {
            Plan::where('is_default', true)->update(['is_default' => false]);
        }

        Plan::create($validated);

        return redirect()->route('super-admin.plans.index')
            ->with('success', 'Plan berhasil dibuat.');
    }

    public function update(Request $request, Plan $plan)
    {
        // Konversi checkbox dulu untuk validasi yang benar
        $isTrial = $request->boolean('is_trial');
        $isEssayAI = $request->boolean('essay_ai_enabled');
        
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:0',
            'max_packages' => 'required|integer|min:-1',
            'max_users' => 'required|integer|min:-1',
            'max_question_banks' => 'required|integer|min:-1',
            'essay_ai_enabled' => 'boolean',
            'essay_ai_monthly_limit' => 'nullable|integer|min:0',
            'is_trial' => 'boolean',
            'is_default' => 'boolean',
            'trial_duration_days' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'proctoring_defaults' => 'nullable|array',
            'proctoring_defaults.enable_anti_copy' => 'boolean',
            'proctoring_defaults.enable_tab_switch_detection' => 'boolean',
            'proctoring_defaults.enable_webcam_check' => 'boolean',
            'proctoring_defaults.enable_screen_check' => 'boolean',
        ];
        
        // Validasi kondisional manual
        if ($isEssayAI) {
            $rules['essay_ai_monthly_limit'] = 'required|integer|min:0';
        }
        if ($isTrial) {
            $rules['trial_duration_days'] = 'required|integer|min:1';
        }
        
        $validated = $request->validate($rules);

        // Handle checkboxes
        $validated['essay_ai_enabled'] = $isEssayAI;
        $validated['is_trial'] = $isTrial;
        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['features_json'] = array_merge($plan->features_json ?? [], [
            'proctoring_defaults' => $this->proctoringDefaultsFromRequest($request),
        ]);
        unset($validated['proctoring_defaults']);

        // If this is set as default, remove default from others
        if ($validated['is_default']) {
            Plan::where('is_default', true)->where('id', '!=', $plan->id)->update(['is_default' => false]);
        }

        $plan->update($validated);

        return redirect()->route('super-admin.plans.index')
            ->with('success', 'Plan berhasil diperbarui.');
    }

    public function edit(Plan $plan)
    {
        return view('super-admin.plans.edit', compact('plan'));
    }

    public function destroy(Plan $plan)
    {
        // Check if plan has subscriptions
        if ($plan->subscriptions()->exists()) {
            return redirect()->route('super-admin.plans.index')
                ->with('error', 'Plan tidak dapat dihapus karena sudah digunakan oleh client.');
        }

        $plan->delete();

        return redirect()->route('super-admin.plans.index')
            ->with('success', 'Plan berhasil dihapus.');
    }

    private function proctoringDefaultsFromRequest(Request $request): array
    {
        return [
            'enable_anti_copy' => $request->boolean(
                'proctoring_defaults.enable_anti_copy',
                PlanQuotaService::DEFAULT_PROCTORING_SETTINGS['enable_anti_copy']
            ),
            'enable_tab_switch_detection' => $request->boolean(
                'proctoring_defaults.enable_tab_switch_detection',
                PlanQuotaService::DEFAULT_PROCTORING_SETTINGS['enable_tab_switch_detection']
            ),
            'enable_webcam_check' => $request->boolean(
                'proctoring_defaults.enable_webcam_check',
                PlanQuotaService::DEFAULT_PROCTORING_SETTINGS['enable_webcam_check']
            ),
            'enable_screen_check' => $request->boolean(
                'proctoring_defaults.enable_screen_check',
                PlanQuotaService::DEFAULT_PROCTORING_SETTINGS['enable_screen_check']
            ),
        ];
    }
}
