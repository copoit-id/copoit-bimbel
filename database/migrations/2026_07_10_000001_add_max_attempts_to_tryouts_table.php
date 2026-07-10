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
            if (! Schema::hasColumn('tryouts', 'max_attempts')) {
                $table->unsignedInteger('max_attempts')
                    ->default(0)
                    ->after('section_break_duration');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tryouts', function (Blueprint $table) {
            if (Schema::hasColumn('tryouts', 'max_attempts')) {
                $table->dropColumn('max_attempts');
            }
        });
    }
};
