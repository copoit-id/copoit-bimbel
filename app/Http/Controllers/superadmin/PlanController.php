<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\PlanModuleService;
use App\Services\PlanQuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function __construct(
        private PlanModuleService $planModules
    ) {}

    public function index(): View
    {
        $plans = Plan::orderBy('price')->orderBy('name')->get();

        return view('super-admin.plans.index', [
            'plans' => $plans,
            'modulePresets' => $this->planModules->presets(),
        ]);
    }

    public function create(): View
    {
        return view('super-admin.plans.create', $this->moduleFormData());
    }

    public function store(Request $request): RedirectResponse
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
            'plan_features' => 'nullable|array',
            'plan_features.affiliate_enabled' => 'boolean',
            ...$this->moduleValidationRules(),
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
        $validated['is_active'] = true;
        $validated['features_json'] = [
            'plan_features' => $this->planFeaturesFromRequest($request),
            'module_access' => $this->moduleAccessFromRequest($request),
            'proctoring_defaults' => $this->proctoringDefaultsFromRequest($request),
        ];
        unset($validated['plan_features']);
        unset($validated['module_preset']);
        unset($validated['module_features']);
        unset($validated['proctoring_defaults']);

        DB::transaction(function () use ($validated): void {
            // Plan writes are rare. Locking the small catalog keeps slug and
            // default-plan changes deterministic under concurrent requests.
            Plan::query()->orderBy('id')->lockForUpdate()->get(['id']);
            $validated['slug'] = $this->uniqueSlug($validated['name']);

            if ($validated['is_default']) {
                Plan::query()->where('is_default', true)->update(['is_default' => false]);
            }

            Plan::create($validated);
        });

        return redirect()->route('super-admin.plans.index')
            ->with('success', 'Plan berhasil dibuat.');
    }

    public function update(Request $request, Plan $plan): RedirectResponse
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
            'plan_features' => 'nullable|array',
            'plan_features.affiliate_enabled' => 'boolean',
            ...$this->moduleValidationRules(),
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
        $featureUpdates = [
            'plan_features' => $this->planFeaturesFromRequest($request),
            'module_access' => $this->moduleAccessFromRequest($request),
            'proctoring_defaults' => $this->proctoringDefaultsFromRequest($request),
        ];
        unset($validated['plan_features']);
        unset($validated['module_preset']);
        unset($validated['module_features']);
        unset($validated['proctoring_defaults']);

        DB::transaction(function () use ($plan, $validated, $featureUpdates): void {
            Plan::query()->orderBy('id')->lockForUpdate()->get(['id']);
            $lockedPlan = Plan::query()->findOrFail($plan->getKey());

            if ($validated['is_default']) {
                Plan::query()
                    ->where('is_default', true)
                    ->whereKeyNot($lockedPlan->getKey())
                    ->update(['is_default' => false]);
            }

            $validated['features_json'] = array_merge(
                $lockedPlan->features_json ?? [],
                $featureUpdates
            );
            $lockedPlan->update($validated);
        });

        return redirect()->route('super-admin.plans.index')
            ->with('success', 'Plan berhasil diperbarui.');
    }

    public function edit(Plan $plan): View
    {
        return view('super-admin.plans.edit', [
            'plan' => $plan,
            ...$this->moduleFormData($plan),
        ]);
    }

    public function destroy(Plan $plan): RedirectResponse
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

    private function planFeaturesFromRequest(Request $request): array
    {
        return [
            'affiliate_enabled' => $request->boolean(
                'plan_features.affiliate_enabled',
                PlanQuotaService::DEFAULT_PLAN_FEATURES['affiliate_enabled']
            ),
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name, '_');
        $slug = $baseSlug;
        $suffix = 1;

        while (Plan::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'_'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function moduleValidationRules(): array
    {
        $rules = [
            'module_preset' => ['required', 'string', Rule::in(array_keys($this->planModules->presets()))],
            'module_features' => ['required', 'array'],
        ];

        foreach ($this->planModules->featureKeys() as $feature) {
            $rules['module_features.'.$feature] = ['required', 'boolean'];
        }

        return $rules;
    }

    private function moduleAccessFromRequest(Request $request): array
    {
        $preset = (string) $request->input('module_preset', 'full');
        $features = $preset === 'custom'
            ? collect($this->planModules->featureKeys())
                ->mapWithKeys(fn (string $feature): array => [
                    $feature => $request->boolean('module_features.'.$feature),
                ])
                ->all()
            : $this->planModules->presetAccess($preset);

        return [
            'preset' => $preset,
            'features' => $features,
        ];
    }

    private function moduleFormData(?Plan $plan = null): array
    {
        $savedFeatures = $this->planModules->accessForPlan($plan);

        return [
            'moduleGroups' => $this->planModules->groups(),
            'modulePresets' => $this->planModules->presets(),
            'moduleFeatureLabels' => $this->planModules->featureLabels(),
            'selectedModulePreset' => old('module_preset', $this->planModules->presetForPlan($plan)),
            'selectedModuleFeatures' => old('module_features', $savedFeatures),
            'modulePresetFeatures' => collect(array_keys($this->planModules->presets()))
                ->mapWithKeys(fn (string $preset): array => [
                    $preset => $this->planModules->presetAccess($preset),
                ])
                ->all(),
        ];
    }
}
