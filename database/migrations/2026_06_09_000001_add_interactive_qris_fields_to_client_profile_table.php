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
            if (!Schema::hasColumn('client_profile', 'interactive_qris_api_key')) {
                $table->string('interactive_qris_api_key', 500)->nullable()->after('midtrans_client_key');
            }

            if (!Schema::hasColumn('client_profile', 'interactive_qris_mid')) {
                $table->string('interactive_qris_mid', 100)->nullable()->after('interactive_qris_api_key');
            }

            if (!Schema::hasColumn('client_profile', 'interactive_qris_use_tip')) {
                $table->boolean('interactive_qris_use_tip')->default(false)->after('interactive_qris_mid');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            foreach (['interactive_qris_use_tip', 'interactive_qris_mid', 'interactive_qris_api_key'] as $column) {
                if (Schema::hasColumn('client_profile', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
