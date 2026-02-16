<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('user_answers', 'subtest_submitted_at')) {
                $table->timestamp('subtest_submitted_at')->nullable()->after('finished_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_answers', function (Blueprint $table) {
            if (Schema::hasColumn('user_answers', 'subtest_submitted_at')) {
                $table->dropColumn('subtest_submitted_at');
            }
        });
    }
};
