<?php

namespace App\Services;

use App\Models\Tryout;

class TryoutScoreDisplayService
{
    public const SCALE_RAW = 'raw';

    public const SCALE_HUNDRED = 'scale_100';

    /**
     * Present a score without changing the score persisted for scoring,
     * ranking, or passing-grade calculations. The 0-100 scale is based on
     * correct answers divided by the total question count.
     *
     * @return array{value: float, maximum: int, formatted: string, formatted_maximum: string, label: string, scale: string}
     */
    public function present(
        Tryout $tryout,
        float|int|null $rawScore,
        int $correctAnswers = 0,
        int $totalQuestions = 0,
        ?float $rawMaximum = null
    ): array
    {
        $score = (float) ($rawScore ?? 0);

        if ($this->usesHundredScale($tryout)) {
            $value = $totalQuestions > 0
                ? min(100, max(0, ($correctAnswers / $totalQuestions) * 100))
                : 0;

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
            'maximum' => $rawMaximum ?? ($tryout->requiresIrtScoring() ? 1000 : $score),
            'formatted' => $this->formatNumber($score),
            'formatted_maximum' => $this->formatNumber($rawMaximum ?? ($tryout->requiresIrtScoring() ? 1000 : $score)),
            'label' => 'Skor asli',
            'scale' => self::SCALE_RAW,
        ];
    }

    public function usesHundredScale(Tryout $tryout): bool
    {
        return ($tryout->result_score_scale ?? self::SCALE_RAW) === self::SCALE_HUNDRED;
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }
}
