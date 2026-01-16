<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\UserPackageAcces;
use App\Models\UserAnswer;
use App\Models\Package;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

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
                return $answer->tryout_id . '|' . $attemptKey;
            })
            ->map(function ($answers) {
                $latest = $answers->sortByDesc('created_at')->first();
                $allCompleted = $answers->every(fn (UserAnswer $item) => $item->status === 'completed');
                $overallPassed = $allCompleted && $answers->every(fn (UserAnswer $item) => (bool) $item->is_passed);

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
                ->avg('score') ?? 0
        ];

        // Get packages expiring soon (within 7 days)
        $expiringSoon = UserPackageAcces::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('end_date', '>', Carbon::now())
            ->where('end_date', '<=', Carbon::now()->addDays(7))
            ->with('package')
            ->get();

        return view('user.pages.dashboard.index', compact(
            'activePackages',
            'recentAttempts',
            'stats',
            'expiringSoon'
        ));
    }
}
