<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('discounts')) {
            return;
        }

        Schema::table('discounts', function (Blueprint $table) {
            if (!Schema::hasColumn('discounts', 'applicable_tryout_ids')) {
                $table->json('applicable_tryout_ids')->nullable()->after('applicable_package_ids');
            }

            if (!Schema::hasColumn('discounts', 'applicable_material_ids')) {
                $table->json('applicable_material_ids')->nullable()->after('applicable_tryout_ids');
            }

            if (!Schema::hasColumn('discounts', 'applicable_tes_koran_ids')) {
                $table->json('applicable_tes_koran_ids')->nullable()->after('applicable_material_ids');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('discounts')) {
            return;
        }

        Schema::table('discounts', function (Blueprint $table) {
            foreach (['applicable_tes_koran_ids', 'applicable_material_ids', 'applicable_tryout_ids'] as $column) {
                if (Schema::hasColumn('discounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
