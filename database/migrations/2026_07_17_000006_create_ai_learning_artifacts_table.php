<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_learning_artifacts')) {
            return;
        }

        Schema::create('ai_learning_artifacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tryout_id')->nullable()->constrained('tryouts', 'tryout_id')->nullOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('questions', 'question_id')->nullOnDelete();
            $table->string('attempt_token', 100)->nullable();
            $table->string('tool', 40);
            $table->string('title');
            $table->json('payload');
            $table->string('provider', 30)->nullable();
            $table->string('model', 120)->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->timestamp('saved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tool', 'saved_at'], 'ai_learning_artifacts_user_tool_saved_index');
            $table->index(['question_id', 'created_at'], 'ai_learning_artifacts_question_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_learning_artifacts');
    }
};
