<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_profile')) {
            Schema::table('client_profile', function (Blueprint $table) {
                if (!Schema::hasColumn('client_profile', 'payment_bank_name')) {
                    $table->string('payment_bank_name')->nullable()->after('payment_mode');
                }
                if (!Schema::hasColumn('client_profile', 'payment_account_number')) {
                    $table->string('payment_account_number')->nullable()->after('payment_bank_name');
                }
                if (!Schema::hasColumn('client_profile', 'payment_account_holder')) {
                    $table->string('payment_account_holder')->nullable()->after('payment_account_number');
                }
                if (!Schema::hasColumn('client_profile', 'payment_bank_note')) {
                    $table->string('payment_bank_note')->nullable()->after('payment_account_holder');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('client_profile')) {
            Schema::table('client_profile', function (Blueprint $table) {
                if (Schema::hasColumn('client_profile', 'payment_bank_note')) {
                    $table->dropColumn('payment_bank_note');
                }
                if (Schema::hasColumn('client_profile', 'payment_account_holder')) {
                    $table->dropColumn('payment_account_holder');
                }
                if (Schema::hasColumn('client_profile', 'payment_account_number')) {
                    $table->dropColumn('payment_account_number');
                }
                if (Schema::hasColumn('client_profile', 'payment_bank_name')) {
                    $table->dropColumn('payment_bank_name');
                }
            });
        }
    }
};
