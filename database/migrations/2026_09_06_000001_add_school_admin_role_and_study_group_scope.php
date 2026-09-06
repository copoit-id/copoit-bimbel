<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_admin_study_group', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_group_id')->constrained('study_groups')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'study_group_id']);
        });

        $roleId = DB::table('roles')->where('slug', 'admin_sekolah')->value('id');
        if (! $roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'Admin Sekolah',
                'slug' => 'admin_sekolah',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (['view'] as $action) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => 'school_admin.'.$action],
                ['name' => 'Admin Sekolah '.ucfirst($action), 'feature' => 'school_admin', 'action' => $action, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $permissionId = DB::table('permissions')->where('slug', 'school_admin.view')->value('id');
        DB::table('permission_role')->updateOrInsert(['permission_id' => $permissionId, 'role_id' => $roleId]);
    }

    public function down(): void
    {
        Schema::dropIfExists('school_admin_study_group');
        $permissionIds = DB::table('permissions')->where('feature', 'school_admin')->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        // Role mungkin telah dibuat manual sebelum migration ini dijalankan.
        // Jangan menghapusnya saat rollback agar tidak menghilangkan assignment yang sudah ada.
    }
};
