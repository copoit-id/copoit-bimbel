<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('packages') && Schema::hasColumn('packages', 'type_package')) {
            DB::statement("ALTER TABLE packages MODIFY COLUMN type_package ENUM('bimbel', 'tryout', 'sertifikasi', 'tes_koran', 'kecermatan') NOT NULL DEFAULT 'bimbel'");
        }

        if (!Schema::hasTable('kecermatans')) {
            Schema::create('kecermatans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->enum('type', ['kecermatan_polri', 'kecermatan_tni']);
                $table->text('description')->nullable();
                $table->unsignedInteger('price')->default(0);
                $table->boolean('is_for_sale')->default(false);
                $table->boolean('is_displayed')->default(false);
                $table->boolean('is_active')->default(false);
                $table->unsignedInteger('access_duration_value')->nullable();
                $table->string('access_duration_unit', 20)->default('forever');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kecermatan_columns')) {
            Schema::create('kecermatan_columns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kecermatan_id')->constrained('kecermatans')->cascadeOnDelete();
                $table->string('name');
                $table->unsignedInteger('sort_order')->default(1);
                $table->unsignedInteger('duration_seconds')->default(60);
                $table->unsignedInteger('questions_count')->default(50);
                $table->enum('column_type', ['simbol', 'huruf', 'angka', 'campuran'])->default('huruf');
                $table->json('references')->nullable();
                $table->timestamps();

                $table->index(['kecermatan_id', 'sort_order']);
            });
        }

        if (!Schema::hasTable('kecermatan_questions')) {
            Schema::create('kecermatan_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kecermatan_column_id')->constrained('kecermatan_columns')->cascadeOnDelete();
                $table->unsignedInteger('sort_order')->default(1);
                $table->json('payload');
                $table->string('correct_answer', 50);
                $table->timestamps();

                $table->index(['kecermatan_column_id', 'sort_order']);
            });
        }

        if (!Schema::hasTable('kecermatan_attempts')) {
            Schema::create('kecermatan_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kecermatan_id')->constrained('kecermatans')->cascadeOnDelete();
                $table->foreignId('kecermatan_column_id')->constrained('kecermatan_columns')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->uuid('attempt_token');
                $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->unsignedInteger('correct_answers')->default(0);
                $table->unsignedInteger('wrong_answers')->default(0);
                $table->unsignedInteger('unanswered')->default(0);
                $table->decimal('score', 8, 2)->default(0);
                $table->json('answers')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'kecermatan_column_id', 'attempt_token'], 'kecermatan_attempt_unique');
                $table->index(['user_id', 'kecermatan_id', 'attempt_token'], 'kecermatan_attempt_lookup');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kecermatan_attempts');
        Schema::dropIfExists('kecermatan_questions');
        Schema::dropIfExists('kecermatan_columns');
        Schema::dropIfExists('kecermatans');

        if (Schema::hasTable('packages') && Schema::hasColumn('packages', 'type_package')) {
            DB::statement("ALTER TABLE packages MODIFY COLUMN type_package ENUM('bimbel', 'tryout', 'sertifikasi', 'tes_koran') NOT NULL DEFAULT 'bimbel'");
        }
    }
};
