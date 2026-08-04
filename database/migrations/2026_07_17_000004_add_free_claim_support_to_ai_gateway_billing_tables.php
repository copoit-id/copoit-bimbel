<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_gateway_subscriptions')) {
            return;
        }

        if (! Schema::hasColumn('ai_gateway_subscriptions', 'free_claim_key')) {
            Schema::table('ai_gateway_subscriptions', function (Blueprint $table) {
                $table->string('free_claim_key', 64)->nullable()->after('external_user_email');
            });
        }

        if (! Schema::hasIndex('ai_gateway_subscriptions', 'ai_gateway_subscriptions_free_claim_key_unique')) {
            Schema::table('ai_gateway_subscriptions', function (Blueprint $table) {
                $table->unique('free_claim_key', 'ai_gateway_subscriptions_free_claim_key_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_gateway_subscriptions')) {
            return;
        }

        if (Schema::hasIndex('ai_gateway_subscriptions', 'ai_gateway_subscriptions_free_claim_key_unique')) {
            Schema::table('ai_gateway_subscriptions', function (Blueprint $table) {
                $table->dropUnique('ai_gateway_subscriptions_free_claim_key_unique');
            });
        }

        if (Schema::hasColumn('ai_gateway_subscriptions', 'free_claim_key')) {
            Schema::table('ai_gateway_subscriptions', function (Blueprint $table) {
                $table->dropColumn('free_claim_key');
            });
        }
    }
};
