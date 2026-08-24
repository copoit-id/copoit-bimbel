<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_profile') && ! Schema::hasColumn('client_profile', 'tutor_content_visibility')) {
            Schema::table('client_profile', function (Blueprint $table): void {
                $table->string('tutor_content_visibility', 20)->default('shared');
            });
        }

        if (Schema::hasTable('tryouts') && ! Schema::hasColumn('tryouts', 'created_by')) {
            Schema::table('tryouts', function (Blueprint $table): void {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('question_banks') && Schema::hasColumn('question_banks', 'created_by')) {
            Schema::table('question_banks', function (Blueprint $table): void {
                $table->index('created_by', 'question_banks_created_by_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('question_banks') && Schema::hasColumn('question_banks', 'created_by')) {
            Schema::table('question_banks', function (Blueprint $table): void {
                $table->dropIndex('question_banks_created_by_index');
            });
        }

        if (Schema::hasTable('tryouts') && Schema::hasColumn('tryouts', 'created_by')) {
            Schema::table('tryouts', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('created_by');
            });
        }

        if (Schema::hasTable('client_profile') && Schema::hasColumn('client_profile', 'tutor_content_visibility')) {
            Schema::table('client_profile', function (Blueprint $table): void {
                $table->dropColumn('tutor_content_visibility');
            });
        }
    }
};
