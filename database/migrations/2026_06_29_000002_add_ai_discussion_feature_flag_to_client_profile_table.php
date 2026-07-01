<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            if (!Schema::hasColumn('client_profile', 'ai_discussion_feature_enabled')) {
                $column = $table->boolean('ai_discussion_feature_enabled')->default(false);

                if (Schema::hasColumn('client_profile', 'ai_discussion_settings')) {
                    $column->after('ai_discussion_settings');
                } elseif (Schema::hasColumn('client_profile', 'ai_question_generator_settings')) {
                    $column->after('ai_question_generator_settings');
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            if (Schema::hasColumn('client_profile', 'ai_discussion_feature_enabled')) {
                $table->dropColumn('ai_discussion_feature_enabled');
            }
        });
    }
};
