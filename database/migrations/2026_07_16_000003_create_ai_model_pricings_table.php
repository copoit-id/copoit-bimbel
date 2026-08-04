<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_model_pricings')) {
            Schema::create('ai_model_pricings', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 30);
                $table->string('model', 120);
                $table->decimal('input_per_million_usd', 16, 6);
                $table->decimal('output_per_million_usd', 16, 6);
                $table->decimal('usd_to_idr', 16, 4)->default(16000);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['provider', 'model']);
                $table->index(['provider', 'is_active']);
            });
        }

        $now = now();
        foreach ([
            ['provider' => 'openai', 'model' => 'gpt-4o-mini', 'input_per_million_usd' => 0.15, 'output_per_million_usd' => 0.60],
            ['provider' => 'openai', 'model' => 'gpt-4o', 'input_per_million_usd' => 2.50, 'output_per_million_usd' => 10.00],
            ['provider' => 'openai', 'model' => 'gpt-5-mini', 'input_per_million_usd' => 0.25, 'output_per_million_usd' => 2.00],
            ['provider' => 'openai', 'model' => 'gpt-5.4-mini', 'input_per_million_usd' => 0.75, 'output_per_million_usd' => 4.50],
            ['provider' => 'openai', 'model' => 'gpt-5.4', 'input_per_million_usd' => 2.50, 'output_per_million_usd' => 15.00],
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
        Schema::dropIfExists('ai_model_pricings');
    }
};
