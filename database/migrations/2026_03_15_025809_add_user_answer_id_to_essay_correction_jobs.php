<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('essay_correction_jobs', function (Blueprint $table) {
            // Kolom sudah ada dari migration 2026_03_14_170000
            // Migration ini sudah tidak diperlukan lagi
            if (!Schema::hasColumn('essay_correction_jobs', 'user_answer_id')) {
                // Link ke attempt spesifik (per token)
                $table->foreignId('user_answer_id')->nullable()->after('user_id')
                      ->constrained('user_answers', 'user_answer_id')
                      ->nullOnDelete();
                
                $table->index(['user_answer_id', 'status']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('essay_correction_jobs', function (Blueprint $table) {
            $table->dropForeign(['user_answer_id']);
            $table->dropColumn('user_answer_id');
            $table->dropIndex(['user_answer_id', 'status']);
        });
    }
};
