<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('user_answers', 'tab_switch_count')) {
                $table->unsignedInteger('tab_switch_count')->default(0)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_answers', function (Blueprint $table) {
            if (Schema::hasColumn('user_answers', 'tab_switch_count')) {
                $table->dropColumn('tab_switch_count');
            }
        });
    }
};
