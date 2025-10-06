<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use App\Models\UserAnswer;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index()
    {
        // Get all tryouts with their packages and participant counts - GROUP BY tryout
        $tryouts = Tryout::where('is_active', true)
            ->with(['tryoutDetails', 'packages' => function ($query) {
                $query->where('status', 'active');
            }])
            ->get()
            ->map(function ($tryout) {
                $tryoutDetail = $tryout->tryoutDetails->first();

                // Count total participants across all packages for this tryout
                $participantCount = UserAnswer::where('tryout_id', $tryout->tryout_id)
                    ->where('status', 'completed')
                    ->distinct('user_id')
                    ->count();

                // Get all packages that have this tryout
                $packages = $tryout->packages->where('status', 'active');

                if ($packages->isEmpty()) {
                    return null; // Skip if no active packages
                }

                // Combine package names for display
                $packageNames = $packages->pluck('name')->toArray();
                $combinedPackageName = count($packageNames) > 1
                    ? implode(' + ', array_slice($packageNames, 0, 2)) . (count($packageNames) > 2 ? ' + ' . (count($packageNames) - 2) . ' lainnya' : '')
                    : $packageNames[0] ?? 'Unknown Package';

                return [
                    'tryout_id' => $tryout->tryout_id,
                    'package_id' => $packages->first()->package_id, // Use first package for routing
                    'name' => $tryout->name,
                    'description' => $tryout->description,
                    'total_questions' => $tryoutDetail ? $tryoutDetail->questions()->count() : 0,
                    'duration' => $tryoutDetail ? $tryoutDetail->duration : 0,
                    'difficulty' => $this->getDifficultyLevel($tryoutDetail ? $tryoutDetail->duration : 0),
                    'participant_count' => $participantCount,
                    'package_name' => $combinedPackageName,
                    'package_count' => count($packageNames),
                    'all_packages' => $packages->map(function ($pkg) {
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
        $tryout = Tryout::findOrFail($tryout_id);

        // Get tryout details
        $tryoutDetail = $tryout->tryoutDetails()->first();
        if (!$tryoutDetail) {
            return redirect()->route('admin.leaderboard.index')
                ->with('error', 'Tryout belum memiliki detail soal');
        }

        // Get leaderboard data - real participants
        $rankings = UserAnswer::where('tryout_id', $tryout_id)
            ->where('status', 'completed')
            ->whereNotNull('score')
            ->with(['user'])
            ->orderBy('score', 'desc')
            ->orderBy('finished_at', 'asc') // Earlier finish time for same score
            ->paginate(15);

        // Calculate statistics
        $totalParticipants = UserAnswer::where('tryout_id', $tryout_id)
            ->where('status', 'completed')
            ->distinct('user_id')
            ->count();

        $averageScore = UserAnswer::where('tryout_id', $tryout_id)
            ->where('status', 'completed')
            ->whereNotNull('score')
            ->avg('score');

        $highestScore = UserAnswer::where('tryout_id', $tryout_id)
            ->where('status', 'completed')
            ->whereNotNull('score')
            ->max('score');

        $passedCount = UserAnswer::where('tryout_id', $tryout_id)
            ->where('status', 'completed')
            ->where('is_passed', true)
            ->count();

        $passRate = $totalParticipants > 0 ? ($passedCount / $totalParticipants) * 100 : 0;

        $statistics = [
            'total_participants' => $totalParticipants,
            'average_score' => round($averageScore ?? 0, 1),
            'highest_score' => $highestScore ?? 0,
            'pass_rate' => round($passRate, 1),
            'total_questions' => $tryoutDetail->questions()->count(),
            'duration' => $tryoutDetail->duration
        ];

        return view('admin.pages.leaderboard.show', compact(
            'package',
            'tryout',
            'tryoutDetail',
            'rankings',
            'statistics'
        ));
    }

    /**
     * Determine difficulty level based on duration and question count
     */
    private function getDifficultyLevel($duration)
    {
        if ($duration <= 30) {
            return 'Mudah';
        } elseif ($duration <= 60) {
            return 'Sedang';
        } else {
            return 'Sulit';
        }
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
}
