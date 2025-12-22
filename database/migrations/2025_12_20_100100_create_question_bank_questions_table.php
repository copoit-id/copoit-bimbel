<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_bank_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')
                ->constrained('question_banks')
                ->cascadeOnDelete();
            $table->enum('question_type', ['multiple_choice', 'essay', 'true_false', 'short_answer', 'matching', 'audio'])
                ->default('multiple_choice');
            $table->text('question_text');
            $table->string('sound')->nullable();
            $table->text('explanation')->nullable();
            $table->json('metadata')->nullable();
            $table->decimal('default_weight', 5, 2)->default(1.00);
            $table->enum('custom_score', ['yes', 'no'])->default('no');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_questions');
    }
};
