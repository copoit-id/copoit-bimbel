<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Support\Collection;

class TryoutRankingService
{
    /**
     * Select the participant's earliest completed attempt for leaderboard use.
     *
     * @param Collection<int, array{started_at?: mixed}> $attempts
     * @return array{started_at?: mixed}|null
     */
    public function firstAttempt(Collection $attempts): ?array
    {
        return $attempts
            ->sort(fn (array $left, array $right): int => $this->startedAtTimestamp($left['started_at'] ?? null)
                <=> $this->startedAtTimestamp($right['started_at'] ?? null))
            ->first();
    }

    /**
     * Sort ranking rows by the final displayed total, then completion time, then participant ID.
     *
     * @param Collection<int, array{ranking_score?: float|int, raw_score?: float|int, finished_at?: mixed, user?: object}> $rankings
     * @return Collection<int, array{ranking_score?: float|int, raw_score?: float|int, finished_at?: mixed, user?: object}>
     */
    public function sort(Collection $rankings): Collection
    {
        return $rankings
            ->sort(function (array $left, array $right): int {
                $scoreComparison = ((float) ($right['ranking_score'] ?? $right['raw_score'] ?? 0))
                    <=> ((float) ($left['ranking_score'] ?? $left['raw_score'] ?? 0));
                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }

                $completionComparison = $this->finishedAtTimestamp($left['finished_at'] ?? null)
                    <=> $this->finishedAtTimestamp($right['finished_at'] ?? null);
                if ($completionComparison !== 0) {
                    return $completionComparison;
                }

                return (int) ($left['user']->id ?? 0) <=> (int) ($right['user']->id ?? 0);
            })
            ->values();
    }

    private function finishedAtTimestamp(mixed $finishedAt): int
    {
        if ($finishedAt instanceof DateTimeInterface) {
            return $finishedAt->getTimestamp();
        }

        return strtotime((string) $finishedAt) ?: PHP_INT_MAX;
    }

    private function startedAtTimestamp(mixed $startedAt): int
    {
        if ($startedAt instanceof DateTimeInterface) {
            return $startedAt->getTimestamp();
        }

        return strtotime((string) $startedAt) ?: PHP_INT_MAX;
    }
}
