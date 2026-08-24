<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tes_korans', function (Blueprint $table) {
            if (!Schema::hasColumn('tes_korans', 'price')) {
                $table->decimal('price', 12, 0)->nullable()->after('rows_count');
            }

            if (!Schema::hasColumn('tes_korans', 'is_for_sale')) {
                $table->boolean('is_for_sale')->default(false)->after('price');
            }

            if (!Schema::hasColumn('tes_korans', 'is_displayed')) {
                $table->boolean('is_displayed')->default(true)->after('is_for_sale');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tes_korans', function (Blueprint $table) {
            if (Schema::hasColumn('tes_korans', 'is_displayed')) {
                $table->dropColumn('is_displayed');
            }

            if (Schema::hasColumn('tes_korans', 'is_for_sale')) {
                $table->dropColumn('is_for_sale');
            }

            if (Schema::hasColumn('tes_korans', 'price')) {
                $table->dropColumn('price');
            }
        });
    }
};
