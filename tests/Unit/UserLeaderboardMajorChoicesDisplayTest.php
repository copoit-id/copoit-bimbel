<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserLeaderboardMajorChoicesDisplayTest extends TestCase
{
    public function test_it_combines_available_major_choices_for_the_leaderboard(): void
    {
        $user = new User([
            'major_choice_1' => 'Teknik Informatika',
            'major_choice_2' => 'Sistem Informasi',
        ]);

        $this->assertSame('Teknik Informatika / Sistem Informasi', $user->leaderboard_major_choices_display);
    }

    public function test_it_returns_null_when_no_major_choice_is_available(): void
    {
        $user = new User([
            'major_choice_1' => '   ',
            'major_choice_2' => null,
        ]);

        $this->assertNull($user->leaderboard_major_choices_display);
    }
}
