<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Tryout;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UtbkResultReleaseService
{
    public function releasePending(): void
    {
        $now = Carbon::now('Asia/Jakarta');

        $tryouts = Tryout::where('type_tryout', 'utbk_full')
            ->where('is_irt', true)
            ->where(function ($query) use ($now) {
                $query->where(function ($q) use ($now) {
                    $q->whereNotNull('results_release_at')
                        ->where('results_release_at', '<=', $now);
                })->orWhere(function ($q) use ($now) {
                    $q->whereNull('results_release_at')
                        ->whereNotNull('end_date')
                        ->where('end_date', '<=', $now);
                });
            })
            ->where(function ($query) {
                $query->whereNull('results_released_at')
                    ->orWhere(function ($sub) {
                        $sub->whereNotNull('results_reset_at')
                            ->whereNotNull('results_released_at')
                            ->whereColumn('results_reset_at', '>', 'results_released_at');
                    });
            })
            ->get();

        foreach ($tryouts as $tryout) {
            $this->processTryoutRelease($tryout, $now);
        }
    }

    public function releaseForTryout(Tryout $tryout): bool
    {
        if (! $tryout->requiresIrtScoring()) {
            return false;
        }

        return $this->processTryoutRelease($tryout, Carbon::now('Asia/Jakarta'));
    }

    public function resetResults(Tryout $tryout): bool
    {
        if (! $tryout->requiresIrtScoring()) {
            return false;
        }

        $answers = UserAnswer::where('tryout_id', $tryout->tryout_id)
            ->where('status', 'completed')
            ->whereNotNull('utbk_total_score')
            ->get();

        if ($answers->isEmpty()) {
            return false;
        }

        $now = Carbon::now('Asia/Jakarta');

        foreach ($answers as $answer) {
            $answer->update([
                'status' => 'pending_release',
                'utbk_total_score' => null,
                'score' => 0,
                'is_passed' => false,
            ]);
        }

        $tryout->update([
            'results_released_at' => null,
            'results_reset_at' => $now,
        ]);

        return true;
    }

    private function processTryoutRelease(Tryout $tryout, Carbon $now): bool
    {
        $pendingAnswers = UserAnswer::where('tryout_id', $tryout->tryout_id)
            ->where('status', 'pending_release')
            ->with(['userAnswerDetails', 'tryoutDetail'])
            ->get()
            ->groupBy('attempt_token');

        if ($pendingAnswers->isEmpty()) {
            $tryout->update([
                'results_released_at' => $now,
                'results_release_at' => $tryout->results_release_at ?: $tryout->end_date,
                'results_reset_at' => null,
            ]);
            return false;
        }

        DB::transaction(function () use ($pendingAnswers, $tryout, $now) {
            $questionStats = $this->buildQuestionWeights($tryout->tryout_id, $pendingAnswers);

            if ($questionStats['max_total_weight'] <= 0) {
                $questionStats['max_total_weight'] = max(1, count($questionStats['weights']));
            }

            foreach ($pendingAnswers as $attemptToken => $answers) {
                $results = $this->calculateAttemptScore($answers, $questionStats);
                $totalScore = $results['total'];
                $subTestScores = $results['subtests'];

                foreach ($answers as $userAnswer) {
                    $subtestId = $userAnswer->tryout_detail_id;
                    $subScore = $subTestScores[$subtestId]['score'] ?? 0;

                        $userAnswer->update([
                            'finished_at' => $userAnswer->finished_at ?? $now,
                            'score' => $subScore,
                            'utbk_total_score' => $totalScore,
                            'status' => 'completed',
                            'is_passed' => false,
                        ]);
                }
            }

            $tryout->update([
                'results_released_at' => $now,
                'results_release_at' => $tryout->results_release_at ?: $tryout->end_date,
                'results_reset_at' => null,
            ]);
        });

        return true;
    }

    private function buildQuestionWeights(int $tryoutId, Collection $pendingAnswers): array
    {
        $usage = [];
        foreach ($pendingAnswers as $answers) {
            foreach ($answers as $userAnswer) {
                foreach ($userAnswer->userAnswerDetails as $detail) {
                    $questionId = $detail->question_id;
                    if (! isset($usage[$questionId])) {
                        $usage[$questionId] = ['total' => 0, 'correct' => 0];
                    }
                    $usage[$questionId]['total']++;
                    if ($detail->is_correct) {
                        $usage[$questionId]['correct']++;
                    }
                }
            }
        }

        $questionIds = array_keys($usage);
        if (empty($questionIds)) {
            return [
                'weights' => [],
                'subtest_totals' => [],
                'max_total_weight' => 0,
            ];
        }

        $weights = [];
        $subtestTotals = [];

        $questions = Question::with('tryoutDetail')
            ->whereIn('question_id', $questionIds)
            ->get();

        foreach ($questions as $question) {
            $stats = $usage[$question->question_id] ?? ['total' => 0, 'correct' => 0];
            if (($stats['total'] ?? 0) <= 1) {
                $difficulty = 0;
            } else {
                $difficulty = $stats['correct'] / max(1, $stats['total']);
            }
            $weight = max(0.05, 1 - $difficulty);

            $weights[$question->question_id] = [
                'weight' => $weight,
                'tryout_detail_id' => $question->tryout_detail_id,
            ];

            if (! isset($subtestTotals[$question->tryout_detail_id])) {
                $subtestTotals[$question->tryout_detail_id] = 0;
            }
            $subtestTotals[$question->tryout_detail_id] += $weight;
        }

        $maxTotalWeight = collect($weights)->sum('weight');

        return [
            'weights' => $weights,
            'subtest_totals' => $subtestTotals,
            'max_total_weight' => $maxTotalWeight,
        ];
    }

    private function calculateAttemptScore(Collection $answers, array $questionStats): array
    {
        $subtestRaw = [];

        foreach ($answers as $userAnswer) {
            $subtestId = $userAnswer->tryout_detail_id;
            if (! isset($subtestRaw[$subtestId])) {
                $subtestRaw[$subtestId] = 0;
            }

            foreach ($userAnswer->userAnswerDetails as $detail) {
                $stats = $questionStats['weights'][$detail->question_id] ?? null;
                if (! $stats || ! $detail->is_correct) {
                    continue;
                }

                $subtestRaw[$subtestId] += $stats['weight'];
            }
        }

        $subtestScores = [];
        $scoreValues = [];
        foreach ($subtestRaw as $subtestId => $raw) {
            $maxSub = $questionStats['subtest_totals'][$subtestId] ?? 1;
            $score = (int) round(($raw / max($maxSub, 1)) * 1000);

            $subtestScores[$subtestId] = [
                'raw' => $raw,
                'score' => $score,
            ];

            $scoreValues[] = $score;
        }

        $totalScore = !empty($scoreValues)
            ? (int) round(array_sum($scoreValues) / count($scoreValues))
            : 0;

        return [
            'total' => $totalScore,
            'subtests' => $subtestScores,
        ];
    }
}
