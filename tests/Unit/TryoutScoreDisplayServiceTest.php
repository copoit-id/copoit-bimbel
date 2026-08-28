<?php

namespace Tests\Unit;

use App\Models\Tryout;
use App\Services\TryoutScoreDisplayService;
use Tests\TestCase;

class TryoutScoreDisplayServiceTest extends TestCase
{
    public function test_irt_scores_default_to_the_original_scale(): void
    {
        $tryout = new Tryout([
            'scoring_method' => 'irt_utbk',
        ]);

        $presentation = app(TryoutScoreDisplayService::class)->present($tryout, 850);

        $this->assertSame('raw', $presentation['scale']);
        $this->assertSame(850.0, $presentation['value']);
        $this->assertSame('850', $presentation['formatted']);
        $this->assertSame('Skor asli (0 - 1000)', $presentation['label']);
    }

    public function test_irt_scores_can_be_presented_on_a_zero_to_one_hundred_scale(): void
    {
        $tryout = new Tryout([
            'scoring_method' => 'irt_utbk',
            'result_score_scale' => 'scale_100',
        ]);

        $presentation = app(TryoutScoreDisplayService::class)->present($tryout, 856);

        $this->assertSame('scale_100', $presentation['scale']);
        $this->assertSame(85.6, $presentation['value']);
        $this->assertSame('85.6', $presentation['formatted']);
        $this->assertSame('Skala 0 - 100', $presentation['label']);
    }

    public function test_hundred_scale_is_only_applied_to_irt_tryouts(): void
    {
        $tryout = new Tryout([
            'scoring_method' => 'normal',
            'result_score_scale' => 'scale_100',
        ]);

        $this->assertFalse(app(TryoutScoreDisplayService::class)->usesHundredScale($tryout));
    }
}
