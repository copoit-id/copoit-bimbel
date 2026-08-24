<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const USER_ANSWERS_INDEX = 'user_answers_user_tryout_status_attempt_idx';
    private const ANSWER_DETAILS_INDEX = 'user_answer_details_answer_question_idx';

    public function up(): void
    {
        if (! Schema::hasIndex('user_answers', self::USER_ANSWERS_INDEX)) {
            Schema::table('user_answers', function (Blueprint $table): void {
                $table->index(
                    ['user_id', 'tryout_id', 'status', 'attempt_token'],
                    self::USER_ANSWERS_INDEX
                );
            });
        }

        if (! Schema::hasIndex('user_answer_details', self::ANSWER_DETAILS_INDEX)) {
            Schema::table('user_answer_details', function (Blueprint $table): void {
                $table->index(
                    ['user_answer_id', 'question_id'],
                    self::ANSWER_DETAILS_INDEX
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('user_answer_details', self::ANSWER_DETAILS_INDEX)) {
            Schema::table('user_answer_details', function (Blueprint $table): void {
                $table->dropIndex(self::ANSWER_DETAILS_INDEX);
            });
        }

        if (Schema::hasIndex('user_answers', self::USER_ANSWERS_INDEX)) {
            Schema::table('user_answers', function (Blueprint $table): void {
                $table->dropIndex(self::USER_ANSWERS_INDEX);
            });
        }
    }
};
