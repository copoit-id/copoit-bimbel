<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tryouts', function (Blueprint $table) {
            if (!Schema::hasColumn('tryouts', 'subtest_display_mode')) {
                $table->string('subtest_display_mode', 32)
                    ->default('per_subtest')
                    ->after('answer_persistence_mode');
            }
        });

        DB::table('tryouts')
            ->whereNull('subtest_display_mode')
            ->update(['subtest_display_mode' => 'per_subtest']);
    }

    public function down(): void
    {
        Schema::table('tryouts', function (Blueprint $table) {
            if (Schema::hasColumn('tryouts', 'subtest_display_mode')) {
                $table->dropColumn('subtest_display_mode');
            }
        });
    }
};

