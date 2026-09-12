<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_progress_reports') || Schema::hasColumn('student_progress_reports', 'class_session_id')) {
            return;
        }

        Schema::table('student_progress_reports', function (Blueprint $table): void {
            $table->foreignId('class_session_id')->nullable()->after('user_package_access_id')
                ->constrained('class_sessions')->nullOnDelete();
            $table->unique(['class_session_id', 'user_id'], 'student_progress_session_user_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('student_progress_reports') || ! Schema::hasColumn('student_progress_reports', 'class_session_id')) {
            return;
        }

        Schema::table('student_progress_reports', function (Blueprint $table): void {
            $table->dropUnique('student_progress_session_user_unique');
            $table->dropForeign(['class_session_id']);
            $table->dropColumn('class_session_id');
        });
    }
};
