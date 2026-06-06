<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('material_categories')) {
            return;
        }

        if (!Schema::hasColumn('material_categories', 'parent_id')) {
            Schema::table('material_categories', function (Blueprint $table) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('category_id');
                $table->index(['parent_id', 'is_active', 'order_number'], 'mc_parent_active_order_index');
                $table->foreign('parent_id', 'mc_parent_id_foreign')
                    ->references('category_id')
                    ->on('material_categories')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('material_categories') || !Schema::hasColumn('material_categories', 'parent_id')) {
            return;
        }

        Schema::table('material_categories', function (Blueprint $table) {
            $table->dropForeign('mc_parent_id_foreign');
            $table->dropIndex('mc_parent_active_order_index');
            $table->dropColumn('parent_id');
        });
    }
};
