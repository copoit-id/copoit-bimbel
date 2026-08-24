@php
    $activeManagementTab = $activeManagementTab ?? 'user';
    $authUser = auth()->user();
    $isSuperAdmin = $authUser?->isSuperAdmin() ?? false;
    $canAccessAdminPanel = $authUser?->canAccessAdminPanel() ?? false;
    $permissionSlugs = $authUser?->getEffectivePermissionSlugs() ?? [];
    $canViewFeature = function (string $feature) use ($isSuperAdmin, $canAccessAdminPanel, $permissionSlugs): bool {
        if ($isSuperAdmin) {
            return true;
        }

        if ($feature === 'dashboard') {
            return $canAccessAdminPanel;
        }

        return in_array($feature . '.view', $permissionSlugs, true);
    };

    $tabs = [];

    if ($canViewFeature('user') && \Illuminate\Support\Facades\Route::has('admin.user.index')) {
        $tabs = collect($roleOptions ?? [])
            ->map(fn ($label, $slug) => [
                'label' => $label,
                'href' => route('admin.user.index', array_merge(request()->except(['page', 'role']), ['role' => $slug])),
                'active' => $activeManagementTab === $slug,
            ])
            ->values()
            ->all();
    }

@endphp

@if($tabs)
    <x-tab :tabs="$tabs" variant="underline" class="overflow-x-auto" />
@endif
