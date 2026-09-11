<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_progress_reports')) {
            return;
        }

        Schema::table('student_progress_reports', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreignId('package_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('student_progress_reports')) {
            return;
        }

        DB::table('student_progress_reports')
            ->whereNotNull('class_session_id')
            ->whereNull('user_id')
            ->delete();

        Schema::table('student_progress_reports', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreignId('package_id')->nullable(false)->change();
        });
    }
};
