<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Tryout;
use App\Models\UserAnswer;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use stdClass;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class LaporanController extends Controller
{
    private array $maxScoreByDetail = [];

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
        $this->prepareDownloadRuntime();

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
                $tryout->unique_participants,
                $tryout->completed_attempts,
                $tryout->completion_rate,
                $tryout->avg_score,
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
        $this->prepareDownloadRuntime();

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

    public function exportTryoutExcel($id)
    {
        $this->prepareDownloadRuntime();

        [$tryout, $statistics, $participants] = $this->getTryoutDetailReport($id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Nama Peserta',
            'Email',
            'Attempt Token',
            'Status',
            'Skor',
            'Benar',
            'Salah',
            'Kosong',
            'Total Soal',
            'Durasi',
            'Mulai',
            'Selesai',
        ];

        $sheet->fromArray([$tryout->name], null, 'A1');
        $sheet->fromArray([
            'Peserta: ' . $statistics['total_participants'],
            'Selesai: ' . $statistics['completed_participants'],
            'Rata-rata: ' . $statistics['average_score'],
            'Skor tertinggi: ' . $statistics['highest_score'],
        ], null, 'A2');
        $sheet->fromArray($headers, null, 'A4');
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        $sheet->getStyle('A4:L4')->getFont()->setBold(true);

        $row = 5;
        foreach ($participants as $participant) {
            foreach ($participant['attempts'] as $attempt) {
                $sheet->fromArray([
                    $participant['user']->name,
                    $participant['user']->email,
                    $attempt->attempt_token ?: '-',
                    $attempt->result_status_label,
                    round($attempt->display_score ?? 0, 1),
                    $attempt->total_correct ?? 0,
                    $attempt->total_wrong ?? 0,
                    $attempt->total_unanswered ?? 0,
                    $attempt->question_count ?? 0,
                    $attempt->duration_label ?? '-',
                    $attempt->started_label ?? '-',
                    $attempt->finished_label ?? '-',
                ], null, 'A' . $row);
                $row++;
            }
        }

        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = sprintf(
            'laporan-tryout-%s-%s.xlsx',
            $tryout->tryout_id,
            Carbon::now()->format('Ymd_His')
        );
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportTryoutPdf($id)
    {
        $this->prepareDownloadRuntime();

        [$tryout, $statistics, $participants] = $this->getTryoutDetailReport($id);

        $html = view('admin.pages.laporan.export-detail-pdf', [
            'tryout' => $tryout,
            'statistics' => $statistics,
            'participants' => $participants,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = sprintf(
            'laporan-tryout-%s-%s.pdf',
            $tryout->tryout_id,
            Carbon::now()->format('Ymd_His')
        );

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function show($id)
    {
        [$tryout, $statistics, $participants] = $this->getTryoutDetailReport($id);

        $leaderboardPackageId = optional($tryout->packages->first())->package_id
            ?? optional($tryout->directPackage)->package_id;

        return view('admin.pages.laporan.show', compact('tryout', 'statistics', 'participants', 'leaderboardPackageId'));
    }

    private function getTryoutDetailReport($id, bool $withCalculatedScores = true): array
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
                SUM(score) as total_score,
                AVG(score) as average_score,
                MAX(status) as attempt_status,
                SUM(CASE WHEN status = 'pending_release' THEN 1 ELSE 0 END) as pending_release_count,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                COUNT(*) as subtest_count
            ")
            ->where('tryout_id', $tryout->tryout_id)
            ->groupBy('user_id', 'tryout_id', 'attempt_token')
            ->orderByDesc(DB::raw('MAX(finished_at)'))
            ->with('user')
            ->get();

        $answersByAttempt = collect();

        if ($withCalculatedScores && $attemptSummaries->isNotEmpty()) {
            $answersByAttempt = UserAnswer::where('tryout_id', $tryout->tryout_id)
                ->whereIn('user_id', $attemptSummaries->pluck('user_id')->unique())
                ->with([
                    'tryoutDetail' => function ($query) {
                        $query->withCount('questions');
                    },
                    'userAnswerDetails.question',
                    'userAnswerDetails.questionOption',
                ])
                ->get()
                ->groupBy(['user_id', 'attempt_token']);
        }

        $participants = $attemptSummaries->groupBy('user_id')
            ->map(function ($attempts) use ($answersByAttempt, $tryout, $withCalculatedScores) {
                $attempts = $attempts->map(function ($attempt) use ($answersByAttempt, $tryout, $withCalculatedScores) {
                    if ($withCalculatedScores) {
                        $userAnswers = $answersByAttempt[$attempt->user_id][$attempt->attempt_token] ?? collect();
                        $this->hydrateAttemptAnswerCounts($attempt, $userAnswers);
                        $attempt->attempt_status = $this->resolveAttemptStatus($userAnswers, $attempt->attempt_status);
                        $this->hydrateAttemptScoreAndResult($attempt, $userAnswers, $tryout);
                    } else {
                        $attempt->raw_score = (float) ($attempt->total_score ?? $attempt->average_score ?? 0);
                        $attempt->display_score = (float) ($attempt->average_score ?? 0);
                        $attempt->is_passed = false;
                        $attempt->attempt_status = $this->resolveAttemptStatusFromSummary($attempt);
                    }

                    $this->hydrateAttemptDisplayFields($attempt);
                    $attempt->attempt_status_label = $this->formatAttemptStatus($attempt->attempt_status);
                    $attempt->result_status_label = $this->formatAttemptResultStatus(
                        $attempt->attempt_status,
                        (bool) $attempt->is_passed
                    );

                    return $attempt;
                });

                $sortedAttempts = $attempts->sortByDesc('finished_at')->values();
                $latest = $sortedAttempts->first();
                $user = $latest->user ?: $this->missingUserPlaceholder($latest->user_id);

                return [
                    'user' => $user,
                    'total_attempts' => $attempts->count(),
                    'latest_score' => round($latest->display_score ?? 0, 1),
                    'last_finished' => $latest->finished_at,
                    'attempts' => $sortedAttempts,
                ];
            })
            ->values();

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

        return [$tryout, $statistics, $participants];
    }

    private function prepareDownloadRuntime(): void
    {
        @set_time_limit(120);
        @ini_set('memory_limit', '512M');
    }

    private function hydrateAttemptDisplayFields($attempt): void
    {
        $startedAt = $attempt->started_at ? Carbon::parse($attempt->started_at) : null;
        $finishedAt = $attempt->finished_at ? Carbon::parse($attempt->finished_at) : null;

        $attempt->question_count = (int) ($attempt->total_correct ?? 0)
            + (int) ($attempt->total_wrong ?? 0)
            + (int) ($attempt->total_unanswered ?? 0);
        $attempt->duration_label = ($startedAt && $finishedAt)
            ? $startedAt->diffForHumans($finishedAt, true)
            : '-';
        $attempt->started_label = $startedAt ? $startedAt->format('d M Y H:i') : '-';
        $attempt->finished_label = $finishedAt ? $finishedAt->format('d M Y H:i') : '-';
        $attempt->finished_date_label = $finishedAt ? $finishedAt->translatedFormat('d M Y') : '-';
        $attempt->finished_time_label = $finishedAt ? $finishedAt->format('H:i') : '';
    }

    /**
     * Use answer details as the source of truth, rather than persisted summary
     * columns which can be stale for attempts completed before the current logic.
     */
    private function hydrateAttemptAnswerCounts($attempt, $userAnswers): void
    {
        $counts = collect($userAnswers)
            ->map(fn (UserAnswer $answer) => $this->calculateUserAnswerCounts($answer));

        $attempt->total_correct = $counts->sum('correct');
        $attempt->total_wrong = $counts->sum('wrong');
        $attempt->total_unanswered = $counts->sum('unanswered');
    }

    private function calculateUserAnswerCounts(UserAnswer $userAnswer): array
    {
        $details = $userAnswer->userAnswerDetails;
        $answered = $details->count();
        $correct = $details->where('is_correct', true)->count();
        $totalQuestions = (int) (optional($userAnswer->tryoutDetail)->questions_count ?? 0);

        return [
            'correct' => $correct,
            'wrong' => max(0, $answered - $correct),
            'unanswered' => max(0, $totalQuestions - $answered),
        ];
    }

    private function hydrateAttemptScoreAndResult($attempt, $userAnswers, Tryout $tryout): void
    {
        $userAnswers = collect($userAnswers);

        if ($tryout->requiresIrtScoring()) {
            $attempt->raw_score = (float) ($userAnswers->first()?->utbk_total_score ?? 0);
            $attempt->display_score = $attempt->raw_score;
            $attempt->is_passed = $attempt->attempt_status === 'completed'
                && $userAnswers->every(fn (UserAnswer $answer) => (bool) $answer->is_passed);

            return;
        }

        if ($tryout->is_toefl) {
            $attempt->raw_score = (float) ($userAnswers->first()?->toefl_total_score
                ?? $userAnswers->first()?->score
                ?? 0);
            $attempt->display_score = $attempt->raw_score;
            $attempt->is_passed = $attempt->attempt_status === 'completed'
                && $userAnswers->every(fn (UserAnswer $answer) => (bool) $answer->is_passed);

            return;
        }

        $totalScore = 0.0;
        $totalMaxScore = 0.0;
        $allSubtestsPassed = $userAnswers->isNotEmpty();

        foreach ($userAnswers as $userAnswer) {
            $type = optional($userAnswer->tryoutDetail)->type_subtest;
            $subtestScore = $this->calculateTotalScore($userAnswer, $type);
            $maxSubtestScore = $this->getMaxPossibleScoreForDetail(
                $userAnswer->tryout_detail_id,
                $type
            );

            $totalScore += $subtestScore;
            $totalMaxScore += $maxSubtestScore;

            if (! $this->isSubtestPassed($userAnswer->tryoutDetail, $subtestScore, $maxSubtestScore, $type)) {
                $allSubtestsPassed = false;
            }
        }

        $attempt->raw_score = $totalScore;
        $attempt->display_score = $totalMaxScore > 0 ? ($totalScore / $totalMaxScore) * 100 : 0;
        $attempt->is_passed = $attempt->attempt_status === 'completed' && $allSubtestsPassed;
    }

    private function getMaxPossibleScoreForDetail(int $tryoutDetailId, ?string $typeSubtest): float
    {
        if (isset($this->maxScoreByDetail[$tryoutDetailId])) {
            return $this->maxScoreByDetail[$tryoutDetailId];
        }

        $questions = Question::where('tryout_detail_id', $tryoutDetailId)
            ->with('questionOptions')
            ->get();
        $total = 0.0;

        foreach ($questions as $question) {
            $questionType = $question->question_type ?? 'multiple_choice';

            switch ($questionType) {
                case 'matching':
                    $weight = (float) ($question->default_weight ?? 0);
                    if ($weight <= 0) {
                        $pairs = isset($question->metadata['matching_pairs']) && is_array($question->metadata['matching_pairs'])
                            ? count($question->metadata['matching_pairs'])
                            : 1;
                        $weight = max(1, $pairs);
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
                    $maxWeight = (float) ($question->questionOptions->max('weight') ?? 0);
                    $fallbackWeight = match ($typeSubtest) {
                        'twk', 'tiu' => 5,
                        'writing', 'reading', 'listening' => 10,
                        default => 1,
                    };
                    $total += $maxWeight > 0 ? $maxWeight : $fallbackWeight;
                    break;
            }
        }

        return $this->maxScoreByDetail[$tryoutDetailId] = $total;
    }

    private function isSubtestPassed($detail, float $rawScore, float $maxScore, ?string $type): bool
    {
        $passingScore = $detail?->passing_score ?? $this->getDefaultPassingScore($type);
        $passingType = $detail?->passing_type ?? 'score';

        if ($passingType === 'percentage') {
            return $maxScore > 0 && (($rawScore / $maxScore) * 100) >= $passingScore;
        }

        return $rawScore >= $passingScore;
    }

    private function getDefaultPassingScore(?string $type): int
    {
        return match ($type) {
            'word', 'excel', 'ppt' => 70,
            'teknis', 'social culture', 'management', 'interview' => 65,
            default => 60,
        };
    }

    private function formatAttemptResultStatus(?string $status, bool $isPassed): string
    {
        if ($status === 'completed') {
            return $isPassed ? 'Lulus' : 'Tidak Lulus';
        }

        return $this->formatAttemptStatus($status);
    }

    public function attemptDetail($tryoutId, $attemptToken)
    {
        return view('admin.pages.laporan.answer', $this->getAttemptDetailData($tryoutId, $attemptToken));
    }

    public function exportAttemptPdf($tryoutId, $attemptToken)
    {
        $this->prepareDownloadRuntime();

        $html = view(
            'admin.pages.laporan.export-attempt-pdf',
            $this->getAttemptDetailData($tryoutId, $attemptToken)
        )->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = sprintf(
            'detail-jawaban-%s-%s.pdf',
            $tryoutId,
            Carbon::now()->format('Ymd_His')
        );
        $disposition = request()->boolean('inline') ? 'inline' : 'attachment';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
        ]);
    }

    private function getAttemptDetailData($tryoutId, $attemptToken): array
    {
        $tryout = Tryout::with([
            'tryoutDetails' => function ($query) {
                $query->withCount('questions');
            },
        ])->findOrFail($tryoutId);

        $attemptAnswers = UserAnswer::with([
            'user',
            'tryoutDetail' => function ($query) {
                $query->withCount('questions');
            },
            'userAnswerDetails.question.questionOptions',
            'userAnswerDetails.questionOption',
        ])
            ->where('tryout_id', $tryout->tryout_id)
            ->where('attempt_token', $attemptToken)
            ->get();

        if ($attemptAnswers->isEmpty()) {
            abort(404);
        }

        $firstAnswer = $attemptAnswers->first();
        $user = $firstAnswer->user ?: $this->missingUserPlaceholder($firstAnswer->user_id);
        $answerCounts = $attemptAnswers->mapWithKeys(function (UserAnswer $answer) {
            return [$answer->user_answer_id => $this->calculateUserAnswerCounts($answer)];
        });
        $overallStats = [
            'correct' => $answerCounts->sum('correct'),
            'wrong' => $answerCounts->sum('wrong'),
            'unanswered' => $answerCounts->sum('unanswered'),
            'score' => round($attemptAnswers->avg('score') ?? 0, 1),
            'started_at' => $attemptAnswers->min('started_at'),
            'finished_at' => $attemptAnswers->max('finished_at'),
        ];
        $overallStats['total_questions'] = $overallStats['correct'] + $overallStats['wrong'] + $overallStats['unanswered'];

        $subtests = $attemptAnswers->map(function (UserAnswer $answer) {
            $counts = $this->calculateUserAnswerCounts($answer);

            return [
                'name' => $this->formatSubtestName(optional($answer->tryoutDetail)->type_subtest),
                'type' => optional($answer->tryoutDetail)->type_subtest,
                'duration' => optional($answer->tryoutDetail)->duration,
                'correct' => $counts['correct'],
                'wrong' => $counts['wrong'],
                'unanswered' => $counts['unanswered'],
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

        return compact(
            'tryout',
            'user',
            'attemptToken',
            'overallStats',
            'subtests',
            'answerDetails'
        );
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

    private function missingUserPlaceholder($userId): stdClass
    {
        return (object) [
            'id' => $userId,
            'name' => $userId ? "User Terhapus #{$userId}" : 'User Terhapus',
            'email' => '-',
            'is_missing' => true,
        ];
    }

    private function formatAttemptStatus(?string $status): string
    {
        return [
            'completed' => 'Selesai',
            'pending_release' => 'Menunggu Rilis',
            'in_progress' => 'Sedang Dikerjakan',
            'abandoned' => 'Ditinggalkan',
        ][$status] ?? ucfirst((string) $status);
    }

    private function resolveAttemptStatusFromSummary($attempt): string
    {
        if ((int) ($attempt->pending_release_count ?? 0) > 0) {
            return 'pending_release';
        }

        if ((int) ($attempt->subtest_count ?? 0) > 0
            && (int) ($attempt->completed_count ?? 0) === (int) ($attempt->subtest_count ?? 0)) {
            return 'completed';
        }

        if ((int) ($attempt->in_progress_count ?? 0) > 0) {
            return 'in_progress';
        }

        return (string) ($attempt->attempt_status ?? 'unknown');
    }

    private function resolveAttemptStatus($userAnswers, ?string $fallback): string
    {
        if ($userAnswers->isEmpty()) {
            return (string) $fallback;
        }

        if ($userAnswers->contains(fn (UserAnswer $answer) => $answer->status === 'pending_release')) {
            return 'pending_release';
        }

        if ($userAnswers->every(fn (UserAnswer $answer) => $answer->status === 'completed')) {
            return 'completed';
        }

        if ($userAnswers->contains(fn (UserAnswer $answer) => $answer->status === 'in_progress')) {
            return 'in_progress';
        }

        return (string) ($userAnswers->sortByDesc('created_at')->first()->status ?? $fallback);
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
            $tryout->total_questions = $tryout->tryoutDetails->sum('questions_count');
            $tryout->total_duration = $tryout->tryoutDetails->sum('duration');
            $tryout->leaderboard_package_id = optional($tryout->packages->first())->package_id
                ?? optional($tryout->directPackage)->package_id;

            $tryout->total_attempts = \App\Models\UserAnswer::where('tryout_id', $tryout->tryout_id)
                ->select('user_id', 'attempt_token')
                ->groupBy('user_id', 'attempt_token')
                ->get()
                ->count();

            $tryout->completed_attempts = \App\Models\UserAnswer::where('tryout_id', $tryout->tryout_id)
                ->where('status', 'completed')
                ->select('user_id', 'attempt_token')
                ->groupBy('user_id', 'attempt_token')
                ->get()
                ->count();

            $tryout->unique_participants = \App\Models\UserAnswer::where('tryout_id', $tryout->tryout_id)
                ->distinct('user_id')
                ->count('user_id');

            $tryout->completion_rate = $tryout->total_attempts > 0
                ? round(($tryout->completed_attempts / $tryout->total_attempts) * 100)
                : 0;

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
                case 'matching':
                    $weight = (float) ($question->default_weight ?? 1);
                    if ($weight <= 0) {
                        $pairs = isset($question->metadata['matching_pairs']) && is_array($question->metadata['matching_pairs'])
                            ? count($question->metadata['matching_pairs'])
                            : 1;
                        $weight = max(1, $pairs);
                    }
                    $totalScore += $detail->is_correct ? $weight : 0;
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
                                $totalScore += $w > 0 ? $w : ($detail->is_correct ? 5 : 0);
                                break;
                            case 'tkp':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $w > 0 ? $w : 1;
                                break;
                            case 'writing':
                            case 'reading':
                            case 'listening':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $w > 0 ? $w : ($detail->is_correct ? 10 : 0);
                                break;
                            default:
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $w > 0 ? $w : ($detail->is_correct ? 1 : 0);
                                break;
                        }
                    }
                    break;
            }
        }

        return $totalScore;
    }
}
