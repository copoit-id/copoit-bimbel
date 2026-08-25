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
use App\Services\MultipleAnswerScoringService;
use App\Support\Pagination;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $scoreDisplay = $this->scoreDisplayMode($request);

        if (! in_array($status, ['active', 'inactive'], true)) {
            $status = null;
        }

        $tryouts = $this->buildTryoutReportQuery()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($status !== null, fn ($query) => $query->where('is_active', $status === 'active'))
            ->paginate(Pagination::perPage(10))
            ->withQueryString();

        $this->hydrateTryoutReport($tryouts->getCollection(), $scoreDisplay);

        $summary = [
            'total_tryouts' => Tryout::count(),
            'active_tryouts' => Tryout::where('is_active', true)->count(),
            'total_participants' => $this->countDistinctTryoutParticipants(),
            'completed_participants' => $this->countDistinctTryoutParticipants(['completed', 'pending_release']),
        ];

        return view('admin.pages.laporan.index', compact('tryouts', 'summary', 'search', 'status', 'scoreDisplay'));
    }

    public function exportExcel(Request $request)
    {
        $scoreDisplay = $this->scoreDisplayMode($request);
        $tryouts = $this->buildTryoutReportQuery()->get();
        $this->hydrateTryoutReport($tryouts, $scoreDisplay);

        $spreadsheet = new Spreadsheet;
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
            $scoreDisplay === 'percentage' ? 'Rata-rata Persentase' : 'Rata-rata Skor',
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
                $tryout->total_participants,
                $tryout->completed_participants,
                $tryout->completion_rate,
                $this->formatReportScore($tryout->report_score, $scoreDisplay),
                $tryout->is_active ? 'Aktif' : 'Tidak Aktif',
            ], null, 'A'.$row);

            $row++;
        }

        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'laporan-tryout-'.Carbon::now()->format('Ymd_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $scoreDisplay = $this->scoreDisplayMode($request);
        $tryouts = $this->buildTryoutReportQuery()->get();
        $this->hydrateTryoutReport($tryouts, $scoreDisplay);

        $html = view('admin.pages.laporan.export-pdf', [
            'tryouts' => $tryouts,
            'scoreDisplay' => $scoreDisplay,
        ])->render();

        $options = new Options;
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'laporan-tryout-'.Carbon::now()->format('Ymd_His').'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function exportTryoutExcel(Tryout $tryout)
    {
        $report = $this->buildTryoutParticipantExport($tryout);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Peserta');

        $headers = ['No.', 'Nama Peserta', 'Email'];
        foreach ($report['subtests'] as $subtest) {
            $headers[] = 'Nilai '.$subtest['name'];
        }
        $headers = [...$headers, 'Total Nilai', 'Durasi', 'Status'];
        $sheet->fromArray($headers, null, 'A1');
        $row = 2;

        foreach ($report['participants'] as $index => $participant) {
            $values = [
                $index + 1,
                $participant['name'],
                $participant['email'],
            ];
            foreach ($report['subtests'] as $subtest) {
                $values[] = $this->formatNumericScore($participant['subtests'][$subtest['id']]['score'] ?? 0);
            }

            $sheet->fromArray([
                ...$values,
                $this->formatNumericScore($participant['total_score']),
                $this->formatExportDuration($participant['started_at'], $participant['finished_at']),
                $participant['status_label'],
            ], null, 'A'.$row++);
        }

        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $widths = [8, 28, 32];
        $widths = [...$widths, ...array_fill(0, $report['subtests']->count(), 22), 16, 14, 20];
        $this->styleReportExportSheet($sheet, $lastColumn, $row - 1, $widths);

        $filename = 'laporan-'.Str::slug($tryout->name).'-'.Carbon::now()->format('Ymd_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportTryoutPdf(Tryout $tryout)
    {
        $report = $this->buildTryoutParticipantExport($tryout);
        $html = view('admin.pages.laporan.tryout-export-pdf', [
            'tryout' => $tryout,
            'participants' => $report['participants'],
            'subtests' => $report['subtests'],
        ])->render();

        $options = new Options;
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'laporan-'.Str::slug($tryout->name).'-'.Carbon::now()->format('Ymd_His').'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
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

        $attemptSummaries = UserAnswer::selectRaw('
                user_id,
                tryout_id,
                attempt_token,
                MIN(started_at) as started_at,
                MAX(finished_at) as finished_at,
                MAX(COALESCE(finished_at, started_at)) as last_activity_at,
                SUM(correct_answers) as total_correct,
                SUM(wrong_answers) as total_wrong,
                SUM(unanswered) as total_unanswered,
                SUM(score) as total_score,
                MAX(status) as attempt_status
            ')
            ->where('tryout_id', $tryout->tryout_id)
            ->groupBy('user_id', 'tryout_id', 'attempt_token')
            ->orderByDesc('last_activity_at')
            ->with('user:id,name,email')
            ->get();

        $subtestSummariesByAttempt = UserAnswer::query()
            ->selectRaw('
                user_id,
                attempt_token,
                tryout_detail_id,
                SUM(score) as score,
                SUM(correct_answers) as correct_answers,
                SUM(wrong_answers) as wrong_answers,
                SUM(unanswered) as unanswered
            ')
            ->where('tryout_id', $tryout->tryout_id)
            ->groupBy('user_id', 'attempt_token', 'tryout_detail_id')
            ->get()
            ->groupBy(fn (UserAnswer $answer) => $answer->user_id.'|'.$answer->attempt_token);

        $answerStatsBySubtest = UserAnswerDetail::query()
            ->join('user_answers', 'user_answer_details.user_answer_id', '=', 'user_answers.user_answer_id')
            ->where('user_answers.tryout_id', $tryout->tryout_id)
            ->selectRaw('
                user_answers.user_id,
                user_answers.attempt_token,
                user_answers.tryout_detail_id,
                COUNT(DISTINCT user_answer_details.question_id) as answered_questions,
                COUNT(DISTINCT CASE WHEN user_answer_details.is_correct = 1 THEN user_answer_details.question_id END) as correct_answers
            ')
            ->groupBy('user_answers.user_id', 'user_answers.attempt_token', 'user_answers.tryout_detail_id')
            ->get()
            ->keyBy(fn ($row) => $row->user_id.'|'.$row->attempt_token.'|'.$row->tryout_detail_id);

        $subtestDefinitions = $tryout->tryoutDetails->mapWithKeys(fn ($detail) => [
            $detail->tryout_detail_id => [
                'name' => $this->formatSubtestName($detail->type_subtest),
                'alias' => $this->formatSubtestAlias($detail->type_subtest),
                'total_questions' => (int) $detail->questions_count,
            ],
        ]);

        $participants = $attemptSummaries->groupBy('user_id')
            ->map(function ($attempts) use ($answerStatsBySubtest, $subtestSummariesByAttempt, $subtestDefinitions) {
                $sortedAttempts = $attempts->sortByDesc('last_activity_at')->values();
                $latest = $sortedAttempts->first();
                $latestSubtestRows = $subtestSummariesByAttempt->get($latest->user_id.'|'.$latest->attempt_token, collect())
                    ->keyBy('tryout_detail_id');

                $status = 'belum_mengerjakan';
                if ($attempts->where('attempt_status', 'in_progress')->count() > 0) {
                    $status = 'sedang_mengerjakan';
                } elseif ($attempts->whereIn('attempt_status', ['completed', 'pending_release'])->count() > 0) {
                    $status = 'selesai';
                }

                $subtests = $subtestDefinitions->map(function (array $definition, int $detailId) use ($answerStatsBySubtest, $latest, $latestSubtestRows) {
                    $row = $latestSubtestRows->get($detailId);
                    $answerStats = $answerStatsBySubtest->get($latest->user_id.'|'.$latest->attempt_token.'|'.$detailId);
                    $answered = min((int) $definition['total_questions'], (int) ($answerStats->answered_questions ?? 0));
                    $correct = min($answered, (int) ($answerStats->correct_answers ?? 0));
                    $wrong = max(0, $answered - $correct);

                    return [
                        ...$definition,
                        'score' => round((float) ($row->score ?? 0), 1),
                        'correct' => $correct,
                        'wrong' => $wrong,
                        'unanswered' => max(0, (int) $definition['total_questions'] - $answered),
                    ];
                })->values();

                return [
                    'user' => $latest->user,
                    'total_attempts' => $attempts->count(),
                    'latest_score' => round($latest->total_score ?? 0, 1),
                    'last_finished' => $latest->finished_at,
                    'latest_attempt' => $latest,
                    'total_correct' => $subtests->sum('correct'),
                    'total_wrong' => $subtests->sum('wrong'),
                    'total_unanswered' => $subtests->sum('unanswered'),
                    'subtests' => $subtests,
                    // Keep the report compact: one latest logical attempt per participant.
                    // Older attempts remain represented by total_attempts, without repeating rows.
                    'attempts' => collect([$latest]),
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
            'total_participants' => $participants->count(),
            'completed_participants' => $participants->where('status', 'selesai')->count(),
            'average_score' => round($participants->avg('latest_score') ?? 0, 1),
            'highest_score' => round($participants->max('latest_score') ?? 0, 1),
        ];

        $statistics['completion_rate'] = $statistics['total_participants'] > 0
            ? round(($statistics['completed_participants'] / $statistics['total_participants']) * 100)
            : 0;

        $leaderboardPackageId = optional($tryout->packages->first())->package_id;

        $hasSnapshotProctoring = $this->hasSnapshotProctoring($tryout);

        return view('admin.pages.laporan.show', compact(
            'tryout',
            'statistics',
            'participants',
            'subtestDefinitions',
            'leaderboardPackageId',
            'hasSnapshotProctoring'
        ));
    }

    public function ranking($tryoutId)
    {
        $tryout = Tryout::with('tryoutDetails')->findOrFail($tryoutId);
        $liveScore = $this->buildLiveScoreBoard($tryout);
        $publicLiveScoreUrl = URL::signedRoute('laporan.live-score.public', [
            'tryout' => $tryout->tryout_id,
        ]);

        return view('admin.pages.laporan.ranking', compact('tryout', 'liveScore', 'publicLiveScoreUrl'));
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
                UserAnswerDetail::whereIn('user_answer_id', $answers)->delete();
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
                UserAnswerDetail::whereIn('user_answer_id', $answers)->delete();
                UserAnswer::whereIn('user_answer_id', $answers)->delete();
            }
        });

        return redirect()->route('admin.laporan.show', $tryoutId)
            ->with('success', 'Attempt berhasil direset.');
    }

    public function attemptDetail(Request $request, $tryoutId, $attemptToken)
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
            ->orderBy('tryout_detail_id')
            ->get();

        if ($attemptAnswers->isEmpty()) {
            abort(404);
        }

        $user = $attemptAnswers->first()->user;
        $attemptAnswersByDetail = $attemptAnswers->keyBy('tryout_detail_id');
        $detailIds = $attemptAnswersByDetail->keys()->values();
        $questionCounts = Question::query()
            ->whereIn('tryout_detail_id', $detailIds)
            ->selectRaw('tryout_detail_id, COUNT(*) as total_questions')
            ->groupBy('tryout_detail_id')
            ->pluck('total_questions', 'tryout_detail_id');
        $answerStats = UserAnswerDetail::query()
            ->whereIn('user_answer_id', $attemptAnswers->pluck('user_answer_id'))
            ->selectRaw('user_answer_id, COUNT(DISTINCT question_id) as answered_questions, COUNT(DISTINCT CASE WHEN is_correct = 1 THEN question_id END) as correct_answers')
            ->groupBy('user_answer_id')
            ->get()
            ->keyBy('user_answer_id');

        $subtests = $attemptAnswers->map(function (UserAnswer $answer) use ($questionCounts, $answerStats) {
            $totalQuestions = (int) ($questionCounts[$answer->tryout_detail_id] ?? 0);
            $stats = $answerStats->get($answer->user_answer_id);
            $answeredQuestions = min($totalQuestions, (int) ($stats->answered_questions ?? 0));
            $correctAnswers = min($answeredQuestions, (int) ($stats->correct_answers ?? 0));
            $subtestType = optional($answer->tryoutDetail)->type_subtest;
            $rawScore = $this->calculateTotalScore($answer, $subtestType);
            $maxScore = $this->getMaxPossibleScoreForDetail((int) $answer->tryout_detail_id, $subtestType);

            return [
                'id' => (int) $answer->tryout_detail_id,
                'name' => $this->formatSubtestName(optional($answer->tryoutDetail)->type_subtest),
                'type' => $subtestType,
                'duration' => optional($answer->tryoutDetail)->duration,
                'total_questions' => $totalQuestions,
                'correct' => $correctAnswers,
                'wrong' => max(0, $answeredQuestions - $correctAnswers),
                'unanswered' => max(0, $totalQuestions - $answeredQuestions),
                'score' => round($maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0, 1),
                'alias' => $this->formatSubtestAlias(optional($answer->tryoutDetail)->type_subtest),
            ];
        })->values();

        $activeSubtestId = (int) $request->query('subtest', $subtests->first()['id'] ?? 0);
        if (! $attemptAnswersByDetail->has($activeSubtestId)) {
            $activeSubtestId = (int) ($subtests->first()['id'] ?? 0);
        }

        $activeAnswer = $attemptAnswersByDetail->get($activeSubtestId);
        $activeAnswerDetails = UserAnswerDetail::query()
            ->where('user_answer_id', $activeAnswer?->user_answer_id)
            ->get()
            ->keyBy('question_id');
        $questionPreviews = Question::query()
            ->where('tryout_detail_id', $activeSubtestId)
            ->with('questionOptions')
            ->orderBy('question_id')
            ->get()
            ->map(fn (Question $question) => [
                'question' => $question,
                'answer' => $activeAnswerDetails->get($question->question_id),
            ]);

        $overallStats = [
            'correct' => $subtests->sum('correct'),
            'wrong' => $subtests->sum('wrong'),
            'unanswered' => $subtests->sum('unanswered'),
            'score' => round($subtests->sum('score'), 1),
            'started_at' => $attemptAnswers->min('started_at'),
            'finished_at' => $attemptAnswers->max('finished_at'),
        ];
        $overallStats['total_questions'] = $subtests->sum('total_questions');

        return view('admin.pages.laporan.answer', compact(
            'tryout',
            'user',
            'attemptToken',
            'overallStats',
            'subtests',
            'activeSubtestId',
            'questionPreviews'
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
            $shouldStartNewGroup = ! $currentGroup
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
                ! $currentGroup['captured_at']
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
        $perPage = Pagination::perPage(10);
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

    /**
     * Build one latest logical attempt per participant for the detailed exports.
     * The aggregation mirrors the report page without loading question relations.
     */
    private function buildTryoutParticipantExport(Tryout $tryout): array
    {
        $tryout->loadMissing([
            'tryoutDetails' => fn ($query) => $query->withCount('questions'),
        ]);

        $subtests = $tryout->tryoutDetails
            ->sortBy('tryout_detail_id')
            ->mapWithKeys(fn ($detail) => [
                (int) $detail->tryout_detail_id => [
                    'id' => (int) $detail->tryout_detail_id,
                    'name' => $this->formatSubtestName($detail->type_subtest),
                    'alias' => $this->formatSubtestAlias($detail->type_subtest),
                    'total_questions' => (int) $detail->questions_count,
                ],
            ]);

        $attemptSummaries = UserAnswer::query()
            ->selectRaw('user_id, attempt_token, MIN(started_at) as started_at, MAX(finished_at) as finished_at, MAX(COALESCE(finished_at, started_at)) as last_activity_at, SUM(score) as total_score, MAX(status) as attempt_status')
            ->where('tryout_id', $tryout->tryout_id)
            ->groupBy('user_id', 'attempt_token')
            ->orderByDesc('last_activity_at')
            ->with('user:id,name,email')
            ->get();

        $subtestScores = UserAnswer::query()
            ->selectRaw('user_id, attempt_token, tryout_detail_id, SUM(score) as score')
            ->where('tryout_id', $tryout->tryout_id)
            ->groupBy('user_id', 'attempt_token', 'tryout_detail_id')
            ->get()
            ->groupBy(fn (UserAnswer $answer) => $answer->user_id.'|'.$answer->attempt_token)
            ->map(fn ($answers) => $answers->keyBy('tryout_detail_id'));

        $participants = $attemptSummaries
            ->groupBy('user_id')
            ->map(function ($attempts) use ($subtests, $subtestScores) {
                $latest = $attempts->sortByDesc('last_activity_at')->first();
                $attemptKey = $latest->user_id.'|'.$latest->attempt_token;
                $scores = $subtestScores->get($attemptKey, collect());
                $isInProgress = $attempts->contains('attempt_status', 'in_progress');

                $subtestValues = $subtests->mapWithKeys(function (array $subtest, int $detailId) use ($scores) {
                    $score = $scores->get($detailId);

                    return [$detailId => [
                        'score' => round((float) ($score->score ?? 0), 2),
                    ]];
                });

                return [
                    'name' => $latest->user?->name ?? 'Peserta',
                    'email' => $latest->user?->email ?? '-',
                    'status_label' => $isInProgress ? 'Sedang Mengerjakan' : 'Selesai',
                    'total_score' => round((float) ($latest->total_score ?? 0), 2),
                    'started_at' => $latest->started_at ? Carbon::parse($latest->started_at) : null,
                    'finished_at' => $latest->finished_at ? Carbon::parse($latest->finished_at) : null,
                    'subtests' => $subtestValues,
                ];
            })
            ->sortBy(fn (array $participant) => Str::lower($participant['name']))
            ->values();

        return compact('participants', 'subtests');
    }

    private function styleReportExportSheet($sheet, string $lastColumn, int $lastRow, array $widths): void
    {
        $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        if ($lastRow >= 2) {
            $sheet->getStyle('A2:'.$lastColumn.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }
        $sheet->getRowDimension(1)->setRowHeight(32);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:'.$lastColumn.max(1, $lastRow));
        $sheet->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0);

        foreach ($widths as $index => $width) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function formatExportDuration(?Carbon $startedAt, ?Carbon $finishedAt): string
    {
        if (! $startedAt || ! $finishedAt) {
            return '-';
        }

        $seconds = $startedAt->diffInSeconds($finishedAt);

        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }

    private function formatNumericScore(float|int|null $score): string
    {
        return rtrim(rtrim(number_format((float) $score, 2, '.', ''), '0'), '.');
    }

    private function formatSubtestAlias(?string $type): string
    {
        if (! $type) {
            return 'Sub';
        }

        $typeLower = strtolower($type);

        if (Str::contains($typeLower, 'wawasan kebangsaan') || $typeLower === 'twk') {
            return 'TWK';
        }
        if (Str::contains($typeLower, 'inteleg') || $typeLower === 'tiu') {
            return 'TIU';
        }
        if (Str::contains($typeLower, 'karakteristik') || $typeLower === 'tkp') {
            return 'TKP';
        }

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
        ][$typeLower] ?? strtoupper(Str::limit($type, 3, ''));
    }

    private function formatSubtestName(?string $type): string
    {
        if (! $type) {
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
            ->select('tryouts.*')
            ->selectSub(
                UserAnswer::query()
                    ->selectRaw('COUNT(DISTINCT user_id)')
                    ->whereColumn('tryout_id', 'tryouts.tryout_id'),
                'total_participants'
            )
            ->selectSub(
                UserAnswer::query()
                    ->selectRaw('COUNT(DISTINCT user_id)')
                    ->whereColumn('tryout_id', 'tryouts.tryout_id')
                    ->whereIn('status', ['completed', 'pending_release']),
                'completed_participants'
            )
            ->latest();
    }

    private function hydrateTryoutReport($tryouts, string $scoreDisplay = 'score'): void
    {
        $globalProctoringSettings = PlanQuotaService::getDefaultProctoringSettings();
        $tryoutIds = $tryouts->pluck('tryout_id')->filter()->values();
        $scoreStatsByTryout = UserAnswer::query()
            ->selectRaw('tryout_id, AVG(score) as average_score, SUM(correct_answers) as total_correct, SUM(correct_answers + wrong_answers + unanswered) as total_questions')
            ->whereIn('tryout_id', $tryoutIds)
            ->where('status', 'completed')
            ->groupBy('tryout_id')
            ->get()
            ->keyBy('tryout_id');

        $tryouts->transform(function (Tryout $tryout) use ($globalProctoringSettings, $scoreStatsByTryout, $scoreDisplay) {
            $scoreStats = $scoreStatsByTryout->get($tryout->tryout_id);
            $tryout->avg_score = round((float) ($scoreStats->average_score ?? 0), 1);
            $tryout->avg_percentage = $this->percentageFromCorrectAnswers(
                (int) ($scoreStats->total_correct ?? 0),
                (int) ($scoreStats->total_questions ?? 0)
            );
            $tryout->report_score = $scoreDisplay === 'percentage'
                ? $tryout->avg_percentage
                : $tryout->avg_score;
            $tryout->completion_rate = $tryout->total_participants > 0
                ? round(($tryout->completed_participants / $tryout->total_participants) * 100)
                : 0;
            $tryout->total_questions = $tryout->tryoutDetails->sum('questions_count');
            $tryout->total_duration = $tryout->tryoutDetails->sum('duration');
            $tryout->leaderboard_package_id = optional($tryout->packages->first())->package_id;
            $tryout->has_snapshot_proctoring = $this->hasSnapshotProctoring($tryout, $globalProctoringSettings);

            return $tryout;
        });
    }

    private function countDistinctTryoutParticipants(?array $statuses = null): int
    {
        return DB::query()
            ->fromSub(
                UserAnswer::query()
                    ->select('tryout_id', 'user_id')
                    ->when($statuses !== null, fn ($query) => $query->whereIn('status', $statuses))
                    ->groupBy('tryout_id', 'user_id'),
                'tryout_participants'
            )
            ->count();
    }

    private function scoreDisplayMode(Request $request): string
    {
        return $request->query('score_display') === 'percentage' ? 'percentage' : 'score';
    }

    private function percentageFromCorrectAnswers(int $correctAnswers, int $totalQuestions): float
    {
        if ($totalQuestions <= 0) {
            return 0.0;
        }

        return round(($correctAnswers / $totalQuestions) * 100, 1);
    }

    private function formatReportScore(float|int|null $score, string $scoreDisplay): string
    {
        $formatted = rtrim(rtrim(number_format((float) $score, 1, '.', ''), '0'), '.');

        return $scoreDisplay === 'percentage' ? $formatted.'%' : $formatted;
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
            if (! $question) {
                continue;
            }

            $questionType = $question->question_type ?? 'multiple_choice';
            $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
            $pendingReview = (bool) ($answerMeta['pending_review'] ?? false);

            switch ($questionType) {
                case 'multiple_answer':
                    $totalScore += app(MultipleAnswerScoringService::class)->scoreForDetail($question, $detail);
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

    private function getMaxPossibleScoreForDetail(int $tryoutDetailId, ?string $typeSubtest): float
    {
        $questions = Question::query()
            ->where('tryout_detail_id', $tryoutDetailId)
            ->with('questionOptions')
            ->get();

        return $questions->sum(function (Question $question) use ($typeSubtest): float {
            return match ($question->question_type) {
                'multiple_answer' => app(MultipleAnswerScoringService::class)->config($question)['score_correct'],
                'matching' => max(0, (float) (data_get($question->metadata, 'matching_scores.score_correct') ?? $question->default_weight ?? 1)),
                'multiple_true_false' => max(0, (float) (data_get($question->metadata, 'multiple_true_false.score_correct') ?? $question->default_weight ?? 1)),
                'short_answer', 'essay' => max(0, (float) ($question->getEssayScoreCorrect() ?? $question->default_weight ?? 1)),
                'audio' => 0.0,
                default => $this->maximumOptionScore($question, $typeSubtest),
            };
        });
    }

    private function maximumOptionScore(Question $question, ?string $typeSubtest): float
    {
        $options = $question->questionOptions;

        return match ($typeSubtest) {
            'tkp' => (float) ($options->max('weight') ?? 1),
            'twk', 'tiu' => (float) ($options->where('is_correct', true)->max('weight') ?? 5),
            'writing', 'reading', 'listening' => (float) ($options->where('is_correct', true)->max('weight') ?? 10),
            default => (float) ($options->where('is_correct', true)->max('weight') ?? 1),
        };
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

        if (! empty($selectedIds)) {
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
                'userAnswerDetails.question.questionOptions',
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

                if (! $firstAttemptAnswers || $firstAttemptAnswers->isEmpty()) {
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
                    if (! array_key_exists($detailId, $scoreByDetail)) {
                        continue;
                    }

                    $isSubmitted = ! is_null($answer->subtest_submitted_at)
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

                if (! $hasSubmittedSubtest) {
                    return null;
                }

                $total = collect($scoreByDetail)
                    ->filter(fn ($value) => ! is_null($value))
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
