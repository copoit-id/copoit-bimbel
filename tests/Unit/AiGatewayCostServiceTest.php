<?php

namespace Tests\Unit;

use App\Models\AiModelPricing;
use App\Services\AiGatewayCostService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiGatewayCostServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('ai_model_pricings');
        Schema::create('ai_model_pricings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('model', 120);
            $table->decimal('input_per_million_usd', 16, 6);
            $table->decimal('output_per_million_usd', 16, 6);
            $table->decimal('usd_to_idr', 16, 4);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['provider', 'model']);
        });
        AiModelPricing::insert([
            ['provider' => 'openai', 'model' => 'gpt-4o-mini', 'input_per_million_usd' => 0.15, 'output_per_million_usd' => 0.60, 'usd_to_idr' => 16000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['provider' => 'openai', 'model' => 'gpt-4o', 'input_per_million_usd' => 2.50, 'output_per_million_usd' => 10.00, 'usd_to_idr' => 16000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['provider' => 'openai', 'model' => 'gpt-5.4-mini', 'input_per_million_usd' => 0.75, 'output_per_million_usd' => 4.50, 'usd_to_idr' => 16000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        Cache::flush();
    }

    public function test_it_calculates_gpt_4o_mini_cost_from_input_and_output_tokens(): void
    {
        $estimate = app(AiGatewayCostService::class)->estimate('openai', 'gpt-4o-mini', 1_000_000, 1_000_000);

        $this->assertNotNull($estimate);
        $this->assertSame(2400.0, $estimate['input_cost_idr']);
        $this->assertSame(9600.0, $estimate['output_cost_idr']);
        $this->assertSame(12000.0, $estimate['total_cost_idr']);
    }

    public function test_it_does_not_estimate_models_without_a_configured_price(): void
    {
        $service = app(AiGatewayCostService::class);

        $this->assertNull($service->estimate('gemini', 'gemini-2.5-flash', 100, 100));
        $this->assertNull($service->estimate('openai', 'gpt-4.1-mini', 100, 100));
    }

    public function test_it_calculates_gpt_4o_cost_from_input_and_output_tokens(): void
    {
        $estimate = app(AiGatewayCostService::class)->estimate('openai', 'gpt-4o', 1_000_000, 1_000_000);

        $this->assertNotNull($estimate);
        $this->assertSame(40000.0, $estimate['input_cost_idr']);
        $this->assertSame(160000.0, $estimate['output_cost_idr']);
        $this->assertSame(200000.0, $estimate['total_cost_idr']);
    }

    public function test_it_calculates_gpt_5_4_mini_cost_and_marks_it_as_priced(): void
    {
        $service = app(AiGatewayCostService::class);
        $estimate = $service->estimate('openai', 'gpt-5.4-mini', 1_000_000, 1_000_000);

        $this->assertTrue($service->hasPricing('openai', 'gpt-5.4-mini'));
        $this->assertTrue($service->hasPricing('openai', 'gpt-5.4-mini-2026-03-01'));
        $this->assertNotNull($estimate);
        $this->assertSame(12000.0, $estimate['input_cost_idr']);
        $this->assertSame(72000.0, $estimate['output_cost_idr']);
        $this->assertSame(84000.0, $estimate['total_cost_idr']);
    }

    public function test_it_uses_the_price_saved_in_the_database(): void
    {
        AiModelPricing::query()
            ->where('provider', 'openai')
            ->where('model', 'gpt-4o-mini')
            ->update(['input_per_million_usd' => 1.25, 'output_per_million_usd' => 3.50]);
        $service = app(AiGatewayCostService::class);
        $service->forgetCachedPricing();

        $estimate = $service->estimate('openai', 'gpt-4o-mini', 1_000_000, 1_000_000);

        $this->assertNotNull($estimate);
        $this->assertSame(20000.0, $estimate['input_cost_idr']);
        $this->assertSame(56000.0, $estimate['output_cost_idr']);
        $this->assertSame(76000.0, $estimate['total_cost_idr']);
    }
}
