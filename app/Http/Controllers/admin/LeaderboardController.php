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

                $totalDuration = $tryout->tryoutDetails->sum('duration');

                return [
                    'tryout_id' => $tryout->tryout_id,
                    'package_id' => $allPackages->first()->package_id, // Use first package for routing
                    'name' => $tryout->name,
                    'description' => $tryout->description,
                    'total_questions' => $tryout->tryoutDetails->sum(fn($detail) => $detail->questions()->count()),
                    'duration' => $totalDuration,
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

        $isSksFull = in_array($tryout->type_tryout, ['skd_full', 'pppk_full']);

        // Get leaderboard data - real participants
        $rankingRows = $this->buildLeaderboardRows($this->getLeaderboardRankings($tryout_id)->get());

        // For SKD Full, group by user and combine subtest scores
        if ($isSksFull) {
            $rankingRows = $this->groupRankingsByUser($rankingRows);
        }

        $rankings = $this->paginateLeaderboardRows($rankingRows);

        // Calculate statistics
        $totalParticipants = UserAnswer::where('tryout_id', $tryout_id)
            ->where('status', 'completed')
            ->distinct('user_id')
            ->count();

        $averageScore = $rankingRows->avg('raw_score');
        $highestScore = $rankingRows->max('raw_score');

        $passedCount = $rankingRows->where('is_passed', true)->count();

        $passRate = $totalParticipants > 0 ? ($passedCount / $totalParticipants) * 100 : 0;

        $statistics = [
            'total_participants' => $totalParticipants,
            'average_score' => round($averageScore ?? 0, 1),
            'highest_score' => $highestScore ?? 0,
            'pass_rate' => round($passRate, 1),
            'total_questions' => $tryout->tryoutDetails->sum(fn($detail) => $detail->questions()->count()),
            'duration' => $tryout->tryoutDetails->sum('duration')
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

        $rankingRows = $this->buildLeaderboardRows($this->getLeaderboardRankings($tryout_id)->get());

        if (in_array($tryout->type_tryout, ['skd_full', 'pppk_full'])) {
            $rankingRows = $this->groupRankingsByUser($rankingRows);
        }

        $rankings = $this->sortLeaderboardRows($rankingRows);

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
            $score = round((float) ($ranking->raw_score ?? 0), 2);
            $maxScore = round((float) ($ranking->max_score ?? 0), 2);
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

        $rankingRows = $this->buildLeaderboardRows($this->getLeaderboardRankings($tryout_id)->get());

        if (in_array($tryout->type_tryout, ['skd_full', 'pppk_full'])) {
            $rankingRows = $this->groupRankingsByUser($rankingRows);
        }

        $rankings = $this->sortLeaderboardRows($rankingRows);

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
            ->whereNotNull('raw_score')
            ->with(['user', 'tryoutDetail'])
            ->orderBy('raw_score', 'desc')
            ->orderBy('finished_at', 'asc');
    }

    private function buildLeaderboardPaginator($tryoutId, int $perPage = 15)
    {
        $rankings = $this->buildLeaderboardRows($this->getLeaderboardRankings($tryoutId)->get());
        return $this->paginateLeaderboardRows($rankings, $perPage);
    }

    private function paginateLeaderboardRows(Collection $rankings, int $perPage = 15)
    {
        $sorted = $this->sortLeaderboardRows($rankings);

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

    private function sortLeaderboardRows(Collection $rankings): Collection
    {
        return $rankings
            ->sortBy([
                ['raw_score', 'desc'],
                ['finished_at', 'asc'],
            ])
            ->values();
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
            $storedRawScore = (float) ($ranking->raw_score ?? 0);
            $storedMaxScore = (float) ($ranking->max_score ?? 0);

            if ($storedMaxScore > 0) {
                $rawScore = $storedRawScore;
                $maxScore = $storedMaxScore;
            } else {
                $rawScore = $type ? $this->calculateTotalScore($ranking, $type) : (float) ($ranking->score ?? 0);
                $maxScore = $type
                    ? $this->getMaxPossibleScoreForDetail($ranking->tryout_detail_id, $type)
                    : 0;
            }

            $detail = $ranking->tryoutDetail;
            $ranking->raw_score = $rawScore;
            $ranking->max_score = $maxScore;
            $ranking->is_passed = $this->isSubtestPassed($detail, $rawScore, $maxScore, $type);

            return $ranking;
        });
    }

    /**
     * For SKD Full: group by user and combine subtest scores, picking best attempt per user
     */
    private function groupRankingsByUser(Collection $rankingRows): Collection
    {
        return $rankingRows->groupBy('user_id')
            ->map(function ($userAnswers) {
                // Group by attempt_token, sum subtest scores per attempt, pick best
                $bestAttempt = $userAnswers
                    ->groupBy('attempt_token')
                    ->map(function ($attempt) {
                        $totalRaw = $attempt->sum('raw_score');
                        $totalMax = $attempt->sum('max_score');

                        // Recalculate pass: all subtests must pass
                        $allPassed = $attempt->every(function ($ua) {
                            $detail = $ua->tryoutDetail;
                            if (!$detail) {
                                return false;
                            }
                            $passingScore = $detail->passing_score ?? $this->getDefaultPassingScore($detail->type_subtest);
                            $passingType = $detail->passing_type ?? 'score';

                            if ($passingType === 'percentage') {
                                $percentage = $ua->max_score > 0 ? ($ua->raw_score / $ua->max_score) * 100 : 0;
                                return $percentage >= $passingScore;
                            }

                            return $ua->raw_score >= $passingScore;
                        });

                        return (object) [
                            'raw_score' => $totalRaw,
                            'max_score' => $totalMax,
                            'is_passed' => $allPassed,
                            'finished_at' => $attempt->max('finished_at'),
                            'started_at' => $attempt->min('started_at'),
                            'created_at' => $attempt->min('created_at'),
                        ];
                    })
                    ->sortByDesc('raw_score')
                    ->values()
                    ->first();

                $first = $userAnswers->first();
                $first->raw_score = $bestAttempt->raw_score;
                $first->max_score = $bestAttempt->max_score;
                $first->is_passed = $bestAttempt->is_passed;
                $first->finished_at = $bestAttempt->finished_at;
                $first->started_at = $bestAttempt->started_at;
                $first->created_at = $bestAttempt->created_at;

                return $first;
            })
            ->sortBy([
                ['raw_score', 'desc'],
                ['finished_at', 'asc'],
            ])
            ->values();
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
