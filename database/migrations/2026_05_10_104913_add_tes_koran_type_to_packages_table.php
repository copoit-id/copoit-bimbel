<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            // Add tes_koran to type_package enum
            DB::statement("ALTER TABLE packages MODIFY COLUMN type_package ENUM('bimbel', 'tryout', 'sertifikasi', 'tes_koran') NOT NULL DEFAULT 'bimbel'");
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            DB::statement("ALTER TABLE packages MODIFY COLUMN type_package ENUM('bimbel', 'tryout', 'sertifikasi') NOT NULL DEFAULT 'bimbel'");
        });
    }
};
