<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddTesKoranPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $actions = ['view', 'create', 'update', 'delete'];

        foreach ($actions as $action) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => "tes_koran.{$action}"],
                [
                    'name' => "Tes Koran - " . ucfirst($action),
                    'feature' => 'tes_koran',
                    'action' => $action,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // Assign all tes_koran permissions to super_admin role
        $superAdminRole = DB::table('roles')->where('slug', 'super_admin')->first();
        $permissionIds = DB::table('permissions')->where('feature', 'tes_koran')->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => $superAdminRole->id],
                ['permission_id' => $permissionId, 'role_id' => $superAdminRole->id]
            );
        }

        // Also assign to admin role
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => $adminRole->id],
                ['permission_id' => $permissionId, 'role_id' => $adminRole->id]
            );
        }

        $this->command->info('Tes koran permissions seeded successfully!');
    }
}