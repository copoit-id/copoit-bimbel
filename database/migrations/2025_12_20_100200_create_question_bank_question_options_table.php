<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_bank_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_question_id')
                ->constrained('question_bank_questions')
                ->cascadeOnDelete();
            $table->text('option_text');
            $table->decimal('weight', 5, 2)->default(0.00);
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_question_options');
    }
};
