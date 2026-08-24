<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_bank_questions', function (Blueprint $table) {
            $table->longText('question_text')->change();
        });

        Schema::table('question_bank_question_options', function (Blueprint $table) {
            $table->longText('option_text')->change();
        });
    }

    public function down(): void
    {
        Schema::table('question_bank_questions', function (Blueprint $table) {
            $table->text('question_text')->change();
        });

        Schema::table('question_bank_question_options', function (Blueprint $table) {
            $table->text('option_text')->change();
        });
    }
};