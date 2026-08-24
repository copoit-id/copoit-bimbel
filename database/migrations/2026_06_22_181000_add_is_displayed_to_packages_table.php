<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('packages') || Schema::hasColumn('packages', 'is_displayed')) {
            return;
        }

        Schema::table('packages', function (Blueprint $table) {
            $table->boolean('is_displayed')->default(true)->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('packages') || ! Schema::hasColumn('packages', 'is_displayed')) {
            return;
        }

        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('is_displayed');
        });
    }
};
