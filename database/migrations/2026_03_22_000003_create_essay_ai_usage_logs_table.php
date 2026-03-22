<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('essay_ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_profile_id')->constrained('client_profile')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('essay_correction_job_id')->nullable()->constrained('essay_correction_jobs')->onDelete('set null');
            
            $table->integer('essays_count')->default(1);
            $table->timestamp('used_at');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['client_profile_id', 'used_at']);
            $table->index('used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('essay_ai_usage_logs');
    }
};
