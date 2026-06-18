<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        foreach (['view', 'create', 'update', 'delete'] as $action) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => "kecermatan.{$action}"],
                [
                    'name' => 'Kecermatan - ' . ucfirst($action),
                    'feature' => 'kecermatan',
                    'action' => $action,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('feature', 'kecermatan')
            ->pluck('id');

        if ($permissionIds->isNotEmpty() && Schema::hasTable('permission_role')) {
            DB::table('permission_role')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table('permissions')
            ->where('feature', 'kecermatan')
            ->delete();
    }
};
