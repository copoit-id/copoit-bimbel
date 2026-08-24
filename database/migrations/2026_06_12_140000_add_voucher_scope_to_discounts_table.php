<?php

use App\Models\Discount;
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
            if (!Schema::hasColumn('discounts', 'applicable_package_ids')) {
                $table->json('applicable_package_ids')->nullable()->after('description');
            }

            if (!Schema::hasColumn('discounts', 'applicable_purchase_types')) {
                $table->json('applicable_purchase_types')->nullable()->after('applicable_package_ids');
            }
        });

        // Backfill existing vouchers so they keep working for all package purchases.
        Discount::query()
            ->where('application_type', Discount::TYPE_VOUCHER)
            ->whereNull('applicable_purchase_types')
            ->update([
                'applicable_purchase_types' => json_encode(['package']),
                'applicable_package_ids' => null,
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('discounts')) {
            return;
        }

        Schema::table('discounts', function (Blueprint $table) {
            if (Schema::hasColumn('discounts', 'applicable_purchase_types')) {
                $table->dropColumn('applicable_purchase_types');
            }

            if (Schema::hasColumn('discounts', 'applicable_package_ids')) {
                $table->dropColumn('applicable_package_ids');
            }
        });
    }
};
