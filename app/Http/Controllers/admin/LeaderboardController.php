<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\ParticipantDestinationCategory;
use App\Models\Question;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use App\Services\MultipleAnswerScoringService;
use App\Services\TryoutRankingService;
use App\Services\TryoutScoreDisplayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class LeaderboardController extends Controller
{
    /** @var array<string, float> */
    private array $maxPossibleScoreCache = [];

    public function __construct(
        private readonly TryoutRankingService $tryoutRankingService,
        private readonly TryoutScoreDisplayService $scoreDisplayService,
    ) {
    }

    public function index()
    {
        // Get all tryouts with their packages and participant counts - GROUP BY tryout
        $tryouts = Tryout::with([
            'tryoutDetails' => fn ($query) => $query->withCount('questions'),
            'packages',
        ])
            ->get()
            ->map(function ($tryout) {
                $totalQuestions = (int) $tryout->tryoutDetails->sum('questions_count');
                $totalDuration = (int) $tryout->tryoutDetails->sum('duration');

                // Count total participants across all packages for this tryout
                $participantCount = UserAnswer::where('tryout_id', $tryout->tryout_id)
                    ->where('status', 'completed')
                    ->distinct('user_id')
                    ->count();

                // Get all packages that have this tryout
                $allPackages = $tryout->packages;

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
                    'total_questions' => $totalQuestions,
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
        $tryout = Tryout::with([
            'tryoutDetails' => fn ($query) => $query->withCount('questions'),
        ])->findOrFail($tryout_id);
        $destinationCategories = $this->getDestinationCategories();
        $destinationFilter = $this->resolveDestinationFilter(request(), $destinationCategories);

        // Get tryout details
        $tryoutDetail = $tryout->tryoutDetails->first();
        if (!$tryoutDetail) {
            return redirect()->route('admin.leaderboard.index')
                ->with('error', 'Tryout belum memiliki detail soal');
        }

        // Get leaderboard data - real participants
        $rankingRows = $this->sortLeaderboardRows($this->buildLeaderboardRows(
            $this->getLeaderboardRankings($tryout_id, $destinationFilter['ids'])->get(),
            $tryout
        ));
        $rankings = $this->paginateLeaderboardRows($rankingRows);

        // Calculate statistics
        $totalParticipants = $rankingRows->count();

        $finalScoreSummary = $this->scoreDisplayService->summarizeFinalScores($rankingRows);

        $passedCount = $rankingRows->where('is_passed', true)->count();

        $passRate = $totalParticipants > 0 ? ($passedCount / $totalParticipants) * 100 : 0;

        $statistics = [
            'total_participants' => $totalParticipants,
            'average_score' => $finalScoreSummary['average'],
            'highest_score' => $finalScoreSummary['highest'],
            'pass_rate' => round($passRate, 1),
            'total_questions' => (int) $tryout->tryoutDetails->sum('questions_count'),
            'duration' => (int) $tryout->tryoutDetails->sum('duration')
        ];

        $scoreDisplayService = $this->scoreDisplayService;
        $statistics['average_score_display'] = $finalScoreSummary['average_formatted'];
        $statistics['highest_score_display'] = $finalScoreSummary['highest_formatted'];
        $podiumRankings = $rankingRows
            ->take(3)
            ->values()
            ->map(function ($ranking, int $index) use ($scoreDisplayService, $tryout): array {
                $displayScore = $ranking->display_score
                    ?? $scoreDisplayService->present($tryout, $ranking->raw_score, 0, 0, $ranking->max_score);

                return [
                    'rank' => $index + 1,
                    'name' => $ranking->user->name ?? 'Peserta',
                    'origin_institution' => $ranking->user?->origin_institution,
                    'major_choices' => $ranking->user?->leaderboard_major_choices ?? [],
                    'score' => $displayScore['formatted'],
                    'maximum' => $scoreDisplayService->shouldShowMaximum($tryout)
                        ? $displayScore['formatted_maximum']
                        : null,
                ];
            })
            ->keyBy('rank');

        return view('admin.pages.leaderboard.show', compact(
            'package',
            'tryout',
            'tryoutDetail',
            'rankings',
            'statistics',
            'podiumRankings',
            'destinationCategories',
            'destinationFilter'
        ));
    }

    public function exportExcel(Request $request, $package_id, $tryout_id)
    {
        $package = Package::findOrFail($package_id);
        $tryout = Tryout::with('tryoutDetails')->findOrFail($tryout_id);
        $destinationCategories = $this->getDestinationCategories();
        $destinationFilter = $this->resolveDestinationFilter($request, $destinationCategories);

        $rankings = $this->sortLeaderboardRows(
            $this->buildLeaderboardRows($this->getLeaderboardRankings($tryout_id, $destinationFilter['ids'])->get(), $tryout)
        );

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Peringkat');
        $subtests = $this->exportSubtests($tryout);

        $headers = [
            'Peringkat',
            'Nama Peserta',
            'Email',
            'Asal Sekolah / Instansi',
            'Pilihan Jurusan',
            'Tujuan / Instansi',
        ];

        foreach ($subtests as $subtest) {
            $headers[] = 'Skor '.$subtest['alias'];
        }

        $headers = [
            ...$headers,
            'Skor Total',
            'Skor Maks',
            'Status',
            'Waktu Selesai',
            'Durasi',
            'Tanggal',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($rankings as $index => $ranking) {
            $rank = $index + 1;
            $score = round((float) ($ranking->raw_score ?? 0), 2);
            $maxScore = round((float) ($ranking->max_score ?? 0), 2);
            $finishedAt = $ranking->finished_at;
            $startedAt = $ranking->started_at;
            $duration = $this->formatDuration($startedAt, $finishedAt);

            $values = [
                $rank,
                $ranking->user->name ?? 'Unknown User',
                $ranking->user->email ?? '-',
                $ranking->user?->origin_institution ?? '-',
                $ranking->user?->leaderboard_major_choices_display ?? '-',
                $ranking->user?->participant_destination_display_name ?? '-',
            ];

            foreach ($subtests as $subtest) {
                $values[] = $ranking->display_subtest_scores[$subtest['id']]['formatted']
                    ?? $this->formatScore($ranking->subtest_scores[$subtest['id']] ?? 0);
            }

            $displayScore = $ranking->display_score['formatted'] ?? $score;
            $displayMaximum = $ranking->display_score['formatted_maximum'] ?? $maxScore;

            $sheet->fromArray([
                ...$values,
                $displayScore,
                $displayMaximum,
                $ranking->is_passed ? 'Lulus' : 'Tidak Lulus',
                $finishedAt ? $finishedAt->format('H:i') : '-',
                $duration,
                $ranking->created_at ? $ranking->created_at->format('d M Y H:i') : '-',
            ], null, 'A'.$row);

            $row++;
        }

        $lastColumn = $sheet->getHighestColumn();
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(32);
        $sheet->getColumnDimension('D')->setWidth(28);
        $sheet->getColumnDimension('E')->setWidth(32);
        $sheet->getColumnDimension('F')->setWidth(28);
        for ($column = 7; $column <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastColumn); $column++) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column))->setWidth(15);
        }
        $this->styleExportSheet($sheet, $lastColumn, $row - 1);

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

    public function exportPdf(Request $request, $package_id, $tryout_id)
    {
        $package = Package::findOrFail($package_id);
        $tryout = Tryout::with('tryoutDetails')->findOrFail($tryout_id);
        $destinationCategories = $this->getDestinationCategories();
        $destinationFilter = $this->resolveDestinationFilter($request, $destinationCategories);

        $rankings = $this->sortLeaderboardRows(
            $this->buildLeaderboardRows($this->getLeaderboardRankings($tryout_id, $destinationFilter['ids'])->get(), $tryout)
        );

        $html = view('admin.pages.leaderboard.export-pdf', [
            'package' => $package,
            'tryout' => $tryout,
            'rankings' => $rankings,
            'subtests' => $this->exportSubtests($tryout),
            'destinationFilter' => $destinationFilter,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
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

    private function getLeaderboardRankings($tryoutId, array $destinationCategoryIds = [])
    {
        return UserAnswer::where('tryout_id', $tryoutId)
            ->where('status', 'completed')
            ->whereNotNull('score')
            ->when(!empty($destinationCategoryIds), function ($query) use ($destinationCategoryIds) {
                $query->whereHas('user', function ($userQuery) use ($destinationCategoryIds) {
                    $userQuery->whereIn('participant_destination_category_id', $destinationCategoryIds);
                });
            })
            ->with([
                'user:id,name,email,origin_institution,major_choice_1,major_choice_2,participant_destination_category_id,second_participant_destination_category_id,participant_destination_source,participant_destination_institution_name,participant_destination_program_name,second_participant_destination_source,second_participant_destination_institution_name,second_participant_destination_program_name',
                'user.participantDestinationCategory.parent',
                'user.secondParticipantDestinationCategory.parent',
                'tryoutDetail',
                'userAnswerDetails.question.questionOptions',
                'userAnswerDetails.questionOption',
            ])
            ->orderBy('score', 'desc')
            ->orderBy('finished_at', 'asc');
    }

    private function buildLeaderboardPaginator($tryoutId, int $perPage = 15)
    {
        $tryout = Tryout::findOrFail($tryoutId);
        $rankings = $this->buildLeaderboardRows($this->getLeaderboardRankings($tryoutId)->get(), $tryout);
        return $this->paginateLeaderboardRows($rankings, \App\Support\Pagination::perPage($perPage));
    }

    private function getDestinationCategories()
    {
        return ParticipantDestinationCategory::query()
            ->root()
            ->active()
            ->with(['activeChildren'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function resolveDestinationFilter(Request $request, $destinationCategories): array
    {
        $categoryId = $request->integer('destination_category_id') ?: null;
        $subcategoryId = $request->integer('destination_subcategory_id') ?: null;
        $selectedCategory = $categoryId
            ? $destinationCategories->firstWhere('id', $categoryId)
            : null;

        if (!$selectedCategory) {
            return [
                'category_id' => null,
                'subcategory_id' => null,
                'ids' => [],
                'label' => 'Semua tujuan / instansi',
            ];
        }

        $selectedSubcategory = $subcategoryId
            ? $selectedCategory->activeChildren->firstWhere('id', $subcategoryId)
            : null;

        if ($selectedSubcategory) {
            return [
                'category_id' => $selectedCategory->id,
                'subcategory_id' => $selectedSubcategory->id,
                'ids' => [$selectedSubcategory->id],
                'label' => $selectedCategory->name . ' - ' . $selectedSubcategory->name,
            ];
        }

        $ids = $selectedCategory->activeChildren->pluck('id')
            ->prepend($selectedCategory->id)
            ->unique()
            ->values()
            ->all();

        return [
            'category_id' => $selectedCategory->id,
            'subcategory_id' => null,
            'ids' => $ids,
            'label' => $selectedCategory->name,
        ];
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
        return $this->tryoutRankingService->sort($rankings->map(function (object $ranking): array {
            return [
                'ranking_score' => $ranking->display_score['value'] ?? $ranking->raw_score ?? 0,
                'raw_score' => $ranking->raw_score ?? 0,
                'finished_at' => $ranking->finished_at,
                'user' => $ranking->user,
                'ranking' => $ranking,
            ];
        }))->map(fn (array $row): object => $row['ranking']);
    }

    private function buildLeaderboardRows(Collection $allAnswers, Tryout $tryout): Collection
    {
        $scoreDisplayService = $this->scoreDisplayService;

        return $allAnswers->groupBy('user_id')->map(function ($userAnswers) use ($tryout, $scoreDisplayService) {
            $attemptGroups = $userAnswers->groupBy('attempt_token');

            $bestAttempt = $attemptGroups->map(function ($attempt) use ($tryout, $scoreDisplayService) {
                $totalScore = 0.0;
                $totalMaxScore = 0.0;
                $totalCorrect = 0;
                $totalQuestions = 0;
                $allSubtestsPassed = true;
                $subtestScores = [];

                foreach ($attempt as $ranking) {
                    $ranking->loadMissing([
                        'tryoutDetail',
                        'userAnswerDetails.question.questionOptions',
                        'userAnswerDetails.questionOption',
                        'userAnswerDetails.question.questionOptions',
                    ]);

                    $type = $ranking->tryoutDetail->type_subtest ?? null;
                    $rawScore = $type ? $this->calculateTotalScore($ranking, $type) : (float) ($ranking->score ?? 0);
                    $maxScore = $type ? $this->getMaxPossibleScoreForDetail($ranking->tryout_detail_id, $type) : 0;
                    $detail = $ranking->tryoutDetail;

                    if (!$this->isSubtestPassed($detail, $rawScore, $maxScore, $type)) {
                        $allSubtestsPassed = false;
                    }

                    $totalScore += $rawScore;
                    $totalMaxScore += $maxScore;
                    $subtestScores[$ranking->tryout_detail_id] = $rawScore;
                    $totalCorrect += $ranking->userAnswerDetails->where('is_correct', true)->count();
                    $totalQuestions += (int) ($detail->questions_count ?? $ranking->userAnswerDetails->count());
                }

                $representative = $attempt->first();
                if ($tryout->requiresIrtScoring()) {
                    $totalScore = (float) ($representative->utbk_total_score ?? 0);
                    $totalMaxScore = 1000.0;
                }

                $representative->raw_score = $totalScore;
                $representative->max_score = $totalMaxScore;
                $representative->is_passed = $allSubtestsPassed;
                $representative->subtest_scores = $subtestScores;
                $representative->display_score = $scoreDisplayService->present(
                    $tryout, $totalScore, $totalCorrect, $totalQuestions, $totalMaxScore, $attempt->count()
                );
                $representative->started_at = $attempt->min('started_at');
                $representative->finished_at = $attempt->max('finished_at');

                return $representative;
            })->filter()->sortByDesc('raw_score')->values()->first();

            return $bestAttempt;
        })->filter()->values();
    }

    private function calculateTotalScore(UserAnswer $userAnswer, string $type_subtest): float
    {
        $totalScore = 0.0;

        $details = $userAnswer->relationLoaded('userAnswerDetails')
            ? $userAnswer->userAnswerDetails
            : UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
            ->with(['questionOption', 'question.questionOptions'])
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

    private function getMaxPossibleScoreForDetail(int $tryoutDetailId, ?string $type_subtest): float
    {
        $cacheKey = $tryoutDetailId.'|'.($type_subtest ?? '');
        if (isset($this->maxPossibleScoreCache[$cacheKey])) {
            return $this->maxPossibleScoreCache[$cacheKey];
        }

        $questions = Question::where('tryout_detail_id', $tryoutDetailId)
            ->with('questionOptions')
            ->get();

        if ($questions->isEmpty()) {
            return $this->maxPossibleScoreCache[$cacheKey] = 0;
        }

        $total = 0.0;

        foreach ($questions as $question) {
            $questionType = $question->question_type ?? 'multiple_choice';

            switch ($questionType) {
                case 'multiple_answer':
                    $total += app(MultipleAnswerScoringService::class)->config($question)['score_correct'];
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
                    // Gunakan essay_score_correct (field "Benar") untuk max score
                    $weight = (float) ($question->getEssayScoreCorrect() ?? $question->default_weight ?? 1);
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

        return $this->maxPossibleScoreCache[$cacheKey] = $total;
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

    private function exportSubtests(Tryout $tryout): Collection
    {
        $aliases = [
            'twk' => 'TWK',
            'tiu' => 'TIU',
            'tkp' => 'TKP',
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
        ];

        return $tryout->tryoutDetails
            ->sortBy('tryout_detail_id')
            ->values()
            ->map(function (TryoutDetail $detail) use ($aliases) {
                $type = Str::lower((string) ($detail->type_subtest ?? ''));

                return [
                    'id' => (int) $detail->tryout_detail_id,
                    'alias' => $aliases[$type] ?? Str::upper(Str::limit($type ?: 'Subtest', 6, '')),
                    'name' => Str::headline(str_replace('_', ' ', $type ?: 'Subtest')),
                ];
            });
    }

    private function formatScore(float|int|null $score): string
    {
        return rtrim(rtrim(number_format((float) $score, 2, '.', ''), '0'), '.');
    }

    private function styleExportSheet($sheet, string $lastColumn, int $lastRow): void
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
        $sheet->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.25)->setRight(0.25);
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
