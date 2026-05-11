<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tes_koran_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tes_koran_id');
            $table->unsignedBigInteger('user_id');
            $table->string('attempt_token');
            $table->integer('total_correct')->default(0);
            $table->integer('total_wrong')->default(0);
            $table->integer('total_skipped')->default(0);
            $table->json('column_scores')->nullable();
            $table->decimal('speed_score', 8, 2)->default(0);
            $table->decimal('accuracy_score', 8, 2)->default(0);
            $table->decimal('stability_score', 8, 2)->default(0);
            $table->enum('stability_status', ['meningkat', 'menurun', 'datar'])->nullable();
            $table->enum('final_result', ['rendah', 'sedang', 'tinggi'])->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->timestamps();

            $table->foreign('tes_koran_id')->references('id')->on('tes_korans')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_koran_results');
    }
};
