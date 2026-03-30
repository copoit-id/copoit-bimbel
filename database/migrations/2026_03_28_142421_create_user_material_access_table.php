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
        Schema::create('user_material_access', function (Blueprint $table) {
            $table->id('user_material_access_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials', 'material_id')->onDelete('cascade');
            $table->enum('access_type', ['free', 'purchased', 'subscription'])->default('free');
            $table->enum('access_source', ['direct', 'package'])->default('direct');
            $table->unsignedBigInteger('source_id')->nullable(); // ID package jika dari package
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedTinyInteger('progress_percentage')->default(0); // 0-100
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->timestamps();

            // Indexes
            $table->unique(['user_id', 'material_id']);
            $table->index(['user_id', 'status']);
            $table->index(['material_id', 'status']);
            $table->index('access_type');
            $table->index('access_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_material_access');
    }
};
