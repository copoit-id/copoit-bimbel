<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\TesKoran;
use App\Models\TesKoranResult;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TesKoranController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $tesKorans = TesKoran::with('packages')
            ->where('is_active', true)
            ->where('is_displayed', true)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($tesKorans as $tesKoran) {
            $tesKoran->has_access = $user ? $tesKoran->canUserAccess($user->id) : false;
            $tesKoran->access_via_package = $tesKoran->accessiblePackageForUser($user?->id);
            $tesKoran->is_for_sale = $tesKoran->is_for_sale && $tesKoran->price > 0;
            $tesKoran->has_pending_purchase = $user ? $tesKoran->hasPendingPurchase($user->id) : false;
        }

        return view('user.pages.tes-koran.index', compact('tesKorans'));
    }

    public function show(TesKoran $tesKoran)
    {
        $package = $tesKoran->accessiblePackageForUser(Auth::id());

        if (!$tesKoran->canUserAccess(Auth::id())) {
            return redirect()->route('user.tes-koran.index')
                ->with('error', 'Anda tidak memiliki akses ke tes ini');
        }

        // Generate columns data
        $columns = $tesKoran->generateColumns($tesKoran->columns_count);
        $columnsJson = json_encode($columns);

        return view('user.pages.tes-koran.show', compact('tesKoran', 'package', 'columnsJson', 'columns'));
    }

    public function start(Request $request, TesKoran $tesKoran)
    {
        if (!$tesKoran->canUserAccess(Auth::id())) {
            return response()->json(['error' => 'Akses ditolak'], 403);
        }

        $request->validate([
            'answers' => 'required|array',
        ]);

        $attemptToken = Str::uuid()->toString();
        $answers = $request->input('answers', []);
        $columnsData = json_decode($request->input('columns_data', '[]'), true);

        // Calculate results
        $result = $this->calculateResults($answers, $columnsData, $tesKoran);

        // Store result
        $tesKoranResult = TesKoranResult::create([
            'tes_koran_id' => $tesKoran->id,
            'user_id' => Auth::id(),
            'attempt_token' => $attemptToken,
            'total_correct' => $result['total_correct'],
            'total_wrong' => $result['total_wrong'],
            'total_skipped' => $result['total_skipped'],
            'column_scores' => $result['column_scores'],
            'speed_score' => $result['speed_score'],
            'accuracy_score' => $result['accuracy_score'],
            'stability_score' => $result['stability_score'],
            'stability_status' => $result['stability_status'],
            'final_result' => $result['final_result'],
            'started_at' => Carbon::now()->subMinutes($tesKoran->duration_minutes),
            'finished_at' => Carbon::now(),
            'status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'result_id' => $tesKoranResult->id,
            'redirect' => route('user.tes-koran.result', [$tesKoran, $tesKoranResult]),
        ]);
    }

    public function result(TesKoran $tesKoran, TesKoranResult $result)
    {
        if ($result->user_id !== Auth::id()) {
            abort(403);
        }

        $package = $tesKoran->accessiblePackageForUser(Auth::id());

        return view('user.pages.tes-koran.result', compact('tesKoran', 'package', 'result'));
    }

    private function calculateResults(array $answers, array $columns, TesKoran $tesKoran): array
    {
        $totalCorrect = 0;
        $totalWrong = 0;
        $totalSkipped = 0;
        $columnScores = [];
        $totalAnswers = 0;

        foreach ($columns as $colIndex => $column) {
            $colCorrect = 0;
            $colCount = count($column);

            // Calculate answers for this column (number of rows - 1)
            $maxAnswers = $colCount - 1;

            for ($i = 0; $i < $maxAnswers; $i++) {
                $userAnswer = $answers[$colIndex][$i] ?? null;

                if ($userAnswer === null || $userAnswer === '') {
                    $totalSkipped++;
                    continue;
                }

                $expected = $column[$i] + $column[$i + 1];
                $lastDigit = $expected > 9 ? (int) substr((string) $expected, -1) : $expected;

                if ((int) $userAnswer === $lastDigit) {
                    $totalCorrect++;
                    $colCorrect++;
                } else {
                    $totalWrong++;
                }

                $totalAnswers++;
            }

            $columnScores[] = $colCorrect;
        }

        // Calculate scores
        $speedScore = min(100, ($totalCorrect / max(1, $tesKoran->duration_minutes)) * 5);
        $accuracyScore = $totalAnswers > 0 ? (($totalCorrect / $totalAnswers) * 100) : 0;

        // Stability analysis
        $stabilityAnalysis = TesKoran::analyzeStability($columnScores);
        $stabilityScore = $stabilityAnalysis['score'];
        $stabilityStatus = $stabilityAnalysis['status'];

        // Final result
        $avgScore = ($speedScore + $accuracyScore + $stabilityScore) / 3;
        $finalResult = match (true) {
            $avgScore >= 70 => 'tinggi',
            $avgScore >= 40 => 'sedang',
            default => 'rendah',
        };

        return [
            'total_correct' => $totalCorrect,
            'total_wrong' => $totalWrong,
            'total_skipped' => $totalSkipped,
            'column_scores' => $columnScores,
            'speed_score' => $speedScore,
            'accuracy_score' => $accuracyScore,
            'stability_score' => $stabilityScore,
            'stability_status' => $stabilityStatus,
            'final_result' => $finalResult,
        ];
    }

    public function history()
    {
        $results = TesKoranResult::where('user_id', Auth::id())
            ->with('tesKoran.packages')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('user.pages.tes-koran.history', compact('results'));
    }
}
