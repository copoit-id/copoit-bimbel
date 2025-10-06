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
        Schema::create('user_answers', function (Blueprint $table) {
            $table->id('user_answer_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tryout_id')->constrained('tryouts', 'tryout_id')->onDelete('cascade');
            $table->foreignId('tryout_detail_id')->constrained('tryout_details', 'tryout_detail_id')->onDelete('cascade');
            $table->string('attempt_token'); // untuk mengelompokkan attempt yang sama
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->integer('correct_answers')->default(0);
            $table->integer('wrong_answers')->default(0);
            $table->integer('unanswered')->default(0);
            $table->decimal('score', 5, 2)->default(0);
            $table->boolean('is_passed')->default(false);
            $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress');
            $table->timestamps();

            // Index untuk query cepat berdasarkan token
            $table->index(['user_id', 'attempt_token']);
            $table->index(['tryout_id', 'attempt_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_answers');
    }
};
