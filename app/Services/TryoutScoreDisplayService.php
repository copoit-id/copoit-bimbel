<?php

namespace App\Services;

use App\Models\Tryout;

class TryoutScoreDisplayService
{
    public const SCALE_RAW = 'raw';

    public const SCALE_HUNDRED = 'scale_100';

    /**
     * Present an IRT score without changing the score persisted for scoring,
     * ranking, or passing-grade calculations.
     *
     * @return array{value: float, maximum: int, formatted: string, formatted_maximum: string, label: string, scale: string}
     */
    public function present(Tryout $tryout, float|int|null $rawScore): array
    {
        $score = (float) ($rawScore ?? 0);

        if ($this->usesHundredScale($tryout)) {
            $value = min(100, max(0, $score / 10));

            return [
                'value' => $value,
                'maximum' => 100,
                'formatted' => $this->formatNumber($value),
                'formatted_maximum' => '100',
                'label' => 'Skala 0 - 100',
                'scale' => self::SCALE_HUNDRED,
            ];
        }

        return [
            'value' => $score,
            'maximum' => 1000,
            'formatted' => $this->formatNumber($score),
            'formatted_maximum' => '1000',
            'label' => 'Skor asli (0 - 1000)',
            'scale' => self::SCALE_RAW,
        ];
    }

    public function usesHundredScale(Tryout $tryout): bool
    {
        return $tryout->requiresIrtScoring()
            && ($tryout->result_score_scale ?? self::SCALE_RAW) === self::SCALE_HUNDRED;
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }
}
