<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_answer_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_answer_details', 'question_option_id')) {
                $table->dropForeign(['question_option_id']);
            }
        });

        DB::statement('ALTER TABLE user_answer_details MODIFY question_option_id BIGINT UNSIGNED NULL');

        Schema::table('user_answer_details', function (Blueprint $table) {
            $table->foreign('question_option_id')
                ->references('question_option_id')
                ->on('question_options')
                ->nullOnDelete();

            if (!Schema::hasColumn('user_answer_details', 'answer_text')) {
                $table->text('answer_text')->nullable()->after('question_option_id');
            }

            if (!Schema::hasColumn('user_answer_details', 'answer_json')) {
                $table->json('answer_json')->nullable()->after('answer_text');
            }

            if (!Schema::hasColumn('user_answer_details', 'answer_file_path')) {
                $table->string('answer_file_path')->nullable()->after('answer_json');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_answer_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_answer_details', 'question_option_id')) {
                $table->dropForeign(['question_option_id']);
            }

            if (Schema::hasColumn('user_answer_details', 'answer_file_path')) {
                $table->dropColumn('answer_file_path');
            }

            if (Schema::hasColumn('user_answer_details', 'answer_json')) {
                $table->dropColumn('answer_json');
            }

            if (Schema::hasColumn('user_answer_details', 'answer_text')) {
                $table->dropColumn('answer_text');
            }
        });

        DB::statement('ALTER TABLE user_answer_details MODIFY question_option_id BIGINT UNSIGNED NOT NULL');

        Schema::table('user_answer_details', function (Blueprint $table) {
            $table->foreign('question_option_id')
                ->references('question_option_id')
                ->on('question_options')
                ->cascadeOnDelete();
        });
    }
};
