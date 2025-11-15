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
        Schema::create('client_profile', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bimbel');
            $table->string('logo');
            $table->string('warna_primary');
            $table->string('warna_secondary')->nullable();
            $table->boolean('enable_certificate_management')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_profile');
    }
};
