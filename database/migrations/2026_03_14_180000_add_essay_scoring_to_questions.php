<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Essay scoring mode: full atau range
            $table->string('essay_scoring_mode')->default('full')->after('default_weight');
            // Skor untuk jawaban benar (default pakai default_weight)
            $table->decimal('essay_score_correct', 8, 2)->nullable()->after('essay_scoring_mode');
            // Skor untuk jawaban salah
            $table->decimal('essay_score_wrong', 8, 2)->default(0)->after('essay_score_correct');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['essay_scoring_mode', 'essay_score_correct', 'essay_score_wrong']);
        });
    }
};
