<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('ai_gateway_plans')
            && Schema::hasColumn('ai_gateway_plans', 'scope')
            && Schema::hasColumn('ai_gateway_plans', 'duration_days')
        ) {
            DB::table('ai_gateway_plans')
                ->where('scope', 'admin_question_generator')
                ->update(['duration_days' => 0]);
        }

        if (
            Schema::hasTable('ai_gateway_subscriptions')
            && Schema::hasColumn('ai_gateway_subscriptions', 'scope')
            && Schema::hasColumn('ai_gateway_subscriptions', 'ends_at')
        ) {
            DB::table('ai_gateway_subscriptions')
                ->where('scope', 'admin_question_generator')
                ->where('status', 'active')
                ->whereNotNull('ends_at')
                ->update(['ends_at' => null]);
        }
    }

    public function down(): void
    {
        // Existing expiry timestamps are intentionally not reconstructed.
    }
};
