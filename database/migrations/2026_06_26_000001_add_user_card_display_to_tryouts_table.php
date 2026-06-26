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
            if (! Schema::hasColumn('tryouts', 'user_card_display')) {
                $table->string('user_card_display', 20)->default('icon')->after('subtest_display_mode');
            }

            if (! Schema::hasColumn('tryouts', 'thumbnail_url')) {
                $table->string('thumbnail_url')->nullable()->after('user_card_display');
            }

            if (! Schema::hasColumn('tryouts', 'icon_class')) {
                $table->string('icon_class', 100)->nullable()->after('thumbnail_url');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tryouts')) {
            return;
        }

        Schema::table('tryouts', function (Blueprint $table) {
            if (Schema::hasColumn('tryouts', 'icon_class')) {
                $table->dropColumn('icon_class');
            }

            if (Schema::hasColumn('tryouts', 'thumbnail_url')) {
                $table->dropColumn('thumbnail_url');
            }

            if (Schema::hasColumn('tryouts', 'user_card_display')) {
                $table->dropColumn('user_card_display');
            }
        });
    }
};
