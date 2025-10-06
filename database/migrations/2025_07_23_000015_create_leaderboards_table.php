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
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->id('leaderboard_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tryout_id')->constrained('tryouts', 'tryout_id')->onDelete('cascade');
            $table->string('attempt_token'); // token yang sama dengan user_answers
            $table->decimal('total_score', 5, 2);
            $table->integer('total_correct');
            $table->integer('total_questions');
            $table->integer('rank')->nullable();
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->unique(['user_id', 'tryout_id', 'attempt_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboards');
    }
};
