<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_gateway_plans') && ! Schema::hasColumn('ai_gateway_plans', 'scope')) {
            Schema::table('ai_gateway_plans', function (Blueprint $table): void {
                $table->string('scope', 40)->default('learning_tools')->after('slug');
                $table->index(['scope', 'is_active'], 'aigw_plans_scope_active_idx');
            });
        }

        if (Schema::hasTable('ai_gateway_subscriptions') && ! Schema::hasColumn('ai_gateway_subscriptions', 'scope')) {
            Schema::table('ai_gateway_subscriptions', function (Blueprint $table): void {
                $table->string('scope', 40)->default('learning_tools')->after('ai_gateway_plan_id');
                $table->index(['ai_gateway_client_id', 'external_user_id', 'scope', 'status'], 'aigw_subscriptions_scope_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_gateway_subscriptions')) {
            Schema::table('ai_gateway_subscriptions', function (Blueprint $table): void {
                if (Schema::hasIndex('ai_gateway_subscriptions', 'aigw_subscriptions_scope_status_idx')) {
                    $table->dropIndex('aigw_subscriptions_scope_status_idx');
                }
                if (Schema::hasColumn('ai_gateway_subscriptions', 'scope')) {
                    $table->dropColumn('scope');
                }
            });
        }

        if (Schema::hasTable('ai_gateway_plans')) {
            Schema::table('ai_gateway_plans', function (Blueprint $table): void {
                if (Schema::hasIndex('ai_gateway_plans', 'aigw_plans_scope_active_idx')) {
                    $table->dropIndex('aigw_plans_scope_active_idx');
                }
                if (Schema::hasColumn('ai_gateway_plans', 'scope')) {
                    $table->dropColumn('scope');
                }
            });
        }
    }
};
