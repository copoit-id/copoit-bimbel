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
        if (Schema::hasTable('client_profile') && !Schema::hasColumn('client_profile', 'enable_utbk_types')) {
            Schema::table('client_profile', function (Blueprint $table) {
                $table->boolean('enable_utbk_types')->default(true)->after('sidebar_primary_color');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('client_profile') && Schema::hasColumn('client_profile', 'enable_utbk_types')) {
            Schema::table('client_profile', function (Blueprint $table) {
                $table->dropColumn('enable_utbk_types');
            });
        }
    }
};
