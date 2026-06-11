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
            if (!Schema::hasColumn('discounts', 'application_type')) {
                $table->string('application_type', 30)->default('voucher')->after('description');
            }

            if (!Schema::hasColumn('discounts', 'tryout_id')) {
                $table->foreignId('tryout_id')
                    ->nullable()
                    ->after('application_type')
                    ->constrained('tryouts', 'tryout_id')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('discounts')) {
            return;
        }

        Schema::table('discounts', function (Blueprint $table) {
            if (Schema::hasColumn('discounts', 'tryout_id')) {
                $table->dropConstrainedForeignId('tryout_id');
            }

            if (Schema::hasColumn('discounts', 'application_type')) {
                $table->dropColumn('application_type');
            }
        });
    }
};
