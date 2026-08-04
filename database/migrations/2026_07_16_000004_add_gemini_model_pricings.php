<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_model_pricings')) {
            return;
        }

        $now = now();
        foreach ([
            ['provider' => 'gemini', 'model' => 'gemini-2.5-flash-lite', 'input_per_million_usd' => 0.10, 'output_per_million_usd' => 0.40],
            ['provider' => 'gemini', 'model' => 'gemini-2.5-flash', 'input_per_million_usd' => 0.30, 'output_per_million_usd' => 2.50],
            ['provider' => 'gemini', 'model' => 'gemini-2.5-pro', 'input_per_million_usd' => 1.25, 'output_per_million_usd' => 10.00],
        ] as $pricing) {
            if (! DB::table('ai_model_pricings')
                ->where('provider', $pricing['provider'])
                ->where('model', $pricing['model'])
                ->exists()) {
                DB::table('ai_model_pricings')->insert([
                    ...$pricing,
                    'usd_to_idr' => 16000,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Pricing can be changed by the super admin after this migration runs.
        // Keep those records intact when rolling back application code.
    }
};
