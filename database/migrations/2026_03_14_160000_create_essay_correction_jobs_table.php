<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('essay_correction_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tryout_id')->constrained('tryouts', 'tryout_id')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->string('job_type')->default('bulk'); // bulk, single
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->integer('total_essays')->default(0);
            $table->integer('processed_essays')->default(0);
            $table->integer('correct_count')->default(0);
            $table->integer('incorrect_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('tryout_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('essay_correction_jobs');
    }
};
