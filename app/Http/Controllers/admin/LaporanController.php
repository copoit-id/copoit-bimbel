<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Tryout;
use App\Models\UserAnswer;
use Illuminate\Support\Facades\DB;
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
                $tryout->unique_participants,
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

    public function exportTryoutExcel($id)
    {
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
        $sheet->getStyle('A4:J4')->getFont()->setBold(true);

        $row = 5;
        foreach ($participants as $participant) {
            foreach ($participant['attempts'] as $attempt) {
                $sheet->fromArray([
                    $participant['user']->name ?? 'User',
                    $participant['user']->email ?? '-',
                    $attempt->attempt_token,
                    $attempt->attempt_status_label,
                    round($attempt->raw_score ?? 0, 1),
                    $attempt->total_correct ?? 0,
                    $attempt->total_wrong ?? 0,
                    $attempt->total_unanswered ?? 0,
                    $attempt->started_at ? Carbon::parse($attempt->started_at)->format('d M Y H:i') : '-',
                    $attempt->finished_at ? Carbon::parse($attempt->finished_at)->format('d M Y H:i') : '-',
                ], null, 'A' . $row);
                $row++;
            }
        }

        foreach (range('A', 'J') as $column) {
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

    private function getTryoutDetailReport($id): array
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
                    $attempt->attempt_status = $this->resolveAttemptStatus($userAnswers, $attempt->attempt_status);
                    $attempt->attempt_status_label = $this->formatAttemptStatus($attempt->attempt_status);

                    return $attempt;
                });

                $sortedAttempts = $attempts->sortByDesc('finished_at')->values();
                $latest = $sortedAttempts->first();

                return [
                    'user' => $latest->user,
                    'total_attempts' => $attempts->count(),
                    'latest_score' => round($latest->raw_score ?? 0, 1),
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

    private function formatAttemptStatus(?string $status): string
    {
        return [
            'completed' => 'Selesai',
            'pending_release' => 'Menunggu Rilis',
            'in_progress' => 'Sedang Dikerjakan',
            'abandoned' => 'Ditinggalkan',
        ][$status] ?? ucfirst((string) $status);
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
}
