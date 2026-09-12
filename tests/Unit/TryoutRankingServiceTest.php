<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\TryoutRankingService;
use Carbon\Carbon;
use Tests\TestCase;

class TryoutRankingServiceTest extends TestCase
{
    public function test_it_sorts_by_total_score_descending(): void
    {
        $rankings = collect([
            ['raw_score' => 68.6, 'finished_at' => Carbon::parse('2025-12-29 10:07:00'), 'user' => $this->user(1)],
            ['raw_score' => 71.4, 'finished_at' => Carbon::parse('2026-01-07 13:07:00'), 'user' => $this->user(2)],
            ['raw_score' => 0, 'finished_at' => Carbon::parse('2026-05-17 20:22:00'), 'user' => $this->user(3)],
            ['raw_score' => 10.4, 'finished_at' => Carbon::parse('2026-07-17 15:40:00'), 'user' => $this->user(4)],
        ]);

        $sortedScores = app(TryoutRankingService::class)->sort($rankings)->pluck('raw_score')->all();

        $this->assertSame([71.4, 68.6, 10.4, 0], $sortedScores);
    }

    public function test_it_prioritizes_the_displayed_final_score_when_raw_scores_are_equal(): void
    {
        $rankings = collect([
            ['raw_score' => 0, 'ranking_score' => 68.6, 'finished_at' => Carbon::parse('2025-12-29 10:07:00'), 'user' => $this->user(1)],
            ['raw_score' => 0, 'ranking_score' => 71.4, 'finished_at' => Carbon::parse('2026-01-07 13:07:00'), 'user' => $this->user(2)],
            ['raw_score' => 0, 'ranking_score' => 0, 'finished_at' => Carbon::parse('2026-05-17 20:22:00'), 'user' => $this->user(3)],
            ['raw_score' => 0, 'ranking_score' => 10.4, 'finished_at' => Carbon::parse('2026-07-17 15:40:00'), 'user' => $this->user(4)],
        ]);

        $sortedScores = app(TryoutRankingService::class)->sort($rankings)->pluck('ranking_score')->all();

        $this->assertSame([71.4, 68.6, 10.4, 0], $sortedScores);
    }

    public function test_it_uses_completion_time_for_equal_scores(): void
    {
        $rankings = collect([
            ['raw_score' => 50, 'finished_at' => Carbon::parse('2026-01-02 10:00:00'), 'user' => $this->user(2)],
            ['raw_score' => 50, 'finished_at' => Carbon::parse('2026-01-01 10:00:00'), 'user' => $this->user(1)],
        ]);

        $sortedUserIds = app(TryoutRankingService::class)->sort($rankings)->map(fn (array $ranking) => $ranking['user']->id)->all();

        $this->assertSame([1, 2], $sortedUserIds);
    }

    public function test_it_selects_the_first_attempt_instead_of_the_highest_score(): void
    {
        $firstAttempt = app(TryoutRankingService::class)->firstAttempt(collect([
            ['started_at' => Carbon::parse('2026-01-03 10:00:00'), 'ranking_score' => 99],
            ['started_at' => Carbon::parse('2026-01-01 10:00:00'), 'ranking_score' => 10],
        ]));

        $this->assertSame(10, $firstAttempt['ranking_score']);
    }

    private function user(int $id): User
    {
        $user = new User;
        $user->id = $id;

        return $user;
    }
}
