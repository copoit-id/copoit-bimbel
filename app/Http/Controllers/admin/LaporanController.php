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
                $tryout->total_attempts,
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

        $participants = $attemptSummaries->groupBy('user_id')
            ->map(function ($attempts) {
                $sortedAttempts = $attempts->sortByDesc('finished_at')->values();
                $latest = $sortedAttempts->first();

                return [
                    'user' => $latest->user,
                    'total_attempts' => $attempts->count(),
                    'latest_score' => round($latest->average_score ?? 0, 1),
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

        $leaderboardPackageId = optional($tryout->packages->first())->package_id
            ?? optional($tryout->directPackage)->package_id;

        return view('admin.pages.laporan.show', compact('tryout', 'statistics', 'participants', 'leaderboardPackageId'));
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
}
