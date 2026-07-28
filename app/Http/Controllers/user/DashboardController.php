<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\BillInvoice;
use App\Models\ClassSession;
use App\Models\GeneralPage;
use App\Models\MaterialProgressLog;
use App\Models\Package;
use App\Models\Question;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use App\Models\UserPackageAcces;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $showStatisticsDashboard = $this->showStatisticsDashboard();
        $showLandingDashboard = $this->showLandingDashboard();

        // If no user, just show guest view with public packages
        if (! $user) {
            // Get public packages for guest view (BE logic di BE)
            $publicPackages = Package::where('status', 'active')
                ->where('is_displayed', true)
                ->limit(3)
                ->get();

            return view('user.pages.dashboard.new-index', [
                'user' => null,
                'activePackages' => collect(),
                'recentAttempts' => collect(),
                'stats' => [],
                'expiringSoon' => collect(),
                'publicPackages' => $publicPackages,
                'destinationKeketatan' => [
                    'snbp' => 'Pilih Target',
                    'snbt' => 'Pilih Target',
                ],
                'showStatisticsDashboard' => $showStatisticsDashboard,
                'showLandingDashboard' => $showLandingDashboard,
            ]);
        }

        // Get active packages with proper query
        $activePackages = UserPackageAcces::where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->with('package')
            ->orderBy('end_date', 'asc')
            ->limit(5)
            ->get();

        // Get recent tryout attempts (grouped per tryout attempt, not per subtest)
        $recentAnswers = UserAnswer::where('user_id', $user->id)
            ->with('tryout')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $recentAttempts = $recentAnswers
            ->groupBy(function (UserAnswer $answer) {
                $attemptKey = $answer->attempt_token ?: $answer->user_answer_id;

                return $answer->tryout_id.'|'.$attemptKey;
            })
            ->map(function ($answers) {
                $latest = $answers->sortByDesc('created_at')->first();
                $answers->loadMissing([
                    'tryoutDetail',
                    'userAnswerDetails.question',
                    'userAnswerDetails.questionOption',
                ]);
                $allCompleted = $answers->every(fn (UserAnswer $item) => $item->status === 'completed');
                $tryout = $latest->tryout;

                if ($tryout && ($tryout->requiresIrtScoring() || $tryout->is_toefl)) {
                    $overallPassed = $allCompleted && $answers->every(fn (UserAnswer $item) => (bool) $item->is_passed);
                } else {
                    $overallPassed = $allCompleted && $answers->every(function (UserAnswer $item) {
                        $detail = $item->tryoutDetail;
                        if (! $detail) {
                            return false;
                        }

                        $type = $detail->type_subtest;
                        $rawScore = $this->calculateTotalScore($item, $type);
                        $maxScore = $this->getMaxPossibleScoreForDetail($item->tryout_detail_id, $type);

                        return $this->isSubtestPassed($detail, $rawScore, $maxScore, $type);
                    });
                }

                return (object) [
                    'tryout' => $latest->tryout,
                    'created_at' => $latest->created_at,
                    'status' => $allCompleted ? 'completed' : ($latest->status ?? 'in_progress'),
                    'is_passed' => $overallPassed,
                ];
            })
            ->sortByDesc('created_at')
            ->take(5)
            ->values();

        // Get statistics with correct field names
        $stats = [
            'total_packages' => $activePackages->count(),
            'total_attempts' => UserAnswer::where('user_id', $user->id)->count(),
            'completed_tryouts' => UserAnswer::where('user_id', $user->id)
                ->where('status', 'completed')
                ->count(),
            'average_score' => UserAnswer::where('user_id', $user->id)
                ->where('status', 'completed')
                ->whereNotNull('score')
                ->avg('score') ?? 0,
        ];

        // Get packages expiring soon (within 7 days)
        $expiringSoon = UserPackageAcces::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('end_date', '>', Carbon::now())
            ->where('end_date', '<=', Carbon::now()->addDays(7))
            ->with('package')
            ->get();

        $unpaidInvoices = BillInvoice::where('user_id', $user->id)
            ->whereIn('status', ['unpaid', 'overdue'])
            ->orderBy('due_date')
            ->limit(3)
            ->get();

        $activePackageIds = $activePackages->pluck('package_id');
        $user->loadMissing('participantDestinationCategory');
        $destinationCategoryIds = collect([
            $user->participant_destination_category_id,
            $user->participantDestinationCategory?->parent_id,
        ])->filter()->map(fn ($id) => (int) $id)->values();

        $upcomingClassSessions = ClassSession::with([
            'class',
            'schedule.attendanceSetting',
            'schedule.destinationCategories',
            'schedule.packages:package_id,name',
            'attendances' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            },
        ])
            ->where(function ($query) use ($destinationCategoryIds, $activePackageIds) {
                if ($destinationCategoryIds->isNotEmpty()) {
                    $query->whereHas('schedule.destinationCategories', function ($categoryQuery) use ($destinationCategoryIds) {
                        $categoryQuery->whereIn('participant_destination_categories.id', $destinationCategoryIds);
                    });
                }

                $query->orWhereHas('schedule.packages', fn ($packageQuery) => $packageQuery
                    ->whereIn('packages.package_id', $activePackageIds));

                $query->orWhere(function ($fallbackQuery) use ($activePackageIds) {
                    $fallbackQuery
                        ->whereDoesntHave('schedule.packages')
                        ->whereDoesntHave('schedule.destinationCategories')
                        ->whereHas('class.packages', fn ($packageQuery) => $packageQuery->whereIn('packages.package_id', $activePackageIds));
                });
            })
            ->where('status', 'scheduled')
            ->where('start_at', '>=', now()->subHours(2))
            ->orderBy('start_at')
            ->limit(3)
            ->get();

        // Calculate accuracy stats
        $totalAnswered = UserAnswerDetail::whereHas('userAnswer', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();

        $totalCorrect = UserAnswerDetail::whereHas('userAnswer', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->where('is_correct', true)->count();

        $accuracyPercent = $totalAnswered > 0 ? round(($totalCorrect / $totalAnswered) * 100) : 0;

        // Get recent tryout results (latest 3)
        $recentTryouts = UserAnswer::where('user_id', $user->id)
            ->where('status', 'completed')
            ->with('tryout')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        // Calculate package progress
        $packageProgress = [];
        foreach ($activePackages as $access) {
            $pkg = $access->package;
            $totalItems = $pkg->materials->count() + $pkg->tryouts->count();
            $completedItems = 0;

            // Count completed materials
            foreach ($pkg->materials as $material) {
                $progress = MaterialProgressLog::where('user_id', $user->id)
                    ->where('material_id', $material->material_id)
                    ->where('is_completed', true)
                    ->first();
                if ($progress) {
                    $completedItems++;
                }
            }

            // Count completed tryouts
            foreach ($pkg->tryouts as $tryout) {
                $attempt = UserAnswer::where('user_id', $user->id)
                    ->where('tryout_id', $tryout->tryout_id)
                    ->where('status', 'completed')
                    ->first();
                if ($attempt) {
                    $completedItems++;
                }
            }

            $packageProgress[$pkg->package_id] = $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : 0;
        }

        $destinationKeketatan = $showStatisticsDashboard
            ? $this->destinationKeketatan($user)
            : [
                'snbp' => 'Pilih Target',
                'snbt' => 'Pilih Target',
            ];

        // Check if user wants new layout (default for now)
        return view('user.pages.dashboard.new-index', compact(
            'activePackages',
            'recentAttempts',
            'stats',
            'expiringSoon',
            'totalAnswered',
            'totalCorrect',
            'accuracyPercent',
            'recentTryouts',
            'packageProgress',
            'unpaidInvoices',
            'upcomingClassSessions',
            'destinationKeketatan',
            'showStatisticsDashboard',
            'showLandingDashboard'
        ));
    }

    private function showStatisticsDashboard(): bool
    {
        return $this->isGeneralPageActive('statistik-ptn');
    }

    private function showLandingDashboard(): bool
    {
        return $this->isGeneralPageActive('landing');
    }

    private function isGeneralPageActive(string $pageKey): bool
    {
        if (! Schema::hasTable('general_pages')) {
            return false;
        }

        return (bool) GeneralPage::findActiveByKey($pageKey);
    }

    private function destinationKeketatan($user): array
    {
        $result = [
            'snbp' => 'Pilih Target',
            'snbt' => 'Pilih Target',
        ];

        [$institutionName, $programName, $externalProgramId] = $this->targetDestinationSnapshot($user);

        if ($institutionName === '') {
            return $result;
        }

        foreach (['snbp', 'snbt'] as $source) {
            $result[$source] = $this->resolveKeketatanLabel(
                $source,
                $institutionName,
                $programName,
                $externalProgramId
            ) ?? 'N/A';
        }

        return $result;
    }

    private function targetDestinationSnapshot($user): array
    {
        $institutionName = '';
        $programName = '';

        if ($user->participantDestinationCategory) {
            $institutionName = (string) ($user->participantDestinationCategory->parent->name ?? $user->participantDestinationCategory->name);
            $programName = $user->participantDestinationCategory->parent
                ? (string) $user->participantDestinationCategory->name
                : '';
        } else {
            $institutionName = (string) ($user->participant_destination_institution_name ?? '');
            $programName = (string) ($user->participant_destination_program_name ?? '');
        }

        return [
            trim($institutionName),
            trim($programName),
            trim((string) ($user->participant_destination_external_id ?? '')),
        ];
    }

    private function resolveKeketatanLabel(string $source, string $institutionName, string $programName, string $externalProgramId): ?string
    {
        $ptn = $this->findOfficialInstitution($source, $institutionName);

        if (! $ptn) {
            return null;
        }

        $program = $this->findOfficialProgram($source, (string) $ptn['id_ptn'], $programName, $externalProgramId);

        if (! $program) {
            return null;
        }

        $history = is_array($program['history_daya_tampung'] ?? null) ? $program['history_daya_tampung'] : [];
        $latest = ! empty($history) ? end($history) : null;
        $quotaField = $source === 'snbt' ? 'daya_tampung_snbt' : 'daya_tampung_snbp';
        $dayaTampung = (int) ($latest['daya_tampung'] ?? $program[$quotaField] ?? $program['daya_tampung'] ?? 0);
        $peminat = (int) ($latest['peminat'] ?? $program['peminat'] ?? 0);

        if ($dayaTampung <= 0 || $peminat <= 0) {
            return 'N/A';
        }

        return number_format(($dayaTampung / $peminat) * 100, 2, ',', '.').'%';
    }

    private function findOfficialInstitution(string $source, string $institutionName): ?array
    {
        $target = $this->normalizeDestinationName($institutionName);

        return collect($this->officialInstitutions($source))
            ->first(function ($ptn) use ($target) {
                $name = $this->normalizeDestinationName((string) ($ptn['nama'] ?? ''));

                return $name === $target
                    || Str::contains($name, $target)
                    || Str::contains($target, $name);
            });
    }

    private function findOfficialProgram(string $source, string $ptnId, string $programName, string $externalProgramId): ?array
    {
        $programs = collect($this->officialPrograms($source, $ptnId));
        $externalProgramId = trim($externalProgramId);

        if ($externalProgramId !== '') {
            $byId = $programs->first(function ($program) use ($externalProgramId) {
                return in_array($externalProgramId, array_filter([
                    (string) ($program['id_prodi'] ?? ''),
                    (string) ($program['kode_prodi'] ?? ''),
                ]), true);
            });

            if ($byId) {
                return $byId;
            }
        }

        $target = $this->normalizeDestinationName($programName);

        if ($target === '') {
            return null;
        }

        return $programs->first(function ($program) use ($target) {
            $name = $this->normalizeDestinationName(trim(implode(' ', array_filter([
                $program['jenjang'] ?? null,
                $program['nama'] ?? null,
            ]))));

            return $name === $target
                || Str::contains($name, $target)
                || Str::contains($target, $name);
        });
    }

    private function officialInstitutions(string $source): array
    {
        $endpoint = $source === 'snbt'
            ? 'https://snpmb.id/proxy-ptn-sb.php'
            : 'https://snpmb.id/proxy-ptn-sn.php';

        return Cache::remember("dashboard_snpmb_{$source}_ptn_list", 3600 * 6, function () use ($endpoint) {
            try {
                $response = Http::timeout(10)->get($endpoint);

                return $response->successful() && is_array($response->json())
                    ? $response->json()
                    : [];
            } catch (\Throwable $exception) {
                report($exception);

                return [];
            }
        });
    }

    private function officialPrograms(string $source, string $ptnId): array
    {
        $endpoint = $source === 'snbt'
            ? 'https://snpmb.id/proxy-prodi-sb.php'
            : 'https://snpmb.id/proxy-prodi-sn.php';

        return Cache::remember("dashboard_snpmb_{$source}_prodi_list_{$ptnId}", 3600 * 6, function () use ($endpoint, $ptnId) {
            try {
                $response = Http::timeout(10)->get($endpoint, ['ptn' => $ptnId]);

                return $response->successful() && is_array($response->json())
                    ? $response->json()
                    : [];
            } catch (\Throwable $exception) {
                report($exception);

                return [];
            }
        });
    }

    private function normalizeDestinationName(string $value): string
    {
        return (string) Str::of($value)
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->trim();
    }

    private function calculateTotalScore(UserAnswer $userAnswer, string $type_subtest): float
    {
        $totalScore = 0.0;

        $details = UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
            ->with(['questionOption', 'question'])
            ->get();

        foreach ($details as $detail) {
            $question = $detail->question;
            if (! $question) {
                continue;
            }

            $questionType = $question->question_type ?? 'multiple_choice';
            $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
            $pendingReview = (bool) ($answerMeta['pending_review'] ?? false);

            switch ($questionType) {
                case 'multiple_answer':
                    $totalScore += $this->resolveMultipleAnswerAwardedScore($question, $detail);
                    break;
                case 'multiple_true_false':
                    $totalScore += $this->resolveMultipleTrueFalseAwardedScore($question, $detail);
                    break;

                case 'matching':
                    $totalScore += $this->resolveMatchingAwardedScore($question, $detail);
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
                                $totalScore += $w > 0 ? min($w, 1) : 1;
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
            $isExactCorrect = ($correctCount === $totalCount);
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

    private function getMaxPossibleScoreForDetail(int $tryoutDetailId, string $type_subtest): float
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
                case 'multiple_true_false':
                    $mtfMeta = is_array($question->metadata['multiple_true_false'] ?? null) ? $question->metadata['multiple_true_false'] : [];
                    $weight = (float) ($mtfMeta['score_correct'] ?? ($question->default_weight ?? 0));
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
                    // Gunakan essay_score_correct (field "Benar") untuk max score
                    $weight = (float) ($question->getEssayScoreCorrect() ?? $question->default_weight ?? 1);
                    $total += $weight > 0 ? $weight : 1;
                    break;

                case 'audio':
                    $total += (float) ($question->default_weight ?? 0);
                    break;

                default:
                    $options = $question->questionOptions;
                    switch ($type_subtest) {
                        case 'tkp':
                            $maxWeight = (float) ($options->max('weight') ?? 0);
                            $total += $maxWeight > 0 ? min($maxWeight, 1) : 1;
                            break;
                        case 'twk':
                        case 'tiu':
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 5;
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
}
