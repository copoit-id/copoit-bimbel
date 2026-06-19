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
            if (!Schema::hasColumn('client_profile', 'ipaymu_api_key')) {
                $table->longText('ipaymu_api_key')->nullable()->after('interactive_qris_use_tip');
            }

            if (!Schema::hasColumn('client_profile', 'ipaymu_va')) {
                $table->string('ipaymu_va', 100)->nullable()->after('ipaymu_api_key');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_profile')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            if (Schema::hasColumn('client_profile', 'ipaymu_va')) {
                $table->dropColumn('ipaymu_va');
            }

            if (Schema::hasColumn('client_profile', 'ipaymu_api_key')) {
                $table->dropColumn('ipaymu_api_key');
            }
        });
    }
};
