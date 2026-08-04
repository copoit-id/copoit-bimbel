<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tentors') && ! Schema::hasColumn('tentors', 'honor_per_attendance')) {
            Schema::table('tentors', function (Blueprint $table) {
                $table->decimal('honor_per_attendance', 12, 0)->default(0)->after('is_active');
            });
        }

        if (Schema::hasTable('class_schedules') && Schema::hasColumn('class_schedules', 'tutor_honor_per_attendance')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->dropColumn('tutor_honor_per_attendance');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tentors') && Schema::hasColumn('tentors', 'honor_per_attendance')) {
            Schema::table('tentors', function (Blueprint $table) {
                $table->dropColumn('honor_per_attendance');
            });
        }
    }
};
