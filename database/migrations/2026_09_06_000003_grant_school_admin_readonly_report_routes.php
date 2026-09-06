<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')->where('slug', 'admin_sekolah')->value('id');
        if (! $roleId) return;
        $ids = DB::table('permissions')->whereIn('slug', ['leaderboard.view', 'tryout.view'])->pluck('id');
        foreach ($ids as $id) DB::table('permission_role')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $id]);
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('slug', 'admin_sekolah')->value('id');
        $ids = DB::table('permissions')->whereIn('slug', ['leaderboard.view', 'tryout.view'])->pluck('id');
        if ($roleId) DB::table('permission_role')->where('role_id', $roleId)->whereIn('permission_id', $ids)->delete();
    }
};
