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
        Schema::create('package_materials', function (Blueprint $table) {
            $table->id('package_material_id');
            $table->foreignId('package_id')->constrained('packages', 'package_id')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials', 'material_id')->onDelete('cascade');
            $table->string('section_name')->nullable(); // Nama section (misal: "Minggu 1")
            $table->integer('order_number')->default(0);
            $table->boolean('is_required')->default(true); // Wajib dikerjakan?
            $table->json('unlock_condition')->nullable(); // Syarat membuka
            $table->timestamps();

            // Indexes
            $table->unique(['package_id', 'material_id']);
            $table->index(['package_id', 'order_number']);
            $table->index('material_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_materials');
    }
};
