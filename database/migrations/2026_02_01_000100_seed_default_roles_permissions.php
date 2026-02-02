<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super_admin'],
            ['name' => 'Admin', 'slug' => 'admin'],
            ['name' => 'Admin Demo', 'slug' => 'admin_demo'],
            ['name' => 'User', 'slug' => 'user'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $role['slug']],
                ['name' => $role['name'], 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $features = config('permissions.features', []);
        $actions = array_keys(config('permissions.actions', []));

        foreach ($features as $featureKey => $feature) {
            $label = $feature['label'] ?? Str::headline($featureKey);
            foreach ($actions as $action) {
                $slug = $featureKey . '.' . $action;
                $name = $label . ' - ' . Str::headline($action);

                DB::table('permissions')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'feature' => $featureKey,
                        'action' => $action,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        $roleIds = DB::table('roles')->pluck('id', 'slug');
        $permissionIds = DB::table('permissions')->pluck('id', 'slug');

        $adminRoleId = $roleIds['admin'] ?? null;
        $adminDemoRoleId = $roleIds['admin_demo'] ?? null;
        $superAdminRoleId = $roleIds['super_admin'] ?? null;

        if ($adminRoleId) {
            $rows = $permissionIds->map(fn ($permissionId) => [
                'permission_id' => $permissionId,
                'role_id' => $adminRoleId,
            ])->values()->all();
            if (!empty($rows)) {
                DB::table('permission_role')->insertOrIgnore($rows);
            }
        }

        if ($superAdminRoleId) {
            $rows = $permissionIds->map(fn ($permissionId) => [
                'permission_id' => $permissionId,
                'role_id' => $superAdminRoleId,
            ])->values()->all();
            if (!empty($rows)) {
                DB::table('permission_role')->insertOrIgnore($rows);
            }
        }

        if ($adminDemoRoleId) {
            $viewPermissionIds = DB::table('permissions')
                ->where('action', 'view')
                ->pluck('id')
                ->map(fn ($permissionId) => [
                    'permission_id' => $permissionId,
                    'role_id' => $adminDemoRoleId,
                ])
                ->values()
                ->all();
            if (!empty($viewPermissionIds)) {
                DB::table('permission_role')->insertOrIgnore($viewPermissionIds);
            }
        }

        $users = DB::table('users')->select('id', 'role')->get();
        $roleUserRows = [];
        foreach ($users as $user) {
            $slug = $user->role ?? 'user';
            $roleId = $roleIds[$slug] ?? $roleIds['user'] ?? null;
            if (!$roleId) {
                continue;
            }
            $roleUserRows[] = [
                'role_id' => $roleId,
                'user_id' => $user->id,
            ];
        }

        if (!empty($roleUserRows)) {
            DB::table('role_user')->insertOrIgnore($roleUserRows);
        }
    }

    public function down(): void
    {
        DB::table('role_user')->truncate();
        DB::table('permission_role')->truncate();
        DB::table('permissions')->truncate();
        DB::table('roles')->truncate();
    }
};
