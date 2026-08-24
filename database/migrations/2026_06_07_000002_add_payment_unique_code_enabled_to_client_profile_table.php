<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_profile') && !Schema::hasColumn('client_profile', 'payment_unique_code_enabled')) {
            Schema::table('client_profile', function (Blueprint $table) {
                $table->boolean('payment_unique_code_enabled')->default(true)->after('payment_bank_note');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('client_profile') && Schema::hasColumn('client_profile', 'payment_unique_code_enabled')) {
            Schema::table('client_profile', function (Blueprint $table) {
                $table->dropColumn('payment_unique_code_enabled');
            });
        }
    }
};
