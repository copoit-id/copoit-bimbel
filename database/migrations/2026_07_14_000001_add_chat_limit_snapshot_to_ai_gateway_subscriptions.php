<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_gateway_subscriptions') || Schema::hasColumn('ai_gateway_subscriptions', 'chat_limit')) {
            return;
        }

        Schema::table('ai_gateway_subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('chat_limit')->default(0)->after('token_limit');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_gateway_subscriptions') || ! Schema::hasColumn('ai_gateway_subscriptions', 'chat_limit')) {
            return;
        }

        Schema::table('ai_gateway_subscriptions', function (Blueprint $table) {
            $table->dropColumn('chat_limit');
        });
    }
};
