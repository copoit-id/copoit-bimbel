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
        Schema::table('client_profile', function (Blueprint $table) {
            if (!Schema::hasColumn('client_profile', 'allow_video_thumbnail')) {
                $table->boolean('allow_video_thumbnail')
                    ->default(false)
                    ->after('enable_utbk_types');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_profile', function (Blueprint $table) {
            if (Schema::hasColumn('client_profile', 'allow_video_thumbnail')) {
                $table->dropColumn('allow_video_thumbnail');
            }
        });
    }
};
