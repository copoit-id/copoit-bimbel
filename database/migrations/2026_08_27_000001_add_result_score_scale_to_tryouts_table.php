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
            if (! Schema::hasColumn('tryouts', 'result_score_scale')) {
                $table->string('result_score_scale', 20)
                    ->default('raw')
                    ->after('result_score_display');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tryouts') || ! Schema::hasColumn('tryouts', 'result_score_scale')) {
            return;
        }

        Schema::table('tryouts', function (Blueprint $table): void {
            $table->dropColumn('result_score_scale');
        });
    }
};
