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
        Schema::table('client_profile', function (Blueprint $table) {
            if (!Schema::hasColumn('client_profile', 'header_primary_color')) {
                $table->boolean('header_primary_color')
                    ->default(false)
                    ->after('enable_certificate_management');
            }

            if (!Schema::hasColumn('client_profile', 'sidebar_primary_color')) {
                $table->boolean('sidebar_primary_color')
                    ->default(false)
                    ->after('header_primary_color');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_profile', function (Blueprint $table) {
            if (Schema::hasColumn('client_profile', 'sidebar_primary_color')) {
                $table->dropColumn('sidebar_primary_color');
            }

            if (Schema::hasColumn('client_profile', 'header_primary_color')) {
                $table->dropColumn('header_primary_color');
            }
        });
    }
};
