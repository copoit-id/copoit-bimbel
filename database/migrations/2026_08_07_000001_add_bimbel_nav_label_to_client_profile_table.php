<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_profile') || Schema::hasColumn('client_profile', 'bimbel_nav_label')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $table->string('bimbel_nav_label', 80)->default('Bimbel')->after('live_session_label');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_profile') || ! Schema::hasColumn('client_profile', 'bimbel_nav_label')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $table->dropColumn('bimbel_nav_label');
        });
    }
};
