<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tentors')) {
            Schema::create('tentors', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable()->unique();
                $table->string('phone', 30)->nullable();
                $table->string('expertise')->nullable();
                $table->text('bio')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'name']);
            });
        }

        if (Schema::hasTable('classes') && !Schema::hasColumn('classes', 'tentor_id')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->foreignId('tentor_id')
                    ->nullable()
                    ->after('mentor')
                    ->constrained('tentors')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('class_schedules') && !Schema::hasColumn('class_schedules', 'tentor_id')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->foreignId('tentor_id')
                    ->nullable()
                    ->after('class_id')
                    ->constrained('tentors')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('class_sessions') && !Schema::hasColumn('class_sessions', 'tentor_id')) {
            Schema::table('class_sessions', function (Blueprint $table) {
                $table->foreignId('tentor_id')
                    ->nullable()
                    ->after('class_id')
                    ->constrained('tentors')
                    ->nullOnDelete();
            });
        }

        $this->seedTentorPermissions();
    }

    public function down(): void
    {
        foreach (['class_sessions', 'class_schedules', 'classes'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tentor_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('tentor_id');
                });
            }
        }

        if (Schema::hasTable('permission_role') && Schema::hasTable('permissions')) {
            $permissionIds = DB::table('permissions')->where('feature', 'tentor')->pluck('id');
            if ($permissionIds->isNotEmpty()) {
                DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
                DB::table('permissions')->whereIn('id', $permissionIds)->delete();
            }
        }

        Schema::dropIfExists('tentors');
    }

    private function seedTentorPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $actions = array_keys(config('permissions.actions', []));

        foreach ($actions as $action) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => 'tentor.' . $action],
                [
                    'name' => 'Manajemen Tentor - ' . Str::headline($action),
                    'feature' => 'tentor',
                    'action' => $action,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        if (!Schema::hasTable('roles') || !Schema::hasTable('permission_role')) {
            return;
        }

        $roleIds = DB::table('roles')
            ->whereIn('slug', ['admin', 'super_admin'])
            ->pluck('id');
        $permissionIds = DB::table('permissions')
            ->where('feature', 'tentor')
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
};
