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
            if (!Schema::hasColumn('client_profile', 'enable_certificate_management')) {
                $table->boolean('enable_certificate_management')
                    ->default(true)
                    ->after('warna_secondary');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_profile', function (Blueprint $table) {
            if (Schema::hasColumn('client_profile', 'enable_certificate_management')) {
                $table->dropColumn('enable_certificate_management');
            }
        });
    }
};
