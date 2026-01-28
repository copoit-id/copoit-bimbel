<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_questions', function (Blueprint $table) {
            $table->bigIncrements('feedback_question_id');
            $table->unsignedBigInteger('tryout_id');
            $table->string('question_text');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tryout_id')->references('tryout_id')->on('tryouts')->onDelete('cascade');
            $table->index(['tryout_id', 'is_active']);
        });

        Schema::create('feedback_submissions', function (Blueprint $table) {
            $table->bigIncrements('feedback_submission_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('tryout_id');
            $table->string('attempt_token')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'tryout_id'], 'fb_submissions_user_tryout_unique');
            $table->index(['tryout_id', 'user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('tryout_id')->references('tryout_id')->on('tryouts')->onDelete('cascade');
        });

        Schema::create('feedback_answers', function (Blueprint $table) {
            $table->bigIncrements('feedback_answer_id');
            $table->unsignedBigInteger('feedback_submission_id');
            $table->unsignedBigInteger('feedback_question_id');
            $table->unsignedTinyInteger('score');
            $table->timestamps();

            $table->unique(['feedback_submission_id', 'feedback_question_id'], 'fb_answers_submission_question_unique');
            $table->foreign('feedback_submission_id')->references('feedback_submission_id')->on('feedback_submissions')->onDelete('cascade');
            $table->foreign('feedback_question_id')->references('feedback_question_id')->on('feedback_questions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_answers');
        Schema::dropIfExists('feedback_submissions');
        Schema::dropIfExists('feedback_questions');
    }
};
