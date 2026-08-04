<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_gateway_usage_logs')
            || Schema::hasColumn('ai_gateway_usage_logs', 'feature')) {
            return;
        }

        Schema::table('ai_gateway_usage_logs', function (Blueprint $table) {
            $table->string('feature', 40)->default('discussion')->after('question_reference');
            $table->index(['feature', 'created_at'], 'ai_gateway_usage_logs_feature_created_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_gateway_usage_logs')
            || ! Schema::hasColumn('ai_gateway_usage_logs', 'feature')) {
            return;
        }

        Schema::table('ai_gateway_usage_logs', function (Blueprint $table) {
            $table->dropIndex('ai_gateway_usage_logs_feature_created_index');
            $table->dropColumn('feature');
        });
    }
};
