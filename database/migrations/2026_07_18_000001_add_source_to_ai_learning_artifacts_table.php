<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_learning_artifacts')) {
            return;
        }

        Schema::table('ai_learning_artifacts', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_learning_artifacts', 'source_type')) {
                $table->string('source_type', 30)->default('discussion')->after('attempt_token');
            }

            if (! Schema::hasColumn('ai_learning_artifacts', 'source_label')) {
                $table->string('source_label', 160)->nullable()->after('source_type');
            }
        });

        Schema::table('ai_learning_artifacts', function (Blueprint $table) {
            $table->index(['user_id', 'source_type', 'created_at'], 'ai_learning_artifacts_user_source_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_learning_artifacts')) {
            return;
        }

        Schema::table('ai_learning_artifacts', function (Blueprint $table) {
            $table->dropIndex('ai_learning_artifacts_user_source_index');
            if (Schema::hasColumn('ai_learning_artifacts', 'source_label')) {
                $table->dropColumn('source_label');
            }
            if (Schema::hasColumn('ai_learning_artifacts', 'source_type')) {
                $table->dropColumn('source_type');
            }
        });
    }
};
