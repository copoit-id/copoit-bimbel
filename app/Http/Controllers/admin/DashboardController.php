<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserPackageAcces;
use App\Models\Payment;
use App\Models\Tryout;
use App\Models\ClassModel;
use App\Models\UserAnswer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
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
            ->sum('amount');
        $previousRevenue = Payment::where('status', 'success')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('amount');
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
            'total_revenue' => Payment::where('status', 'success')->sum('amount'),
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
    private function getDateRanges($period)
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
    private function calculateTrend($current, $previous)
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
    private function getTryoutChartData($period)
    {
        $now = Carbon::now();
        $labels = [];
        $data = [];

        switch ($period) {
            case '7d':
                for ($i = 6; $i >= 0; $i--) {
                    $date = $now->copy()->subDays($i);
                    $labels[] = $date->format('D');
                    $data[] = UserAnswer::whereDate('started_at', $date)->count();
                }
                break;
            
            case '90d':
                for ($i = 11; $i >= 0; $i--) {
                    $startOfWeek = $now->copy()->subWeeks($i)->startOfWeek();
                    $endOfWeek = $now->copy()->subWeeks($i)->endOfWeek();
                    $labels[] = 'W' . $startOfWeek->format('W');
                    $data[] = UserAnswer::whereBetween('started_at', [$startOfWeek, $endOfWeek])->count();
                }
                break;
            
            case '1y':
                for ($i = 11; $i >= 0; $i--) {
                    $month = $now->copy()->subMonths($i);
                    $labels[] = $month->format('M');
                    $data[] = UserAnswer::whereMonth('started_at', $month->month)
                        ->whereYear('started_at', $month->year)
                        ->count();
                }
                break;
            
            case '30d':
            default:
                for ($i = 29; $i >= 0; $i--) {
                    $date = $now->copy()->subDays($i);
                    $labels[] = $date->format('d M');
                    $data[] = UserAnswer::whereDate('started_at', $date)->count();
                }
                break;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get revenue chart data
     */
    private function getRevenueChartData($period)
    {
        $now = Carbon::now();
        $labels = [];
        $data = [];

        switch ($period) {
            case '7d':
                for ($i = 6; $i >= 0; $i--) {
                    $date = $now->copy()->subDays($i);
                    $labels[] = $date->format('D');
                    $data[] = Payment::where('status', 'success')
                        ->whereDate('created_at', $date)
                        ->sum('amount');
                }
                break;
            
            case '90d':
                for ($i = 11; $i >= 0; $i--) {
                    $startOfWeek = $now->copy()->subWeeks($i)->startOfWeek();
                    $endOfWeek = $now->copy()->subWeeks($i)->endOfWeek();
                    $labels[] = 'W' . $startOfWeek->format('W');
                    $data[] = Payment::where('status', 'success')
                        ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                        ->sum('amount');
                }
                break;
            
            case '1y':
                for ($i = 11; $i >= 0; $i--) {
                    $month = $now->copy()->subMonths($i);
                    $labels[] = $month->format('M');
                    $data[] = Payment::where('status', 'success')
                        ->whereMonth('created_at', $month->month)
                        ->whereYear('created_at', $month->year)
                        ->sum('amount');
                }
                break;
            
            case '30d':
            default:
                for ($i = 29; $i >= 0; $i--) {
                    $date = $now->copy()->subDays($i);
                    $labels[] = $date->format('d M');
                    $data[] = Payment::where('status', 'success')
                        ->whereDate('created_at', $date)
                        ->sum('amount');
                }
                break;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
}
