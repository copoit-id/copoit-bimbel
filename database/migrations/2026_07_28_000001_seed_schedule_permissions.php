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
        $actions = array_keys(config('permissions.actions', []));
        $label = (string) data_get(config('permissions.features', []), 'schedule.label', 'Jadwal');

        foreach ($actions as $action) {
            $sourcePermissionId = DB::table('permissions')
                ->where('slug', 'class.'.$action)
                ->value('id');
            $roleIds = $sourcePermissionId
                ? DB::table('permission_role')
                    ->where('permission_id', $sourcePermissionId)
                    ->pluck('role_id')
                : collect();

            DB::table('permissions')->updateOrInsert(
                ['slug' => 'schedule.'.$action],
                [
                    'name' => $label.' - '.Str::headline($action),
                    'feature' => 'schedule',
                    'action' => $action,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $schedulePermissionId = DB::table('permissions')
                ->where('slug', 'schedule.'.$action)
                ->value('id');

            if (! $schedulePermissionId || $roleIds->isEmpty()) {
                continue;
            }

            DB::table('permission_role')->insertOrIgnore(
                $roleIds->map(fn ($roleId): array => [
                    'permission_id' => $schedulePermissionId,
                    'role_id' => $roleId,
                ])->all()
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')
            || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('feature', 'schedule')
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
