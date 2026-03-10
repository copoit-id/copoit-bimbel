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
            if (!Schema::hasColumn('client_profile', 'payment_gateway')) {
                $table->string('payment_gateway', 30)->nullable();
            }
            if (!Schema::hasColumn('client_profile', 'payment_gateway_mode')) {
                $table->string('payment_gateway_mode', 20)->nullable();
            }
            if (!Schema::hasColumn('client_profile', 'xendit_secret_key')) {
                $table->string('xendit_secret_key')->nullable();
            }
            if (!Schema::hasColumn('client_profile', 'xendit_webhook_token')) {
                $table->string('xendit_webhook_token')->nullable();
            }
            if (!Schema::hasColumn('client_profile', 'midtrans_server_key')) {
                $table->string('midtrans_server_key')->nullable();
            }
            if (!Schema::hasColumn('client_profile', 'midtrans_client_key')) {
                $table->string('midtrans_client_key')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            if (Schema::hasColumn('client_profile', 'midtrans_client_key')) {
                $table->dropColumn('midtrans_client_key');
            }
            if (Schema::hasColumn('client_profile', 'midtrans_server_key')) {
                $table->dropColumn('midtrans_server_key');
            }
            if (Schema::hasColumn('client_profile', 'xendit_webhook_token')) {
                $table->dropColumn('xendit_webhook_token');
            }
            if (Schema::hasColumn('client_profile', 'xendit_secret_key')) {
                $table->dropColumn('xendit_secret_key');
            }
            if (Schema::hasColumn('client_profile', 'payment_gateway_mode')) {
                $table->dropColumn('payment_gateway_mode');
            }
            if (Schema::hasColumn('client_profile', 'payment_gateway')) {
                $table->dropColumn('payment_gateway');
            }
        });
    }
};
