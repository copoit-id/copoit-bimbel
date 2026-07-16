<?php

namespace App\Services;

use App\Models\AiModelPricing;
use Illuminate\Support\Facades\Cache;

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

    public function hasPricing(string $provider, string $model): bool
    {
        return $this->pricingFor($provider, $model) !== null;
    }

    private function pricingFor(string $provider, string $model): ?array
    {
        $provider = strtolower(trim($provider));
        $normalizedModel = strtolower(trim($model));
        $pricing = $this->activePricings($provider)
            ->first(fn (array $item): bool => $normalizedModel === $item['model'] || str_starts_with($normalizedModel, $item['model'] . '-'));

        return $pricing === null ? null : [
            'input_per_million_usd' => $pricing['input_per_million_usd'],
            'output_per_million_usd' => $pricing['output_per_million_usd'],
            'usd_to_idr' => $pricing['usd_to_idr'],
        ];
    }

    public function forgetCachedPricing(string $provider = 'openai'): void
    {
        Cache::forget($this->cacheKey(strtolower($provider)));
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{model: string, input_per_million_usd: float, output_per_million_usd: float, usd_to_idr: float}>
     */
    private function activePricings(string $provider)
    {
        return Cache::remember($this->cacheKey($provider), now()->addMinutes(15), fn () => AiModelPricing::query()
            ->where('provider', $provider)
            ->where('is_active', true)
            ->orderByDesc('model')
            ->get(['model', 'input_per_million_usd', 'output_per_million_usd', 'usd_to_idr'])
            ->map(fn (AiModelPricing $pricing): array => [
                'model' => strtolower($pricing->model),
                'input_per_million_usd' => (float) $pricing->input_per_million_usd,
                'output_per_million_usd' => (float) $pricing->output_per_million_usd,
                'usd_to_idr' => (float) $pricing->usd_to_idr,
            ]));
    }

    private function cacheKey(string $provider): string
    {
        return 'ai-gateway-model-pricings:' . $provider;
    }
}
