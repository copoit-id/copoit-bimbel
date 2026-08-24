<?php

namespace Tests\Unit;

use App\Http\Controllers\admin\LaporanController;
use Tests\TestCase;

class LaporanPercentageDisplayTest extends TestCase
{
    public function test_percentage_display_uses_correct_answers_divided_by_total_questions(): void
    {
        $method = new \ReflectionMethod(LaporanController::class, 'percentageFromCorrectAnswers');

        $this->assertSame(75.0, $method->invoke(app(LaporanController::class), 15, 20));
        $this->assertSame(0.0, $method->invoke(app(LaporanController::class), 0, 0));
    }
}
