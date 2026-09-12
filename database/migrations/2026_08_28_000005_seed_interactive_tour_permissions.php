<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('permission_role') || ! Schema::hasTable('roles')) {
            return;
        }

        $now = now();
        foreach (['view', 'create'] as $action) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => 'interactive_tour.'.$action],
                [
                    'name' => 'Tutor Navigasi - '.str($action)->headline(),
                    'feature' => 'interactive_tour',
                    'action' => $action,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', ['interactive_tour.view', 'interactive_tour.create'])
            ->pluck('id');
        $roleIds = DB::table('roles')
            ->whereIn('slug', ['admin', 'admin_demo'])
            ->pluck('id');

        $rows = [];
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                $rows[] = ['permission_id' => $permissionId, 'role_id' => $roleId];
            }
        }

        if ($rows !== []) {
            DB::table('permission_role')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', ['interactive_tour.view', 'interactive_tour.create'])
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
