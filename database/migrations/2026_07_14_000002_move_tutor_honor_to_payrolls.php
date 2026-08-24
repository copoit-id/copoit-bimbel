<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tutor_payrolls')) {
            Schema::table('tutor_payrolls', function (Blueprint $table) {
                if (! Schema::hasColumn('tutor_payrolls', 'rate_per_attendance')) {
                    $table->decimal('rate_per_attendance', 12, 0)->default(0)->after('period_end');
                }
            });
        }

        if (Schema::hasTable('tentors') && Schema::hasColumn('tentors', 'honor_per_session')) {
            Schema::table('tentors', function (Blueprint $table) {
                $table->dropColumn('honor_per_session');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tentors') && ! Schema::hasColumn('tentors', 'honor_per_session')) {
            Schema::table('tentors', function (Blueprint $table) {
                $table->decimal('honor_per_session', 12, 0)->default(0);
            });
        }

        if (Schema::hasTable('tutor_payrolls') && Schema::hasColumn('tutor_payrolls', 'rate_per_attendance')) {
            Schema::table('tutor_payrolls', function (Blueprint $table) {
                $table->dropColumn('rate_per_attendance');
            });
        }
    }
};
