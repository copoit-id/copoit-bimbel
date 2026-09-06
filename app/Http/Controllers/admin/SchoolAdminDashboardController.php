<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\StudyGroup;
use App\Models\Payment;
use App\Models\Tryout;
use App\Models\User;
use App\Models\UserAnswer;
use App\Models\UserPackageAcces;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SchoolAdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureSchoolAdmin($request);
        $studentIds = $this->studentIds($request);

        $studyGroups = $request->user()->schoolAdminStudyGroups()
            ->where('study_groups.is_active', true)
            ->withCount(['users as students_count' => fn (Builder $query) => $query->where('users.role', 'user')])
            ->orderBy('name')
            ->get(['study_groups.id', 'study_groups.name']);

        $recentAttempts = UserAnswer::query()
            ->with(['user:id,name,email', 'tryout:tryout_id,name'])
            ->whereIn('user_id', $studentIds)
            ->latest('started_at')
            ->limit(8)
            ->get();
        $recentTryouts = $recentAttempts;

        $period = $request->query('period', '30d');
        $from = now()->subDays(match ($period) { '7d' => 7, '90d' => 90, '1y' => 365, default => 30 })->startOfDay();
        $attemptCount = UserAnswer::query()->whereIn('user_id', $studentIds)->whereBetween('started_at', [$from, now()])->count();
        $activePackages = UserPackageAcces::query()->whereIn('user_id', $studentIds)->active()->count();
        $payments = Payment::query()->with(['user', 'package'])->whereIn('user_id', $studentIds)->where('status', 'success')->latest()->limit(8)->get();
        $trend = ['direction' => 'neutral', 'value' => '0%'];
        $summary = ['total_users' => $studentIds->count(), 'total_revenue' => (int) $payments->sum('total_amount'), 'total_tryouts' => $attemptCount, 'total_classes' => 0, 'active_packages' => $activePackages];
        $chartLabels = collect(range(6, 0))->map(fn ($day) => now()->subDays($day)->format('d M'))->all();
        $tryoutChartData = ['labels' => $chartLabels, 'data' => collect(range(6, 0))->map(fn ($day) => UserAnswer::query()->whereIn('user_id', $studentIds)->whereDate('started_at', now()->subDays($day))->count())->all()];
        $revenueChartData = ['labels' => $chartLabels, 'data' => collect(range(6, 0))->map(fn ($day) => Payment::query()->whereIn('user_id', $studentIds)->where('status', 'success')->whereDate('created_at', now()->subDays($day))->sum('total_amount'))->all()];
        $users = User::query()->whereIn('id', $studentIds)->latest()->limit(8)->get();
        $count_user = $summary['total_users']; $count_amount = $summary['total_revenue']; $count_tryout = $summary['total_tryouts']; $count_class = 0;
        $userTrend = $trend; $revenueTrend = $trend; $tryoutTrend = $trend; $packageTrend = $trend;
        $upgradeBanner = ['enabled' => false, 'title' => '', 'description' => '', 'button_text' => '', 'button_url' => ''];

        $schoolDashboard = true;
        return view('admin.pages.dashboard', compact('count_user', 'count_amount', 'count_tryout', 'count_class', 'users', 'payments', 'userTrend', 'revenueTrend', 'tryoutTrend', 'packageTrend', 'tryoutChartData', 'revenueChartData', 'recentTryouts', 'summary', 'upgradeBanner', 'period', 'schoolDashboard'));
    }

    public function students(Request $request): View
    {
        $this->ensureSchoolAdmin($request);
        $search = trim((string) $request->query('search'));
        $students = User::query()
            ->whereIn('id', $this->studentIds($request))
            ->with(['studyGroups:id,name', 'userPackageAccess' => fn ($query) => $query->active()->with('package:package_id,name')])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $nested) => $nested
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pages.school.students', compact('students', 'search'));
    }

    public function showStudent(Request $request, User $user, UserController $userController): View
    {
        $this->ensureSchoolAdmin($request);
        abort_unless($this->studentIds($request)->contains($user->id), 404);

        return $userController->show($user, true);
    }

    public function leaderboard(Request $request): View
    {
        $this->ensureSchoolAdmin($request);
        $studentIds = $this->studentIds($request);
        $tryouts = Tryout::query()
            ->whereHas('userAnswers', fn (Builder $query) => $query->whereIn('user_id', $studentIds)->where('status', 'completed'))
            ->orderBy('name')
            ->get(['tryout_id', 'name']);
        $tryoutId = $request->integer('tryout_id') ?: $tryouts->first()?->tryout_id;
        $ranking = new LengthAwarePaginator([], 0, 20);
        $selectedTryout = null;

        if ($tryoutId && $tryouts->contains('tryout_id', $tryoutId)) {
            $selectedTryout = $tryouts->firstWhere('tryout_id', $tryoutId);
            $ranking = UserAnswer::query()
                ->with('user:id,name,email')
                ->whereIn('user_id', $studentIds)
                ->where('tryout_id', $tryoutId)
                ->where('status', 'completed')
                ->orderByDesc('score')
                ->orderBy('finished_at')
                ->paginate(20)
                ->withQueryString();
        }

        return view('admin.pages.school.leaderboard', compact('tryouts', 'selectedTryout', 'ranking'));
    }

    private function ensureSchoolAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin_sekolah', 403);
    }

    /** @return \Illuminate\Support\Collection<int, int> */
    /** @return Collection<int, int> */
    private function studentIds(Request $request): Collection
    {
        $studyGroupIds = $request->user()->schoolAdminStudyGroups()->pluck('study_groups.id');

        return User::query()
            ->where('role', 'user')
            ->whereHas('studyGroups', fn (Builder $query) => $query->whereIn('study_groups.id', $studyGroupIds))
            ->pluck('id');
    }
}
