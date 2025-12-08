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
        if (Schema::hasTable('client_profile') && !Schema::hasColumn('client_profile', 'favicon')) {
            Schema::table('client_profile', function (Blueprint $table) {
                $table->string('favicon')->nullable()->after('logo');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('client_profile') && Schema::hasColumn('client_profile', 'favicon')) {
            Schema::table('client_profile', function (Blueprint $table) {
                $table->dropColumn('favicon');
            });
        }
    }
};
