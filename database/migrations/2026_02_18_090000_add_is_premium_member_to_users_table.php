<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'is_premium_member')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_premium_member')->default(false)->after('is_devisadia_student');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_premium_member')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_premium_member');
            });
        }
    }
};
