<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('roles')
            && Schema::hasTable('permissions')
            && Schema::hasTable('permission_role')
        ) {
            $roleIds = DB::table('roles')
                ->whereIn('slug', ['admin', 'super_admin'])
                ->pluck('id');
            $permissionIds = DB::table('permissions')
                ->where('feature', 'kecermatan')
                ->pluck('id');

            $rows = [];
            foreach ($roleIds as $roleId) {
                foreach ($permissionIds as $permissionId) {
                    $rows[] = [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ];
                }
            }

            if (!empty($rows)) {
                DB::table('permission_role')->insertOrIgnore($rows);
            }
        }

        if (Schema::hasTable('kecermatans')) {
            DB::table('kecermatans')->update([
                'is_active' => true,
                'is_displayed' => true,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('roles')
            && Schema::hasTable('permissions')
            && Schema::hasTable('permission_role')
        ) {
            $roleIds = DB::table('roles')
                ->whereIn('slug', ['admin', 'super_admin'])
                ->pluck('id');
            $permissionIds = DB::table('permissions')
                ->where('feature', 'kecermatan')
                ->pluck('id');

            DB::table('permission_role')
                ->whereIn('role_id', $roleIds)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }
    }
};
