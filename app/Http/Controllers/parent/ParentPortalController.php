<?php

namespace App\Http\Controllers\parent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\user\PackageController as UserPackageController;
use App\Models\ClassAttendance;
use App\Models\Package;
use App\Models\ScheduleBookingRequest;
use App\Models\StudentFeedback;
use App\Models\StudentProgressReport;
use App\Models\User;
use App\Models\UserAnswer;
use App\Models\UserPackageAcces;
use App\Support\Pagination;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ParentPortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        [$children, $child] = $this->childrenAndSelectedChild($request);

        if (! $child) {
            return view('parent.dashboard', $this->emptyPortalData($children));
        }

        $attendanceSummary = $this->attendanceSummary($child->id);
        $activePackages = UserPackageAcces::query()
            ->where('user_id', $child->id)
            ->active()
            ->with('package:package_id,name')
            ->orderBy('end_date')
            ->limit(4)
            ->get();
        $upcomingBookings = ScheduleBookingRequest::query()
            ->where('user_id', $child->id)
            ->whereIn('status', [ScheduleBookingRequest::STATUS_APPROVED, ScheduleBookingRequest::STATUS_COUNTER_PROPOSED])
            ->where('scheduled_start_at', '>=', now())
            ->with(['tentor:id,name', 'package:package_id,name'])
            ->orderBy('scheduled_start_at')
            ->limit(5)
            ->get();
        $recentAnswers = $this->attemptRowsQuery($child->id)
            ->orderByDesc('attempts.finished_at')
            ->limit(5)
            ->get();
        $scoreTrend = $this->attemptRowsQuery($child->id)
            ->orderByDesc('attempts.finished_at')
            ->limit(6)
            ->get()
            ->sortBy('finished_at')
            ->values();
        $scoreTrendChart = $this->scoreTrendChart($scoreTrend);
        $assessmentSummary = $this->assessmentSummary($child->id);
        $childAccountStatus = $this->childAccountStatus($child);
        $recentFeedback = $this->feedbackQuery($child)
            ->with(['tentor:id,name,expertise', 'studyGroup:id,name'])
            ->latest()
            ->limit(3)
            ->get();
        $alerts = $this->alerts($child, $activePackages, $attendanceSummary);

        return view('parent.dashboard', compact(
            'children',
            'child',
            'attendanceSummary',
            'activePackages',
            'upcomingBookings',
            'recentAnswers',
            'scoreTrend',
            'scoreTrendChart',
            'assessmentSummary',
            'childAccountStatus',
            'recentFeedback',
            'alerts'
        ));
    }

    public function attendance(Request $request): View
    {
        [$children, $child] = $this->childrenAndSelectedChild($request);
        $attendances = $child
            ? ClassAttendance::query()
                ->where('user_id', $child->id)
                ->with(['session.schedule:id,title', 'session.studyGroup:id,name', 'session.tentor:id,name'])
                ->latest('check_in_at')
                ->latest()
                ->paginate(Pagination::perPage(15), ['*'], 'attendance_page')
                ->withQueryString()
            : collect();

        return view('parent.attendance', [
            'children' => $children,
            'child' => $child,
            'attendances' => $attendances,
            'attendanceSummary' => $child ? $this->attendanceSummary($child->id) : $this->emptyAttendanceSummary(),
        ]);
    }

    public function packages(Request $request): View
    {
        [$children, $child] = $this->childrenAndSelectedChild($request);
        $accesses = $child
            ? UserPackageAcces::query()
                ->where('user_id', $child->id)
                ->with('package:package_id,name')
                ->withCount(['bookingRequests as completed_booking_count' => fn (Builder $query) => $query->consumesQuota()])
                ->latest()
                ->paginate(Pagination::perPage(12), ['*'], 'package_page')
                ->withQueryString()
            : collect();
        $payments = $child
            ? $child->payments()
                ->with('package:package_id,name')
                ->latest()
                ->paginate(Pagination::perPage(10), ['*'], 'payment_page')
                ->withQueryString()
            : collect();

        return view('parent.packages', compact('children', 'child', 'accesses', 'payments'));
    }

    public function catalog(Request $request): View
    {
        [$children, $child] = $this->childrenAndSelectedChild($request);
        $ownedPackageIds = $child
            ? UserPackageAcces::query()
                ->where('user_id', $child->id)
                ->active()
                ->where(fn (Builder $query) => $query->whereNull('end_date')->orWhere('end_date', '>', now()))
                ->pluck('package_id')
                ->map(fn (int $id): int => $id)
                ->all()
            : [];
        $catalogPackages = $child
            ? Package::query()
                ->active()
                ->where('is_displayed', true)
                ->where('enrollment_mode', Package::ENROLLMENT_DIRECT_PURCHASE)
                ->whereDoesntHave('bookingRule', fn (Builder $query) => $query
                    ->where('is_enabled', true)
                    ->where('learning_mode', 'group'))
                ->withCount(['materials', 'tryouts', 'classes', 'tesKorans'])
                ->latest()
                ->paginate(Pagination::perPage(12))
                ->withQueryString()
            : collect();

        return view('parent.catalog', compact('children', 'child', 'catalogPackages', 'ownedPackageIds'));
    }

    public function checkoutPackage(Request $request, Package $package): Response
    {
        [, $child] = $this->childrenAndSelectedChild($request);
        abort_unless($child, 404, 'Anak belum terhubung ke akun ini.');
        abort_unless(
            $package->status === 'active'
                && $package->is_displayed
                && $package->enrollment_mode === Package::ENROLLMENT_DIRECT_PURCHASE,
            404,
            'Paket tidak tersedia untuk dibeli.'
        );

        $guard = auth()->guard();
        $parent = $guard->user();
        $guard->setUser($child);

        try {
            $response = app(UserPackageController::class)->buyPackage($request, $package->package_id);

            if ($response instanceof JsonResponse) {
                $payload = $response->getData(true);
                $redirectPath = parse_url((string) ($payload['redirect_url'] ?? ''), PHP_URL_PATH);

                if (is_string($redirectPath) && str_starts_with($redirectPath, '/user/')) {
                    $payload['redirect_url'] = route('parent.packages', ['anak' => $child->id]);
                }

                return response()->json($payload, $response->getStatusCode());
            }

            return $response;
        } finally {
            $guard->setUser($parent);
        }
    }

    public function assessments(Request $request): View
    {
        [$children, $child] = $this->childrenAndSelectedChild($request);
        $assessmentItems = $child
            ? DB::query()
                ->fromSub($this->completedAttemptsQuery($child->id), 'attempts')
                ->join('tryouts', 'tryouts.tryout_id', '=', 'attempts.tryout_id')
                ->selectRaw('attempts.tryout_id, tryouts.name as tryout_name, COUNT(*) as attempt_count, AVG(attempts.score) as average_score, MAX(attempts.score) as highest_score, MAX(attempts.finished_at) as last_finished_at, SUM(attempts.correct_answers) as correct_answers, SUM(attempts.total_questions) as total_questions')
                ->groupBy('attempts.tryout_id', 'tryouts.name')
                ->orderByDesc('last_finished_at')
                ->paginate(Pagination::perPage(15))
                ->withQueryString()
            : collect();
        $assessmentSummary = $child
            ? $this->assessmentSummary($child->id)
            : $this->emptyAssessmentSummary();
        $assessmentTrend = $child
            ? $this->attemptRowsQuery($child->id)
                ->orderByDesc('attempts.finished_at')
                ->limit(8)
                ->get()
                ->reverse()
                ->values()
            : collect();
        $assessmentTrendChart = $this->scoreTrendChart($assessmentTrend);

        return view('parent.assessments', compact(
            'children',
            'child',
            'assessmentItems',
            'assessmentSummary',
            'assessmentTrendChart'
        ));
    }

    public function assessmentDetail(Request $request, int $tryout): View
    {
        [$children, $child] = $this->childrenAndSelectedChild($request);

        abort_unless($child, 404, 'Anak belum terhubung ke akun ini.');

        $assessment = DB::table('tryouts')
            ->select(['tryout_id', 'name', 'description'])
            ->where('tryout_id', $tryout)
            ->first();
        abort_unless($assessment, 404, 'Tryout tidak ditemukan.');

        $attemptsQuery = $this->attemptRowsQuery($child->id)
            ->where('attempts.tryout_id', $tryout);
        $attemptSummary = DB::query()
            ->fromSub($this->completedAttemptsQuery($child->id), 'attempts')
            ->where('attempts.tryout_id', $tryout)
            ->selectRaw('COUNT(*) as completed, AVG(attempts.score) as average_score, MAX(attempts.score) as highest_score, SUM(attempts.correct_answers) as correct_answers, SUM(attempts.total_questions) as total_questions')
            ->first();
        abort_unless((int) ($attemptSummary->completed ?? 0) > 0, 404, 'Riwayat tryout tidak ditemukan.');

        $attemptTrend = (clone $attemptsQuery)
            ->orderBy('attempts.finished_at')
            ->limit(12)
            ->get();
        $attempts = $attemptsQuery
            ->orderByDesc('attempts.finished_at')
            ->paginate(Pagination::perPage(12))
            ->withQueryString();
        $feedback = $this->feedbackQuery($child)
            ->with(['tentor:id,name,expertise', 'studyGroup:id,name'])
            ->latest()
            ->limit(5)
            ->get();
        $progress = StudentProgressReport::query()
            ->where('user_id', $child->id)
            ->with(['tentor:id,name,expertise', 'package:package_id,name', 'studyGroup:id,name'])
            ->latest('period_end')
            ->limit(3)
            ->get();

        $attemptSummary = [
            'completed' => (int) $attemptSummary->completed,
            'average_score' => (float) $attemptSummary->average_score,
            'highest_score' => (float) $attemptSummary->highest_score,
            'correct_answers' => (int) $attemptSummary->correct_answers,
            'total_questions' => (int) $attemptSummary->total_questions,
        ];

        return view('parent.assessment-detail', compact(
            'children',
            'child',
            'attempts',
            'attemptSummary',
            'feedback',
            'progress'
        ) + [
            'tryout' => $assessment,
            'attemptTrendChart' => $this->scoreTrendChart($attemptTrend),
        ]);
    }

    public function development(Request $request): View
    {
        [$children, $child] = $this->childrenAndSelectedChild($request);
        $feedback = $child
            ? $this->feedbackQuery($child)
                ->with(['tentor:id,name,expertise', 'studyGroup:id,name'])
                ->latest()
                ->paginate(Pagination::perPage(10), ['*'], 'feedback_page')
                ->withQueryString()
            : collect();
        $progress = $child
            ? StudentProgressReport::query()
                ->where('user_id', $child->id)
                ->with(['tentor:id,name,expertise', 'package:package_id,name', 'studyGroup:id,name'])
                ->latest('period_end')
                ->paginate(Pagination::perPage(10), ['*'], 'progress_page')
                ->withQueryString()
            : collect();

        return view('parent.development', compact('children', 'child', 'feedback', 'progress'));
    }

    public function report(Request $request): View
    {
        [$children, $child] = $this->childrenAndSelectedChild($request);

        abort_unless($child, 404, 'Anak belum terhubung ke akun ini.');

        $attendanceSummary = $this->attendanceSummary($child->id);
        $packages = UserPackageAcces::query()
            ->where('user_id', $child->id)
            ->with('package:package_id,name')
            ->latest()
            ->limit(10)
            ->get();
        $answers = UserAnswer::query()
            ->where('user_id', $child->id)
            ->where('status', 'completed')
            ->with('tryout:tryout_id,name')
            ->latest('finished_at')
            ->limit(10)
            ->get();
        $progress = StudentProgressReport::query()
            ->where('user_id', $child->id)
            ->with(['tentor:id,name', 'package:package_id,name'])
            ->latest('period_end')
            ->limit(3)
            ->get();
        $feedback = $this->feedbackQuery($child)
            ->with(['tentor:id,name,expertise', 'studyGroup:id,name', 'session.schedule:id,title'])
            ->latest()
            ->limit(10)
            ->get();

        return view('parent.report', compact('children', 'child', 'attendanceSummary', 'packages', 'answers', 'progress', 'feedback'));
    }

    private function childrenAndSelectedChild(Request $request): array
    {
        $children = $request->user()->children()
            ->where('users.status', 'aktif')
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.email', 'users.phone', 'users.status']);
        $selectedChildId = (int) $request->query('anak', $children->first()?->id);
        $child = $children->firstWhere('id', $selectedChildId);

        abort_unless(! $selectedChildId || $child, 403, 'Data anak tidak dapat diakses.');

        return [$children, $child];
    }

    private function feedbackQuery(User $child): Builder
    {
        return StudentFeedback::query()
            ->where('is_visible_to_student', true)
            ->where(function (Builder $query) use ($child): void {
                $query->where('user_id', $child->id)
                    ->orWhere(function (Builder $groupQuery) use ($child): void {
                        $groupQuery->whereNull('user_id')
                            ->whereHas('studyGroup.users', fn (Builder $users) => $users->where('users.id', $child->id));
                    });
            });
    }

    private function attendanceSummary(int $childId): array
    {
        $records = ClassAttendance::query()->where('user_id', $childId);
        $total = (clone $records)->count();
        $present = (clone $records)->whereIn('status', ['present', 'late'])->count();

        return [
            'total' => $total,
            'present' => $present,
            'late' => (clone $records)->where('status', 'late')->count(),
            'absent' => (clone $records)->where('status', 'absent')->count(),
            'excused' => (clone $records)->where('status', 'excused')->count(),
            'rate' => $total > 0 ? round(($present / $total) * 100) : null,
        ];
    }

    private function emptyAttendanceSummary(): array
    {
        return ['total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0, 'rate' => null];
    }

    /** @return array{completed: int, average_score: float, highest_score: float} */
    private function assessmentSummary(int $childId): array
    {
        $summary = DB::query()
            ->fromSub($this->completedAttemptsQuery($childId), 'attempts')
            ->selectRaw('COUNT(*) as completed, AVG(score) as average_score, MAX(score) as highest_score')
            ->first();

        return [
            'completed' => (int) ($summary->completed ?? 0),
            'average_score' => (float) ($summary->average_score ?? 0),
            'highest_score' => (float) ($summary->highest_score ?? 0),
        ];
    }

    private function completedAttemptsQuery(int $childId): \Illuminate\Database\Query\Builder
    {
        return DB::table('user_answers')
            ->where('user_id', $childId)
            ->where('status', 'completed')
            ->whereNotNull('finished_at')
            ->selectRaw("tryout_id, COALESCE(NULLIF(attempt_token, ''), CAST(user_answer_id AS CHAR)) as attempt_key, MAX(finished_at) as finished_at, COALESCE(MAX(utbk_total_score), SUM(COALESCE(score, 0))) as score, SUM(COALESCE(correct_answers, 0)) as correct_answers, SUM(COALESCE(correct_answers, 0) + COALESCE(wrong_answers, 0) + COALESCE(unanswered, 0)) as total_questions")
            ->groupByRaw("tryout_id, COALESCE(NULLIF(attempt_token, ''), CAST(user_answer_id AS CHAR))");
    }

    private function attemptRowsQuery(int $childId): \Illuminate\Database\Query\Builder
    {
        return DB::query()
            ->fromSub($this->completedAttemptsQuery($childId), 'attempts')
            ->join('tryouts', 'tryouts.tryout_id', '=', 'attempts.tryout_id')
            ->select([
                'attempts.tryout_id',
                'attempts.attempt_key',
                'attempts.finished_at',
                'attempts.score',
                'attempts.correct_answers',
                'attempts.total_questions',
                'tryouts.name as tryout_name',
            ]);
    }

    /** @return array{points: array<int, array{x: float, y: float, score: float, label: string, name: string}>, polyline: string, area: string, maximum: float, last_score: float|null, change: float|null, change_label: string, label: string} */
    private function scoreTrendChart(Collection $attempts): array
    {
        $items = $attempts->values();
        $maximum = max(100, (float) $items->max('score'));
        $width = 640;
        $height = 210;
        $paddingX = 28;
        $paddingY = 26;
        $usableWidth = $width - ($paddingX * 2);
        $usableHeight = $height - ($paddingY * 2);
        $denominator = max(1, $items->count() - 1);

        $points = $items->map(function (object $attempt, int $index) use ($denominator, $maximum, $usableWidth, $usableHeight, $paddingX, $paddingY): array {
            $score = (float) $attempt->score;

            return [
                'x' => round($paddingX + (($usableWidth / $denominator) * $index), 2),
                'y' => round($paddingY + ($usableHeight - min(1, max(0, $score / $maximum)) * $usableHeight), 2),
                'score' => $score,
                'label' => Carbon::parse($attempt->finished_at)->translatedFormat('d M'),
                'name' => (string) ($attempt->tryout_name ?? $attempt->tryout?->name ?? 'Tryout'),
            ];
        })->all();
        $polyline = collect($points)->map(fn (array $point): string => $point['x'].','.$point['y'])->implode(' ');
        $area = $polyline === ''
            ? ''
            : $paddingX.','.($height - $paddingY).' '.$polyline.' '.($paddingX + $usableWidth).','.($height - $paddingY);
        $firstScore = $points[0]['score'] ?? null;
        $lastPoint = $points !== [] ? $points[array_key_last($points)] : null;
        $lastScore = $lastPoint['score'] ?? null;
        $change = $firstScore !== null && $lastScore !== null && count($points) > 1
            ? $lastScore - $firstScore
            : null;

        return [
            'points' => $points,
            'polyline' => $polyline,
            'area' => $area,
            'maximum' => $maximum,
            'last_score' => $lastScore,
            'change' => $change,
            'change_label' => $change === null ? 'Belum cukup data' : (($change >= 0 ? '+' : '').number_format($change, 1)),
            'label' => $change === null
                ? 'Selesaikan lebih banyak tryout untuk membaca pola nilai.'
                : ($change >= 0 ? 'Nilai terakhir meningkat dibanding percobaan pertama.' : 'Nilai terakhir perlu ditingkatkan dibanding percobaan pertama.'),
        ];
    }

    /** @return array{completed: int, average_score: float, highest_score: float} */
    private function emptyAssessmentSummary(): array
    {
        return ['completed' => 0, 'average_score' => 0, 'highest_score' => 0];
    }

    /** @return array{label: string, description: string, icon: string, classes: string} */
    private function childAccountStatus(User $child): array
    {
        if ($child->status === 'aktif') {
            return [
                'label' => 'Akun aktif',
                'description' => 'Anak dapat mengikuti program belajar.',
                'icon' => 'ri-checkbox-circle-line',
                'classes' => 'bg-emerald-50 text-emerald-700',
            ];
        }

        return [
            'label' => 'Akun nonaktif',
            'description' => 'Hubungi Admin untuk mengaktifkan kembali akun.',
            'icon' => 'ri-information-line',
            'classes' => 'bg-slate-100 text-slate-600',
        ];
    }

    private function alerts(User $child, Collection $packages, array $attendanceSummary): Collection
    {
        $alerts = collect();

        if ($attendanceSummary['absent'] > 0) {
            $alerts->push(['icon' => 'ri-user-unfollow-line', 'tone' => 'amber', 'text' => $child->name.' memiliki '.$attendanceSummary['absent'].' catatan alpa.']);
        }

        foreach ($packages->filter(fn (UserPackageAcces $access) => $access->days_remaining !== null && $access->days_remaining <= 7) as $access) {
            $alerts->push(['icon' => 'ri-time-line', 'tone' => 'red', 'text' => 'Paket '.$access->package?->name.' berakhir dalam '.$access->days_remaining.' hari.']);
        }

        return $alerts;
    }

    private function emptyPortalData(Collection $children): array
    {
        return [
            'children' => $children,
            'child' => null,
            'attendanceSummary' => $this->emptyAttendanceSummary(),
            'activePackages' => collect(),
            'upcomingBookings' => collect(),
            'recentAnswers' => collect(),
            'scoreTrend' => collect(),
            'scoreTrendChart' => $this->scoreTrendChart(collect()),
            'assessmentSummary' => $this->emptyAssessmentSummary(),
            'childAccountStatus' => null,
            'recentFeedback' => collect(),
            'alerts' => collect(),
        ];
    }
}
