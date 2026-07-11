<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_profile') || Schema::hasColumn('client_profile', 'ai_discussion_admin_configurable')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            $table->boolean('ai_discussion_admin_configurable')
                ->default(false)
                ->after('ai_discussion_feature_enabled');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_profile') || !Schema::hasColumn('client_profile', 'ai_discussion_admin_configurable')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table) {
            $table->dropColumn('ai_discussion_admin_configurable');
        });
    }
};
