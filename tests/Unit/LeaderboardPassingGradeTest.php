<?php

namespace Tests\Unit;

use App\Http\Controllers\admin\LeaderboardController;
use App\Models\TryoutDetail;
use Tests\TestCase;

class LeaderboardPassingGradeTest extends TestCase
{
    public function test_irt_leaderboard_status_uses_the_configured_passing_grade(): void
    {
        $method = new \ReflectionMethod(LeaderboardController::class, 'isUtbkSubtestPassed');
        $controller = app(LeaderboardController::class);

        $scorePassingGrade = new TryoutDetail([
            'passing_score' => 650,
            'passing_type' => 'score',
        ]);
        $percentagePassingGrade = new TryoutDetail([
            'passing_score' => 65,
            'passing_type' => 'percentage',
        ]);

        $this->assertTrue($method->invoke($controller, $scorePassingGrade, 650.0));
        $this->assertFalse($method->invoke($controller, $scorePassingGrade, 649.9));
        $this->assertTrue($method->invoke($controller, $percentagePassingGrade, 650.0));
        $this->assertFalse($method->invoke($controller, $percentagePassingGrade, 649.9));
    }
}
