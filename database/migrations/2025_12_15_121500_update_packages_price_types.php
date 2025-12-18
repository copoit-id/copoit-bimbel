<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'conditional_requirement')) {
                $table->text('conditional_requirement')->nullable()->after('type_price');
            }
        });

        DB::statement("ALTER TABLE packages MODIFY COLUMN type_price ENUM('paid','free_unconditional','free_conditional') NOT NULL DEFAULT 'paid'");
        DB::table('packages')->where('type_price', 'free')->update(['type_price' => 'free_unconditional']);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE packages MODIFY COLUMN type_price ENUM('free','paid') NOT NULL DEFAULT 'paid'");

        Schema::table('packages', function (Blueprint $table) {
            if (Schema::hasColumn('packages', 'conditional_requirement')) {
                $table->dropColumn('conditional_requirement');
            }
        });
    }
};
