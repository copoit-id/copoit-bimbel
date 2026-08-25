<?php

namespace App\Services;

use App\Models\Question;
use App\Models\UserAnswerDetail;
use Illuminate\Support\Collection;

class MultipleAnswerScoringService
{
    /**
     * Evaluate a multiple-answer response using the score configuration stored on its question.
     *
     * @param array<int, int|string> $selectedOptionIds
     * @return array{selected_option_ids: array<int, int>, correct_option_ids: array<int, int>, correct_matched: int, correct_total: int, wrong_selected: int, wrong_count: int, scoring_mode: string, score_ratio: float, score_obtained: float, is_correct: bool}
     */
    public function evaluate(Question $question, array $selectedOptionIds): array
    {
        $selectedIds = collect($selectedOptionIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $correctIds = $this->options($question)
            ->where('is_correct', true)
            ->pluck('question_option_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $config = $this->config($question);
        $correctTotal = count($correctIds);
        $matchedCorrect = count(array_intersect($selectedIds, $correctIds));
        $wrongSelected = max(0, count($selectedIds) - $matchedCorrect);
        $missedCorrect = max(0, $correctTotal - $matchedCorrect);
        $isCorrect = $correctTotal > 0 && $selectedIds === $correctIds;

        if ($config['scoring_mode'] === 'partial' && $matchedCorrect > 0 && $correctTotal > 0) {
            $score = ($matchedCorrect / $correctTotal) * $config['score_correct'];
        } else {
            $score = $isCorrect ? $config['score_correct'] : $config['score_wrong'];
        }

        return [
            'selected_option_ids' => $selectedIds,
            'correct_option_ids' => $correctIds,
            'correct_matched' => $matchedCorrect,
            'correct_total' => $correctTotal,
            'wrong_selected' => $wrongSelected,
            'wrong_count' => $missedCorrect + $wrongSelected,
            'scoring_mode' => $config['scoring_mode'],
            'score_ratio' => $correctTotal > 0 ? $matchedCorrect / $correctTotal : 0.0,
            'score_obtained' => (float) $score,
            'is_correct' => $isCorrect,
        ];
    }

    public function scoreForDetail(Question $question, UserAnswerDetail $detail): float
    {
        $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];

        if (array_key_exists('selected_option_ids', $answerMeta)) {
            return $this->evaluate($question, (array) $answerMeta['selected_option_ids'])['score_obtained'];
        }

        if (is_numeric($answerMeta['score_obtained'] ?? null)) {
            return (float) $answerMeta['score_obtained'];
        }

        $config = $this->config($question);

        return $detail->is_correct ? $config['score_correct'] : $config['score_wrong'];
    }

    /**
     * @return array{score_correct: float, score_wrong: float, scoring_mode: string}
     */
    public function config(Question $question): array
    {
        $metadata = is_array($question->metadata) ? $question->metadata : [];
        $multipleAnswer = is_array($metadata['multiple_answer'] ?? null)
            ? $metadata['multiple_answer']
            : [];
        $defaultScore = (float) ($question->default_weight ?? 1);

        return [
            'score_correct' => (float) ($multipleAnswer['score_correct'] ?? ($defaultScore > 0 ? $defaultScore : 1)),
            'score_wrong' => (float) ($multipleAnswer['score_wrong'] ?? 0),
            'scoring_mode' => in_array($multipleAnswer['scoring_mode'] ?? null, ['fullscore', 'partial'], true)
                ? $multipleAnswer['scoring_mode']
                : 'fullscore',
        ];
    }

    private function options(Question $question): Collection
    {
        return $question->relationLoaded('questionOptions')
            ? $question->questionOptions
            : $question->questionOptions()->get();
    }
}
