<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('individual_purchases')) {
            return;
        }

        Schema::table('individual_purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('individual_purchases', 'discount_id')) {
                $table->foreignId('discount_id')
                    ->nullable()
                    ->after('purchasable_id')
                    ->constrained('discounts')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('individual_purchases', 'discount_code')) {
                $table->string('discount_code')->nullable()->after('discount_id');
            }

            if (!Schema::hasColumn('individual_purchases', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 0)->default(0)->after('discount_code');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('individual_purchases')) {
            return;
        }

        Schema::table('individual_purchases', function (Blueprint $table) {
            if (Schema::hasColumn('individual_purchases', 'discount_id')) {
                $table->dropForeign(['discount_id']);
                $table->dropColumn(['discount_id', 'discount_code', 'discount_amount']);
            }
        });
    }
};
