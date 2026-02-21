<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Tryout;
use App\Models\TryoutUserTimeAdjustment;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class LaporanController extends Controller
{
    public function index()
    {
        $tryouts = $this->buildTryoutReportQuery()
            ->paginate(10);

        $this->hydrateTryoutReport($tryouts->getCollection());

        $summary = [
            'total_tryouts' => Tryout::count(),
            'active_tryouts' => Tryout::where('is_active', true)->count(),
            'total_attempts' => UserAnswer::count(),
            'completed_attempts' => UserAnswer::where('status', 'completed')->count(),
        ];

        return view('admin.pages.laporan.index', compact('tryouts', 'summary'));
    }

    public function exportExcel()
    {
        $tryouts = $this->buildTryoutReportQuery()->get();
        $this->hydrateTryoutReport($tryouts);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Tryout',
            'Tipe',
            'Subtest',
            'Total Soal',
            'Durasi (menit)',
            'Peserta',
            'Selesai',
            'Completion (%)',
            'Rata-rata Skor',
            'Status',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);

        $row = 2;
        foreach ($tryouts as $tryout) {
            $sheet->fromArray([
                $tryout->name,
                $tryout->type_tryout === 'utbk_full' ? 'UTBK' : ucfirst($tryout->type_tryout),
                $tryout->tryoutDetails->count(),
                $tryout->total_questions,
                $tryout->total_duration,
                $tryout->total_attempts,
                $tryout->completed_attempts,
                $tryout->completion_rate,
                $tryout->avg_score . '%',
                $tryout->is_active ? 'Aktif' : 'Tidak Aktif',
            ], null, 'A' . $row);

            $row++;
        }

        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'laporan-tryout-' . Carbon::now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf()
    {
        $tryouts = $this->buildTryoutReportQuery()->get();
        $this->hydrateTryoutReport($tryouts);

        $html = view('admin.pages.laporan.export-pdf', [
            'tryouts' => $tryouts,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'laporan-tryout-' . Carbon::now()->format('Ymd_His') . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function show($id)
    {
        $tryout = Tryout::with([
            'tryoutDetails' => function ($query) {
                $query->withCount('questions');
            },
            'packages',
            'directPackage',
        ])->findOrFail($id);

        $participantGroups = UserAnswer::where('tryout_id', $tryout->tryout_id)
            ->select('user_id', 'attempt_token')
            ->groupBy('user_id', 'attempt_token')
            ->get();

        $completedParticipantGroups = UserAnswer::where('tryout_id', $tryout->tryout_id)
            ->where('status', 'completed')
            ->select('user_id', 'attempt_token')
            ->groupBy('user_id', 'attempt_token')
            ->get();

        $attemptSummaries = UserAnswer::selectRaw("
                user_id,
                tryout_id,
                attempt_token,
                MIN(started_at) as started_at,
                MAX(finished_at) as finished_at,
                SUM(correct_answers) as total_correct,
                SUM(wrong_answers) as total_wrong,
                SUM(unanswered) as total_unanswered,
                AVG(score) as average_score,
                MAX(status) as attempt_status
            ")
            ->where('tryout_id', $tryout->tryout_id)
            ->groupBy('user_id', 'tryout_id', 'attempt_token')
            ->orderByDesc(DB::raw('MAX(finished_at)'))
            ->with('user')
            ->get();

        $answersByAttempt = UserAnswer::where('tryout_id', $tryout->tryout_id)
            ->whereIn('user_id', $attemptSummaries->pluck('user_id')->unique())
            ->whereIn('attempt_token', $attemptSummaries->pluck('attempt_token')->unique())
            ->with(['tryoutDetail', 'userAnswerDetails.question', 'userAnswerDetails.questionOption'])
            ->get()
            ->groupBy(['user_id', 'attempt_token']);

        $participants = $attemptSummaries->groupBy('user_id')
            ->map(function ($attempts) use ($answersByAttempt) {
                $attempts = $attempts->map(function ($attempt) use ($answersByAttempt) {
                    $userAnswers = $answersByAttempt[$attempt->user_id][$attempt->attempt_token] ?? collect();
                    $rawScore = $userAnswers->sum(function ($answer) {
                        return $this->calculateTotalScore($answer, optional($answer->tryoutDetail)->type_subtest);
                    });
                    $attempt->raw_score = $rawScore;

                    return $attempt;
                });

                $sortedAttempts = $attempts->sortByDesc('finished_at')->values();
                $latest = $sortedAttempts->first();

                $status = 'belum_mengerjakan';
                if ($attempts->where('attempt_status', 'in_progress')->count() > 0) {
                    $status = 'sedang_mengerjakan';
                } elseif ($attempts->whereIn('attempt_status', ['completed', 'pending_release'])->count() > 0) {
                    $status = 'selesai';
                }

                return [
                    'user' => $latest->user,
                    'total_attempts' => $attempts->count(),
                    'latest_score' => round($latest->raw_score ?? 0, 1),
                    'last_finished' => $latest->finished_at,
                    'attempts' => $sortedAttempts,
                    'status' => $status,
                ];
            })
            ->values();

        $timeAdjustments = TryoutUserTimeAdjustment::where('tryout_id', $tryout->tryout_id)
            ->pluck('extra_minutes', 'user_id');

        $participants = $participants->map(function ($participant) use ($timeAdjustments) {
            $participant['extra_minutes'] = (int) ($timeAdjustments[$participant['user']->id] ?? 0);
            return $participant;
        });

        $statistics = [
            'total_subtests' => $tryout->tryoutDetails->count(),
            'total_questions' => $tryout->tryoutDetails->sum('questions_count'),
            'total_duration' => $tryout->tryoutDetails->sum('duration'),
            'total_participants' => $participantGroups->count(),
            'completed_participants' => $completedParticipantGroups->count(),
            'average_score' => round(UserAnswer::where('tryout_id', $tryout->tryout_id)->where('status', 'completed')->avg('score') ?? 0, 1),
            'highest_score' => round(UserAnswer::where('tryout_id', $tryout->tryout_id)->max('score') ?? 0, 1),
        ];

        $statistics['completion_rate'] = $statistics['total_participants'] > 0
            ? round(($statistics['completed_participants'] / $statistics['total_participants']) * 100)
            : 0;

        $leaderboardPackageId = optional($tryout->packages->first())->package_id
            ?? optional($tryout->directPackage)->package_id;

        $liveScore = $this->buildLiveScoreBoard($tryout);
        $publicLiveScoreUrl = URL::signedRoute('laporan.live-score.public', [
            'tryout' => $tryout->tryout_id,
        ]);

        return view('admin.pages.laporan.show', compact(
            'tryout',
            'statistics',
            'participants',
            'leaderboardPackageId',
            'liveScore',
            'publicLiveScoreUrl'
        ));
    }

    public function addTime(Request $request, $tryoutId, $userId)
    {
        $request->validate([
            'extra_minutes' => 'required|integer|min:0|max:300',
        ]);

        $extraMinutes = (int) $request->input('extra_minutes');

        if ($extraMinutes === 0) {
            TryoutUserTimeAdjustment::where('tryout_id', $tryoutId)
                ->where('user_id', $userId)
                ->delete();
        } else {
            TryoutUserTimeAdjustment::updateOrCreate(
                ['tryout_id' => $tryoutId, 'user_id' => $userId],
                ['extra_minutes' => $extraMinutes]
            );
        }

        return redirect()->route('admin.laporan.show', $tryoutId)
            ->with('success', 'Waktu tambahan berhasil disimpan.');
    }

    public function resetUserAttempt($tryoutId, $userId)
    {
        DB::transaction(function () use ($tryoutId, $userId) {
            $answers = UserAnswer::where('tryout_id', $tryoutId)
                ->where('user_id', $userId)
                ->pluck('user_answer_id');

            if ($answers->isNotEmpty()) {
                \App\Models\UserAnswerDetail::whereIn('user_answer_id', $answers)->delete();
                UserAnswer::whereIn('user_answer_id', $answers)->delete();
            }
        });

        return redirect()->route('admin.laporan.show', $tryoutId)
            ->with('success', 'Pengerjaan user berhasil direset.');
    }

    public function resetAttempt($tryoutId, $attemptToken)
    {
        DB::transaction(function () use ($tryoutId, $attemptToken) {
            $answers = UserAnswer::where('tryout_id', $tryoutId)
                ->where('attempt_token', $attemptToken)
                ->pluck('user_answer_id');

            if ($answers->isNotEmpty()) {
                \App\Models\UserAnswerDetail::whereIn('user_answer_id', $answers)->delete();
                UserAnswer::whereIn('user_answer_id', $answers)->delete();
            }
        });

        return redirect()->route('admin.laporan.show', $tryoutId)
            ->with('success', 'Attempt berhasil direset.');
    }

    public function attemptDetail($tryoutId, $attemptToken)
    {
        $tryout = Tryout::with('tryoutDetails')->findOrFail($tryoutId);

        $attemptAnswers = UserAnswer::with([
            'user',
            'tryoutDetail',
            'userAnswerDetails.question.questionOptions',
            'userAnswerDetails.questionOption',
        ])
            ->where('tryout_id', $tryout->tryout_id)
            ->where('attempt_token', $attemptToken)
            ->get();

        if ($attemptAnswers->isEmpty()) {
            abort(404);
        }

        $user = $attemptAnswers->first()->user;
        $overallStats = [
            'correct' => $attemptAnswers->sum('correct_answers'),
            'wrong' => $attemptAnswers->sum('wrong_answers'),
            'unanswered' => $attemptAnswers->sum('unanswered'),
            'score' => round($attemptAnswers->avg('score') ?? 0, 1),
            'started_at' => $attemptAnswers->min('started_at'),
            'finished_at' => $attemptAnswers->max('finished_at'),
        ];
        $overallStats['total_questions'] = $overallStats['correct'] + $overallStats['wrong'] + $overallStats['unanswered'];

        $subtests = $attemptAnswers->map(function (UserAnswer $answer) {
            return [
                'name' => $this->formatSubtestName(optional($answer->tryoutDetail)->type_subtest),
                'type' => optional($answer->tryoutDetail)->type_subtest,
                'duration' => optional($answer->tryoutDetail)->duration,
                'correct' => $answer->correct_answers,
                'wrong' => $answer->wrong_answers,
                'unanswered' => $answer->unanswered,
                'score' => round($answer->score ?? 0, 1),
            ];
        });

        $answerDetails = collect();

        foreach ($attemptAnswers as $answer) {
            foreach ($answer->userAnswerDetails as $detail) {
                $detail->subtest_name = $this->formatSubtestName(optional($answer->tryoutDetail)->type_subtest);
                $detail->subtest_type = optional($answer->tryoutDetail)->type_subtest;
                $answerDetails->push($detail);
            }
        }

        $answerDetails = $answerDetails->sortBy('subtest_name');

        return view('admin.pages.laporan.answer', compact(
            'tryout',
            'user',
            'attemptToken',
            'overallStats',
            'subtests',
            'answerDetails'
        ));
    }

    public function publicLiveScore(Request $request, $tryoutId)
    {
        $tryout = Tryout::with('tryoutDetails')->findOrFail($tryoutId);
        $liveScore = $this->buildLiveScoreBoard($tryout);

        return view('public.livescore', [
            'tryout' => $tryout,
            'liveScore' => $liveScore,
            'generatedAt' => Carbon::now('Asia/Jakarta'),
        ]);
    }

    private function formatSubtestName(?string $type): string
    {
        if (!$type) {
            return 'Subtest';
        }

        return [
            'twk' => 'Tes Wawasan Kebangsaan',
            'tiu' => 'Tes Intelegensi Umum',
            'tkp' => 'Tes Karakteristik Pribadi',
            'writing' => 'Writing Test',
            'reading' => 'Reading',
            'listening' => 'Listening',
            'word' => 'Microsoft Word',
            'excel' => 'Microsoft Excel',
            'ppt' => 'Microsoft PowerPoint',
        ][$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    private function buildTryoutReportQuery()
    {
        return Tryout::with([
            'tryoutDetails' => function ($query) {
                $query->withCount('questions');
            },
            'packages',
            'directPackage',
        ])
            ->withCount([
                'userAnswers as total_attempts',
                'userAnswers as completed_attempts' => function ($query) {
                    $query->where('status', 'completed');
                },
            ])
            ->latest();
    }

    private function hydrateTryoutReport($tryouts): void
    {
        $tryouts->transform(function (Tryout $tryout) {
            $tryout->avg_score = round(
                $tryout->userAnswers()->where('status', 'completed')->avg('score') ?? 0,
                1
            );
            $tryout->completion_rate = $tryout->total_attempts > 0
                ? round(($tryout->completed_attempts / $tryout->total_attempts) * 100)
                : 0;
            $tryout->total_questions = $tryout->tryoutDetails->sum('questions_count');
            $tryout->total_duration = $tryout->tryoutDetails->sum('duration');
            $tryout->leaderboard_package_id = optional($tryout->packages->first())->package_id
                ?? optional($tryout->directPackage)->package_id;

            return $tryout;
        });
    }

    private function calculateTotalScore(UserAnswer $userAnswer, ?string $type_subtest): float
    {
        $totalScore = 0.0;

        foreach ($userAnswer->userAnswerDetails as $detail) {
            $question = $detail->question;
            if (!$question) {
                continue;
            }

            $questionType = $question->question_type ?? 'multiple_choice';
            $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
            $pendingReview = (bool) ($answerMeta['pending_review'] ?? false);

            switch ($questionType) {
                case 'multiple_answer':
                    $totalScore += $this->resolveMultipleAnswerAwardedScore($question, $detail);
                    break;

                case 'matching':
                    $totalScore += $this->resolveMatchingAwardedScore($question, $detail);
                    break;

                case 'short_answer':
                case 'essay':
                    if ($pendingReview) {
                        continue 2;
                    }
                    $weight = (float) ($question->default_weight ?? 1);
                    $totalScore += $detail->is_correct ? ($weight > 0 ? $weight : 1) : 0;
                    break;

                case 'audio':
                    continue 2;

                default:
                    if ($detail->questionOption) {
                        switch ($type_subtest) {
                            case 'twk':
                            case 'tiu':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 5) : 0;
                                break;
                            case 'tkp':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $w > 0 ? $w : 1;
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

        return $totalScore;
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
            $fullScore = $totalCorrectCount * $scoreCorrect;
            $score = 0.0;

            if ($scoringMode === 'partial') {
                $score = $matchedCorrect > 0
                    ? ($matchedCorrect / $totalCorrectCount) * $fullScore
                    : ($wrongCount * $scoreWrong);
            } else {
                $score = ($matchedCorrect * $scoreCorrect) + ($wrongCount * $scoreWrong);
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
            $fullScore = $totalCount * $scoreCorrect;
            $score = 0.0;
            if ($scoringMode === 'partial') {
                $score = $correctCount > 0
                    ? ($correctCount / $totalCount) * $fullScore
                    : ($wrongCount * $scoreWrong);
            } else {
                $score = ($correctCount * $scoreCorrect) + ($wrongCount * $scoreWrong);
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

    private function buildLiveScoreBoard(Tryout $tryout): array
    {
        $subtests = $tryout->tryoutDetails
            ->sortBy('tryout_detail_id')
            ->values()
            ->map(function ($detail) {
                return [
                    'tryout_detail_id' => (int) $detail->tryout_detail_id,
                    'type' => (string) $detail->type_subtest,
                    'label' => strtoupper((string) $detail->type_subtest),
                ];
            })
            ->values();

        $answers = UserAnswer::where('tryout_id', $tryout->tryout_id)
            ->with([
                'user',
                'tryoutDetail',
                'userAnswerDetails.question',
                'userAnswerDetails.questionOption',
            ])
            ->get();

        $rows = $answers
            ->groupBy('user_id')
            ->map(function ($userAnswersByUser) use ($subtests) {
                $firstAttemptAnswers = $userAnswersByUser
                    ->groupBy('attempt_token')
                    ->sortBy(function ($attemptAnswers) {
                        return $attemptAnswers->min(function ($answer) {
                            return optional($answer->started_at ?? $answer->created_at)->timestamp ?? PHP_INT_MAX;
                        });
                    })
                    ->first();

                if (!$firstAttemptAnswers || $firstAttemptAnswers->isEmpty()) {
                    return null;
                }

                $user = $firstAttemptAnswers->first()->user;
                $scoreByDetail = [];
                $hasSubmittedSubtest = false;
                $lastActivityAt = null;

                foreach ($subtests as $subtest) {
                    $scoreByDetail[$subtest['tryout_detail_id']] = null;
                }

                foreach ($firstAttemptAnswers as $answer) {
                    $detailId = (int) $answer->tryout_detail_id;
                    if (!array_key_exists($detailId, $scoreByDetail)) {
                        continue;
                    }

                    $isSubmitted = !is_null($answer->subtest_submitted_at)
                        || in_array($answer->status, ['completed', 'pending_release'], true);

                    if ($isSubmitted) {
                        $rawScore = $this->calculateTotalScore($answer, optional($answer->tryoutDetail)->type_subtest);
                        $scoreByDetail[$detailId] = round((float) $rawScore, 2);
                        $hasSubmittedSubtest = true;
                    }

                    $candidateLastActivity = $answer->subtest_submitted_at
                        ?? $answer->finished_at
                        ?? $answer->updated_at
                        ?? $answer->started_at;

                    if ($candidateLastActivity && (is_null($lastActivityAt) || $candidateLastActivity->gt($lastActivityAt))) {
                        $lastActivityAt = $candidateLastActivity;
                    }
                }

                if (!$hasSubmittedSubtest) {
                    return null;
                }

                $total = collect($scoreByDetail)
                    ->filter(fn($value) => !is_null($value))
                    ->sum();

                return [
                    'user_id' => (int) $user->id,
                    'name' => (string) ($user->name ?? 'User'),
                    'attempt_token' => (string) ($firstAttemptAnswers->first()->attempt_token ?? ''),
                    'scores' => $scoreByDetail,
                    'total' => round((float) $total, 2),
                    'last_activity_at' => $lastActivityAt,
                ];
            })
            ->filter()
            ->sortBy([
                ['total', 'desc'],
                ['name', 'asc'],
            ])
            ->values()
            ->map(function ($row, $index) {
                $row['rank'] = $index + 1;
                return $row;
            });

        return [
            'subtests' => $subtests,
            'rows' => $rows,
        ];
    }
}
