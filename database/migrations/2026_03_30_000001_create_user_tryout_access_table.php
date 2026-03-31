<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_tryout_access')) {
            Schema::create('user_tryout_access', function (Blueprint $table) {
                $table->id('user_tryout_access_id');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('tryout_id')->constrained('tryouts', 'tryout_id')->onDelete('cascade');
                $table->enum('access_type', ['free', 'purchased', 'subscription'])->default('free');
                $table->enum('access_source', ['direct', 'package'])->default('direct');
                $table->unsignedBigInteger('source_id')->nullable(); // ID package jika dari package
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedTinyInteger('progress_percentage')->default(0);
                $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
                $table->timestamp('expires_at')->nullable(); // Untuk akses berbayar dengan masa aktif
                $table->timestamps();

                $table->unique(['user_id', 'tryout_id']);
                $table->index(['user_id', 'status']);
                $table->index(['tryout_id', 'status']);
                $table->index('access_type');
                $table->index('expires_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tryout_access');
    }
};
