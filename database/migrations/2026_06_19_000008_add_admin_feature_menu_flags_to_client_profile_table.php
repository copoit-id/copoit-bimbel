<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            if (!Schema::hasColumn('client_profile', 'class_schedule_menu_enabled')) {
                $column = $table->boolean('class_schedule_menu_enabled')->default(false);
                if (Schema::hasColumn('client_profile', 'admin_assistant_enabled')) {
                    $column->after('admin_assistant_enabled');
                }
            }

            if (!Schema::hasColumn('client_profile', 'recurring_bill_menu_enabled')) {
                $column = $table->boolean('recurring_bill_menu_enabled')->default(false);
                if (Schema::hasColumn('client_profile', 'class_schedule_menu_enabled')) {
                    $column->after('class_schedule_menu_enabled');
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            foreach (['recurring_bill_menu_enabled', 'class_schedule_menu_enabled'] as $column) {
                if (Schema::hasColumn('client_profile', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
