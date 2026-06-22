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

        Schema::table('tryouts', function (Blueprint $table) {
            if (! Schema::hasColumn('tryouts', 'show_discussion')) {
                $table->boolean('show_discussion')->default(true)->after('is_displayed');
            }

            if (! Schema::hasColumn('tryouts', 'show_leaderboard')) {
                $table->boolean('show_leaderboard')->default(true)->after('show_discussion');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tryouts')) {
            return;
        }

        Schema::table('tryouts', function (Blueprint $table) {
            if (Schema::hasColumn('tryouts', 'show_leaderboard')) {
                $table->dropColumn('show_leaderboard');
            }

            if (Schema::hasColumn('tryouts', 'show_discussion')) {
                $table->dropColumn('show_discussion');
            }
        });
    }
};
