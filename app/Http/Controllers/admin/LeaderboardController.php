<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Question;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use stdClass;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class LeaderboardController extends Controller
{
    public function index()
    {
        // Show every tryout that is available through a package, including those
        // that have not received any participant attempts yet.
        $tryouts = Tryout::with(['tryoutDetails', 'packages', 'directPackage'])
            ->get()
            ->map(function ($tryout) {
                $tryoutDetail = $tryout->tryoutDetails->first();

                // Count total participants across all packages for this tryout
                $participantCount = UserAnswer::where('tryout_id', $tryout->tryout_id)
                    ->distinct('user_id')
                    ->count();

                // Get all packages that have this tryout
                $packages = $tryout->packages;
                $directPackage = $tryout->directPackage ? collect([$tryout->directPackage]) : collect();
                $allPackages = $packages->isEmpty() ? $directPackage : $packages;

                if ($allPackages->isEmpty()) {
                    return null; // Skip if no package
                }

                // Combine package names for display
                $packageNames = $allPackages->pluck('name')->toArray();
                $combinedPackageName = count($packageNames) > 1
                    ? implode(' + ', array_slice($packageNames, 0, 2)) . (count($packageNames) > 2 ? ' + ' . (count($packageNames) - 2) . ' lainnya' : '')
                    : $packageNames[0] ?? 'Unknown Package';

                return [
                    'tryout_id' => $tryout->tryout_id,
                    'package_id' => $allPackages->first()->package_id, // Use first package for routing
                    'name' => $tryout->name,
                    'description' => $tryout->description,
                    'total_questions' => $tryoutDetail ? $tryoutDetail->questions()->count() : 0,
                    'duration' => $tryoutDetail ? $tryoutDetail->duration : 0,
                    'difficulty' => $this->getDifficultyLevel($tryoutDetail ? $tryoutDetail->duration : 0),
                    'participant_count' => $participantCount,
                    'package_name' => $combinedPackageName,
                    'package_count' => count($packageNames),
                    'all_packages' => $allPackages->map(function ($pkg) {
                        return [
                            'id' => $pkg->package_id,
                            'name' => $pkg->name,
                            'type' => $pkg->type_package
                        ];
                    })->toArray()
                ];
            })
            ->filter() // Remove null values
            ->values(); // Reset array keys

        return view('admin.pages.leaderboard.index', compact('tryouts'));
    }

    public function show($package_id, $tryout_id)
    {
        $package = Package::findOrFail($package_id);
        $tryout = Tryout::findOrFail($tryout_id);

        // Get tryout details
        $tryoutDetail = $tryout->tryoutDetails()->first();
        if (!$tryoutDetail) {
            return redirect()->route('admin.leaderboard.index')
                ->with('error', 'Tryout belum memiliki detail soal');
        }

        $rankingRows = $this->buildLeaderboardRows($this->getLeaderboardRankings($tryout_id));
        $rankings = $this->paginateLeaderboardRows($rankingRows);

        $totalParticipants = UserAnswer::where('tryout_id', $tryout_id)
            ->distinct('user_id')
            ->count();

        $completedRows = $rankingRows->where('status', 'completed');
        $averageScore = $completedRows->avg('raw_score');
        $highestScore = $rankingRows->max('raw_score');

        $passedCount = $completedRows->where('is_passed', true)->count();

        $passRate = $completedRows->count() > 0 ? ($passedCount / $completedRows->count()) * 100 : 0;

        $statistics = [
            'total_participants' => $totalParticipants,
            'completed_participants' => $completedRows->count(),
            'average_score' => round($averageScore ?? 0, 1),
            'highest_score' => $highestScore ?? 0,
            'pass_rate' => round($passRate, 1),
            'total_questions' => $tryoutDetail->questions()->count(),
            'duration' => $tryoutDetail->duration
        ];

        return view('admin.pages.leaderboard.show', compact(
            'package',
            'tryout',
            'tryoutDetail',
            'rankings',
            'statistics'
        ));
    }

    public function exportExcel($package_id, $tryout_id)
    {
        $package = Package::findOrFail($package_id);
        $tryout = Tryout::findOrFail($tryout_id);

        $rankings = $this->buildLeaderboardRows($this->getLeaderboardRankings($tryout_id));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Peringkat',
            'Nama Peserta',
            'Email',
            'Skor',
            'Skor Maks',
            'Status',
            'Waktu Selesai',
            'Durasi',
            'Tanggal',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        $row = 2;
        foreach ($rankings as $index => $ranking) {
            $rank = $index + 1;
            $score = round($ranking->raw_score ?? 0);
            $maxScore = round($ranking->max_score ?? 0);
            $finishedAt = $ranking->finished_at;
            $startedAt = $ranking->started_at;
            $duration = $this->formatDuration($startedAt, $finishedAt);
            $isCompleted = $ranking->status === 'completed';

            $sheet->fromArray([
                $rank,
                $ranking->user->name ?? 'Unknown User',
                $ranking->user->email ?? '-',
                $isCompleted ? $score : '-',
                $isCompleted ? $maxScore : '-',
                $this->formatAttemptStatus($ranking->status, $ranking->is_passed),
                $finishedAt ? $finishedAt->format('H:i') : '-',
                $duration,
                $ranking->created_at ? $ranking->created_at->format('d M Y H:i') : '-',
            ], null, 'A' . $row);

            $row++;
        }

        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = sprintf(
            'leaderboard-%s-%s-%s.xlsx',
            $package->package_id,
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

    public function exportPdf($package_id, $tryout_id)
    {
        $package = Package::findOrFail($package_id);
        $tryout = Tryout::findOrFail($tryout_id);

        $rankings = $this->buildLeaderboardRows($this->getLeaderboardRankings($tryout_id));

        $html = view('admin.pages.leaderboard.export-pdf', [
            'package' => $package,
            'tryout' => $tryout,
            'rankings' => $rankings,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf(
            'leaderboard-%s-%s-%s.pdf',
            $package->package_id,
            $tryout->tryout_id,
            Carbon::now()->format('Ymd_His')
        );

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Determine difficulty level based on duration and question count
     */
    private function getDifficultyLevel($duration)
    {
        if ($duration <= 30) {
            return 'Mudah';
        } elseif ($duration <= 60) {
            return 'Sedang';
        } else {
            return 'Sulit';
        }
    }

    /**
     * Get status badge based on score
     */
    private function getStatusFromScore($score)
    {
        if ($score >= 85) {
            return 'Lulus';
        } elseif ($score >= 70) {
            return 'Cukup';
        } else {
            return 'Gagal';
        }
    }

    private function getLeaderboardRankings($tryoutId)
    {
        return UserAnswer::where('tryout_id', $tryoutId)
            ->with([
                'user',
                'tryoutDetail',
                'userAnswerDetails.question',
                'userAnswerDetails.questionOption',
                'userAnswerDetails.question.questionOptions',
            ])
            ->orderByDesc('finished_at')
            ->orderByDesc('created_at')
            ->get();
    }

    private function buildLeaderboardPaginator($tryoutId, int $perPage = 15)
    {
        $rankings = $this->buildLeaderboardRows($this->getLeaderboardRankings($tryoutId));
        return $this->paginateLeaderboardRows($rankings, $perPage);
    }

    private function paginateLeaderboardRows(Collection $rankings, int $perPage = 15)
    {
        $sorted = $rankings
            ->sortBy([
                ['is_completed', 'desc'],
                ['raw_score', 'desc'],
                ['finished_at', 'asc'],
                ['started_at', 'asc'],
            ])
            ->values();

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $items = $sorted->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $sorted->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    private function buildLeaderboardRows(Collection $rankings): Collection
    {
        return $rankings
            ->groupBy('user_id')
            ->map(function (Collection $userAnswers) {
                return $userAnswers
                    ->groupBy('attempt_token')
                    ->map(fn (Collection $attempt) => $this->buildAttemptLeaderboardRow($attempt))
                    ->filter()
                    ->sortBy([
                        ['is_completed', 'desc'],
                        ['raw_score', 'desc'],
                        ['finished_at', 'asc'],
                        ['started_at', 'asc'],
                    ])
                    ->values()
                    ->first();
            })
            ->filter()
            ->values();
    }

    private function buildAttemptLeaderboardRow(Collection $attempt): ?stdClass
    {
        $representative = $attempt->sortByDesc('created_at')->first();
        if (! $representative) {
            return null;
        }

        $row = new stdClass();
        $row->user = $representative->user;
        $row->attempt_token = $representative->attempt_token;
        $row->status = $this->resolveAttemptStatus($attempt);
        $row->is_completed = $row->status === 'completed';
        $row->started_at = $attempt->min('started_at');
        $row->finished_at = $attempt->max('finished_at');
        $row->created_at = $representative->created_at;
        $row->correct_answers = $attempt->sum('correct_answers');
        $row->wrong_answers = $attempt->sum('wrong_answers');
        $row->unanswered = $attempt->sum('unanswered');

        $totalScore = 0.0;
        $totalMaxScore = 0.0;
        $allSubtestsPassed = true;

        foreach ($attempt as $userAnswer) {
            $type = $userAnswer->tryoutDetail->type_subtest ?? null;
            $subtestScore = $type ? $this->calculateTotalScore($userAnswer, $type) : (float) ($userAnswer->score ?? 0);
            $maxSubtestScore = $type ? $this->getMaxPossibleScoreForDetail($userAnswer->tryout_detail_id, $type) : 0.0;

            $totalScore += $subtestScore;
            $totalMaxScore += $maxSubtestScore;

            if (! $this->isSubtestPassed($userAnswer->tryoutDetail, $subtestScore, $maxSubtestScore, $type)) {
                $allSubtestsPassed = false;
            }
        }

        $row->raw_score = $row->is_completed ? $totalScore : 0.0;
        $row->max_score = $totalMaxScore;
        $row->percentage = $totalMaxScore > 0 ? ($totalScore / $totalMaxScore) * 100 : 0;
        $row->is_passed = $row->is_completed && $allSubtestsPassed;

        return $row;
    }

    private function resolveAttemptStatus(Collection $attempt): string
    {
        if ($attempt->contains(fn (UserAnswer $answer) => $answer->status === 'pending_release')) {
            return 'pending_release';
        }

        if ($attempt->every(fn (UserAnswer $answer) => $answer->status === 'completed')) {
            return 'completed';
        }

        if ($attempt->contains(fn (UserAnswer $answer) => $answer->status === 'in_progress')) {
            return 'in_progress';
        }

        return (string) ($attempt->sortByDesc('created_at')->first()->status ?? 'unknown');
    }

    private function calculateTotalScore(UserAnswer $userAnswer, string $type_subtest): float
    {
        $totalScore = 0.0;

        $details = $userAnswer->relationLoaded('userAnswerDetails')
            ? $userAnswer->userAnswerDetails
            : UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
                ->with(['questionOption', 'question'])
                ->get();

        foreach ($details as $detail) {
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
                    $options = $question->questionOptions;
                    switch ($type_subtest) {
                        case 'twk':
                        case 'tiu':
                            $maxWeight = (float) ($options->max('weight') ?? 0);
                            $total += $maxWeight > 0 ? $maxWeight : 5;
                            break;
                        case 'tkp':
                            $maxWeight = (float) ($options->max('weight') ?? 0);
                            $total += $maxWeight > 0 ? $maxWeight : 1;
                            break;
                        case 'writing':
                        case 'reading':
                        case 'listening':
                            $maxWeight = (float) ($options->max('weight') ?? 0);
                            $total += $maxWeight > 0 ? $maxWeight : 10;
                            break;
                        default:
                            $maxWeight = (float) ($options->max('weight') ?? 0);
                            $total += $maxWeight > 0 ? $maxWeight : 1;
                            break;
                    }
                    break;
            }
        }

        return $total;
    }

    private function getDefaultPassingScore(?string $type_subtest): int
    {
        return match ($type_subtest) {
            'word', 'excel', 'ppt' => 70,
            'teknis', 'social culture', 'management', 'interview' => 65,
            default => 60,
        };
    }

    private function isSubtestPassed($detail, float $rawScore, float $maxScore, ?string $type): bool
    {
        $passingScore = $detail?->passing_score ?? $this->getDefaultPassingScore($type);
        if ($passingScore === null) {
            return false;
        }

        $passingType = $detail?->passing_type ?? 'score';
        if ($passingType === 'percentage') {
            $percentage = $maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0;
            return $percentage >= $passingScore;
        }

        return $rawScore >= $passingScore;
    }

    private function formatAttemptStatus(?string $status, bool $isPassed = false): string
    {
        if ($status === 'completed') {
            return $isPassed ? 'Lulus' : 'Tidak Lulus';
        }

        return [
            'pending_release' => 'Menunggu Rilis',
            'in_progress' => 'Sedang Dikerjakan',
            'abandoned' => 'Ditinggalkan',
        ][$status] ?? ucfirst((string) $status);
    }

    private function formatDuration(?Carbon $startedAt, ?Carbon $finishedAt): string
    {
        if (!$startedAt || !$finishedAt) {
            return '-';
        }

        $seconds = $startedAt->diffInSeconds($finishedAt);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }

        return sprintf('%02d:%02d', $minutes, $secs);
    }
}
