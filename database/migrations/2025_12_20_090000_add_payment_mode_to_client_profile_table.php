<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_profile') && !Schema::hasColumn('client_profile', 'payment_mode')) {
            Schema::table('client_profile', function (Blueprint $table) {
                $table->string('payment_mode')->default('gateway')->after('allow_video_thumbnail');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('client_profile') && Schema::hasColumn('client_profile', 'payment_mode')) {
            Schema::table('client_profile', function (Blueprint $table) {
                $table->dropColumn('payment_mode');
            });
        }
    }
};
