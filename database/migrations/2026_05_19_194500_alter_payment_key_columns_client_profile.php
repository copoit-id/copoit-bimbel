<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_profile', function (Blueprint $table) {
            $table->string('midtrans_server_key', 500)->nullable()->change();
            $table->string('midtrans_client_key', 500)->nullable()->change();
            $table->string('xendit_secret_key', 500)->nullable()->change();
            $table->string('xendit_webhook_token', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('client_profile', function (Blueprint $table) {
            $table->string('midtrans_server_key', 255)->nullable()->change();
            $table->string('midtrans_client_key', 255)->nullable()->change();
            $table->string('xendit_secret_key', 255)->nullable()->change();
            $table->string('xendit_webhook_token', 255)->nullable()->change();
        });
    }
};