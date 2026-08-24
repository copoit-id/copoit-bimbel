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
        Schema::create('materials', function (Blueprint $table) {
            $table->id('material_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['video', 'document', 'live_session'])->default('video');
            $table->string('content_url'); // URL video atau PDF
            $table->string('thumbnail_url')->nullable();
            $table->integer('duration_minutes')->nullable(); // Durasi dalam menit
            $table->boolean('is_active')->default(true);
            $table->integer('order_number')->default(0);
            $table->json('metadata')->nullable(); // Data tambahan
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes
            $table->index('type');
            $table->index('is_active');
            $table->index('order_number');
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
