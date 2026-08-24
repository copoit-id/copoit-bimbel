<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['tryouts', 'materials', 'tes_korans'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'type_price')) {
                    $table->string('type_price', 30)->default('paid')->after('is_for_sale');
                }

                if (! Schema::hasColumn($tableName, 'conditional_requirement')) {
                    $table->text('conditional_requirement')->nullable()->after('type_price');
                }
            });

            DB::table($tableName)
                ->whereNull('type_price')
                ->orWhere('type_price', '')
                ->update(['type_price' => 'paid']);
        }
    }

    public function down(): void
    {
        foreach (['tes_korans', 'materials', 'tryouts'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'conditional_requirement')) {
                    $table->dropColumn('conditional_requirement');
                }

                if (Schema::hasColumn($tableName, 'type_price')) {
                    $table->dropColumn('type_price');
                }
            });
        }
    }
};
