<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            if (! Schema::hasColumn('client_profile', 'booking_schedule_enabled')) {
                $column = $table->boolean('booking_schedule_enabled')->default(false);

                if (Schema::hasColumn('client_profile', 'tutor_chat_enabled')) {
                    $column->after('tutor_chat_enabled');
                }
            }

            if (! Schema::hasColumn('client_profile', 'learning_progress_enabled')) {
                $column = $table->boolean('learning_progress_enabled')->default(false);

                if (Schema::hasColumn('client_profile', 'booking_schedule_enabled')) {
                    $column->after('booking_schedule_enabled');
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            foreach (['learning_progress_enabled', 'booking_schedule_enabled'] as $column) {
                if (Schema::hasColumn('client_profile', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
