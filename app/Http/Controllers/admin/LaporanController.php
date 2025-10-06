<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAnswer;
use App\Models\UserPackageAcces;
use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function index()
    {
        // Get all users with their statistics - use 'role' instead of 'is_admin'
        $users = User::where('role', '!=', 'admin')
            ->withCount([
                'userAnswers as completed_tryouts' => function ($query) {
                    $query->where('status', 'completed');
                }
            ])
            ->paginate(15);

        // Calculate statistics for each user
        $users->getCollection()->transform(function ($user) {
            // Calculate average score from completed tryouts
            $avgScore = $user->userAnswers()
                ->where('status', 'completed')
                ->avg('score') ?? 0;

            // Determine if user is active (has recent activity)
            $lastActivity = $user->userAnswers()->max('created_at') ?? $user->updated_at ?? $user->created_at;
            $isActive = Carbon::parse($lastActivity)->gt(Carbon::now()->subDays(30));

            // Add calculated fields
            $user->avg_score = round($avgScore, 1);
            $user->is_active_user = $isActive;
            $user->last_activity = Carbon::parse($lastActivity);
            $user->total_certificates = 0; // Set to 0 since certificates table doesn't exist yet

            return $user;
        });

        // Get summary statistics - use 'role' instead of 'is_admin'
        $totalUsers = User::where('role', '!=', 'admin')->count();
        $activeUsers = User::where('role', '!=', 'admin')
            ->whereHas('userAnswers', function ($query) {
                $query->where('created_at', '>', Carbon::now()->subDays(30));
            })
            ->count();

        $totalCompletedTryouts = UserAnswer::where('status', 'completed')->count();
        $totalCertificates = 0; // Will be updated when certificate system is implemented

        return view('admin.pages.laporan.index', compact(
            'users',
            'totalUsers',
            'activeUsers',
            'totalCompletedTryouts',
            'totalCertificates'
        ));
    }

    public function show($id)
    {
        $user = User::with([
            'userAnswers' => function ($query) {
                $query->where('status', 'completed')
                    ->with(['tryout', 'userAnswerDetails'])
                    ->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        // Calculate user statistics
        $completedTryouts = $user->userAnswers->where('status', 'completed');
        $totalTryouts = $completedTryouts->count();
        $avgScore = $completedTryouts->avg('score') ?? 0;

        // Get recent tryout history (last 5)
        $recentTryouts = $completedTryouts->take(5)->map(function ($answer) {
            return [
                'name' => $answer->tryout->name ?? 'Unknown Tryout',
                'score' => round($answer->score ?? 0, 1),
                'date' => Carbon::parse($answer->finished_at ?? $answer->created_at),
                'is_passed' => $answer->is_passed ?? false
            ];
        });

        // Calculate total study time (estimated based on tryout durations)
        $totalStudyMinutes = $completedTryouts->sum(function ($answer) {
            if ($answer->started_at && $answer->finished_at) {
                return Carbon::parse($answer->started_at)->diffInMinutes(Carbon::parse($answer->finished_at));
            }
            // If no timing data, estimate based on tryout details
            return $answer->tryoutDetail->duration ?? 60; // Default 60 minutes
        });
        $totalStudyHours = round($totalStudyMinutes / 60, 1);

        // Get certificates (mock data for now since table doesn't exist)
        $certificates = collect();

        // Calculate activity timeline
        $activities = collect();

        // Add tryout activities
        foreach ($completedTryouts->take(4) as $answer) {
            $activities->push([
                'type' => 'tryout',
                'text' => 'Menyelesaikan tryout ' . ($answer->tryout->name ?? 'Unknown') . ' dengan skor ' . round($answer->score ?? 0, 1),
                'icon' => 'ri-file-list-line',
                'color' => 'blue',
                'date' => Carbon::parse($answer->finished_at ?? $answer->created_at)
            ]);
        }

        // Add login activities (mock data)
        $activities->push([
            'type' => 'login',
            'text' => 'Login ke sistem',
            'icon' => 'ri-login-box-line',
            'color' => 'green',
            'date' => Carbon::now()->subHours(2)
        ]);

        // Sort activities by date
        $activities = $activities->sortByDesc('date')->take(8);

        $statistics = [
            'total_tryouts' => $totalTryouts,
            'avg_score' => round($avgScore, 1),
            'total_certificates' => $certificates->count(),
            'study_hours' => $totalStudyHours
        ];

        return view('admin.pages.laporan.show', compact(
            'user',
            'statistics',
            'recentTryouts',
            'certificates',
            'activities'
        ));
    }
}
