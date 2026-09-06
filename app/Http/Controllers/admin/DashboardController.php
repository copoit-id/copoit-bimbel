<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Models\ClassModel;
use App\Models\Payment;
use App\Models\Tryout;
use App\Models\UserAnswer;
use App\Models\UserPackageAcces;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()?->role === 'admin_sekolah') {
            return redirect()->route('admin.school.dashboard', $request->query());
        }

        // Redirect super admin to super admin dashboard
        if (auth()->user()->isSuperAdmin()) {
            return redirect()->route('super-admin.admins.index');
        }
        
        $period = $request->get('period', '30d'); // 7d, 30d, 90d, 1y
        
        // Calculate date ranges
        $dateRanges = $this->getDateRanges($period);
        $currentStart = $dateRanges['current_start'];
        $currentEnd = $dateRanges['current_end'];
        $previousStart = $dateRanges['previous_start'];
        $previousEnd = $dateRanges['previous_end'];

        // === STAT CARDS DATA ===
        
        // Total Users
        $currentUsers = User::where('role', 'user')
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->count();
        $previousUsers = User::where('role', 'user')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();
        $userTrend = $this->calculateTrend($currentUsers, $previousUsers);

        // Total Revenue
        $currentRevenue = Payment::where('status', 'success')
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->sum('total_amount');
        $previousRevenue = Payment::where('status', 'success')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('total_amount');
        $revenueTrend = $this->calculateTrend($currentRevenue, $previousRevenue);

        // Total Tryout Attempts
        $currentTryouts = UserAnswer::whereBetween('started_at', [$currentStart, $currentEnd])
            ->count();
        $previousTryouts = UserAnswer::whereBetween('started_at', [$previousStart, $previousEnd])
            ->count();
        $tryoutTrend = $this->calculateTrend($currentTryouts, $previousTryouts);

        // Total Packages Sold
        $currentPackages = UserPackageAcces::whereBetween('created_at', [$currentStart, $currentEnd])
            ->count();
        $previousPackages = UserPackageAcces::whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();
        $packageTrend = $this->calculateTrend($currentPackages, $previousPackages);

        // === CHART DATA ===
        
        // Tryout Attempts Chart Data (Bar Chart)
        $tryoutChartData = $this->getTryoutChartData($period);
        
        // Revenue Chart Data (Line Chart)
        $revenueChartData = $this->getRevenueChartData($period);

        // === RECENT ACTIVITY ===
        
        // Recent users (8 terbaru)
        $users = User::where('role', 'user')
            ->latest()
            ->limit(8)
            ->get();

        // Recent payments/transactions (8 terbaru)
        $payments = Payment::with(['user'])
            ->where('status', 'success')
            ->latest()
            ->limit(8)
            ->get();

        // Recent tryout attempts
        $recentTryouts = UserAnswer::with(['user', 'tryout'])
            ->latest('started_at')
            ->limit(5)
            ->get();

        // === SUMMARY DATA ===
        $summary = [
            'total_users' => User::where('role', 'user')->count(),
            'total_revenue' => Payment::where('status', 'success')->sum('total_amount'),
            'total_tryouts' => Tryout::count(),
            'total_classes' => ClassModel::count(),
            'active_packages' => UserPackageAcces::where('status', 'active')->count(),
        ];

        // Legacy variables for backward compatibility
        $count_user = $summary['total_users'];
        $count_amount = $summary['total_revenue'];
        $count_tryout = $summary['total_tryouts'];
        $count_class = $summary['total_classes'];

        // === UPGRADE BANNER SETTINGS (Database > Config > Default) ===
        $upgradeBanner = [
            'enabled' => Setting::get('upgrade_banner_enabled', config('settings.upgrade_banner_enabled', true)),
            'title' => Setting::get('upgrade_banner_title', config('settings.upgrade_banner_title', 'Unlock premium features')),
            'description' => Setting::get('upgrade_banner_description', config('settings.upgrade_banner_description', 'Upgrade to Pro for unlimited analytics & real-time insights.')),
            'button_text' => Setting::get('upgrade_banner_button_text', config('settings.upgrade_banner_button_text', 'Upgrade Now')),
            'button_url' => Setting::get('upgrade_banner_button_url', config('settings.upgrade_banner_button_url', '#')),
        ];

        return view('admin.pages.dashboard', compact(
            'count_user',
            'count_amount',
            'count_tryout',
            'count_class',
            'users',
            'payments',
            'userTrend',
            'revenueTrend',
            'tryoutTrend',
            'packageTrend',
            'tryoutChartData',
            'revenueChartData',
            'recentTryouts',
            'summary',
            'upgradeBanner',
            'period'
        ));
    }

    /**
     * Calculate date ranges based on period
     */
    private function getDateRanges(string $period): array
    {
        $now = Carbon::now();
        
        switch ($period) {
            case '7d':
                return [
                    'current_start' => $now->copy()->subDays(7)->startOfDay(),
                    'current_end' => $now->copy()->endOfDay(),
                    'previous_start' => $now->copy()->subDays(14)->startOfDay(),
                    'previous_end' => $now->copy()->subDays(7)->endOfDay(),
                ];
            case '90d':
                return [
                    'current_start' => $now->copy()->subDays(90)->startOfDay(),
                    'current_end' => $now->copy()->endOfDay(),
                    'previous_start' => $now->copy()->subDays(180)->startOfDay(),
                    'previous_end' => $now->copy()->subDays(90)->endOfDay(),
                ];
            case '1y':
                return [
                    'current_start' => $now->copy()->subYear()->startOfDay(),
                    'current_end' => $now->copy()->endOfDay(),
                    'previous_start' => $now->copy()->subYears(2)->startOfDay(),
                    'previous_end' => $now->copy()->subYear()->endOfDay(),
                ];
            case '30d':
            default:
                return [
                    'current_start' => $now->copy()->subDays(30)->startOfDay(),
                    'current_end' => $now->copy()->endOfDay(),
                    'previous_start' => $now->copy()->subDays(60)->startOfDay(),
                    'previous_end' => $now->copy()->subDays(30)->endOfDay(),
                ];
        }
    }

    /**
     * Calculate trend percentage
     */
    private function calculateTrend(int|float $current, int|float $previous): array
    {
        if ($previous == 0) {
            return [
                'direction' => $current > 0 ? 'up' : 'neutral',
                'value' => $current > 0 ? '+100%' : '0%',
                'percentage' => $current > 0 ? 100 : 0,
            ];
        }

        $change = (($current - $previous) / $previous) * 100;
        $direction = $change >= 0 ? 'up' : 'down';
        
        return [
            'direction' => $direction,
            'value' => ($change >= 0 ? '+' : '') . round($change, 1) . '%',
            'percentage' => abs(round($change, 1)),
        ];
    }

    /**
     * Get tryout attempts chart data
     */
    private function getTryoutChartData(string $period): array
    {
        return $this->getChartData($period, 'user_answers', 'started_at', 'count');
    }

    /**
     * Get revenue chart data
     */
    private function getRevenueChartData(string $period): array
    {
        return $this->getChartData($period, 'payments', 'created_at', 'sum', 'status', 'success');
    }

    private function getChartData(
        string $period,
        string $table,
        string $dateColumn,
        string $aggregation,
        ?string $filterColumn = null,
        ?string $filterValue = null,
    ): array {
        $now = Carbon::now();
        $periodDays = $period === '7d' ? 7 : ($period === '90d' ? 90 : ($period === '1y' ? 365 : 30));
        $from = $now->copy()->subDays($periodDays - 1)->startOfDay();
        $to = $now->copy()->endOfDay();
        $valueExpression = $aggregation === 'sum' ? 'SUM(total_amount)' : 'COUNT(*)';

        $dailyRows = DB::table($table)
            ->whereBetween($dateColumn, [$from, $to])
            ->when($filterColumn !== null, fn ($query) => $query->where($filterColumn, $filterValue))
            ->selectRaw("DATE({$dateColumn}) as bucket, {$valueExpression} as total")
            ->groupByRaw("DATE({$dateColumn})")
            ->pluck('total', 'bucket');

        $labels = [];
        $data = [];
        $bucketCount = $period === '7d' ? 7 : ($period === '90d' ? 12 : ($period === '1y' ? 12 : 30));

        for ($index = $bucketCount - 1; $index >= 0; $index--) {
            $date = match ($period) {
                '90d' => $now->copy()->subWeeks($index)->startOfWeek(),
                '1y' => $now->copy()->subMonths($index)->startOfMonth(),
                default => $now->copy()->subDays($index)->startOfDay(),
            };
            $bucketTotal = match ($period) {
                '90d' => collect(range(0, 6))->sum(fn (int $day) => (float) $dailyRows->get($date->copy()->addDays($day)->toDateString(), 0)),
                '1y' => collect(range(0, $date->daysInMonth - 1))->sum(fn (int $day) => (float) $dailyRows->get($date->copy()->addDays($day)->toDateString(), 0)),
                default => (float) $dailyRows->get($date->toDateString(), 0),
            };
            $labels[] = match ($period) {
                '7d' => $date->format('D'),
                '90d' => 'W'.$date->format('W'),
                '1y' => $date->format('M'),
                default => $date->format('d M'),
            };
            $data[] = $aggregation === 'sum' ? round($bucketTotal, 2) : (int) $bucketTotal;
        }

        return compact('labels', 'data');
    }
}
