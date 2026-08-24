<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tentors')) {
            Schema::table('tentors', function (Blueprint $table) {
                if (! Schema::hasColumn('tentors', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
                }

                if (! Schema::hasColumn('tentors', 'honor_per_session')) {
                    $table->decimal('honor_per_session', 12, 0)->default(0);
                }
            });

            if (Schema::hasTable('users')) {
                DB::table('tentors')
                    ->whereNull('user_id')
                    ->whereNotNull('email')
                    ->orderBy('id')
                    ->each(function (object $tentor): void {
                        $userId = DB::table('users')
                            ->where('role', 'tutor')
                            ->where('email', $tentor->email)
                            ->value('id');

                        if ($userId) {
                            DB::table('tentors')->where('id', $tentor->id)->update(['user_id' => $userId]);
                        }
                    });
            }
        }

        if (Schema::hasTable('class_attendances') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE class_attendances MODIFY COLUMN source ENUM('user', 'admin', 'tutor') NOT NULL DEFAULT 'user'");
        }

        if (Schema::hasTable('class_sessions') && Schema::hasTable('class_schedules') && Schema::hasTable('study_groups') && Schema::hasTable('classes') && DB::getDriverName() === 'mysql') {
            DB::statement(
                'UPDATE class_sessions sessions '
                . 'INNER JOIN class_schedules schedules ON schedules.id = sessions.class_schedule_id '
                . 'LEFT JOIN study_groups study_groups_table ON study_groups_table.id = sessions.study_group_id '
                . 'LEFT JOIN classes classes_table ON classes_table.class_id = sessions.class_id '
                . 'SET sessions.tentor_id = COALESCE(schedules.tentor_id, study_groups_table.tentor_id, classes_table.tentor_id) '
                . 'WHERE sessions.tentor_id IS NULL'
            );
        }

        if (! Schema::hasTable('tutor_attendances')) {
            Schema::create('tutor_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_session_id')->constrained('class_sessions')->cascadeOnDelete();
                $table->foreignId('tentor_id')->constrained('tentors')->cascadeOnDelete();
                $table->enum('status', ['present', 'late', 'absent', 'excused'])->default('present');
                $table->timestamp('check_in_at')->nullable();
                $table->timestamp('check_out_at')->nullable();
                $table->enum('source', ['tutor', 'admin'])->default('tutor');
                $table->text('notes')->nullable();
                $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['class_session_id', 'tentor_id']);
                $table->index(['tentor_id', 'status']);
            });
        }

        if (! Schema::hasTable('tutor_payrolls')) {
            Schema::create('tutor_payrolls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tentor_id')->constrained('tentors')->cascadeOnDelete();
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('gross_amount', 12, 0)->default(0);
                $table->decimal('adjustment_amount', 12, 0)->default(0);
                $table->decimal('net_amount', 12, 0)->default(0);
                $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
                $table->text('notes')->nullable();
                $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->unique(['tentor_id', 'period_start', 'period_end']);
                $table->index(['period_start', 'period_end', 'status']);
            });
        }

        if (! Schema::hasTable('tutor_payroll_items')) {
            Schema::create('tutor_payroll_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tutor_payroll_id')->constrained('tutor_payrolls')->cascadeOnDelete();
                $table->foreignId('tutor_attendance_id')->nullable()->constrained('tutor_attendances')->nullOnDelete();
                $table->foreignId('class_session_id')->nullable()->constrained('class_sessions')->nullOnDelete();
                $table->date('session_date')->nullable();
                $table->string('description');
                $table->decimal('amount', 12, 0)->default(0);
                $table->timestamps();

                $table->unique(['tutor_payroll_id', 'tutor_attendance_id']);
                $table->index(['class_session_id', 'session_date']);
            });
        }

        $this->seedTutorRoleAndPayrollPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_payroll_items');
        Schema::dropIfExists('tutor_payrolls');
        Schema::dropIfExists('tutor_attendances');

        if (Schema::hasTable('class_attendances') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE class_attendances MODIFY COLUMN source ENUM('user', 'admin') NOT NULL DEFAULT 'user'");
        }

        if (Schema::hasTable('tentors')) {
            Schema::table('tentors', function (Blueprint $table) {
                if (Schema::hasColumn('tentors', 'user_id')) {
                    try {
                        $table->dropForeign(['user_id']);
                    } catch (\Throwable) {
                        // The foreign key may not exist on older installations.
                    }
                    $table->dropColumn('user_id');
                }

                if (Schema::hasColumn('tentors', 'honor_per_session')) {
                    $table->dropColumn('honor_per_session');
                }
            });
        }
    }

    private function seedTutorRoleAndPayrollPermissions(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $now = now();
        DB::table('roles')->updateOrInsert(
            ['slug' => 'tutor'],
            ['name' => 'Tutor', 'created_at' => $now, 'updated_at' => $now]
        );

        if (! Schema::hasTable('permissions') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionIds = [];
        foreach (array_keys(config('permissions.actions', [])) as $action) {
            $slug = 'tutor_payroll.' . $action;
            DB::table('permissions')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => 'Penggajian Tutor - ' . str($action)->headline(),
                    'feature' => 'tutor_payroll',
                    'action' => $action,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $permissionIds[] = DB::table('permissions')->where('slug', $slug)->value('id');
        }

        $roleIds = DB::table('roles')->whereIn('slug', ['admin', 'super_admin'])->pluck('id');
        foreach ($roleIds as $roleId) {
            foreach (array_filter($permissionIds) as $permissionId) {
                DB::table('permission_role')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }
};
