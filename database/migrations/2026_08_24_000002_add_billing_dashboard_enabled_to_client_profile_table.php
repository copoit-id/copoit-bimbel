<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_profile') || Schema::hasColumn('client_profile', 'billing_dashboard_enabled')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $table->boolean('billing_dashboard_enabled')->default(true);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_profile') || ! Schema::hasColumn('client_profile', 'billing_dashboard_enabled')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $table->dropColumn('billing_dashboard_enabled');
        });
    }
};
