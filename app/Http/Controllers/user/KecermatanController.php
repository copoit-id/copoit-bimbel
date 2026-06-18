<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Kecermatan;
use App\Models\KecermatanAttempt;
use App\Models\KecermatanColumn;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class KecermatanController extends Controller
{
    public function index(): View
    {
        $this->ensureFeatureEnabled();

        $kecermatans = Kecermatan::with(['packages'])
            ->where('is_active', true)
            ->where('is_displayed', true)
            ->latest()
            ->paginate(12);

        $userId = Auth::id();
        foreach ($kecermatans as $kecermatan) {
            $kecermatan->has_access = $kecermatan->canUserAccess($userId);
            $kecermatan->has_pending_purchase = $userId ? $kecermatan->hasPendingPurchase($userId) : false;
            $kecermatan->is_for_sale = $kecermatan->is_for_sale && $kecermatan->price > 0;
        }

        return view('user.pages.kecermatan.index', compact('kecermatans'));
    }

    public function start(Kecermatan $kecermatan): RedirectResponse
    {
        $this->ensureFeatureEnabled();
        $this->ensureAccess($kecermatan);

        if (!$kecermatan->columns()->exists()) {
            return redirect()->route('user.kecermatan.index')
                ->with('error', 'Soal kecermatan belum tersedia.');
        }

        $column = $this->nextColumn($kecermatan);

        if (!$column) {
            $token = $this->latestCompletedToken($kecermatan);

            return redirect()->route('user.kecermatan.result', [$kecermatan, $token]);
        }

        return redirect()->route('user.kecermatan.show', [$kecermatan, $column]);
    }

    public function show(Kecermatan $kecermatan, KecermatanColumn $column): View|RedirectResponse
    {
        $this->ensureFeatureEnabled();
        $this->ensureAccess($kecermatan);
        abort_unless($column->kecermatan_id === $kecermatan->id, 404);

        $column->load('questions');
        if ($column->questions->isEmpty()) {
            return redirect()->route('user.kecermatan.index')
                ->with('error', 'Soal kecermatan belum tersedia.');
        }

        $attempt = $this->activeAttempt($kecermatan, $column);
        if ($attempt->status === 'completed') {
            return $this->redirectAfterColumn($kecermatan, $attempt->attempt_token);
        }

        $timeLeft = $this->timeLeft($attempt, $column);

        if ($timeLeft <= 0) {
            $this->finalizeAttempt($attempt, []);

            return $this->redirectAfterColumn($kecermatan, $attempt->attempt_token);
        }

        $columns = $kecermatan->columns()->withCount('questions')->get();
        $completedColumnIds = $this->completedColumnIdsForToken($kecermatan, $attempt->attempt_token);

        return view('user.pages.kecermatan.show', compact('kecermatan', 'column', 'columns', 'attempt', 'timeLeft', 'completedColumnIds'));
    }

    public function submit(Request $request, Kecermatan $kecermatan, KecermatanColumn $column): JsonResponse
    {
        $this->ensureFeatureEnabled();
        $this->ensureAccess($kecermatan);
        abort_unless($column->kecermatan_id === $kecermatan->id, 404);

        $request->validate([
            'attempt_token' => ['required', 'string'],
            'answers' => ['nullable', 'array'],
        ]);

        $attempt = KecermatanAttempt::where('kecermatan_id', $kecermatan->id)
            ->where('kecermatan_column_id', $column->id)
            ->where('user_id', Auth::id())
            ->where('attempt_token', $request->input('attempt_token'))
            ->firstOrFail();

        if ($attempt->status !== 'completed') {
            $answers = $request->input('answers', []);
            $expiresAt = ($attempt->started_at ?: now())->copy()->addSeconds($column->duration_seconds);
            if (now()->greaterThan($expiresAt->copy()->addSeconds(5))) {
                $answers = [];
            }

            $this->finalizeAttempt($attempt, $answers);
        }

        return response()->json([
            'success' => true,
            'redirect' => $this->redirectAfterColumnUrl($kecermatan, $attempt->attempt_token),
        ]);
    }

    public function result(Kecermatan $kecermatan, string $token): View
    {
        $this->ensureFeatureEnabled();
        $this->ensureAccess($kecermatan);
        $kecermatan->load('columns.questions');

        $attempts = KecermatanAttempt::with('column')
            ->where('kecermatan_id', $kecermatan->id)
            ->where('user_id', Auth::id())
            ->where('attempt_token', $token)
            ->where('status', 'completed')
            ->get()
            ->keyBy('kecermatan_column_id');

        abort_if($attempts->isEmpty(), 404);

        $rows = $kecermatan->columns->map(function (KecermatanColumn $column) use ($attempts) {
            $attempt = $attempts->get($column->id);
            $total = $column->questions->count();
            $correct = (int) ($attempt?->correct_answers ?? 0);
            $wrong = (int) ($attempt?->wrong_answers ?? max(0, $total - $correct));

            return [
                'column' => $column,
                'attempt' => $attempt,
                'total' => $total,
                'correct' => $correct,
                'wrong' => $wrong,
                'category' => $this->scoreCategory($total > 0 ? ($correct / $total) * 100 : 0),
            ];
        });

        $totalQuestions = $rows->sum('total');
        $totalCorrect = $rows->sum('correct');
        $percentage = $totalQuestions > 0 ? ($totalCorrect / $totalQuestions) * 100 : 0;
        $category = $this->scoreCategory($percentage);

        return view('user.pages.kecermatan.result', compact('kecermatan', 'token', 'rows', 'totalQuestions', 'totalCorrect', 'percentage', 'category'));
    }

    private function ensureAccess(Kecermatan $kecermatan): void
    {
        abort_unless($kecermatan->is_active && $kecermatan->canUserAccess(Auth::id()), 403);
    }

    private function ensureFeatureEnabled(): void
    {
        abort_unless(config('client.branding.kecermatan_enabled', false), 404);
    }

    private function nextColumn(Kecermatan $kecermatan): ?KecermatanColumn
    {
        $token = $this->activeToken($kecermatan);

        return $token
            ? $this->nextColumnForToken($kecermatan, $token)
            : $kecermatan->columns()->orderBy('sort_order')->first();
    }

    private function nextColumnForToken(Kecermatan $kecermatan, string $token): ?KecermatanColumn
    {
        $completedColumnIds = $this->completedColumnIdsForToken($kecermatan, $token);

        return $kecermatan->columns()
            ->whereNotIn('id', $completedColumnIds)
            ->orderBy('sort_order')
            ->first();
    }

    private function completedColumnIdsForToken(Kecermatan $kecermatan, string $token): array
    {
        return KecermatanAttempt::where('kecermatan_id', $kecermatan->id)
            ->where('user_id', Auth::id())
            ->where('attempt_token', $token)
            ->where('status', 'completed')
            ->pluck('kecermatan_column_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function activeAttempt(Kecermatan $kecermatan, KecermatanColumn $column): KecermatanAttempt
    {
        $token = $this->activeToken($kecermatan) ?: Str::uuid()->toString();

        $completed = KecermatanAttempt::where('kecermatan_id', $kecermatan->id)
            ->where('kecermatan_column_id', $column->id)
            ->where('user_id', Auth::id())
            ->where('attempt_token', $token)
            ->where('status', 'completed')
            ->latest()
            ->first();

        if ($completed) {
            return $completed;
        }

        $inProgress = KecermatanAttempt::where('kecermatan_id', $kecermatan->id)
            ->where('kecermatan_column_id', $column->id)
            ->where('user_id', Auth::id())
            ->where('attempt_token', $token)
            ->where('status', 'in_progress')
            ->latest()
            ->first();

        if ($inProgress) {
            return $inProgress;
        }

        return KecermatanAttempt::create([
            'kecermatan_id' => $kecermatan->id,
            'kecermatan_column_id' => $column->id,
            'user_id' => Auth::id(),
            'attempt_token' => $token,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);
    }

    private function activeToken(Kecermatan $kecermatan): ?string
    {
        $columnsCount = $kecermatan->columns()->count();
        $tokens = KecermatanAttempt::where('kecermatan_id', $kecermatan->id)
            ->where('user_id', Auth::id())
            ->select('attempt_token')
            ->groupBy('attempt_token')
            ->orderByRaw('MAX(created_at) DESC')
            ->pluck('attempt_token');

        foreach ($tokens as $token) {
            $completedCount = KecermatanAttempt::where('kecermatan_id', $kecermatan->id)
                ->where('user_id', Auth::id())
                ->where('attempt_token', $token)
                ->where('status', 'completed')
                ->distinct('kecermatan_column_id')
                ->count('kecermatan_column_id');

            if ($completedCount < $columnsCount) {
                return $token;
            }
        }

        return null;
    }

    private function latestCompletedToken(Kecermatan $kecermatan): string
    {
        return (string) KecermatanAttempt::where('kecermatan_id', $kecermatan->id)
            ->where('user_id', Auth::id())
            ->where('status', 'completed')
            ->latest()
            ->value('attempt_token');
    }

    private function finalizeAttempt(KecermatanAttempt $attempt, array $answers): void
    {
        $attempt->load('column.questions', 'kecermatan');

        $correct = 0;
        $wrong = 0;
        $normalizedAnswers = [];

        foreach ($attempt->column->questions as $question) {
            $answer = $answers[$question->id] ?? null;
            $normalizedAnswers[$question->id] = $answer;

            if ($answer === null || $answer === '') {
                continue;
            }

            $isCorrect = $attempt->kecermatan->type === 'kecermatan_tni'
                ? ((string) $answer === (string) $question->correct_answer)
                : ((string) $answer === (string) $question->correct_answer);

            $isCorrect ? $correct++ : $wrong++;
        }

        $total = $attempt->column->questions->count();
        $attempt->update([
            'status' => 'completed',
            'finished_at' => now(),
            'correct_answers' => $correct,
            'wrong_answers' => $wrong,
            'unanswered' => max(0, $total - $correct - $wrong),
            'score' => $correct,
            'answers' => $normalizedAnswers,
        ]);
    }

    private function timeLeft(KecermatanAttempt $attempt, KecermatanColumn $column): int
    {
        $startedAt = $attempt->started_at ?: Carbon::now();
        $expiresAt = $startedAt->copy()->addSeconds($column->duration_seconds);

        return max(0, (int) floor(now()->diffInSeconds($expiresAt, false)));
    }

    private function redirectAfterColumn(Kecermatan $kecermatan, string $token): RedirectResponse
    {
        return redirect()->to($this->redirectAfterColumnUrl($kecermatan, $token));
    }

    private function redirectAfterColumnUrl(Kecermatan $kecermatan, string $token): string
    {
        $nextColumn = $this->nextColumnForToken($kecermatan, $token);

        return $nextColumn
            ? route('user.kecermatan.show', [$kecermatan, $nextColumn])
            : route('user.kecermatan.result', [$kecermatan, $token]);
    }

    private function scoreCategory(float $percentage): string
    {
        return match (true) {
            $percentage >= 80 => 'Tinggi',
            $percentage >= 60 => 'Sedang',
            default => 'Perlu Latihan',
        };
    }
}
