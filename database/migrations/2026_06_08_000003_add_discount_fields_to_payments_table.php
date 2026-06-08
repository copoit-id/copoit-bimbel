<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'discount_id')) {
                $table->foreignId('discount_id')->nullable()->after('package_id')->constrained('discounts')->nullOnDelete();
            }

            if (!Schema::hasColumn('payments', 'discount_code')) {
                $table->string('discount_code')->nullable()->after('discount_id');
            }

            if (!Schema::hasColumn('payments', 'original_amount')) {
                $table->decimal('original_amount', 12, 0)->nullable()->after('discount_code');
            }

            if (!Schema::hasColumn('payments', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 0)->default(0)->after('original_amount');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'discount_id')) {
                $table->dropConstrainedForeignId('discount_id');
            }

            foreach (['discount_code', 'original_amount', 'discount_amount'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
