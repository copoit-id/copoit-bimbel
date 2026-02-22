<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EssayReviewController extends Controller
{
    public function index()
    {
        $rows = UserAnswerDetail::query()
            ->select([
                'user_answers.tryout_id',
                DB::raw('COUNT(*) as pending_count'),
                DB::raw('MAX(user_answer_details.answered_at) as last_answered_at'),
            ])
            ->join('user_answers', 'user_answer_details.user_answer_id', '=', 'user_answers.user_answer_id')
            ->join('questions', 'user_answer_details.question_id', '=', 'questions.question_id')
            ->where('questions.question_type', 'essay')
            ->where('user_answer_details.answer_json->pending_review', true)
            ->groupBy('user_answers.tryout_id')
            ->orderByDesc('pending_count')
            ->get();

        $tryouts = \App\Models\Tryout::with('tryoutDetails')->whereIn('tryout_id', $rows->pluck('tryout_id'))
            ->get()
            ->keyBy('tryout_id');

        $pendingTryouts = $rows->map(function ($row) use ($tryouts) {
            return [
                'tryout' => $tryouts->get($row->tryout_id),
                'pending_count' => (int) $row->pending_count,
                'last_answered_at' => $row->last_answered_at,
            ];
        })->filter(fn ($row) => $row['tryout'])->values();

        return view('admin.pages.essay-review.index', compact('pendingTryouts'));
    }

    public function tryout($tryoutId)
    {
        $tryout = \App\Models\Tryout::findOrFail($tryoutId);

        $rows = UserAnswerDetail::query()
            ->select([
                'user_answers.user_id',
                DB::raw('COUNT(*) as pending_count'),
                DB::raw('MAX(user_answer_details.answered_at) as last_answered_at'),
            ])
            ->join('user_answers', 'user_answer_details.user_answer_id', '=', 'user_answers.user_answer_id')
            ->join('questions', 'user_answer_details.question_id', '=', 'questions.question_id')
            ->where('user_answers.tryout_id', $tryout->tryout_id)
            ->where('questions.question_type', 'essay')
            ->where('user_answer_details.answer_json->pending_review', true)
            ->groupBy('user_answers.user_id')
            ->orderByDesc('pending_count')
            ->get();

        $users = \App\Models\User::whereIn('id', $rows->pluck('user_id'))
            ->get()
            ->keyBy('id');

        $pendingUsers = $rows->map(function ($row) use ($users) {
            return [
                'user' => $users->get($row->user_id),
                'pending_count' => (int) $row->pending_count,
                'last_answered_at' => $row->last_answered_at,
            ];
        })->filter(fn ($row) => $row['user'])->values();

        return view('admin.pages.essay-review.tryout', compact('tryout', 'pendingUsers'));
    }

    public function user($tryoutId, $userId)
    {
        $tryout = \App\Models\Tryout::findOrFail($tryoutId);
        $user = \App\Models\User::findOrFail($userId);

        $reviews = UserAnswerDetail::with([
            'question',
            'userAnswer.tryoutDetail',
        ])
            ->whereHas('question', function ($query) {
                $query->where('question_type', 'essay');
            })
            ->whereHas('userAnswer', function ($query) use ($tryoutId, $userId) {
                $query->where('tryout_id', $tryoutId)
                    ->where('user_id', $userId);
            })
            ->where('answer_json->pending_review', true)
            ->orderByDesc('answered_at')
            ->get();

        return view('admin.pages.essay-review.user', compact('tryout', 'user', 'reviews'));
    }

    public function review(Request $request, $detailId)
    {
        $validated = $request->validate([
            'result' => ['required', 'in:correct,incorrect'],
        ]);

        $detail = UserAnswerDetail::with([
            'question',
            'userAnswer',
            'userAnswer.tryoutDetail',
        ])->where('user_answer_detail_id', $detailId)->firstOrFail();

        if (!$detail->question || $detail->question->question_type !== 'essay') {
            return redirect()
                ->back()
                ->with('error', 'Jawaban ini bukan essay.');
        }

        $isCorrect = $validated['result'] === 'correct';
        $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $answerMeta['pending_review'] = false;
        $answerMeta['reviewed_by'] = Auth::id();
        $answerMeta['reviewed_at'] = Carbon::now('Asia/Jakarta')->toIso8601String();

        $detail->update([
            'is_correct' => $isCorrect,
            'answer_json' => $answerMeta,
        ]);

        if ($detail->userAnswer) {
            $this->recalculateSubtestStats($detail->userAnswer);
        }

        return redirect()
            ->back()
            ->with('success', 'Koreksi essay disimpan.');
    }

    private function recalculateSubtestStats(UserAnswer $userAnswer): void
    {
        $userAnswerDetails = UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
            ->with(['questionOption', 'question'])
            ->get();

        $totalQuestions = Question::where('tryout_detail_id', $userAnswer->tryout_detail_id)->count();
        $correctAnswers = 0;
        $wrongAnswers = 0;
        $totalScore = 0;

        foreach ($userAnswerDetails as $detail) {
            $question = $detail->question;
            if (!$question) {
                continue;
            }

            $questionType = $question->question_type ?? 'multiple_choice';
            $pendingReview = false;
            $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
            if (isset($answerMeta['pending_review'])) {
                $pendingReview = (bool) $answerMeta['pending_review'];
            }

            switch ($questionType) {
                case 'multiple_answer':
                    if ($detail->is_correct) {
                        $correctAnswers++;
                    } else {
                        $wrongAnswers++;
                    }

                    $totalScore += $this->resolveMultipleAnswerAwardedScore($question, $detail);
                    break;

                case 'matching':
                    if ($detail->is_correct) {
                        $correctAnswers++;
                    } else {
                        $wrongAnswers++;
                    }

                    $totalScore += $this->resolveMatchingAwardedScore($question, $detail);
                    break;

                case 'short_answer':
                case 'essay':
                    if ($pendingReview) {
                        continue 2;
                    }

                    if ($detail->is_correct) {
                        $correctAnswers++;
                    } else {
                        $wrongAnswers++;
                    }

                    $weight = (float) ($question->default_weight ?? 1);
                    $totalScore += $detail->is_correct ? ($weight > 0 ? $weight : 1) : 0;
                    break;

                case 'audio':
                    continue 2;

                default:
                    if ($detail->questionOption) {
                        if ($detail->is_correct) {
                            $correctAnswers++;
                        } else {
                            $wrongAnswers++;
                        }

                        switch ($userAnswer->tryoutDetail->type_subtest) {
                            case 'twk':
                            case 'tiu':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 5) : 0;
                                break;
                            case 'tkp':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $w > 0 ? min($w, 1) : 1;
                                break;
                            case 'writing':
                            case 'reading':
                            case 'listening':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 10) : 0;
                                break;
                            default:
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 1) : 0;
                                break;
                        }
                    }
                    break;
            }
        }

        $unanswered = max(0, $totalQuestions - $userAnswerDetails->count());
        $maxScore = $this->getMaxPossibleScoreForDetail(
            $userAnswer->tryout_detail_id,
            $userAnswer->tryoutDetail->type_subtest
        );
        $percentage = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;

        $userAnswer->update([
            'correct_answers' => $correctAnswers,
            'wrong_answers' => $wrongAnswers,
            'unanswered' => $unanswered,
            'score' => $percentage,
        ]);
    }

    private function resolveMultipleAnswerAwardedScore(Question $question, UserAnswerDetail $detail): float
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
            $matchedCorrect = count(array_intersect($selectedIds, $correctIds));
            $wrongSelected = max(0, count($selectedIds) - $matchedCorrect);
            $scoreCorrect = (float) ($multipleAnswerMeta['score_correct'] ?? (($maxWeight > 0 && count($correctIds) > 0) ? ($maxWeight / count($correctIds)) : 1));
            $scoreWrong = (float) ($multipleAnswerMeta['score_wrong'] ?? 0);
            $scoringMode = in_array(($multipleAnswerMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
                ? $multipleAnswerMeta['scoring_mode']
                : 'fullscore';
            $totalCorrectCount = max(1, count($correctIds));
            $missedCorrect = max(0, $totalCorrectCount - $matchedCorrect);
            $wrongCount = $missedCorrect + $wrongSelected;
            $isExactCorrect = ($selectedIds === $correctIds);
            $fullScore = $scoreCorrect;
            $score = 0.0;

            if ($scoringMode === 'partial') {
                $score = $matchedCorrect > 0
                    ? ($matchedCorrect / $totalCorrectCount) * $fullScore
                    : $scoreWrong;
            } else {
                $score = $isExactCorrect ? $scoreCorrect : $scoreWrong;
            }

            return max(0, $score);
        }

        $storedScore = $meta['score_obtained'] ?? null;
        if (is_numeric($storedScore)) {
            return max(0, min((float) $storedScore, $maxWeight));
        }

        return $detail->is_correct ? $maxWeight : 0;
    }

    private function resolveMatchingAwardedScore(Question $question, UserAnswerDetail $detail): float
    {
        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $questionMeta = is_array($question->metadata) ? $question->metadata : [];
        $matchingMeta = is_array($questionMeta['matching_scores'] ?? null) ? $questionMeta['matching_scores'] : [];
        $scoreCorrect = (float) ($matchingMeta['score_correct'] ?? 1);
        $scoreWrong = (float) ($matchingMeta['score_wrong'] ?? 0);
        $scoringMode = in_array(($matchingMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
            ? $matchingMeta['scoring_mode']
            : 'fullscore';

        $summary = is_array($meta['summary'] ?? null) ? $meta['summary'] : [];
        $correctCount = (int) ($summary['correct'] ?? 0);
        $totalCount = (int) ($summary['total'] ?? 0);
        $wrongCount = max(0, $totalCount - $correctCount);

        if ($totalCount > 0) {
            $fullScore = max(0, $scoreCorrect);
            $isExactCorrect = ($correctCount === $totalCount);
            $score = 0.0;
            if ($scoringMode === 'partial') {
                $score = $correctCount > 0
                    ? ($correctCount / $totalCount) * $fullScore
                    : $scoreWrong;
            } else {
                $score = $isExactCorrect ? $fullScore : $scoreWrong;
            }

            return max(0, $score);
        }

        $storedScore = $meta['score_obtained'] ?? null;
        if (is_numeric($storedScore)) {
            return max(0, (float) $storedScore);
        }

        $weight = (float) ($question->default_weight ?? 1);
        return $detail->is_correct ? max(0, $weight) : 0;
    }

    private function getMaxPossibleScoreForDetail(int $tryoutDetailId, string $typeSubtest): float
    {
        $questions = Question::where('tryout_detail_id', $tryoutDetailId)
            ->with('questionOptions')
            ->get();

        if ($questions->isEmpty()) {
            return 0;
        }

        $total = 0;

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
                    switch ($typeSubtest) {
                        case 'twk':
                        case 'tiu':
                            $maxWeight = (float) ($question->questionOptions->max('weight') ?? 0);
                            $total += $maxWeight > 0 ? $maxWeight : 5;
                            break;
                        case 'tkp':
                            $maxWeight = (float) ($question->questionOptions->max('weight') ?? 0);
                            $total += $maxWeight > 0 ? min($maxWeight, 1) : 1;
                            break;
                        case 'writing':
                        case 'reading':
                        case 'listening':
                            $maxWeight = (float) ($question->questionOptions->max('weight') ?? 0);
                            $total += $maxWeight > 0 ? $maxWeight : 10;
                            break;
                        default:
                            $maxWeight = (float) ($question->questionOptions->max('weight') ?? 0);
                            $total += $maxWeight > 0 ? $maxWeight : 1;
                            break;
                    }
                    break;
            }
        }

        return $total;
    }
}
