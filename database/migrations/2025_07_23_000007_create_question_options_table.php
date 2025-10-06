<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_options', function (Blueprint $table) {
            $table->id('question_option_id');
            $table->foreignId('question_id')->constrained('questions', 'question_id')->onDelete('cascade');
            $table->text('option_text');
            $table->decimal('weight', 5, 2)->default(0.00)->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};