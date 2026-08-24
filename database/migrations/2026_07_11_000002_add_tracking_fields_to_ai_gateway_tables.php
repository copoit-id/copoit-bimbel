<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_gateway_clients') && !Schema::hasColumn('ai_gateway_clients', 'base_url')) {
            Schema::table('ai_gateway_clients', function (Blueprint $table) {
                $table->string('base_url', 2048)->nullable()->after('slug');
            });
        }

        if (Schema::hasTable('ai_gateway_usage_logs')) {
            Schema::table('ai_gateway_usage_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('ai_gateway_usage_logs', 'external_user_name')) {
                    $table->string('external_user_name', 255)->nullable()->after('external_user_id');
                }
                if (!Schema::hasColumn('ai_gateway_usage_logs', 'external_user_email')) {
                    $table->string('external_user_email', 255)->nullable()->after('external_user_name');
                }
                if (!Schema::hasColumn('ai_gateway_usage_logs', 'origin_base_url')) {
                    $table->string('origin_base_url', 2048)->nullable()->after('question_reference');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_gateway_usage_logs')) {
            Schema::table('ai_gateway_usage_logs', function (Blueprint $table) {
                $columns = ['external_user_name', 'external_user_email', 'origin_base_url'];
                $existing = array_filter($columns, fn (string $column) => Schema::hasColumn('ai_gateway_usage_logs', $column));
                if ($existing !== []) {
                    $table->dropColumn($existing);
                }
            });
        }

        if (Schema::hasTable('ai_gateway_clients') && Schema::hasColumn('ai_gateway_clients', 'base_url')) {
            Schema::table('ai_gateway_clients', function (Blueprint $table) {
                $table->dropColumn('base_url');
            });
        }
    }
};
