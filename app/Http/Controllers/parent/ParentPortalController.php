<?php

namespace App\Http\Controllers\parent;

use App\Http\Controllers\Controller;
use App\Models\ClassAttendance;
use App\Models\ScheduleBookingRequest;
use App\Models\StudentFeedback;
use App\Models\StudentProgressReport;
use App\Models\User;
use App\Models\UserAnswer;
use App\Models\UserPackageAcces;
use App\Support\Pagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

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
        $recentAnswers = UserAnswer::query()
            ->where('user_id', $child->id)
            ->where('status', 'completed')
            ->with('tryout:tryout_id,name')
            ->latest('finished_at')
            ->limit(5)
            ->get();
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

    public function assessments(Request $request): View
    {
        [$children, $child] = $this->childrenAndSelectedChild($request);
        $answers = $child
            ? UserAnswer::query()
                ->where('user_id', $child->id)
                ->where('status', 'completed')
                ->with([
                    'tryout:tryout_id,name',
                    'tryoutDetail:tryout_detail_id,type_subtest,material_category_id',
                    'tryoutDetail.materialCategory:category_id,name',
                ])
                ->latest('finished_at')
                ->paginate(Pagination::perPage(15))
                ->withQueryString()
            : collect();
        $assessmentSummary = $child
            ? [
                'completed' => UserAnswer::query()->where('user_id', $child->id)->where('status', 'completed')->count(),
                'average_score' => (float) (UserAnswer::query()->where('user_id', $child->id)->where('status', 'completed')->avg('score') ?? 0),
                'highest_score' => (float) (UserAnswer::query()->where('user_id', $child->id)->where('status', 'completed')->max('score') ?? 0),
            ]
            : ['completed' => 0, 'average_score' => 0, 'highest_score' => 0];

        return view('parent.assessments', compact('children', 'child', 'answers', 'assessmentSummary'));
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

        return view('parent.report', compact('children', 'child', 'attendanceSummary', 'packages', 'answers', 'progress'));
    }

    private function childrenAndSelectedChild(Request $request): array
    {
        $children = $request->user()->children()
            ->where('users.status', 'aktif')
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.email', 'users.phone']);
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
            'recentFeedback' => collect(),
            'alerts' => collect(),
        ];
    }
}
