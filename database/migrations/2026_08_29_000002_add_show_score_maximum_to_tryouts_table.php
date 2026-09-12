<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tryouts') || Schema::hasColumn('tryouts', 'show_score_maximum')) {
            return;
        }

        Schema::table('tryouts', function (Blueprint $table): void {
            $table->boolean('show_score_maximum')->default(true)->after('show_result_scores');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tryouts') || ! Schema::hasColumn('tryouts', 'show_score_maximum')) {
            return;
        }

        Schema::table('tryouts', function (Blueprint $table): void {
            $table->dropColumn('show_score_maximum');
        });
    }
};
