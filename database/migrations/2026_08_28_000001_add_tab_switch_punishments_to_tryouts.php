<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tryouts', function (Blueprint $table): void {
            if (! Schema::hasColumn('tryouts', 'tab_switch_freeze')) {
                $table->boolean('tab_switch_freeze')->default(false)->after('enable_tab_switch_detection');
            }

            if (! Schema::hasColumn('tryouts', 'tab_switch_freeze_seconds')) {
                $table->unsignedSmallInteger('tab_switch_freeze_seconds')->default(15)->after('tab_switch_freeze');
            }

            if (! Schema::hasColumn('tryouts', 'tab_switch_reset_answer')) {
                $table->boolean('tab_switch_reset_answer')->default(false)->after('tab_switch_freeze');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tryouts', function (Blueprint $table): void {
            foreach (['tab_switch_reset_answer', 'tab_switch_freeze_seconds', 'tab_switch_freeze'] as $column) {
                if (Schema::hasColumn('tryouts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
