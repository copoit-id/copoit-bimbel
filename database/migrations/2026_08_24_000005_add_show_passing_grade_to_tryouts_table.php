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
            if (! Schema::hasColumn('tryouts', 'show_passing_grade')) {
                $table->boolean('show_passing_grade')->default(true);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tryouts') || ! Schema::hasColumn('tryouts', 'show_passing_grade')) {
            return;
        }

        Schema::table('tryouts', function (Blueprint $table): void {
            $table->dropColumn('show_passing_grade');
        });
    }
};
