<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSION_SLUGS = [
        'material_category.view',
        'essay_review.view',
        'feedback.view',
    ];

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

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', self::PERMISSION_SLUGS)
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

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', self::PERMISSION_SLUGS)
            ->pluck('id');

        DB::table('permission_role')
            ->where('role_id', $tutorRoleId)
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }
};
