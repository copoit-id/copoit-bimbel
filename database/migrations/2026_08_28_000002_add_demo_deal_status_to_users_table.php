<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'demo_deal_status')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('demo_deal_status', 32)->default('baru');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'demo_deal_status')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('demo_deal_status');
        });
    }
};
