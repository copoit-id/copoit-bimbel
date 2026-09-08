<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')->where('slug', 'admin_sekolah')->value('id');
        $permissionId = DB::table('permissions')->where('slug', 'laporan.view')->value('id');

        if ($roleId && $permissionId) {
            DB::table('permission_role')->updateOrInsert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('slug', 'admin_sekolah')->value('id');
        $permissionId = DB::table('permissions')->where('slug', 'laporan.view')->value('id');

        if ($roleId && $permissionId) {
            DB::table('permission_role')->where('role_id', $roleId)->where('permission_id', $permissionId)->delete();
        }
    }
};
