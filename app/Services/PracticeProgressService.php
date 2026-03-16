<?php

namespace App\Services;

use App\Models\PracticeSession;
use App\Models\QuestionBankQuestion;
use App\Models\Tryout;
use App\Models\UserTryoutUnlock;
use Illuminate\Support\Collection;

class PracticeProgressService
{
    public function getOrCreateSession(int $userId): PracticeSession
    {
        $session = PracticeSession::firstOrCreate(
            ['user_id' => $userId, 'question_bank_id' => null],
            [
                'total_questions' => $this->countQuestions(),
                'answered_questions' => 0,
                'flagged_questions' => [],
                'status' => 'in_progress',
                'study_started_at' => null,
                'study_duration_seconds' => 0,
            ]
        );

        return $this->refreshTotals($session);
    }

    /**
     * Memulai atau melanjutkan sesi belajar (timer mulai dari 0 jika baru)
     */
    public function startStudySession(PracticeSession $session): PracticeSession
    {
        $session->study_started_at = now();
        $session->save();

        return $session->refresh();
    }

    /**
     * Mengakhiri sesi belajar dan menambahkan durasi ke total
     */
    public function endStudySession(PracticeSession $session, ?int $additionalSeconds = null): PracticeSession
    {
        if ($session->study_started_at) {
            $elapsed = $additionalSeconds ?? max(0, now()->diffInSeconds($session->study_started_at));
            $session->study_duration_seconds += (int) $elapsed;
            $session->study_started_at = null;
            $session->save();
        }

        return $session->refresh();
    }

