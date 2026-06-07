<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'unique_code')) {
                $table->unsignedSmallInteger('unique_code')->nullable()->after('admin_fee');
            }

            if (!Schema::hasColumn('payments', 'unique_code_date')) {
                $table->date('unique_code_date')->nullable()->after('unique_code');
            }
        });

        if (!Schema::hasIndex('payments', 'payments_manual_unique_code_date_status_unique')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unique(
                    ['payment_method', 'status', 'unique_code', 'unique_code_date'],
                    'payments_manual_unique_code_date_status_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('payments', 'payments_manual_unique_code_date_status_unique')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropUnique('payments_manual_unique_code_date_status_unique');
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'unique_code_date')) {
                $table->dropColumn('unique_code_date');
            }

            if (Schema::hasColumn('payments', 'unique_code')) {
                $table->dropColumn('unique_code');
            }
        });
    }
};
