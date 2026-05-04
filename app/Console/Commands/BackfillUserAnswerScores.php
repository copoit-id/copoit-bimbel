<?php

namespace App\Console\Commands;

use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use App\Models\Question;
use Illuminate\Console\Command;

class BackfillUserAnswerScores extends Command
{
    protected $signature = 'app:backfill-user-answer-scores {--limit=1000 : Limit number of records to process}';

    protected $description = 'Backfill raw_score and max_score for existing user_answers records';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $processed = 0;
        $updated = 0;

        $this->info('Starting to backfill scores...');

        UserAnswer::where('status', 'completed')
            ->where(function ($query) {
                $query->whereNull('raw_score')
                    ->orWhere('max_score', 0);
            })
            ->with(['tryoutDetail', 'userAnswerDetails.question', 'userAnswerDetails.questionOption'])
            ->limit($limit)
            ->chunk(100, function ($userAnswers) use (&$processed, &$updated) {
                foreach ($userAnswers as $userAnswer) {
                    $type = $userAnswer->tryoutDetail->type_subtest ?? null;

                    if (!$type) {
                        continue;
                    }

                    $rawScore = $this->calculateTotalScore($userAnswer, $type);
                    $maxScore = $this->getMaxPossibleScoreForDetail(
                        $userAnswer->tryout_detail_id,
                        $type
                    );

                    $userAnswer->update([
                        'raw_score' => $rawScore,
                        'max_score' => $maxScore,
                    ]);

                    $processed++;
                    $updated++;

                    if ($updated % 100 === 0) {
                        $this->info("Updated {$updated} records...");
                    }
                }
            });

        $this->info("Done! Processed {$processed} records, updated {$updated}.");
    }

    private function calculateTotalScore(UserAnswer $userAnswer, string $type_subtest): float
    {
        $totalScore = 0.0;

        $details = UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
            ->with(['questionOption', 'question'])
            ->get();

        foreach ($details as $detail) {
            $question = $detail->question;
            if (!$question) {
                continue;
            }

            $questionType = $question->question_type ?? 'multiple_choice';
            $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];

            switch ($questionType) {
                case 'multiple_answer':
                    $totalScore += $this->resolveMultipleAnswerScore($question, $detail);
                    break;

                case 'matching':
                    $totalScore += $this->resolveMatchingScore($question, $detail);
                    break;

                case 'short_answer':
                case 'essay':
                    if (!empty($answerMeta['pending_review'])) {
                        continue 2;
                    }
                    $weight = (float) ($question->default_weight ?? 1);
                    $totalScore += $detail->is_correct ? ($weight > 0 ? $weight : 1) : 0;
                    break;

                case 'audio':
                    continue 2;

                default:
                    if ($detail->questionOption) {
                        $w = (float) ($detail->questionOption->weight ?? 0);
                        switch ($type_subtest) {
                            case 'twk':
                            case 'tiu':
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 5) : 0;
                                break;
                            case 'tkp':
                                $totalScore += $w > 0 ? $w : 1;
                                break;
                            case 'writing':
                            case 'reading':
                            case 'listening':
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 10) : 0;
                                break;
                            default:
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 1) : 0;
                                break;
                        }
                    }
                    break;
            }
        }

        return $totalScore;
    }

    private function resolveMultipleAnswerScore($question, $detail): float
    {
        $defaultWeight = (float) ($question->default_weight ?? 1);
        $maxWeight = $defaultWeight > 0 ? $defaultWeight : 1;
        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $selectedIds = collect($meta['selected_option_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (!empty($selectedIds)) {
            $correctIds = $question->questionOptions()
                ->where('is_correct', true)
                ->pluck('question_option_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            sort($selectedIds);
            sort($correctIds);
            $multipleAnswerMeta = is_array($question->metadata) ? ($question->metadata['multiple_answer'] ?? []) : [];
            $scoreCorrect = (float) ($multipleAnswerMeta['score_correct'] ?? $maxWeight);
            $isExactCorrect = ($selectedIds === $correctIds);
            $score = $isExactCorrect ? $scoreCorrect : ($multipleAnswerMeta['score_wrong'] ?? 0);

            return max(0, $score);
        }

        return $detail->is_correct ? $maxWeight : 0;
    }

    private function resolveMatchingScore($question, $detail): float
    {
        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $questionMeta = is_array($question->metadata) ? $question->metadata : [];
        $matchingMeta = is_array($questionMeta['matching_scores'] ?? null) ? $questionMeta['matching_scores'] : [];
        $scoreCorrect = (float) ($matchingMeta['score_correct'] ?? 1);

        $summary = is_array($meta['summary'] ?? null) ? $meta['summary'] : [];
        $correctCount = (int) ($summary['correct'] ?? 0);
        $totalCount = (int) ($summary['total'] ?? 0);

        if ($totalCount > 0) {
            return $correctCount === $totalCount ? max(0, $scoreCorrect) : 0;
        }

        return $detail->is_correct ? max(0, $scoreCorrect) : 0;
    }

    private function getMaxPossibleScoreForDetail(int $tryoutDetailId, ?string $type_subtest): float
    {
        $questions = Question::where('tryout_detail_id', $tryoutDetailId)
            ->with('questionOptions')
            ->get();

        if ($questions->isEmpty()) {
            return 0;
        }

        $total = 0.0;

        foreach ($questions as $question) {
            $questionType = $question->question_type ?? 'multiple_choice';

            switch ($questionType) {
                case 'multiple_answer':
                    $weight = (float) ($question->default_weight ?? 1);
                    $total += $weight > 0 ? $weight : 1;
                    break;

                case 'matching':
                    $matchingMeta = is_array($question->metadata['matching_scores'] ?? null) ? $question->metadata['matching_scores'] : [];
                    $weight = (float) ($matchingMeta['score_correct'] ?? ($question->default_weight ?? 0));
                    if ($weight <= 0) {
                        $weight = 1;
                    }
                    $total += $weight;
                    break;

                case 'short_answer':
                case 'essay':
                    $weight = (float) ($question->default_weight ?? 1);
                    $total += $weight > 0 ? $weight : 1;
                    break;

                case 'audio':
                    break;

                default:
                    $options = $question->questionOptions;
                    switch ($type_subtest) {
                        case 'twk':
                        case 'tiu':
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 5;
                            break;
                        case 'tkp':
                            $maxWeight = (float) ($options->max('weight') ?? 0);
                            $total += $maxWeight > 0 ? $maxWeight : 1;
                            break;
                        case 'writing':
                        case 'reading':
                        case 'listening':
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 10;
                            break;
                        default:
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 1;
                            break;
                    }
                    break;
            }
        }

        return $total;
    }
}