    /**
     * Get formatted study duration
     */
    public function getStudyDurationFormatted(PracticeSession $session): string
    {
        $totalSeconds = $session->study_duration_seconds;

        if ($session->study_started_at) {
            $totalSeconds += max(0, now()->diffInSeconds($session->study_started_at));
        }

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function refreshTotals(PracticeSession $session): PracticeSession
    {
        $totalQuestions = $this->countQuestions();
        $dirty = false;

        if ($session->total_questions !== $totalQuestions) {
            $session->total_questions = $totalQuestions;
            $dirty = true;
        }

        if ($session->answered_questions > $session->total_questions) {
            $session->answered_questions = $session->total_questions;
            $dirty = true;
        }

        if ($dirty) {
            $session->save();
        }

        return $session->refresh();
    }

    public function incrementAnsweredCount(PracticeSession $session, bool $isNewAnswer): PracticeSession
    {
        if ($isNewAnswer && $session->answered_questions < $session->total_questions) {
            $session->answered_questions++;
        }

        if ($session->total_questions > 0 && $session->answered_questions >= $session->total_questions) {
            $session->status = 'completed';
        }

        $session->last_answered_at = now();
        $session->save();

        $this->syncUnlocks($session);

        return $session->refresh();
    }

    public function syncUnlocks(PracticeSession $session): void
    {
        $tryouts = $this->getLockableTryouts();
        if ($tryouts->isEmpty()) {
            return;
        }

        $thresholds = $this->buildThresholdMap($tryouts, $session->total_questions);
        if (empty($thresholds)) {
            return;
        }

        $existingUnlocks = UserTryoutUnlock::where('user_id', $session->user_id)
            ->pluck('tryout_id')
            ->all();

        $eligibleTryouts = $tryouts->filter(function ($tryout) use ($thresholds, $session) {
            $needed = $thresholds[$tryout->tryout_id] ?? PHP_INT_MAX;
            if ($needed === PHP_INT_MAX) {
                return false;
            }

            return $session->answered_questions >= $needed;
        });

        $tryoutsToUnlock = $eligibleTryouts->pluck('tryout_id')
            ->diff($existingUnlocks);

        $eligibleIds = $eligibleTryouts->pluck('tryout_id')->all();
        $unlockIdsToRevoke = collect($existingUnlocks)->diff($eligibleIds)->all();

        if (!empty($unlockIdsToRevoke)) {
            UserTryoutUnlock::where('user_id', $session->user_id)
                ->whereIn('tryout_id', $unlockIdsToRevoke)
                ->delete();
        }

        foreach ($tryoutsToUnlock as $tryoutId) {
            UserTryoutUnlock::create([
                'user_id' => $session->user_id,
                'tryout_id' => $tryoutId,
                'progress_count' => $session->answered_questions,
                'unlocked_at' => now(),
            ]);
        }
    }

    public function tryoutIsUnlocked(int $userId, int $tryoutId): bool
    {
        $tryout = Tryout::query()
            ->select(['tryout_id', 'is_premium'])
            ->find($tryoutId);

        if (! $tryout) {
            return false;
        }

        if (! $tryout->is_premium) {
            return true;
        }

        $stats = $this->getStatsForUser($userId);

        return in_array($tryoutId, $stats['unlocked_tryout_ids'] ?? [], true);
    }

    public function getStatsForUser(int $userId): array
    {
        $session = PracticeSession::where('user_id', $userId)
            ->whereNull('question_bank_id')
            ->first();

        if (!$session) {
            $session = $this->getOrCreateSession($userId);
        } else {
            $session = $this->refreshTotals($session);
            $this->syncUnlocks($session);
        }

        $tryouts = $this->getLockableTryouts();
        $tryoutCount = $tryouts->count();
        $thresholds = $this->buildThresholdMap($tryouts, $session->total_questions);

        $existingUnlockIds = UserTryoutUnlock::where('user_id', $userId)
            ->pluck('tryout_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $unlockedCount = count($existingUnlockIds);
        $progressPercent = $session->total_questions > 0
            ? round(($session->answered_questions / $session->total_questions) * 100)
            : 0;

        $nextUnlockRemaining = 0;
        if (!empty($thresholds) && $tryoutCount > 0) {
            $nextThreshold = collect($thresholds)
                ->filter(fn ($threshold) => $threshold > $session->answered_questions && $threshold < PHP_INT_MAX)
                ->min();

            if ($nextThreshold) {
                $nextUnlockRemaining = max(0, $nextThreshold - $session->answered_questions);
            }
        }

        return [
            'session' => $session,
            'total_questions' => $session->total_questions,
            'answered_count' => $session->answered_questions,
            'progress_percent' => $progressPercent,
            'tryouts' => $tryouts,
            'tryout_count' => $tryoutCount,
            'threshold_per_tryout' => $this->calculateThreshold($session->total_questions, $tryoutCount),
            'unlock_thresholds' => $thresholds,
            'unlocked_tryout_ids' => $existingUnlockIds,
            'unlocked_count' => $unlockedCount,
            'next_unlock_remaining' => $nextUnlockRemaining,
        ];
    }

    private function calculateThreshold(int $totalQuestions, int $itemCount): int
    {
        if ($itemCount <= 0) {
            return 0;
        }

        if ($totalQuestions <= 0) {
            return 0;
        }

        return (int) max(1, ceil($totalQuestions / $itemCount));
    }

    private function countQuestions(): int
    {
        return QuestionBankQuestion::count();
    }

    private function getLockableTryouts(): Collection
    {
        return Tryout::where('is_active', true)
            ->where('is_premium', true)
            ->orderBy('ordering')
            ->orderBy('tryout_id')
            ->get();
    }

    private function buildThresholdMap(Collection $tryouts, int $totalQuestions): array
    {
        if ($tryouts->isEmpty()) {
            return [];
        }

        $thresholds = [];

        if ($totalQuestions <= 0) {
            foreach ($tryouts as $tryout) {
                $thresholds[$tryout->tryout_id] = PHP_INT_MAX;
            }
            return $thresholds;
        }

        $questionsPerTryout = $totalQuestions / $tryouts->count();

        foreach ($tryouts->values() as $index => $tryout) {
            $position = $index + 1;
            $thresholds[$tryout->tryout_id] = (int) ceil($questionsPerTryout * $position);
        }

        return $thresholds;
    }
}
