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
        Schema::create('practice_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_session_id')
                ->constrained('practice_sessions')
                ->cascadeOnDelete();
            $table->foreignId('question_bank_question_id')
                ->constrained('question_bank_questions')
                ->cascadeOnDelete();
            $table->enum('question_type', ['multiple_choice', 'true_false', 'matching', 'essay', 'short_answer', 'audio'])
                ->default('multiple_choice');
            $table->foreignId('question_bank_question_option_id')
                ->nullable()
                ->constrained('question_bank_question_options')
                ->nullOnDelete();
            $table->text('answer_text')->nullable();
            $table->json('answer_json')->nullable();
            $table->string('answer_file_path')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['practice_session_id', 'question_bank_question_id'], 'unique_practice_answer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_answers');
    }
};
