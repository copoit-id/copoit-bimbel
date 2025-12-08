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
        if (! Schema::hasColumn('user_answers', 'utbk_total_score')) {
            Schema::table('user_answers', function (Blueprint $table) {
                $table->decimal('utbk_total_score', 8, 2)->nullable()->after('score');
            });
        }

        DB::statement("ALTER TABLE user_answers MODIFY status ENUM('in_progress','completed','abandoned','pending_release') NOT NULL DEFAULT 'in_progress'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('user_answers', 'utbk_total_score')) {
            Schema::table('user_answers', function (Blueprint $table) {
                $table->dropColumn('utbk_total_score');
            });
        }

        DB::statement("ALTER TABLE user_answers MODIFY status ENUM('in_progress','completed','abandoned') NOT NULL DEFAULT 'in_progress'");
    }
};
