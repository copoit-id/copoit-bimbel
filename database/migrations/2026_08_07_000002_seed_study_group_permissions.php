<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')
            || ! Schema::hasTable('permissions')
            || ! Schema::hasTable('permission_role')) {
            return;
        }

        $now = now();
        $label = (string) data_get(config('permissions.features', []), 'study_group.label', 'Rombel');

        foreach (array_keys(config('permissions.actions', [])) as $action) {
            $sourcePermissionId = DB::table('permissions')
                ->where('slug', 'class.'.$action)
                ->value('id');
            $roleIds = $sourcePermissionId
                ? DB::table('permission_role')
                    ->where('permission_id', $sourcePermissionId)
                    ->pluck('role_id')
                : collect();

            DB::table('permissions')->updateOrInsert(
                ['slug' => 'study_group.'.$action],
                [
                    'name' => $label.' - '.Str::headline($action),
                    'feature' => 'study_group',
                    'action' => $action,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $permissionId = DB::table('permissions')
                ->where('slug', 'study_group.'.$action)
                ->value('id');

            if (! $permissionId || $roleIds->isEmpty()) {
                continue;
            }

            DB::table('permission_role')->insertOrIgnore(
                $roleIds->map(fn ($roleId): array => [
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ])->all()
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('feature', 'study_group')
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
