<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_profile') || Schema::hasColumn('client_profile', 'admin_tours_enabled')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $table->boolean('admin_tours_enabled')->default(false);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_profile') || ! Schema::hasColumn('client_profile', 'admin_tours_enabled')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $table->dropColumn('admin_tours_enabled');
        });
    }
};
