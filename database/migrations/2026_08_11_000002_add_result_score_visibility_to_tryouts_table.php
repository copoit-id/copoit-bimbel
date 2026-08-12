<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tryouts')) {
            return;
        }

        Schema::table('tryouts', function (Blueprint $table): void {
            if (! Schema::hasColumn('tryouts', 'show_result_scores')) {
                $table->boolean('show_result_scores')->default(true);
            }

            if (! Schema::hasColumn('tryouts', 'result_score_display')) {
                $table->string('result_score_display', 30)->default('total_and_subtest');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tryouts')) {
            return;
        }

        Schema::table('tryouts', function (Blueprint $table): void {
            if (Schema::hasColumn('tryouts', 'result_score_display')) {
                $table->dropColumn('result_score_display');
            }

            if (Schema::hasColumn('tryouts', 'show_result_scores')) {
                $table->dropColumn('show_result_scores');
            }
        });
    }
};
