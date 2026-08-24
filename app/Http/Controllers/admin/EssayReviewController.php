<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEssayCorrection;
use App\Models\EssayCorrectionJob;
use App\Models\Question;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use App\Services\PlanQuotaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EssayReviewController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'manual');

        if ($tab === 'automatic') {
            return $this->indexAutomatic($request);
        }

        return $this->indexManual($request);
    }

    /**
     * Tab Manual: Essay yang memang butuh koreksi manual (bukan AI)
     */
    protected function indexManual(Request $request)
    {
        // Essay dengan evaluation_mode = 'manual' atau tidak ada kunci jawaban
        $rows = UserAnswerDetail::query()
            ->select([
                'user_answers.tryout_id',
                DB::raw('COUNT(*) as pending_count'),
                DB::raw('MAX(user_answer_details.answered_at) as last_answered_at'),
            ])
            ->join('user_answers', 'user_answer_details.user_answer_id', '=', 'user_answers.user_answer_id')
            ->join('questions', 'user_answer_details.question_id', '=', 'questions.question_id')
            ->where('questions.question_type', 'essay')
            ->whereRaw("JSON_EXTRACT(user_answer_details.answer_json, '$.pending_review') = true")
            // Filter hanya yang manual (evaluation_mode = 'manual' atau tidak ada expected_answers)
            ->where(function ($query) {
                $query->whereRaw("JSON_EXTRACT(user_answer_details.answer_json, '$.evaluation_mode') = 'manual'")
                    ->orWhereRaw("JSON_LENGTH(JSON_EXTRACT(user_answer_details.answer_json, '$.expected_answers')) = 0")
                    ->orWhereRaw("JSON_EXTRACT(user_answer_details.answer_json, '$.expected_answers') IS NULL");
            })
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

        return view('admin.pages.essay-review.index', [
            'tab' => 'manual',
            'pendingTryouts' => $pendingTryouts,
        ]);
    }

    /**
     * Tab Otomatis: Essay yang sedang/sudah diproses AI
     */
    protected function indexAutomatic(Request $request)
    {
        // Essay dengan evaluation_mode = 'auto' dan ada expected_answers (AI yang handle)
        $autoTryoutIds = UserAnswerDetail::query()
            ->join('user_answers', 'user_answer_details.user_answer_id', '=', 'user_answers.user_answer_id')
            ->join('questions', 'user_answer_details.question_id', '=', 'questions.question_id')
            ->where('questions.question_type', 'essay')
            ->whereRaw("JSON_EXTRACT(user_answer_details.answer_json, '$.evaluation_mode') = 'auto'")
            ->whereRaw("JSON_LENGTH(JSON_EXTRACT(user_answer_details.answer_json, '$.expected_answers')) > 0")
            ->distinct('user_answers.tryout_id')
            ->pluck('user_answers.tryout_id');

        // Ambil semua job (sedang berjalan atau selesai 7 hari terakhir)
        $jobs = EssayCorrectionJob::with(['tryout', 'user', 'userAnswer'])
            ->whereIn('tryout_id', $autoTryoutIds)
            ->where(function ($query) {
                $query->whereIn('status', ['pending', 'queued', 'processing'])
                    ->orWhere(function ($q) {
                        $q->whereIn('status', ['completed', 'failed'])
                            ->where('updated_at', '>=', now()->subDays(7));
                    });
            })
            ->orderByDesc('created_at')
            ->get();

        // Stats lengkap
        $stats = [
            'total_pending_ai' => EssayCorrectionJob::where('status', 'pending')->count(),
            'total_processing' => EssayCorrectionJob::whereIn('status', ['queued', 'processing'])->count(),
            'total_completed' => EssayCorrectionJob::where('status', 'completed')->count(),
            'total_failed' => EssayCorrectionJob::where('status', 'failed')->count(),
            'total_completed_today' => EssayCorrectionJob::where('status', 'completed')
                ->whereDate('created_at', today())
                ->count(),
        ];

        return view('admin.pages.essay-review.index', [
            'tab' => 'automatic',
            'jobs' => $jobs,
            'stats' => $stats,
        ]);
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
            ->whereRaw("JSON_EXTRACT(user_answer_details.answer_json, '$.pending_review') = true")
            // Hanya yang manual
            ->where(function ($query) {
                $query->whereRaw("JSON_EXTRACT(user_answer_details.answer_json, '$.evaluation_mode') = 'manual'")
                    ->orWhereRaw("JSON_LENGTH(JSON_EXTRACT(user_answer_details.answer_json, '$.expected_answers')) = 0")
                    ->orWhereRaw("JSON_EXTRACT(user_answer_details.answer_json, '$.expected_answers') IS NULL");
            })
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
            ->whereRaw("JSON_EXTRACT(answer_json, '$.pending_review') = true")
            // Hanya yang manual
            ->where(function ($query) {
                $query->whereRaw("JSON_EXTRACT(answer_json, '$.evaluation_mode') = 'manual'")
                    ->orWhereRaw("JSON_LENGTH(JSON_EXTRACT(answer_json, '$.expected_answers')) = 0")
                    ->orWhereRaw("JSON_EXTRACT(answer_json, '$.expected_answers') IS NULL");
            })
            ->orderByDesc('answered_at')
            ->get();

        return view('admin.pages.essay-review.user', compact('tryout', 'user', 'reviews'));
    }

    public function review(Request $request, $detailId)
    {
        $validated = $request->validate([
            'result' => ['required', 'in:correct,incorrect'],
            'score_obtained' => ['nullable', 'numeric', 'min:0'],
            'similarity' => ['nullable', 'numeric', 'min:0', 'max:100'],
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

        $question = $detail->question;
        $isCorrect = $validated['result'] === 'correct';
        
        // Hitung score_obtained
        $scoreCorrect = $question->getEssayScoreCorrect();
        $scoreWrong = $question->getEssayScoreWrong();
        
        if (isset($validated['score_obtained'])) {
            $scoreObtained = (float) $validated['score_obtained'];
        } else {
            $scoreObtained = $isCorrect ? $scoreCorrect : $scoreWrong;
        }

        $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $answerMeta['pending_review'] = false;
        $answerMeta['reviewed_by'] = Auth::id();
        $answerMeta['reviewed_at'] = Carbon::now('Asia/Jakarta')->toIso8601String();
        $answerMeta['manual_review'] = true;
        $answerMeta['score_obtained'] = $scoreObtained;
        $answerMeta['essay_scoring_mode'] = $question->essay_scoring_mode;
        $answerMeta['essay_score_correct'] = $scoreCorrect;
        $answerMeta['essay_score_wrong'] = $scoreWrong;
        
        if (isset($validated['similarity'])) {
            $answerMeta['manual_similarity'] = (float) $validated['similarity'] / 100;
        }

        $detail->update([
            'is_correct' => $isCorrect,
            'answer_json' => $answerMeta,
        ]);

        // Simpan ke history
        $this->logManualCorrection($detail, $scoreObtained);

        if ($detail->userAnswer) {
            $this->recalculateSubtestStats($detail->userAnswer);
        }

        return redirect()
            ->back()
            ->with('success', 'Koreksi essay disimpan. Skor: ' . $scoreObtained);
    }

    /**
     * Log manual correction ke history
     */
    private function logManualCorrection(UserAnswerDetail $detail, float $scoreObtained): void
    {
        try {
            $userAnswer = $detail->userAnswer;
            if (!$userAnswer) return;

            $job = EssayCorrectionJob::firstOrCreate(
                [
                    'tryout_id' => $userAnswer->tryout_id,
                    'user_id' => $userAnswer->user_id,
                    'job_type' => 'manual',
                    'status' => 'completed',
                ],
                [
                    'total_essays' => 1,
                    'processed_essays' => 0,
                    'method' => 'manual',
                    'completed_at' => Carbon::now(),
                ]
            );

            $job->increment('processed_essays');
            
            if ($detail->is_correct) {
                $job->increment('correct_count');
            } else {
                $job->increment('incorrect_count');
            }
        } catch (\Exception $e) {
            \Log::error("Failed to log manual correction: " . $e->getMessage());
        }
    }

    public function getJobStatus(Request $request)
    {
        $jobIds = $request->get('job_ids', []);
        
        // Pastikan job_ids adalah array
        if (is_string($jobIds)) {
            $jobIds = explode(',', $jobIds);
        }
        
        $jobIds = array_filter(array_map('intval', (array) $jobIds));
        
        if (empty($jobIds)) {
            return response()->json([]);
        }

        $jobs = EssayCorrectionJob::whereIn('id', $jobIds)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'status' => $job->status,
                    'status_label' => $job->status_label,
                    'status_color' => $job->status_color,
                    'progress_percentage' => $job->progress_percentage,
                    'total_essays' => $job->total_essays,
                    'processed_essays' => $job->processed_essays,
                    'correct_count' => $job->correct_count,
                    'incorrect_count' => $job->incorrect_count,
                    'total_similarity_score' => $job->total_similarity_score,
                    'error_message' => $job->error_message,
                    'duration' => $job->duration,
                    'updated_at' => $job->updated_at->diffForHumans(),
                    'method_label' => $job->method_label,
                    'total_similarity_score' => $job->total_similarity_score,
                ];
            });

        return response()->json($jobs);
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

                case 'multiple_true_false':
                    if ($detail->is_correct) {
                        $correctAnswers++;
                    } else {
                        $wrongAnswers++;
                    }

                    $totalScore += $this->resolveMultipleTrueFalseAwardedScore($question, $detail);
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

                    // Gunakan score_obtained kalau ada
                    if (isset($answerMeta['score_obtained'])) {
                        $totalScore += (float) $answerMeta['score_obtained'];
                    } else {
                        // Fallback ke binary scoring
                        $weight = $question->getEssayScoreCorrect();
                        $totalScore += $detail->is_correct ? $weight : $question->getEssayScoreWrong();
                    }
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

    private function resolveMultipleTrueFalseAwardedScore(Question $question, UserAnswerDetail $detail): float
    {
        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $questionMeta = is_array($question->metadata) ? ($question->metadata['multiple_true_false'] ?? []) : [];
        $scoreCorrect = (float) ($questionMeta['score_correct'] ?? ($question->default_weight ?? 1));
        $scoreWrong = (float) ($questionMeta['score_wrong'] ?? 0);
        $scoringMode = in_array(($questionMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
            ? $questionMeta['scoring_mode']
            : 'fullscore';

        $summary = is_array($meta['summary'] ?? null) ? $meta['summary'] : [];
        $correctCount = (int) ($summary['correct'] ?? 0);
        $totalCount = (int) ($summary['total'] ?? 0);

        if ($totalCount > 0) {
            $fullScore = max(0, $scoreCorrect);
            $isExactCorrect = $correctCount === $totalCount;
            if ($scoringMode === 'partial') {
                return max(0, $correctCount > 0 ? ($correctCount / $totalCount) * $fullScore : $scoreWrong);
            }

            return max(0, $isExactCorrect ? $fullScore : $scoreWrong);
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

                case 'multiple_true_false':
                    $mtfMeta = is_array($question->metadata['multiple_true_false'] ?? null) ? $question->metadata['multiple_true_false'] : [];
                    $weight = (float) ($mtfMeta['score_correct'] ?? ($question->default_weight ?? 0));
                    if ($weight <= 0) {
                        $weight = 1;
                    }
                    $total += $weight;
                    break;

                case 'short_answer':
                case 'essay':
                    // Max score untuk essay adalah essay_score_correct
                    $weight = $question->getEssayScoreCorrect();
                    $total += $weight;
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

    /**
     * Delete AI correction job
     */
    public function deleteJob($jobId)
    {
        try {
            $job = EssayCorrectionJob::findOrFail($jobId);
            
            // Hanya bisa hapus job yang completed atau failed
            if (!in_array($job->status, ['completed', 'failed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya bisa menghapus job yang selesai atau gagal'
                ], 422);
            }
            
            $job->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Job berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry failed AI correction job
     */
    public function retryJob($jobId)
    {
        try {
            $job = EssayCorrectionJob::findOrFail($jobId);
            
            if ($job->status !== 'failed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya bisa retry job yang gagal'
                ], 422);
            }
            
            // Cek quota Essay AI - backend validation
            $quotaCheck = PlanQuotaService::canUseEssayAI();
            if (!$quotaCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $quotaCheck['reason'] ?? 'Kuota Essay AI habis atau fitur tidak tersedia'
                ], 403);
            }
            
            // Reset job status
            $job->update([
                'status' => 'pending',
                'error_message' => null,
                'processed_essays' => 0,
                'correct_count' => 0,
                'incorrect_count' => 0,
            ]);
            
            // Dispatch ulang
            ProcessEssayCorrection::dispatch($job->id);
            
            return response()->json([
                'success' => true,
                'message' => 'Job berhasil di-retry'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
