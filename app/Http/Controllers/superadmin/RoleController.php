<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\PlanModuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    private array $protectedRoles = ['super_admin'];

    public function __construct(
        private PlanModuleService $planModules
    ) {}

    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        $features = config('permissions.features', []);
        $actions = config('permissions.actions', []);
        $availableFeatures = collect($features)
            ->mapWithKeys(fn (array $feature, string $featureKey): array => [
                $featureKey => $this->planModules->allows($featureKey),
            ])
            ->all();

        return view('super-admin.roles.index', compact('roles', 'features', 'actions', 'availableFeatures'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $slug = Str::slug($request->name, '_');
        $baseSlug = $slug;
        $suffix = 1;
        while (Role::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'_'.$suffix;
            $suffix++;
        }

        Role::create([
            'name' => $request->name,
            'slug' => $slug,
        ]);

        return redirect()->route('super-admin.roles.index')
            ->with('success', 'Role berhasil ditambahkan.');
    }

    public function update(Request $request, Role $role)
    {
        if (in_array($role->slug, $this->protectedRoles, true)) {
            return redirect()->route('super-admin.roles.index')
                ->with('error', 'Role ini tidak dapat diubah.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $role->name = $request->name;
        $role->save();

        return redirect()->route('super-admin.roles.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        if (in_array($role->slug, $this->protectedRoles, true)) {
            return redirect()->route('super-admin.roles.index')
                ->with('error', 'Role ini tidak dapat dihapus.');
        }

        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        return redirect()->route('super-admin.roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }

    public function updatePermissions(Request $request, Role $role)
    {
        if (in_array($role->slug, $this->protectedRoles, true)) {
            return redirect()->route('super-admin.roles.index')
                ->with('error', 'Role ini tidak dapat diubah.');
        }

        $availableFeatureKeys = collect(array_keys(config('permissions.features', [])))
            ->filter(fn (string $feature): bool => $this->planModules->allows($feature));
        $managedPermissionSlugs = $availableFeatureKeys
            ->flatMap(fn (string $feature): array => collect(array_keys(config('permissions.actions', [])))
                ->map(fn (string $action): string => $feature.'.'.$action)
                ->all())
            ->all();
        $submittedPermissionSlugs = collect($request->input('permissions', []))
            ->filter(fn (mixed $slug): bool => is_string($slug) && in_array($slug, $managedPermissionSlugs, true))
            ->all();
        $preservedPermissionIds = $role->permissions()
            ->whereNotIn('permissions.slug', $managedPermissionSlugs)
            ->pluck('permissions.id')
            ->all();
        $permissionIds = Permission::query()
            ->whereIn('slug', $submittedPermissionSlugs)
            ->pluck('id')
            ->merge($preservedPermissionIds)
            ->unique()
            ->all();

        $role->permissions()->sync($permissionIds);

        return redirect()->route('super-admin.roles.index')
            ->with('success', 'Akses role berhasil diperbarui.');
    }
}
