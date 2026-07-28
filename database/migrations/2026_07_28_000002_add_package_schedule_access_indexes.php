<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DETAIL_REVERSE_INDEX = 'detail_packages_item_package_index';

    private const ACTIVE_ACCESS_INDEX = 'user_package_active_access_index';

    public function up(): void
    {
        if (Schema::hasTable('detail_packages')
            && ! Schema::hasIndex('detail_packages', self::DETAIL_REVERSE_INDEX)) {
            Schema::table('detail_packages', function (Blueprint $table): void {
                $table->index(
                    ['detailable_type', 'detailable_id', 'package_id'],
                    self::DETAIL_REVERSE_INDEX
                );
            });
        }

        if (Schema::hasTable('user_package_access')
            && ! Schema::hasIndex('user_package_access', self::ACTIVE_ACCESS_INDEX)) {
            Schema::table('user_package_access', function (Blueprint $table): void {
                $table->index(
                    ['user_id', 'status', 'end_date'],
                    self::ACTIVE_ACCESS_INDEX
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('detail_packages')
            && Schema::hasIndex('detail_packages', self::DETAIL_REVERSE_INDEX)) {
            Schema::table('detail_packages', function (Blueprint $table): void {
                $table->dropIndex(self::DETAIL_REVERSE_INDEX);
            });
        }

        if (Schema::hasTable('user_package_access')
            && Schema::hasIndex('user_package_access', self::ACTIVE_ACCESS_INDEX)) {
            Schema::table('user_package_access', function (Blueprint $table): void {
                $table->dropIndex(self::ACTIVE_ACCESS_INDEX);
            });
        }
    }
};
