<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'affiliate_code')) {
                $table->string('affiliate_code', 32)->nullable()->unique()->after('remember_token');
            }

            if (!Schema::hasColumn('users', 'referred_by_user_id')) {
                $table->foreignId('referred_by_user_id')->nullable()->after('affiliate_code')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'referred_at')) {
                $table->timestamp('referred_at')->nullable()->after('referred_by_user_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'referred_by_user_id')) {
                $table->dropConstrainedForeignId('referred_by_user_id');
            }

            foreach (['affiliate_code', 'referred_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
