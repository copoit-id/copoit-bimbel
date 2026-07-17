<?php

namespace Tests\Unit;

use App\Services\AiDiscussionService;
use App\Services\AiLearningToolService;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AiLearningToolServiceTest extends TestCase
{
    public function test_it_normalizes_note_json_wrapped_in_markdown_fence(): void
    {
        $service = $this->serviceWithResponse('```json
{"title":"Hukum Newton","summary":"Gaya menyebabkan percepatan.","key_points":["F = ma"],"formulas":["F = m × a"]}
```');

        $result = $service->generate('note', [], $this->context());

        $this->assertSame('Hukum Newton', $result['payload']['title']);
        $this->assertSame(['F = ma'], $result['payload']['key_points']);
        $this->assertSame(30, $result['usage']['total']);
    }

    public function test_it_normalizes_learning_recommendations_without_accepting_urls(): void
    {
        $service = $this->serviceWithResponse(json_encode([
            'title' => 'Fokus Belajar',
            'focus_topics' => [[
                'topic' => 'Persamaan linear',
                'reason' => 'Konsep dasar soal.',
                'priority' => 'tinggi',
                'url' => 'https://example.test/tidak-boleh-dipakai',
            ]],
            'study_plan' => ['Pelajari konsep', 'Kerjakan latihan'],
        ], JSON_THROW_ON_ERROR));

        $payload = $service->generate('recommendation', [], $this->context())['payload'];

        $this->assertSame('tinggi', $payload['focus_topics'][0]['priority']);
        $this->assertArrayNotHasKey('url', $payload['focus_topics'][0]);
    }

    public function test_it_normalizes_generated_question_settings_and_options(): void
    {
        $service = $this->serviceWithResponse(json_encode([
            'title' => 'Soal Latihan',
            'question_text' => 'Berapa hasil 4 + 4?',
            'options' => [['key' => 'a', 'text' => '8'], ['key' => 'b', 'text' => '9']],
            'correct_answer' => 'a',
            'explanation' => 'Empat ditambah empat adalah delapan.',
            'difficulty' => 'mudah',
            'hots_level' => 'rendah',
        ], JSON_THROW_ON_ERROR));

        $payload = $service->generate('question', [
            'difficulty' => 'mudah',
            'variation' => 'angka',
            'hots_level' => 'rendah',
        ], $this->context())['payload'];

        $this->assertSame('A', $payload['correct_answer']);
        $this->assertSame('A', $payload['options'][0]['key']);
        $this->assertSame('mudah', $payload['difficulty']);
    }

    public function test_it_limits_flashcards_to_five_cards(): void
    {
        $cards = collect(range(1, 7))->map(fn (int $number) => [
            'front' => 'Depan '.$number,
            'back' => 'Belakang '.$number,
        ])->all();
        $service = $this->serviceWithResponse(json_encode([
            'title' => 'Kartu Konsep',
            'cards' => $cards,
        ], JSON_THROW_ON_ERROR));

        $payload = $service->generate('flashcard', [], $this->context())['payload'];

        $this->assertCount(5, $payload['cards']);
    }

    public function test_it_rejects_non_json_ai_output(): void
    {
        $service = $this->serviceWithResponse('Ini bukan JSON.');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Format hasil AI belum valid');

        $service->generate('note', [], $this->context());
    }

    public function test_it_rejects_incomplete_structured_output(): void
    {
        $service = $this->serviceWithResponse('{"title":"Catatan kosong","summary":"","key_points":[]}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Hasil AI belum lengkap');

        $service->generate('note', [], $this->context());
    }

    private function serviceWithResponse(string $message): AiLearningToolService
    {
        $discussion = Mockery::mock(AiDiscussionService::class);
        $discussion->shouldReceive('chat')->once()->andReturn([
            'message' => $message,
            'provider' => 'test-provider',
            'model' => 'test-model',
            'usage' => ['input' => 20, 'output' => 10, 'total' => 30],
            'response_time_ms' => 15,
            'quota' => ['tokens_used' => 30],
        ]);

        return new AiLearningToolService($discussion);
    }

    /** @return array<string, string> */
    private function context(): array
    {
        return [
            'tryout_name' => 'Tryout Test',
            'subtest_name' => 'TIU',
            'question_text' => '2 + 2 = ?',
        ];
    }
}
