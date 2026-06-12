<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['packages', 'tryouts', 'materials', 'tes_korans'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'access_duration_value')) {
                    $table->unsignedInteger('access_duration_value')->nullable();
                }

                if (!Schema::hasColumn($tableName, 'access_duration_unit')) {
                    $table->string('access_duration_unit', 20)->default('forever');
                }
            });
        }

        if (Schema::hasTable('individual_purchases') && !Schema::hasColumn('individual_purchases', 'access_expires_at')) {
            Schema::table('individual_purchases', function (Blueprint $table) {
                $table->timestamp('access_expires_at')->nullable()->after('approved_at');
            });
        }

        if (Schema::hasTable('user_material_access') && !Schema::hasColumn('user_material_access', 'expires_at')) {
            Schema::table('user_material_access', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('completed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_material_access') && Schema::hasColumn('user_material_access', 'expires_at')) {
            Schema::table('user_material_access', function (Blueprint $table) {
                $table->dropColumn('expires_at');
            });
        }

        if (Schema::hasTable('individual_purchases') && Schema::hasColumn('individual_purchases', 'access_expires_at')) {
            Schema::table('individual_purchases', function (Blueprint $table) {
                $table->dropColumn('access_expires_at');
            });
        }

        foreach (['tes_korans', 'materials', 'tryouts', 'packages'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'access_duration_value')) {
                    $table->dropColumn('access_duration_value');
                }

                if (Schema::hasColumn($tableName, 'access_duration_unit')) {
                    $table->dropColumn('access_duration_unit');
                }
            });
        }
    }
};
