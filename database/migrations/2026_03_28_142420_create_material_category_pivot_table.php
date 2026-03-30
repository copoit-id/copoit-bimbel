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
        Schema::create('material_category_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials', 'material_id')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('material_categories', 'category_id')->onDelete('cascade');
            $table->timestamps();

            // Unique constraint untuk mencegah duplikat
            $table->unique(['material_id', 'category_id']);

            // Indexes
            $table->index('material_id');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_category_pivot');
    }
};
