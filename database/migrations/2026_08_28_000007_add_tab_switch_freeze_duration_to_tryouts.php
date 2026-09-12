<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tryouts', function (Blueprint $table): void {
            if (! Schema::hasColumn('tryouts', 'tab_switch_freeze_seconds')) {
                $table->unsignedSmallInteger('tab_switch_freeze_seconds')
                    ->default(15)
                    ->after('tab_switch_freeze');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tryouts', function (Blueprint $table): void {
            if (Schema::hasColumn('tryouts', 'tab_switch_freeze_seconds')) {
                $table->dropColumn('tab_switch_freeze_seconds');
            }
        });
    }
};
