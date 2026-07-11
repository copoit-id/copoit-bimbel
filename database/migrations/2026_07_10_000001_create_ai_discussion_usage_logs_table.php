<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_discussion_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tryout_id')->nullable()->constrained('tryouts', 'tryout_id')->nullOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('questions', 'question_id')->nullOnDelete();
            $table->string('attempt_token', 100)->nullable();
            $table->string('provider', 30);
            $table->string('model', 120);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('user_message')->nullable();
            $table->text('assistant_message')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'total_tokens']);
            $table->index(['question_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_discussion_usage_logs');
    }
};
