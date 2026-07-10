<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('study_groups')) {
            Schema::create('study_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('tentor_id')
                    ->nullable()
                    ->constrained('tentors')
                    ->nullOnDelete();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'name']);
            });
        }

        if (!Schema::hasTable('study_group_user')) {
            Schema::create('study_group_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('study_group_id')
                    ->constrained('study_groups')
                    ->cascadeOnDelete();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['study_group_id', 'user_id']);
                $table->index('user_id');
            });
        }

        if (Schema::hasTable('class_schedules') && !Schema::hasColumn('class_schedules', 'study_group_id')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->foreignId('study_group_id')
                    ->nullable()
                    ->after('class_id')
                    ->constrained('study_groups')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('class_sessions') && !Schema::hasColumn('class_sessions', 'study_group_id')) {
            Schema::table('class_sessions', function (Blueprint $table) {
                $table->foreignId('study_group_id')
                    ->nullable()
                    ->after('class_id')
                    ->constrained('study_groups')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('class_sessions') && Schema::hasColumn('class_sessions', 'study_group_id')) {
            Schema::table('class_sessions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('study_group_id');
            });
        }

        if (Schema::hasTable('class_schedules') && Schema::hasColumn('class_schedules', 'study_group_id')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->dropConstrainedForeignId('study_group_id');
            });
        }

        Schema::dropIfExists('study_group_user');
        Schema::dropIfExists('study_groups');
    }
};
