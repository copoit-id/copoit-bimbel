<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_gateway_subscriptions') || Schema::hasColumn('ai_gateway_subscriptions', 'token_limit')) {
            return;
        }

        Schema::table('ai_gateway_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('token_limit')->default(0)->after('ai_gateway_plan_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ai_gateway_subscriptions') || !Schema::hasColumn('ai_gateway_subscriptions', 'token_limit')) {
            return;
        }

        Schema::table('ai_gateway_subscriptions', function (Blueprint $table) {
            $table->dropColumn('token_limit');
        });
    }
};
