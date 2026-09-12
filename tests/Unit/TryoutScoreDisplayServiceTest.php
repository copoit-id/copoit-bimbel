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

    public function test_normal_scores_can_be_presented_on_a_zero_to_one_hundred_scale(): void
    {
        $tryout = new Tryout([
            'scoring_method' => 'normal',
            'result_score_scale' => 'scale_100',
        ]);

        $presentation = app(TryoutScoreDisplayService::class)->present($tryout, 7, 7, 10, 10, 3);

        $this->assertTrue(app(TryoutScoreDisplayService::class)->usesHundredScale($tryout));
        $this->assertSame(210.0, $presentation['value']);
        $this->assertSame(300, $presentation['maximum']);
        $this->assertSame('210', $presentation['formatted']);
    }

    public function test_irt_total_accumulates_the_hundred_scale_of_each_subtest(): void
    {
        $tryout = new Tryout([
            'scoring_method' => 'irt_utbk',
            'result_score_scale' => 'scale_100',
        ]);

        $presentation = app(TryoutScoreDisplayService::class)->present($tryout, 800, 0, 0, 1000, 3);

        $this->assertSame(240.0, $presentation['value']);
        $this->assertSame(300, $presentation['maximum']);
        $this->assertSame('Skala 0 - 300', $presentation['label']);
    }

    public function test_maximum_score_visibility_uses_the_tryout_setting(): void
    {
        $service = app(TryoutScoreDisplayService::class);

        $this->assertFalse($service->shouldShowMaximum(new Tryout([
            'show_score_maximum' => false,
        ])));
        $this->assertTrue($service->shouldShowMaximum(new Tryout([
            'show_score_maximum' => true,
        ])));
    }

    public function test_it_summarizes_total_final_scores_on_the_configured_scale(): void
    {
        $summary = app(TryoutScoreDisplayService::class)->summarizeFinalScores(collect([
            ['raw_score' => 15, 'display_score' => ['value' => 150]],
            ['raw_score' => 21, 'display_score' => ['value' => 210]],
        ]));

        $this->assertSame(180.0, $summary['average']);
        $this->assertSame('180', $summary['average_formatted']);
        $this->assertSame(210.0, $summary['highest']);
        $this->assertSame('210', $summary['highest_formatted']);
    }

}
