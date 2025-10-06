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
        Schema::create('questions', function (Blueprint $table) {
            $table->id('question_id');
            $table->foreignId('tryout_detail_id')->constrained('tryout_details', 'tryout_detail_id')->onDelete('cascade');
            $table->enum('question_type', ['multiple_choice', 'essay', 'true_false'])->default('multiple_choice');
            $table->text('question_text');
            $table->string('sound')->nullable();
            $table->text('explanation')->nullable();
            $table->decimal('default_weight', 5, 2)->default(1.00)->nullable();
            $table->enum('custom_score', ['yes', 'no'])->default('no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};