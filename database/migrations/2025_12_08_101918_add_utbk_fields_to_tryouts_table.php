<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tryouts', function (Blueprint $table) {
            if (! Schema::hasColumn('tryouts', 'is_irt')) {
                $table->boolean('is_irt')->default(false)->after('is_toefl');
            }
            if (! Schema::hasColumn('tryouts', 'results_release_at')) {
                $table->timestamp('results_release_at')->nullable()->after('end_date');
            }
            if (! Schema::hasColumn('tryouts', 'results_released_at')) {
                $table->timestamp('results_released_at')->nullable()->after('results_release_at');
            }
        });

        DB::statement("ALTER TABLE tryouts MODIFY type_tryout ENUM('tiu', 'twk', 'tkp', 'skd_full', 'general', 'certification', 'listening', 'reading', 'writing', 'pppk_full', 'teknis', 'social culture', 'management', 'interview', 'word', 'excel', 'ppt', 'computer', 'utbk_full', 'utbk_section') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tryouts', function (Blueprint $table) {
            if (Schema::hasColumn('tryouts', 'is_irt')) {
                $table->dropColumn('is_irt');
            }
            if (Schema::hasColumn('tryouts', 'results_release_at')) {
                $table->dropColumn('results_release_at');
            }
            if (Schema::hasColumn('tryouts', 'results_released_at')) {
                $table->dropColumn('results_released_at');
            }
        });

        DB::statement("ALTER TABLE tryouts MODIFY type_tryout ENUM('tiu', 'twk', 'tkp', 'skd_full', 'general', 'certification', 'listening', 'reading', 'writing', 'pppk_full', 'teknis', 'social culture', 'management', 'interview', 'word', 'excel', 'ppt', 'computer') NOT NULL");
    }
};
