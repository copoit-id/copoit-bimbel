<?php

namespace Tests\Unit;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\UserAnswerDetail;
use App\Services\MultipleAnswerScoringService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MultipleAnswerScoringServiceTest extends TestCase
{
    public function test_partial_score_is_proportional_to_correct_answers(): void
    {
        $question = $this->question('partial', 10, -2);

        $result = app(MultipleAnswerScoringService::class)->evaluate($question, [1]);

        $this->assertSame(1, $result['correct_matched']);
        $this->assertSame(2, $result['correct_total']);
        $this->assertSame(5.0, $result['score_obtained']);
        $this->assertFalse($result['is_correct']);
    }

    public function test_partial_score_uses_wrong_score_when_no_correct_option_is_selected(): void
    {
        $question = $this->question('partial', 10, -2);

        $result = app(MultipleAnswerScoringService::class)->evaluate($question, [3]);

        $this->assertSame(-2.0, $result['score_obtained']);
    }

    public function test_fullscore_uses_wrong_score_until_all_and_only_correct_options_are_selected(): void
    {
        $question = $this->question('fullscore', 10, 3);
        $service = app(MultipleAnswerScoringService::class);

        $this->assertSame(3.0, $service->evaluate($question, [1])['score_obtained']);
        $this->assertSame(3.0, $service->evaluate($question, [1, 2, 3])['score_obtained']);
        $this->assertSame(10.0, $service->evaluate($question, [1, 2])['score_obtained']);
    }

    public function test_detail_score_is_recalculated_from_selected_options_not_clamped(): void
    {
        $question = $this->question('fullscore', 10, -1);
        $detail = new UserAnswerDetail([
            'answer_json' => ['selected_option_ids' => [1]],
            'is_correct' => false,
        ]);

        $this->assertSame(-1.0, app(MultipleAnswerScoringService::class)->scoreForDetail($question, $detail));
    }

    private function question(string $mode, float $scoreCorrect, float $scoreWrong): Question
    {
        $question = new Question([
            'question_type' => 'multiple_answer',
            'default_weight' => $scoreCorrect,
            'metadata' => [
                'multiple_answer' => [
                    'score_correct' => $scoreCorrect,
                    'score_wrong' => $scoreWrong,
                    'scoring_mode' => $mode,
                ],
            ],
        ]);

        $question->setRelation('questionOptions', new Collection([
            $this->option(1, true),
            $this->option(2, true),
            $this->option(3, false),
        ]));

        return $question;
    }

    private function option(int $id, bool $isCorrect): QuestionOption
    {
        $option = new QuestionOption(['is_correct' => $isCorrect]);
        $option->question_option_id = $id;

        return $option;
    }
}
