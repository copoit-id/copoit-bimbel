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
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class LeaderboardController extends Controller
{
    public function index()
    {
        // Get all tryouts with their packages and participant counts - GROUP BY tryout
        $tryouts = Tryout::with(['tryoutDetails', 'packages', 'directPackage'])
            ->get()
            ->map(function ($tryout) {
                $tryoutDetail = $tryout->tryoutDetails->first();

                // Count total participants across all packages for this tryout
                $participantCount = UserAnswer::where('tryout_id', $tryout->tryout_id)
                    ->where('status', 'completed')
                    ->distinct('user_id')
                    ->count();

                // Skip tryouts with no participants
                if ($participantCount === 0) {
                    return null;
                }

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

        // Get leaderboard data - real participants
        $rankingRows = $this->buildLeaderboardRows($this->getLeaderboardRankings($tryout_id)->get());
        $rankings = $this->paginateLeaderboardRows($rankingRows);

        // Calculate statistics
        $totalParticipants = UserAnswer::where('tryout_id', $tryout_id)
            ->where('status', 'completed')
            ->distinct('user_id')
            ->count();

        $averageScore = $rankingRows->avg('raw_score');
        $highestScore = $rankingRows->max('raw_score');

        $passedCount = UserAnswer::where('tryout_id', $tryout_id)
            ->where('status', 'completed')
            ->where('is_passed', true)
            ->count();

        $passRate = $totalParticipants > 0 ? ($passedCount / $totalParticipants) * 100 : 0;

        $statistics = [
            'total_participants' => $totalParticipants,
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

        $rankings = $this->buildLeaderboardRows($this->getLeaderboardRankings($tryout_id)->get());

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

            $sheet->fromArray([
                $rank,
                $ranking->user->name ?? 'Unknown User',
                $ranking->user->email ?? '-',
                $score,
                $maxScore,
                $ranking->is_passed ? 'Lulus' : 'Tidak Lulus',
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

        $rankings = $this->buildLeaderboardRows($this->getLeaderboardRankings($tryout_id)->get());

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
            ->where('status', 'completed')
            ->whereNotNull('score')
            ->with(['user', 'tryoutDetail'])
            ->orderBy('score', 'desc')
            ->orderBy('finished_at', 'asc');
    }

    private function buildLeaderboardPaginator($tryoutId, int $perPage = 15)
    {
        $rankings = $this->buildLeaderboardRows($this->getLeaderboardRankings($tryoutId)->get());
        return $this->paginateLeaderboardRows($rankings, $perPage);
    }

    private function paginateLeaderboardRows(Collection $rankings, int $perPage = 15)
    {
        $sorted = $rankings
            ->sortBy([
                ['raw_score', 'desc'],
                ['finished_at', 'asc'],
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
        return $rankings->map(function (UserAnswer $ranking) {
            $ranking->loadMissing([
                'tryoutDetail',
                'userAnswerDetails.question',
                'userAnswerDetails.questionOption',
                'userAnswerDetails.question.questionOptions',
            ]);

            $type = $ranking->tryoutDetail->type_subtest ?? null;
            $rawScore = $type ? $this->calculateTotalScore($ranking, $type) : (float) ($ranking->score ?? 0);
            $maxScore = $type
                ? $this->getMaxPossibleScoreForDetail($ranking->tryout_detail_id, $type)
                : 0;
            $detail = $ranking->tryoutDetail;
            $passingScore = $detail->passing_score ?? $this->getDefaultPassingScore($type);

            $ranking->raw_score = $rawScore;
            $ranking->max_score = $maxScore;
            $ranking->is_passed = $this->isSubtestPassed($detail, $rawScore, $maxScore, $type);

            return $ranking;
        });
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
