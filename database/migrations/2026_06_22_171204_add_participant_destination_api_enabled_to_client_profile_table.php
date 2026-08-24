<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            if (!Schema::hasColumn('client_profile', 'participant_destination_api_enabled')) {
                $table->boolean('participant_destination_api_enabled')
                    ->default(false)
                    ->after('recurring_bill_menu_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            if (Schema::hasColumn('client_profile', 'participant_destination_api_enabled')) {
                $table->dropColumn('participant_destination_api_enabled');
            }
        });
    }
};
