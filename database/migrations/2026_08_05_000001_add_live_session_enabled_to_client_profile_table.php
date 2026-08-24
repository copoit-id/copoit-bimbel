<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_profile') || Schema::hasColumn('client_profile', 'live_session_enabled')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $column = $table->boolean('live_session_enabled')->default(true);

            if (Schema::hasColumn('client_profile', 'live_session_label')) {
                $column->after('live_session_label');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_profile') || ! Schema::hasColumn('client_profile', 'live_session_enabled')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $table->dropColumn('live_session_enabled');
        });
    }
};
