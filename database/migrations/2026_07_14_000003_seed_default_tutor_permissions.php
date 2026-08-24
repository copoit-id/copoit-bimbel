<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('roles')
            || ! DB::getSchemaBuilder()->hasTable('permissions')
            || ! DB::getSchemaBuilder()->hasTable('permission_role')) {
            return;
        }

        $tutorRoleId = DB::table('roles')->where('slug', 'tutor')->value('id');
        if (! $tutorRoleId) {
            return;
        }

        $featuresWithFullAccess = ['package', 'question_bank', 'question', 'tryout'];
        $permissionSlugs = ['dashboard.view', 'profile.view', 'profile.update', 'laporan.view', 'leaderboard.view'];

        foreach ($featuresWithFullAccess as $feature) {
            foreach (array_keys(config('permissions.actions', [])) as $action) {
                $permissionSlugs[] = $feature . '.' . $action;
            }
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $tutorRoleId,
            ]);
        }
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('roles')
            || ! DB::getSchemaBuilder()->hasTable('permissions')
            || ! DB::getSchemaBuilder()->hasTable('permission_role')) {
            return;
        }

        $tutorRoleId = DB::table('roles')->where('slug', 'tutor')->value('id');
        if (! $tutorRoleId) {
            return;
        }

        $featuresWithFullAccess = ['package', 'question_bank', 'question', 'tryout'];
        $permissionSlugs = ['dashboard.view', 'profile.view', 'profile.update', 'laporan.view', 'leaderboard.view'];
        foreach ($featuresWithFullAccess as $feature) {
            foreach (array_keys(config('permissions.actions', [])) as $action) {
                $permissionSlugs[] = $feature . '.' . $action;
            }
        }

        $permissionIds = DB::table('permissions')->whereIn('slug', $permissionSlugs)->pluck('id');
        DB::table('permission_role')
            ->where('role_id', $tutorRoleId)
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }
};
