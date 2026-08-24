<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_gateway_subscriptions')) {
            return;
        }

        Schema::table('ai_gateway_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_gateway_subscriptions', 'external_user_id')) {
                $table->string('external_user_id', 120)->nullable()->after('ai_gateway_plan_id');
            }
            if (!Schema::hasColumn('ai_gateway_subscriptions', 'external_user_name')) {
                $table->string('external_user_name', 255)->nullable()->after('external_user_id');
            }
            if (!Schema::hasColumn('ai_gateway_subscriptions', 'external_user_email')) {
                $table->string('external_user_email', 255)->nullable()->after('external_user_name');
            }
            $table->index(['ai_gateway_client_id', 'external_user_id', 'status'], 'aigw_subscription_user_status_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ai_gateway_subscriptions')) {
            return;
        }

        Schema::table('ai_gateway_subscriptions', function (Blueprint $table) {
            $columns = ['external_user_id', 'external_user_name', 'external_user_email'];
            $existing = array_filter($columns, fn (string $column) => Schema::hasColumn('ai_gateway_subscriptions', $column));
            if ($existing !== []) {
                $table->dropIndex('aigw_subscription_user_status_idx');
                $table->dropColumn($existing);
            }
        });
    }
};
