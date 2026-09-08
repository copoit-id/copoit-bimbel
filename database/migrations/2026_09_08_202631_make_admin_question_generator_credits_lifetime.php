<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_gateway_plans')) {
            DB::table('ai_gateway_plans')
                ->update([
                    'duration_days' => 0,
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('ai_gateway_subscriptions')) {
            DB::table('ai_gateway_subscriptions')
                ->where('status', 'active')
                ->whereNotNull('ends_at')
                ->update([
                    'ends_at' => null,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Masa aktif sebelumnya tidak dapat direkonstruksi dengan aman.
        // Kredit token AI memang dirancang lifetime.
    }
};
