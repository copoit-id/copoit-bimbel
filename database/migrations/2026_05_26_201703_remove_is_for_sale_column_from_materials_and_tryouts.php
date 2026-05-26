<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (Schema::hasColumn('materials', 'is_for_sale')) {
                $table->dropColumn('is_for_sale');
            }
        });

        Schema::table('tryouts', function (Blueprint $table) {
            if (Schema::hasColumn('tryouts', 'is_for_sale')) {
                $table->dropColumn('is_for_sale');
            }
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (!Schema::hasColumn('materials', 'is_for_sale')) {
                $table->boolean('is_for_sale')->default(false)->after('price');
            }
        });

        Schema::table('tryouts', function (Blueprint $table) {
            if (!Schema::hasColumn('tryouts', 'is_for_sale')) {
                $table->boolean('is_for_sale')->default(false)->after('price');
            }
        });
    }
};
