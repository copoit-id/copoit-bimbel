<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_profile') || Schema::hasColumn('client_profile', 'tutor_chat_enabled')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $column = $table->boolean('tutor_chat_enabled')->default(false);

            if (Schema::hasColumn('client_profile', 'recurring_bill_menu_enabled')) {
                $column->after('recurring_bill_menu_enabled');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_profile') || ! Schema::hasColumn('client_profile', 'tutor_chat_enabled')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $table->dropColumn('tutor_chat_enabled');
        });
    }
};
