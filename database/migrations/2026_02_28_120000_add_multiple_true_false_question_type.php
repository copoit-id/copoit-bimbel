<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE questions MODIFY question_type ENUM('multiple_choice','essay','true_false','short_answer','matching','audio','multiple_answer','multiple_true_false') NOT NULL DEFAULT 'multiple_choice'");
        DB::statement("ALTER TABLE question_bank_questions MODIFY question_type ENUM('multiple_choice','essay','true_false','short_answer','matching','audio','multiple_answer','multiple_true_false') NOT NULL DEFAULT 'multiple_choice'");
    }

    public function down(): void
    {
        DB::statement("UPDATE questions SET question_type='multiple_choice' WHERE question_type='multiple_true_false'");
        DB::statement("UPDATE question_bank_questions SET question_type='multiple_choice' WHERE question_type='multiple_true_false'");

        DB::statement("ALTER TABLE questions MODIFY question_type ENUM('multiple_choice','essay','true_false','short_answer','matching','audio','multiple_answer') NOT NULL DEFAULT 'multiple_choice'");
        DB::statement("ALTER TABLE question_bank_questions MODIFY question_type ENUM('multiple_choice','essay','true_false','short_answer','matching','audio','multiple_answer') NOT NULL DEFAULT 'multiple_choice'");
    }
};

