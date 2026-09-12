<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_tour_progress')) {
            return;
        }

        Schema::create('admin_tour_progress', function (Blueprint $table): void {
            $table->id('admin_tour_progress_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tour_key', 120);
            $table->unsignedInteger('tour_version');
            $table->string('status', 20);
            $table->string('current_step_id', 120)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tour_key', 'tour_version'], 'admin_tour_progress_user_tour_unique');
            $table->index(['status', 'updated_at'], 'admin_tour_progress_status_updated_index');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('admin_tour_progress')) {
            Schema::drop('admin_tour_progress');
        }
    }
};
