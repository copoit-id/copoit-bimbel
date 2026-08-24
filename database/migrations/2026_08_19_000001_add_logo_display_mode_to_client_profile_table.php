<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_profile') || Schema::hasColumn('client_profile', 'logo_display_mode')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $column = $table->string('logo_display_mode', 16)->default('square');

            if (Schema::hasColumn('client_profile', 'favicon')) {
                $column->after('favicon');
            } else {
                $column->after('logo');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_profile') || ! Schema::hasColumn('client_profile', 'logo_display_mode')) {
            return;
        }

        Schema::table('client_profile', function (Blueprint $table): void {
            $table->dropColumn('logo_display_mode');
        });
    }
};
