<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('essay_correction_jobs', function (Blueprint $table) {
            // Link ke attempt spesifik (per token)
            $table->foreignId('user_answer_id')->nullable()->after('user_id')
                  ->constrained('user_answers', 'user_answer_id')
                  ->nullOnDelete();
            
            // AI Service related
            $table->string('ai_job_id')->nullable()->after('id')->index();
            $table->string('method')->default('semantic')->after('job_type');
            $table->float('threshold')->default(0.6)->after('method');
            $table->text('callback_url')->nullable()->after('threshold');
            
            // Processing details
            $table->integer('estimated_time_seconds')->nullable()->after('total_essays');
            $table->timestamp('queued_at')->nullable()->after('started_at');
            
            // Results from AI
            $table->float('total_similarity_score')->nullable()->after('incorrect_count');
            $table->integer('processing_time_ms')->nullable()->after('total_similarity_score');
            
            $table->index(['user_answer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('essay_correction_jobs', function (Blueprint $table) {
            $table->dropForeign(['user_answer_id']);
            $table->dropIndex(['user_answer_id', 'status']);
            $table->dropColumn([
                'user_answer_id',
                'ai_job_id',
                'method',
                'threshold',
                'callback_url',
                'estimated_time_seconds',
                'queued_at',
                'total_similarity_score',
                'processing_time_ms',
            ]);
        });
    }
};
