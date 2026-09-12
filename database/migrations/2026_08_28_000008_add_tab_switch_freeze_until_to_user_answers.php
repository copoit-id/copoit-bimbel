<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_answers')) {
            return;
        }

        Schema::table('user_answers', function (Blueprint $table): void {
            if (! Schema::hasColumn('user_answers', 'tab_switch_frozen_until')) {
                $table->timestamp('tab_switch_frozen_until')->nullable()->after('tab_switch_count');
                $table->index(['attempt_token', 'tab_switch_frozen_until'], 'user_answers_attempt_freeze_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_answers')) {
            return;
        }

        Schema::table('user_answers', function (Blueprint $table): void {
            if (Schema::hasColumn('user_answers', 'tab_switch_frozen_until')) {
                try {
                    $table->dropIndex('user_answers_attempt_freeze_index');
                } catch (\Throwable $exception) {
                    // The index may not exist on databases upgraded from an earlier release.
                }

                $table->dropColumn('tab_switch_frozen_until');
            }
        });
    }
};
