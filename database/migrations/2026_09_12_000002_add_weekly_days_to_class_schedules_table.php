<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('class_schedules') || Schema::hasColumn('class_schedules', 'weekly_days')) {
            return;
        }

        Schema::table('class_schedules', function (Blueprint $table): void {
            $table->json('weekly_days')->nullable()->after('day_of_week');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('class_schedules') || ! Schema::hasColumn('class_schedules', 'weekly_days')) {
            return;
        }

        Schema::table('class_schedules', function (Blueprint $table): void {
            $table->dropColumn('weekly_days');
        });
    }
};
