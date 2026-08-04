<?php

namespace Tests\Unit;

use App\Models\Tryout;
use Tests\TestCase;

class TryoutIrtScoringTest extends TestCase
{
    public function test_irt_is_available_for_a_single_non_utbk_subtest(): void
    {
        $tryout = new Tryout([
            'type_tryout' => 'tiu',
            'scoring_method' => 'irt_utbk',
            'is_irt' => true,
        ]);

        $this->assertTrue($tryout->requiresIrtScoring());
    }

    public function test_normal_scoring_is_not_treated_as_irt(): void
    {
        $tryout = new Tryout([
            'type_tryout' => 'general',
            'scoring_method' => 'normal',
            'is_irt' => false,
        ]);

        $this->assertFalse($tryout->requiresIrtScoring());
    }
}
