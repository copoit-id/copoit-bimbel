<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('question_bank_questions', 'explanation')) {
            Schema::table('question_bank_questions', function (Blueprint $table) {
                $table->longText('explanation')->nullable()->change();
            });
        }

        if (Schema::hasColumn('questions', 'explanation')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->longText('explanation')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        //
    }
};
