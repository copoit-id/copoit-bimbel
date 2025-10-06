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
        Schema::create('exams', function (Blueprint $table) {
            $table->id('exam_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tryout_id')->constrained('tryouts', 'tryout_id')->onDelete('cascade');
            $table->float('score');
            $table->enum('status', ['not_started', 'completed'])->default('not_started');
            $table->enum('type_exam', ['tiu', 'twk', 'tkp', 'skd full', 'general', 'certification', 'teknis', 'social culture', 'management', 'interview']);
            $table->string('certificate_url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};