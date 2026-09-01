<?php

namespace App\Services;

use App\Models\Tryout;
use Illuminate\Support\Collection;

class TryoutScoreDisplayService
{
    public const SCALE_RAW = 'raw';

    public const SCALE_HUNDRED = 'scale_100';

    /**
     * Present a score without changing the score persisted for scoring,
     * ranking, or passing-grade calculations. The 0-100 scale is based on
     * IRT scores are converted from their original 0-1000 range, while
     * standard tryouts use correct answers divided by total questions. The
     * total score accumulates a 0-100 score for every subtest.
     *
     * @return array{value: float, maximum: int, formatted: string, formatted_maximum: string, label: string, scale: string}
     */
    public function present(
        Tryout $tryout,
        float|int|null $rawScore,
        int $correctAnswers = 0,
        int $totalQuestions = 0,
        ?float $rawMaximum = null,
        int $subtestCount = 1
    ): array
    {
        $score = (float) ($rawScore ?? 0);
        $subtestCount = max(1, $subtestCount);

        if ($this->usesHundredScale($tryout)) {
            $maximum = 100 * $subtestCount;
            $value = $tryout->requiresIrtScoring()
                ? min($maximum, max(0, ($score / 10) * $subtestCount))
                : ($totalQuestions > 0
                    ? min($maximum, max(0, ($correctAnswers / $totalQuestions) * $maximum))
                    : ($rawMaximum && $rawMaximum > 0
                        ? min($maximum, max(0, ($score / $rawMaximum) * $maximum))
                        : $score));

            return [
                'value' => $value,
                'maximum' => $maximum,
                'formatted' => $this->formatNumber($value),
                'formatted_maximum' => (string) $maximum,
                'label' => "Skala 0 - {$maximum}",
                'scale' => self::SCALE_HUNDRED,
            ];
        }

        return [
            'value' => $score,
            'maximum' => $rawMaximum ?? ($tryout->requiresIrtScoring() ? 1000 : $score),
            'formatted' => $this->formatNumber($score),
            'formatted_maximum' => $this->formatNumber($rawMaximum ?? ($tryout->requiresIrtScoring() ? 1000 : $score)),
            'label' => $tryout->requiresIrtScoring() ? 'Skor asli (0 - 1000)' : 'Skor asli',
            'scale' => self::SCALE_RAW,
        ];
    }

    public function usesHundredScale(Tryout $tryout): bool
    {
        return ($tryout->result_score_scale ?? self::SCALE_RAW) === self::SCALE_HUNDRED;
    }

    /**
     * Keep maximum-score visibility consistent across every score presentation.
     */
    public function shouldShowMaximum(Tryout $tryout): bool
    {
        return $tryout->shouldShowScoreMaximum();
    }

    /**
     * Summarize the final score already prepared for leaderboard display.
     *
     * The final display score is the source of truth here: it is the total
     * across subtests and has already applied the tryout's configured scale.
     *
     * @param Collection<int, array<string, mixed>|object> $rankings
     * @return array{average: float, average_formatted: string, highest: float, highest_formatted: string}
     */
    public function summarizeFinalScores(Collection $rankings): array
    {
        $scores = $rankings
            ->map(function (array|object $ranking): float {
                $displayScore = data_get($ranking, 'display_score.value');

                if ($displayScore !== null) {
                    return (float) $displayScore;
                }

                return (float) data_get($ranking, 'raw_score', 0);
            });

        $average = (float) ($scores->avg() ?? 0);
        $highest = (float) ($scores->max() ?? 0);

        return [
            'average' => $average,
            'average_formatted' => $this->formatNumber($average),
            'highest' => $highest,
            'highest_formatted' => $this->formatNumber($highest),
        ];
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }
}
