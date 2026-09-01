<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\IndividualPurchase;
use App\Models\TesKoran;
use App\Models\TesKoranResult;
use App\Models\UserPackageAcces;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TesKoranController extends Controller
{
    public function index(Request $request)
    {
        $this->abortIfFeatureDisabled();

        $user = Auth::user();
        $search = trim((string) $request->get('search', ''));
        $sort = $request->get('sort', 'latest');

        $tesKoransQuery = TesKoran::with('packages')
            ->where('is_active', true)
            ->where('is_displayed', true);

        if ($search !== '') {
            $tesKoransQuery->where('name', 'like', "%{$search}%");
        }

        match ($sort) {
            'oldest' => $tesKoransQuery->orderBy('created_at', 'asc'),
            'name_asc' => $tesKoransQuery->orderBy('name', 'asc'),
            'name_desc' => $tesKoransQuery->orderBy('name', 'desc'),
            default => $tesKoransQuery->orderBy('created_at', 'desc'),
        };

        $tesKorans = $tesKoransQuery->paginate(\App\Support\Pagination::perPage(12))->withQueryString();

        $activePackageIds = $user
            ? UserPackageAcces::query()
                ->where('user_id', $user->id)
                ->active()
                ->pluck('package_id')
                ->map(static fn ($id): int => (int) $id)
                ->all()
            : [];
        $purchasedTesKoranIds = $user
            ? IndividualPurchase::query()
                ->where('user_id', $user->id)
                ->where('purchasable_type', TesKoran::class)
                ->where('status', IndividualPurchase::STATUS_APPROVED)
                ->where(function ($query): void {
                    $query->whereNull('access_expires_at')
                        ->orWhere('access_expires_at', '>', now());
                })
                ->pluck('purchasable_id')
                ->map(static fn ($id): int => (int) $id)
                ->all()
            : [];
        $pendingTesKoranIds = $user
            ? IndividualPurchase::query()
                ->where('user_id', $user->id)
                ->where('purchasable_type', TesKoran::class)
                ->where('status', IndividualPurchase::STATUS_PENDING)
                ->pluck('purchasable_id')
                ->map(static fn ($id): int => (int) $id)
                ->all()
            : [];

        foreach ($tesKorans as $tesKoran) {
            $package = $tesKoran->packages->first(
                static fn ($package): bool => in_array((int) $package->package_id, $activePackageIds, true),
            );
            $tesKoran->has_access = $package !== null
                || in_array((int) $tesKoran->id, $purchasedTesKoranIds, true);
            $tesKoran->access_via_package = $package ?: $tesKoran->packages->first();
            $tesKoran->has_pending_purchase = in_array((int) $tesKoran->id, $pendingTesKoranIds, true);
        }

        return view('user.pages.tes-koran.index', compact('tesKorans', 'search', 'sort'));
    }

    public function show(TesKoran $tesKoran)
    {
        $this->abortIfFeatureDisabled();
        $tesKoran->load('sheets');

        $package = $tesKoran->accessiblePackageForUser(Auth::id());

        if (!$tesKoran->canUserAccess(Auth::id())) {
            return redirect()->route('user.tes-koran.index')
                ->with('error', 'Anda tidak memiliki akses ke tes ini');
        }

        $sessionKey = $this->attemptSessionKey($tesKoran);
        $attempt = session($sessionKey);
        $expiresAt = isset($attempt['expires_at']) ? Carbon::parse($attempt['expires_at']) : null;

        if (!$attempt || !$expiresAt || $expiresAt->isPast()) {
            $sheets = $tesKoran->sheetConfigs()
                ->map(function (array $sheet) use ($tesKoran) {
                    $sheet['columns'] = $tesKoran->generateColumnsForSheet($sheet);
                    return $sheet;
                })
                ->values()
                ->all();
            $totalDurationSeconds = $this->totalDurationSeconds($sheets, $tesKoran);

            $attempt = [
                'sheets' => $sheets,
                'columns' => collect($sheets)->flatMap(fn ($sheet) => $sheet['columns'])->values()->all(),
                'started_at' => now()->toIso8601String(),
                'expires_at' => now()->addSeconds($totalDurationSeconds)->toIso8601String(),
                'duration_seconds' => $totalDurationSeconds,
            ];

            session([$sessionKey => $attempt]);
            $expiresAt = Carbon::parse($attempt['expires_at']);
        }

        $sheets = $attempt['sheets'] ?? [[
            'name' => 'Lembar 1',
            'number_type' => $tesKoran->number_type,
            'operation_type' => $tesKoran->operation_type,
            'column_duration_seconds' => $tesKoran->column_duration_seconds,
            'columns_count' => $tesKoran->columns_count,
            'rows_count' => $tesKoran->rows_count,
            'columns' => $attempt['columns'],
        ]];
        $columns = collect($sheets)->flatMap(fn ($sheet) => $sheet['columns'])->values()->all();
        $columnsJson = json_encode($columns);
        $totalDurationSeconds = $this->totalDurationSeconds($sheets, $tesKoran);
        if ($tesKoran->logic_test_type === 'stan' && isset($attempt['started_at'])) {
            $startedAt = Carbon::parse($attempt['started_at']);
            $normalizedExpiresAt = $startedAt->copy()->addSeconds($totalDurationSeconds);

            if (!$expiresAt || !$expiresAt->equalTo($normalizedExpiresAt)) {
                $attempt['expires_at'] = $normalizedExpiresAt->toIso8601String();
                $attempt['duration_seconds'] = $totalDurationSeconds;
                session([$sessionKey => $attempt]);
                $expiresAt = $normalizedExpiresAt;
            }
        }

        $timeLeft = max(0, (int) floor(now()->diffInSeconds($expiresAt, false)));
        $columnDurations = collect($sheets)
            ->flatMap(fn ($sheet) => array_fill(0, count($sheet['columns'] ?? []), (int) ($sheet['column_duration_seconds'] ?? 60)))
            ->values()
            ->all();
        $columnLabels = collect($sheets)
            ->flatMap(function ($sheet) {
                return collect($sheet['columns'] ?? [])
                    ->keys()
                    ->map(fn ($index) => ($sheet['name'] ?? 'Lembar') . ' - Kolom ' . ($index + 1));
            })
            ->values()
            ->all();
        $sheetRanges = [];
        $startColumn = 0;
        foreach ($sheets as $sheetIndex => $sheet) {
            $sheetColumnCount = count($sheet['columns'] ?? []);
            $sheetRanges[] = [
                'index' => $sheetIndex,
                'name' => $sheet['name'] ?? 'Lembar ' . ($sheetIndex + 1),
                'start' => $startColumn,
                'end' => max($startColumn, $startColumn + $sheetColumnCount - 1),
            ];
            $startColumn += $sheetColumnCount;
        }

        return view('user.pages.tes-koran.show', compact('tesKoran', 'package', 'columnsJson', 'columns', 'sheets', 'timeLeft', 'totalDurationSeconds', 'columnDurations', 'columnLabels', 'sheetRanges'));
    }

    public function start(Request $request, TesKoran $tesKoran)
    {
        $this->abortIfFeatureDisabled();

        if (!$tesKoran->canUserAccess(Auth::id())) {
            return response()->json(['error' => 'Akses ditolak'], 403);
        }

        $request->validate([
            'answers' => 'required|array',
        ]);

        $sessionKey = $this->attemptSessionKey($tesKoran);
        $attempt = session($sessionKey);

        if (!$attempt || empty($attempt['columns'])) {
            return response()->json(['error' => 'Sesi tes tidak ditemukan. Silakan mulai ulang tes.'], 422);
        }

        $attemptToken = Str::uuid()->toString();
        $answers = $request->input('answers', []);
        $sheetsData = $attempt['sheets'] ?? null;
        $columnsData = $attempt['columns'];
        $startedAt = Carbon::parse($attempt['started_at'] ?? now());

        // Calculate results
        $result = $sheetsData
            ? $this->calculateSheetResults($answers, $sheetsData, $tesKoran)
            : $this->calculateResults($answers, $columnsData, $tesKoran);

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
            'started_at' => $startedAt,
            'finished_at' => Carbon::now(),
            'status' => 'completed',
        ]);

        session()->forget($sessionKey);

        return response()->json([
            'success' => true,
            'result_id' => $tesKoranResult->id,
            'redirect' => route('user.tes-koran.result', [$tesKoran, $tesKoranResult]),
        ]);
    }

    public function result(TesKoran $tesKoran, TesKoranResult $result)
    {
        $this->abortIfFeatureDisabled();

        if ($result->user_id !== Auth::id()) {
            abort(403);
        }

        $tesKoran->loadMissing('sheets');

        $package = $tesKoran->accessiblePackageForUser(Auth::id());

        return view('user.pages.tes-koran.result', compact('tesKoran', 'package', 'result'));
    }

    private function attemptSessionKey(TesKoran $tesKoran): string
    {
        return 'tes_koran_attempt.' . Auth::id() . '.' . $tesKoran->id;
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

                $expected = $tesKoran->calculateExpectedAnswer($column[$i], $column[$i + 1]);
                $normalizedAnswer = $tesKoran->normalizeAnswer($userAnswer);

                if ($normalizedAnswer === $expected) {
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

    private function calculateSheetResults(array $answers, array $sheets, TesKoran $tesKoran): array
    {
        $totalCorrect = 0;
        $totalWrong = 0;
        $totalSkipped = 0;
        $columnScores = [];
        $totalAnswers = 0;
        $globalColumnIndex = 0;

        foreach ($sheets as $sheet) {
            foreach (($sheet['columns'] ?? []) as $column) {
                $colCorrect = 0;
                $maxAnswers = count($column) - 1;

                for ($i = 0; $i < $maxAnswers; $i++) {
                    $userAnswer = $answers[$globalColumnIndex][$i] ?? null;

                    if ($userAnswer === null || $userAnswer === '') {
                        $totalSkipped++;
                        continue;
                    }

                    $expected = $tesKoran->calculateExpectedAnswerFor(
                        $column[$i],
                        $column[$i + 1],
                        $sheet['operation_type'] ?? 'addition'
                    );
                    $normalizedAnswer = $tesKoran->normalizeAnswer($userAnswer);

                    if ($normalizedAnswer === $expected) {
                        $totalCorrect++;
                        $colCorrect++;
                    } else {
                        $totalWrong++;
                    }

                    $totalAnswers++;
                }

                $columnScores[] = $colCorrect;
                $globalColumnIndex++;
            }
        }

        $durationMinutes = max(1, (int) ceil($this->totalDurationSeconds($sheets, $tesKoran) / 60));
        $speedScore = min(100, ($totalCorrect / $durationMinutes) * 5);
        $accuracyScore = $totalAnswers > 0 ? (($totalCorrect / $totalAnswers) * 100) : 0;
        $stabilityAnalysis = TesKoran::analyzeStability($columnScores);
        $stabilityScore = $stabilityAnalysis['score'];
        $stabilityStatus = $stabilityAnalysis['status'];
        $avgScore = ($speedScore + $accuracyScore + $stabilityScore) / 3;

        return [
            'total_correct' => $totalCorrect,
            'total_wrong' => $totalWrong,
            'total_skipped' => $totalSkipped,
            'column_scores' => $columnScores,
            'speed_score' => $speedScore,
            'accuracy_score' => $accuracyScore,
            'stability_score' => $stabilityScore,
            'stability_status' => $stabilityStatus,
            'final_result' => $avgScore >= 70 ? 'tinggi' : ($avgScore >= 40 ? 'sedang' : 'rendah'),
        ];
    }

    private function totalDurationSeconds(array $sheets, TesKoran $tesKoran): int
    {
        if ($tesKoran->logic_test_type === 'stan') {
            return (int) ($tesKoran->column_duration_seconds ?? 60);
        }

        return (int) collect($sheets)->sum(
            fn ($sheet) => (int) ($sheet['column_duration_seconds'] ?? 60) * count($sheet['columns'] ?? [])
        );
    }

    public function history()
    {
        $this->abortIfFeatureDisabled();

        $results = TesKoranResult::where('user_id', Auth::id())
            ->with('tesKoran.packages')
            ->orderBy('created_at', 'desc')
            ->paginate(\App\Support\Pagination::perPage(20));

        return view('user.pages.tes-koran.history', compact('results'));
    }

    private function abortIfFeatureDisabled(): void
    {
        abort_unless(config('client.branding.tes_koran_enabled', true), 404);
    }
}
