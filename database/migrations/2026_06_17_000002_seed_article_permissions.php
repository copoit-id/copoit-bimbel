<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $feature = 'artikel';
        $label = 'Artikel';
        $actions = array_keys(config('permissions.actions', []));

        foreach ($actions as $action) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $feature . '.' . $action],
                [
                    'name' => $label . ' - ' . Str::headline($action),
                    'feature' => $feature,
                    'action' => $action,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $permissionIds = DB::table('permissions')
            ->where('feature', $feature)
            ->pluck('id');

        $roleIds = DB::table('roles')
            ->whereIn('slug', ['admin', 'super_admin'])
            ->pluck('id');

        $rows = [];
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                $rows[] = [
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ];
            }
        }

        if (!empty($rows)) {
            DB::table('permission_role')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->where('feature', 'artikel')
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};
