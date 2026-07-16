<?php

namespace Tests\Unit;

use App\Services\AiGatewayCostService;
use Tests\TestCase;

class AiGatewayCostServiceTest extends TestCase
{
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
}
