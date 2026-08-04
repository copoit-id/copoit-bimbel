<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const FEATURES = [
        'material_category',
        'material',
        'finance',
        'recurring_bill',
        'discount',
        'affiliate',
        'activity',
        'update_notification',
    ];

    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('roles')
            || ! DB::getSchemaBuilder()->hasTable('permissions')
            || ! DB::getSchemaBuilder()->hasTable('permission_role')) {
            return;
        }

        $now = now();
        $actions = array_keys(config('permissions.actions', []));
        $features = config('permissions.features', []);

        foreach (self::FEATURES as $featureKey) {
            $label = $features[$featureKey]['label'] ?? Str::headline($featureKey);

            foreach ($actions as $action) {
                DB::table('permissions')->updateOrInsert(
                    ['slug' => $featureKey . '.' . $action],
                    [
                        'name' => $label . ' - ' . Str::headline($action),
                        'feature' => $featureKey,
                        'action' => $action,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('feature', self::FEATURES)
            ->pluck('id');
        $roleIds = DB::table('roles')
            ->whereIn('slug', ['admin', 'super_admin'])
            ->pluck('id');

        $rows = [];
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                $rows[] = [
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('permission_role')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('permissions')
            || ! DB::getSchemaBuilder()->hasTable('permission_role')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('feature', self::FEATURES)
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
