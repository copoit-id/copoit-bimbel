<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_profile') && !Schema::hasColumn('client_profile', 'allow_register')) {
            Schema::table('client_profile', function (Blueprint $table) {
                $table->boolean('allow_register')->default(true)->after('allow_video_thumbnail');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('client_profile') && Schema::hasColumn('client_profile', 'allow_register')) {
            Schema::table('client_profile', function (Blueprint $table) {
                $table->dropColumn('allow_register');
            });
        }
    }
};
