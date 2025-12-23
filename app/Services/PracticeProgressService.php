<?php

namespace App\Services;

use App\Models\Package;
use App\Models\PracticeSession;
use App\Models\QuestionBankQuestion;
use App\Models\UserPackageUnlock;
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
                'status' => 'in_progress',
            ]
        );

        return $this->refreshTotals($session);
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
        $packages = $this->getLockablePackages();
        if ($packages->isEmpty()) {
            return;
        }

        $thresholds = $this->buildThresholdMap($packages, $session->total_questions);
        if (empty($thresholds)) {
            return;
        }

        $existingUnlocks = UserPackageUnlock::where('user_id', $session->user_id)
            ->pluck('package_id')
            ->all();

        $eligiblePackages = $packages->filter(function ($package) use ($thresholds, $session) {
            $needed = $thresholds[$package->package_id] ?? PHP_INT_MAX;
            if ($needed === PHP_INT_MAX) {
                return false;
            }

            return $session->answered_questions >= $needed;
        });

        $packagesToUnlock = $eligiblePackages->pluck('package_id')
            ->diff($existingUnlocks);

        foreach ($packagesToUnlock as $packageId) {
            UserPackageUnlock::create([
                'user_id' => $session->user_id,
                'package_id' => $packageId,
                'progress_count' => $session->answered_questions,
                'unlocked_at' => now(),
            ]);
        }
    }

    public function packageIsUnlocked(int $userId, int $packageId): bool
    {
        $stats = $this->getStatsForUser($userId);

        return in_array($packageId, $stats['unlocked_package_ids'] ?? [], true);
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

        $packages = $this->getLockablePackages();
        $packageCount = $packages->count();
        $thresholds = $this->buildThresholdMap($packages, $session->total_questions);

        $existingUnlockIds = UserPackageUnlock::where('user_id', $userId)
            ->pluck('package_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $unlockedCount = count($existingUnlockIds);
        $progressPercent = $session->total_questions > 0
            ? round(($session->answered_questions / $session->total_questions) * 100)
            : 0;

        $nextUnlockRemaining = 0;
        if (!empty($thresholds)) {
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
            'packages' => $packages,
            'package_count' => $packageCount,
            'threshold_per_package' => $this->calculateThreshold($session->total_questions, $packageCount),
            'unlock_thresholds' => $thresholds,
            'unlocked_package_ids' => $existingUnlockIds,
            'unlocked_count' => $unlockedCount,
            'next_unlock_remaining' => $nextUnlockRemaining,
        ];
    }

    private function calculateThreshold(int $totalQuestions, int $packageCount): int
    {
        if ($packageCount <= 0) {
            return 0;
        }

        if ($totalQuestions <= 0) {
            return 0;
        }

        return (int) max(1, ceil($totalQuestions / $packageCount));
    }

    private function countQuestions(): int
    {
        return QuestionBankQuestion::count();
    }

    private function getLockablePackages(): Collection
    {
        return Package::where('status', 'active')
            ->orderBy('package_id')
            ->get();
    }

    private function buildThresholdMap(Collection $packages, int $totalQuestions): array
    {
        if ($packages->isEmpty()) {
            return [];
        }

        $thresholds = [];

        if ($totalQuestions <= 0) {
            foreach ($packages as $package) {
                $thresholds[$package->package_id] = PHP_INT_MAX;
            }
            return $thresholds;
        }

        $questionsPerPackage = $totalQuestions / $packages->count();

        foreach ($packages->values() as $index => $package) {
            $position = $index + 1;
            $thresholds[$package->package_id] = (int) ceil($questionsPerPackage * $position);
        }

        return $thresholds;
    }
}
