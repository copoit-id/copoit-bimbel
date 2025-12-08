<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tryouts', function (Blueprint $table) {
            if (! Schema::hasColumn('tryouts', 'results_reset_at')) {
                $table->timestamp('results_reset_at')->nullable()->after('results_released_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tryouts', function (Blueprint $table) {
            if (Schema::hasColumn('tryouts', 'results_reset_at')) {
                $table->dropColumn('results_reset_at');
            }
        });
    }
};
