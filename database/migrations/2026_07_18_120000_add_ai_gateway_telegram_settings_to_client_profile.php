<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_profile') || Schema::hasColumn('client_profile', 'ai_gateway_telegram_settings')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $table->longText('ai_gateway_telegram_settings')
                ->nullable()
                ->after('ai_gateway_payment_settings');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_profile') || ! Schema::hasColumn('client_profile', 'ai_gateway_telegram_settings')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $table->dropColumn('ai_gateway_telegram_settings');
        });
    }
};
