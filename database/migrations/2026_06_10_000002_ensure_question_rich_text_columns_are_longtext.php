<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['question_text', 'explanation'] as $column) {
            if (Schema::hasColumn('questions', $column)) {
                Schema::table('questions', function (Blueprint $table) use ($column) {
                    $table->longText($column)->nullable($column === 'explanation')->change();
                });
            }
        }

        if (Schema::hasColumn('question_options', 'option_text')) {
            Schema::table('question_options', function (Blueprint $table) {
                $table->longText('option_text')->change();
            });
        }

        foreach (['question_text', 'explanation'] as $column) {
            if (Schema::hasColumn('question_bank_questions', $column)) {
                Schema::table('question_bank_questions', function (Blueprint $table) use ($column) {
                    $table->longText($column)->nullable($column === 'explanation')->change();
                });
            }
        }

        if (Schema::hasColumn('question_bank_question_options', 'option_text')) {
            Schema::table('question_bank_question_options', function (Blueprint $table) {
                $table->longText('option_text')->change();
            });
        }
    }

    public function down(): void
    {
        //
    }
};
