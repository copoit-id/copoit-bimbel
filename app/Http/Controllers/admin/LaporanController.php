<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ProctoringSnapshot;
use App\Models\Question;
use App\Models\Tryout;
use App\Models\TryoutUserTimeAdjustment;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use App\Services\PlanQuotaService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

        $leaderboardPackageId = optional($tryout->packages->first())->package_id;

        $liveScore = $this->buildLiveScoreBoard($tryout);
        $publicLiveScoreUrl = URL::signedRoute('laporan.live-score.public', [
            'tryout' => $tryout->tryout_id,
        ]);
        $hasSnapshotProctoring = $this->hasSnapshotProctoring($tryout);

        return view('admin.pages.laporan.show', compact(
            'tryout',
            'statistics',
            'participants',
            'leaderboardPackageId',
            'liveScore',
            'publicLiveScoreUrl',
            'hasSnapshotProctoring'
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
                'alias' => $this->formatSubtestAlias(optional($answer->tryoutDetail)->type_subtest),
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

    public function proctoringSnapshots(Tryout $tryout)
    {
        $search = trim((string) request('search', ''));
        $allSnapshots = ProctoringSnapshot::with(['user'])
            ->where('tryout_id', $tryout->tryout_id)
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('attempt_token')
            ->orderBy('captured_at')
            ->get();

        $attemptTokens = $allSnapshots->pluck('attempt_token')->filter()->unique()->values();
        $tabSwitchCounts = UserAnswer::where('tryout_id', $tryout->tryout_id)
            ->whereIn('attempt_token', $attemptTokens)
            ->selectRaw('attempt_token, COALESCE(SUM(tab_switch_count), 0) as total')
            ->groupBy('attempt_token')
            ->pluck('total', 'attempt_token');

        $allSnapshotAttempts = $this->groupProctoringSnapshotAttempts($allSnapshots, $tabSwitchCounts);
        $totalTabSwitchCount = $allSnapshotAttempts->sum('tab_switch_count');
        $snapshotAttempts = $this->paginateProctoringSnapshotAttempts($allSnapshotAttempts, request()->integer('page', 1));

        $summary = ProctoringSnapshot::where('tryout_id', $tryout->tryout_id)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return view('admin.pages.laporan.proctoring-snapshots', compact(
            'tryout',
            'snapshotAttempts',
            'summary',
            'search',
            'totalTabSwitchCount'
        ));
    }

    public function destroyAllProctoringSnapshots(Tryout $tryout)
    {
        $snapshots = ProctoringSnapshot::where('tryout_id', $tryout->tryout_id)->get();

        foreach ($snapshots as $snapshot) {
            Storage::disk('public')->delete($snapshot->file_path);
        }

        ProctoringSnapshot::where('tryout_id', $tryout->tryout_id)->delete();

        return back()->with('success', 'Semua snapshot proctoring berhasil dihapus.');
    }

    public function destroyProctoringSnapshot(Tryout $tryout, ProctoringSnapshot $snapshot)
    {
        if ((int) $snapshot->tryout_id !== (int) $tryout->tryout_id) {
            abort(404);
        }

        Storage::disk('public')->delete($snapshot->file_path);
        $snapshot->delete();

        return back()->with('success', 'Snapshot berhasil dihapus.');
    }

    private function groupProctoringSnapshotAttempts($snapshots, $tabSwitchCounts)
    {
        return $snapshots
            ->groupBy('attempt_token')
            ->map(function ($attemptSnapshots, $attemptToken) use ($tabSwitchCounts) {
                $captures = $this->groupProctoringSnapshots($attemptSnapshots);
                $typeCounts = $attemptSnapshots->countBy('type');

                return [
                    'attempt_token' => $attemptToken,
                    'user' => $attemptSnapshots->first()?->user,
                    'first_captured_at' => $attemptSnapshots->min('captured_at'),
                    'latest_captured_at' => $attemptSnapshots->max('captured_at'),
                    'snapshot_count' => $attemptSnapshots->count(),
                    'webcam_count' => (int) ($typeCounts['webcam'] ?? 0),
                    'screen_count' => (int) ($typeCounts['screen'] ?? 0),
                    'total_size' => $attemptSnapshots->sum('file_size'),
                    'tab_switch_count' => (int) ($tabSwitchCounts[$attemptToken] ?? 0),
                    'captures' => $captures,
                ];
            })
            ->sortByDesc('latest_captured_at')
            ->values();
    }

    private function groupProctoringSnapshots($snapshots)
    {
        $groups = collect();

        $currentGroup = null;

        foreach ($snapshots as $snapshot) {
            $snapshotTime = $snapshot->captured_at;
            $shouldStartNewGroup = !$currentGroup
                || isset($currentGroup['snapshots'][$snapshot->type])
                || (
                    $snapshotTime
                    && $currentGroup['captured_at']
                    && abs($snapshotTime->diffInSeconds($currentGroup['captured_at'], false)) > 120
                );

            if ($shouldStartNewGroup) {
                if ($currentGroup) {
                    $groups->push($currentGroup);
                }

                $currentGroup = [
                    'captured_at' => $snapshotTime,
                    'snapshots' => [],
                    'total_size' => 0,
                ];
            }

            $currentGroup['snapshots'][$snapshot->type] = $snapshot;
            $currentGroup['total_size'] += (int) $snapshot->file_size;

            if (
                !$currentGroup['captured_at']
                || ($snapshotTime && $snapshotTime->lt($currentGroup['captured_at']))
            ) {
                $currentGroup['captured_at'] = $snapshotTime;
            }
        }

        if ($currentGroup) {
            $groups->push($currentGroup);
        }

        return $groups
            ->sortByDesc('captured_at')
            ->values();
    }

    private function paginateProctoringSnapshotAttempts($attempts, int $page): LengthAwarePaginator
    {
        $perPage = 10;
        $page = max(1, $page);

        return new LengthAwarePaginator(
            $attempts->forPage($page, $perPage)->values(),
            $attempts->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
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

    private function formatSubtestAlias(?string $type): string
    {
        if (!$type) {
            return 'Sub';
        }

        $typeLower = strtolower($type);

        if (\Illuminate\Support\Str::contains($typeLower, 'wawasan kebangsaan') || $typeLower === 'twk')
            return 'TWK';
        if (\Illuminate\Support\Str::contains($typeLower, 'inteleg') || $typeLower === 'tiu')
            return 'TIU';
        if (\Illuminate\Support\Str::contains($typeLower, 'karakteristik') || $typeLower === 'tkp')
            return 'TKP';

        return [
            'penalaran_umum' => 'PU',
            'pengetahuan_umum' => 'PPU',
            'pengetahuan_kuantitatif' => 'PK',
            'pemahaman_bacaan_menulis' => 'PBM',
            'literasi_bahasa_indonesia' => 'LBI',
            'literasi_bahasa_inggris' => 'LBE',
            'penalaran_matematika' => 'PM',
            'writing' => 'WT',
            'reading' => 'RD',
            'listening' => 'LS',
        ][$typeLower] ?? strtoupper(\Illuminate\Support\Str::limit($type, 3, ''));
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
        $globalProctoringSettings = PlanQuotaService::getDefaultProctoringSettings();

        $tryouts->transform(function (Tryout $tryout) use ($globalProctoringSettings) {
            $tryout->avg_score = round(
                $tryout->userAnswers()->where('status', 'completed')->avg('score') ?? 0,
                1
            );
            $tryout->completion_rate = $tryout->total_attempts > 0
                ? round(($tryout->completed_attempts / $tryout->total_attempts) * 100)
                : 0;
            $tryout->total_questions = $tryout->tryoutDetails->sum('questions_count');
            $tryout->total_duration = $tryout->tryoutDetails->sum('duration');
            $tryout->leaderboard_package_id = optional($tryout->packages->first())->package_id;
            $tryout->has_snapshot_proctoring = $this->hasSnapshotProctoring($tryout, $globalProctoringSettings);

            return $tryout;
        });
    }

    private function hasSnapshotProctoring(Tryout $tryout, ?array $globalProctoringSettings = null): bool
    {
        $globalProctoringSettings ??= PlanQuotaService::getDefaultProctoringSettings();

        return ((bool) $tryout->enable_webcam_check && (bool) ($globalProctoringSettings['enable_webcam_check'] ?? false))
            || ((bool) $tryout->enable_screen_check && (bool) ($globalProctoringSettings['enable_screen_check'] ?? false));
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

                case 'multiple_true_false':
                    $totalScore += $this->resolveMultipleTrueFalseAwardedScore($question, $detail);
                    break;

                case 'short_answer':
                case 'essay':
                    if ($pendingReview) {
                        continue 2;
                    }
                    // Gunakan score_obtained dari answer_json (hasil koreksi AI/manual)
                    $scoreObtained = isset($answerMeta['score_obtained']) ? (float) $answerMeta['score_obtained'] : null;
                    if ($scoreObtained !== null) {
                        $totalScore += $scoreObtained;
                    } else {
                        // Fallback: gunakan essay_score_correct atau default_weight
                        $weight = (float) ($question->getEssayScoreCorrect() ?? $question->default_weight ?? 1);
                        $totalScore += $detail->is_correct ? ($weight > 0 ? $weight : 1) : 0;
                    }
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
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (!empty($selectedIds)) {
            $correctIds = $question->questionOptions()
                ->where('is_correct', true)
                ->pluck('question_option_id')
                ->map(fn($id) => (int) $id)
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

    private function buildLiveScoreBoard(Tryout $tryout): array
    {
        $subtests = $tryout->tryoutDetails
            ->sortBy('tryout_detail_id')
            ->values()
            ->map(function ($detail) {
                return [
                    'tryout_detail_id' => (int) $detail->tryout_detail_id,
                    'type' => (string) $detail->type_subtest,
                    'label' => $this->formatSubtestAlias((string) ($detail->type_subtest ?? $detail->name)),
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
