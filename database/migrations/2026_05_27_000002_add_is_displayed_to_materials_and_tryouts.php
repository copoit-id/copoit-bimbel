<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (!Schema::hasColumn('materials', 'is_displayed')) {
                $table->boolean('is_displayed')->default(true)->after('is_for_sale');
            }
        });

        Schema::table('tryouts', function (Blueprint $table) {
            if (!Schema::hasColumn('tryouts', 'is_displayed')) {
                $table->boolean('is_displayed')->default(true)->after('is_for_sale');
            }
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (Schema::hasColumn('materials', 'is_displayed')) {
                $table->dropColumn('is_displayed');
            }
        });

        Schema::table('tryouts', function (Blueprint $table) {
            if (Schema::hasColumn('tryouts', 'is_displayed')) {
                $table->dropColumn('is_displayed');
            }
        });
    }
};
