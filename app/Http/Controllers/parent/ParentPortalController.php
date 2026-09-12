<?php

namespace App\Http\Controllers\parent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\admin\LaporanController as AdminLaporanController;
use App\Http\Controllers\user\PackageController as UserPackageController;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Package;
use App\Models\Payment;
use App\Models\ScheduleBookingRequest;
use App\Models\StudentFeedback;
use App\Models\StudentProgressReport;
use App\Models\User;
use App\Models\UserAnswer;
use App\Models\UserPackageAcces;
use App\Support\Pagination;
use App\Support\RichTextSanitizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ParentPortalController extends Controller
{
    public function selectChild(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'child_id' => ['required', 'integer'],
        ]);

        $child = $request->user()->children()
            ->where('users.id', $data['child_id'])
            ->where('users.status', 'aktif')
            ->first();

        abort_unless($child, 403, 'Data anak tidak dapat diakses.');

        $request->session()->put($this->selectedChildSessionKey($request), $child->id);

        return to_route('parent.dashboard');
    }

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

        if ($attendances instanceof \Illuminate\Pagination\AbstractPaginator) {
            $attendances->through(function (ClassAttendance $attendance): ClassAttendance {
                $attendance->setAttribute('status_label', match ($attendance->status) {
                    'present' => 'Hadir',
                    'late' => 'Terlambat',
                    'absent' => 'Alpa',
                    'excused' => 'Izin',
                    default => ucfirst((string) $attendance->status),
                });
                $attendance->setAttribute('status_variant', match ($attendance->status) {
                    'present' => 'success',
                    'late', 'excused' => 'warning',
                    'absent' => 'danger',
                    default => 'secondary',
                });

                return $attendance;
            });
        }

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
                ->with(['package' => fn (BelongsTo $query) => $query
                    ->select(['package_id', 'name'])
                    ->withCount(['materials', 'tryouts', 'classes', 'tesKorans'])])
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

        if ($payments instanceof \Illuminate\Pagination\AbstractPaginator) {
            $payments->through(function (Payment $payment): Payment {
                $payment->setAttribute('status_label', match ($payment->status) {
                    Payment::STATUS_SUCCESS => 'Lunas',
                    Payment::STATUS_PARTIAL => 'Sebagian',
                    Payment::STATUS_PENDING => 'Menunggu',
                    Payment::STATUS_FAILED => 'Gagal',
                    Payment::STATUS_EXPIRED => 'Kedaluwarsa',
                    default => ucfirst((string) $payment->status),
                });
                $payment->setAttribute('status_variant', match ($payment->status) {
                    Payment::STATUS_SUCCESS => 'success',
                    Payment::STATUS_PARTIAL, Payment::STATUS_PENDING => 'warning',
                    Payment::STATUS_FAILED, Payment::STATUS_EXPIRED => 'danger',
                    default => 'secondary',
                });

                return $payment;
            });
        }

        $packageSummary = $child ? [
            'total_access' => UserPackageAcces::query()->where('user_id', $child->id)->count(),
            'active_access' => UserPackageAcces::query()->where('user_id', $child->id)->active()->count(),
            'transactions' => $child->payments()->count(),
            'paid_total' => (int) $child->payments()->where('status', Payment::STATUS_SUCCESS)->sum('total_amount'),
        ] : ['total_access' => 0, 'active_access' => 0, 'transactions' => 0, 'paid_total' => 0];

        return view('parent.packages', compact('children', 'child', 'accesses', 'payments', 'packageSummary'));
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
                    $payload['redirect_url'] = route('parent.packages');
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
            'feedback'
        ) + [
            'tryout' => $assessment,
            'attemptTrendChart' => $this->scoreTrendChart($attemptTrend),
        ]);
    }

    public function development(Request $request): View
    {
        [$children, $child] = $this->childrenAndSelectedChild($request);
        $sessionTimeline = collect();
        if ($child) {
            $attendance = ClassAttendance::query()->where('user_id', $child->id)->get()->keyBy('class_session_id');
            $feedbackBySession = $this->feedbackQuery($child)->whereNotNull('class_session_id')->with('tentor:id,name')->get()->groupBy('class_session_id');
            $sessionIds = $attendance->keys()->merge($feedbackBySession->keys())->unique()->values();
            $progressBySession = StudentProgressReport::query()
                ->whereIn('class_session_id', $sessionIds)
                ->whereNull('user_id')
                ->with('tentor:id,name')
                ->get()
                ->keyBy('class_session_id');
            $sessionTimeline = ClassSession::query()->with(['schedule:id,title', 'studyGroup:id,name'])->whereIn('id', $sessionIds)->latest('start_at')->get()
                ->map(function (ClassSession $session) use ($attendance, $feedbackBySession, $progressBySession): array {
                    $feedback = $feedbackBySession->get($session->id, collect());
                    $progress = $progressBySession->get($session->id);

                    return [
                        'session' => $session,
                        'attendance' => $attendance->get($session->id),
                        'personalFeedback' => $feedback->where('scope', 'personal')->values(),
                        'groupFeedback' => $feedback->where('scope', 'group')->values(),
                        'progress' => $progress,
                        'progressHtml' => $progress ? RichTextSanitizer::sanitize($progress->summary) : null,
                    ];
                });
        }

        return view('parent.development', compact('children', 'child', 'sessionTimeline'));
    }

    public function report(Request $request): View|RedirectResponse
    {
        [$children, $child] = $this->childrenAndSelectedChild($request);

        if ($request->query->has('anak')) {
            return to_route('parent.report');
        }

        if (! $child) {
            return to_route('parent.dashboard')
                ->with('error', 'Pilih atau hubungkan anak terlebih dahulu untuk melihat laporan.');
        }
        $feedback = $this->feedbackQuery($child)
            ->with(['tentor:id,name,expertise', 'studyGroup:id,name', 'session.schedule:id,title'])
            ->latest()
            ->limit(10)
            ->get();
        $adminReport = app(AdminLaporanController::class)->studentDetail($child, true);

        return view('admin.pages.laporan.students.show', $adminReport->getData() + [
            'children' => $children,
            'child' => $child,
            'feedback' => $feedback,
            'parentReport' => true,
        ]);
    }

    public function reportAttempt(Request $request, int $tryout, string $attemptToken): View|RedirectResponse
    {
        [$children, $child] = $this->childrenAndSelectedChild($request);

        if ($request->query->has('anak')) {
            return to_route('parent.report.attempt', compact('tryout', 'attemptToken'));
        }

        if (! $child) {
            return to_route('parent.dashboard')
                ->with('error', 'Pilih atau hubungkan anak terlebih dahulu untuk melihat detail laporan.');
        }

        abort_unless(
            UserAnswer::query()
                ->where('user_id', $child->id)
                ->where('tryout_id', $tryout)
                ->where('attempt_token', $attemptToken)
                ->exists(),
            404,
            'Detail pengerjaan Tryout tidak ditemukan.'
        );
        $adminReport = app(AdminLaporanController::class)->attemptDetail(
            $request,
            $tryout,
            $attemptToken,
            $child->id
        );

        return view('admin.pages.laporan.answer', $adminReport->getData() + [
            'children' => $children,
            'child' => $child,
            'parentReport' => true,
        ]);
    }

    private function childrenAndSelectedChild(Request $request): array
    {
        $children = $request->user()->children()
            ->where('users.status', 'aktif')
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.email', 'users.phone', 'users.status']);
        $requestedChildId = $request->integer('anak');
        $selectedChildId = $requestedChildId
            ?: (int) $request->session()->get($this->selectedChildSessionKey($request), $children->first()?->id);
        $child = $children->firstWhere('id', $selectedChildId);

        abort_unless(! $requestedChildId || $child, 403, 'Data anak tidak dapat diakses.');

        if (! $child && $children->isNotEmpty()) {
            $child = $children->first();
        }

        if ($child) {
            $request->session()->put($this->selectedChildSessionKey($request), $child->id);
        }

        return [$children, $child];
    }

    private function selectedChildSessionKey(Request $request): string
    {
        return 'parent.selected_child.'.$request->user()->id;
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
            ->selectRaw("tryout_id, COALESCE(NULLIF(attempt_token, ''), CAST(user_answer_id AS CHAR)) as attempt_key, MAX(finished_at) as finished_at, COALESCE(MAX(utbk_total_score), SUM(COALESCE(score, 0))) as score, SUM(COALESCE(correct_answers, 0)) as correct_answers, SUM(COALESCE(wrong_answers, 0)) as wrong_answers, SUM(COALESCE(unanswered, 0)) as unanswered, SUM(COALESCE(correct_answers, 0) + COALESCE(wrong_answers, 0) + COALESCE(unanswered, 0)) as total_questions")
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
                'attempts.wrong_answers',
                'attempts.unanswered',
                'attempts.total_questions',
                'tryouts.name as tryout_name',
            ]);
    }

    /** @return array{points: array<int, array{x: float, y: float, score: float, label: string, name: string}>, labels: array<int, string>, values: array<int, float>, polyline: string, area: string, maximum: float, last_score: float|null, change: float|null, change_label: string, label: string} */
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
            'labels' => collect($points)->pluck('label')->all(),
            'values' => collect($points)->pluck('score')->all(),
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
