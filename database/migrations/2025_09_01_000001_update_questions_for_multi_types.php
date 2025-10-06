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
        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'metadata')) {
                $table->json('metadata')->nullable()->after('explanation');
            }
        });

        DB::statement("ALTER TABLE questions MODIFY question_type ENUM('multiple_choice','essay','true_false','short_answer','matching','audio') NOT NULL DEFAULT 'multiple_choice'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });

        DB::statement("ALTER TABLE questions MODIFY question_type ENUM('multiple_choice','essay','true_false') NOT NULL DEFAULT 'multiple_choice'");
    }
};
