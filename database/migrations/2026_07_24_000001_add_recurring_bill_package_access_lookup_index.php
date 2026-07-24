<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'user_package_access_recurring_bill_lookup_index';

    public function up(): void
    {
        if (! Schema::hasTable('user_package_access')
            || Schema::hasIndex('user_package_access', self::INDEX_NAME)) {
            return;
        }

        Schema::table('user_package_access', function (Blueprint $table): void {
            $table->index(
                ['package_id', 'status', 'start_date', 'end_date'],
                self::INDEX_NAME,
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_package_access')
            || ! Schema::hasIndex('user_package_access', self::INDEX_NAME)) {
            return;
        }

        Schema::table('user_package_access', function (Blueprint $table): void {
            $table->dropIndex(self::INDEX_NAME);
        });
    }
};
