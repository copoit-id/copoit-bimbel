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
        if (!Schema::hasTable('class_assessments')) {
            Schema::create('class_assessments', function (Blueprint $table) {
                $table->id('class_assessment_id');
                $table->foreignId('class_id')->constrained('classes', 'class_id')->onDelete('cascade');
                $table->foreignId('tryout_id')->constrained('tryouts', 'tryout_id')->onDelete('cascade');
                $table->enum('assessment_type', ['pre_test', 'post_test']);
                $table->timestamps();

                $table->unique(['class_id', 'assessment_type']);
                $table->unique(['class_id', 'tryout_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_assessments');
    }
};
