<?php

namespace App\Services;

use App\Models\ClientPlanSubscription;
use App\Models\Plan;
use Illuminate\Support\Str;

class PlanModuleService
{
    private bool $resolvedCurrentAccess = false;

    /**
     * @var array<string, bool>
     */
    private array $currentAccess = [];

    /**
     * @return array<string, array{label: string, description: string, features: array<int, string>}>
     */
    public function groups(): array
    {
        return (array) config('modules.groups', []);
    }

    /**
     * @return array<string, array{label: string, description: string, groups: array<int, string>, features?: array<int, string>}>
     */
    public function presets(): array
    {
        return (array) config('modules.presets', []);
    }

    /**
     * @return array<int, string>
     */
    public function featureKeys(): array
    {
        return collect($this->groups())
            ->flatMap(fn (array $group): array => (array) ($group['features'] ?? []))
            ->filter(fn (mixed $feature): bool => is_string($feature) && $feature !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function featureLabels(): array
    {
        $permissionFeatures = (array) config('permissions.features', []);
        $customLabels = (array) config('modules.labels', []);

        return collect($this->featureKeys())
            ->mapWithKeys(fn (string $feature): array => [
                $feature => (string) (
                    $customLabels[$feature]
                    ?? data_get($permissionFeatures, $feature.'.label')
                    ?? Str::headline($feature)
                ),
            ])
            ->all();
    }

    /**
     * @return array<string, bool>
     */
    public function fullAccess(): array
    {
        return array_fill_keys($this->featureKeys(), true);
    }

    /**
     * @return array<string, bool>
     */
    public function presetAccess(string $preset): array
    {
        $access = array_fill_keys($this->featureKeys(), false);
        $presetConfig = $this->presets()[$preset] ?? $this->presets()['full'] ?? ['groups' => []];

        foreach ((array) ($presetConfig['groups'] ?? []) as $groupKey) {
            foreach ((array) data_get($this->groups(), $groupKey.'.features', []) as $feature) {
                if (array_key_exists($feature, $access)) {
                    $access[$feature] = true;
                }
            }
        }

        foreach ((array) ($presetConfig['features'] ?? []) as $feature) {
            if (is_string($feature) && array_key_exists($feature, $access)) {
                $access[$feature] = true;
            }
        }

        return $access;
    }

    /**
     * Missing module_access intentionally resolves to Full Fitur for backward
     * compatibility with plans created before module grouping existed.
     *
     * @return array<string, bool>
     */
    public function accessForPlan(?Plan $plan): array
    {
        $fullAccess = $this->fullAccess();
        $moduleAccess = data_get($plan?->features_json, 'module_access');

        if (! is_array($moduleAccess) || ! is_array($moduleAccess['features'] ?? null)) {
            return $fullAccess;
        }

        $savedFeatures = $moduleAccess['features'];
        $presetDefaults = $this->presetAccess((string) ($moduleAccess['preset'] ?? 'custom'));

        return collect($fullAccess)
            ->mapWithKeys(fn (bool $default, string $feature): array => [
                $feature => array_key_exists($feature, $savedFeatures)
                    ? filter_var($savedFeatures[$feature], FILTER_VALIDATE_BOOL)
                    // Attendance used to be included in the Class module. Preserve
                    // that behavior for saved custom plans until it is configured.
                    : (in_array($feature, ['attendance', 'study_group'], true) && array_key_exists('class', $savedFeatures)
                        ? filter_var($savedFeatures['class'], FILTER_VALIDATE_BOOL)
                        : ($presetDefaults[$feature] ?? false)),
            ])
            ->all();
    }

    public function presetForPlan(?Plan $plan): string
    {
        $preset = (string) data_get($plan?->features_json, 'module_access.preset', 'full');

        return array_key_exists($preset, $this->presets()) ? $preset : 'full';
    }

    public function allows(string $feature): bool
    {
        if (! $this->resolvedCurrentAccess) {
            $this->currentAccess = $this->resolveCurrentAccess();
            $this->resolvedCurrentAccess = true;
        }

        // Features not registered in the module catalog remain available.
        return $this->currentAccess[$feature] ?? true;
    }

    public function featureForRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        $permissionFeatures = (array) config('permissions.features', []);
        $additionalRoutes = (array) config('modules.routes', []);
        $bestMatch = null;
        $bestLength = -1;

        foreach ($this->featureKeys() as $feature) {
            $prefixes = array_unique(array_merge(
                (array) data_get($permissionFeatures, $feature.'.routes', []),
                (array) ($additionalRoutes[$feature] ?? [])
            ));

            foreach ($prefixes as $prefix) {
                if (is_string($prefix) && Str::startsWith($routeName, $prefix) && strlen($prefix) > $bestLength) {
                    $bestMatch = $feature;
                    $bestLength = strlen($prefix);
                }
            }
        }

        return $bestMatch;
    }

    /**
     * @return array<string, bool>
     */
    private function resolveCurrentAccess(): array
    {
        $subscription = ClientPlanSubscription::query()
            ->join('plans', 'plans.id', '=', 'client_plan_subscriptions.plan_id')
            ->whereIn('client_plan_subscriptions.status', ['active', 'trial'])
            ->where(function ($query): void {
                $query->whereNull('client_plan_subscriptions.expires_at')
                    ->orWhere('client_plan_subscriptions.expires_at', '>', now());
            })
            ->latest('client_plan_subscriptions.created_at')
            ->first(['plans.features_json as plan_features_json']);

        if (! $subscription) {
            return $this->fullAccess();
        }

        $features = $subscription->getAttribute('plan_features_json');
        $plan = new Plan;
        $plan->features_json = is_string($features)
            ? json_decode($features, true)
            : $features;

        return $this->accessForPlan($plan);
    }
}
