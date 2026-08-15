<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_profile') || Schema::hasColumn('client_profile', 'tutor_content_enabled')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $column = $table->boolean('tutor_content_enabled')->default(false);

            if (Schema::hasColumn('client_profile', 'learning_progress_enabled')) {
                $column->after('learning_progress_enabled');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_profile') || ! Schema::hasColumn('client_profile', 'tutor_content_enabled')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $table->dropColumn('tutor_content_enabled');
        });
    }
};
