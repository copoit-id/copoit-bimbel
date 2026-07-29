<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('class_schedules')) {
            return;
        }

        Schema::table('class_schedules', function (Blueprint $table): void {
            if (! Schema::hasColumn('class_schedules', 'allow_custom_booking')) {
                $table->boolean('allow_custom_booking')->default(false);
            }
            if (! Schema::hasColumn('class_schedules', 'booking_session_quota')) {
                $table->unsignedSmallInteger('booking_session_quota')->default(1);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('class_schedules')) {
            return;
        }

        Schema::table('class_schedules', function (Blueprint $table): void {
            foreach (['allow_custom_booking', 'booking_session_quota'] as $column) {
                if (Schema::hasColumn('class_schedules', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
