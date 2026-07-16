<?php

namespace App\Services;

class AiGatewayCostService
{
    /**
     * Estimate OpenAI API cost from the token counts recorded by the gateway.
     *
     * @return array{input_cost_idr: float, output_cost_idr: float, total_cost_idr: float, input_cost_usd: float, output_cost_usd: float, total_cost_usd: float, input_per_million_usd: float, output_per_million_usd: float, usd_to_idr: float}|null
     */
    public function estimate(string $provider, string $model, int $inputTokens, int $outputTokens): ?array
    {
        $pricing = $this->pricingFor($provider, $model);

        if ($pricing === null) {
            return null;
        }

        $inputRate = (float) $pricing['input_per_million_usd'];
        $outputRate = (float) $pricing['output_per_million_usd'];
        $usdToIdr = (float) $pricing['usd_to_idr'];
        $inputCostUsd = max(0, $inputTokens) / 1_000_000 * $inputRate;
        $outputCostUsd = max(0, $outputTokens) / 1_000_000 * $outputRate;

        return [
            'input_cost_idr' => $inputCostUsd * $usdToIdr,
            'output_cost_idr' => $outputCostUsd * $usdToIdr,
            'total_cost_idr' => ($inputCostUsd + $outputCostUsd) * $usdToIdr,
            'input_cost_usd' => $inputCostUsd,
            'output_cost_usd' => $outputCostUsd,
            'total_cost_usd' => $inputCostUsd + $outputCostUsd,
            'input_per_million_usd' => $inputRate,
            'output_per_million_usd' => $outputRate,
            'usd_to_idr' => $usdToIdr,
        ];
    }

    private function pricingFor(string $provider, string $model): ?array
    {
        if (strtolower($provider) !== 'openai') {
            return null;
        }

        $normalizedModel = strtolower(trim($model));
        $pricing = config('services.ai_gateway.cost_pricing', []);

        foreach (($pricing['models'] ?? []) as $modelPricing) {
            $aliases = array_map('strtolower', $modelPricing['aliases'] ?? []);

            if (in_array($normalizedModel, $aliases, true)) {
                return [
                    'input_per_million_usd' => (float) ($modelPricing['input_per_million_usd'] ?? 0),
                    'output_per_million_usd' => (float) ($modelPricing['output_per_million_usd'] ?? 0),
                    'usd_to_idr' => (float) ($pricing['usd_to_idr'] ?? 0),
                ];
            }
        }

        return null;
    }
}
