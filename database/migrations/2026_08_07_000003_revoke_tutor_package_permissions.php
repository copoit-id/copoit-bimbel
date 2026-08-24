<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')
            || ! Schema::hasTable('permissions')
            || ! Schema::hasTable('permission_role')) {
            return;
        }

        $tutorRoleId = DB::table('roles')->where('slug', 'tutor')->value('id');
        if (! $tutorRoleId) {
            return;
        }

        $packagePermissionIds = DB::table('permissions')
            ->where('feature', 'package')
            ->pluck('id');

        if ($packagePermissionIds->isEmpty()) {
            return;
        }

        DB::table('permission_role')
            ->where('role_id', $tutorRoleId)
            ->whereIn('permission_id', $packagePermissionIds)
            ->delete();
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')
            || ! Schema::hasTable('permissions')
            || ! Schema::hasTable('permission_role')) {
            return;
        }

        $tutorRoleId = DB::table('roles')->where('slug', 'tutor')->value('id');
        if (! $tutorRoleId) {
            return;
        }

        $packagePermissionIds = DB::table('permissions')
            ->where('feature', 'package')
            ->pluck('id');

        if ($packagePermissionIds->isEmpty()) {
            return;
        }

        DB::table('permission_role')->insertOrIgnore(
            $packagePermissionIds->map(fn (int $permissionId): array => [
                'role_id' => $tutorRoleId,
                'permission_id' => $permissionId,
            ])->all()
        );
    }
};
