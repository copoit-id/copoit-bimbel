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
            'video_recommendations' => [[
                'title' => 'Persamaan Linear Dasar',
                'search_query' => 'persamaan linear satu variabel kelas 7',
                'reason' => 'Memperkuat operasi aljabar dasar.',
                'url' => 'https://example.test/tidak-boleh-dipakai',
            ]],
        ], JSON_THROW_ON_ERROR));

        $payload = $service->generate('recommendation', [], $this->context())['payload'];

        $this->assertSame('tinggi', $payload['focus_topics'][0]['priority']);
        $this->assertArrayNotHasKey('url', $payload['focus_topics'][0]);
        $this->assertSame('persamaan linear satu variabel kelas 7', $payload['video_recommendations'][0]['search_query']);
        $this->assertArrayNotHasKey('url', $payload['video_recommendations'][0]);
    }

    public function test_it_normalizes_generated_question_settings_and_options(): void
    {
        $service = $this->serviceWithResponse(json_encode([
            'title' => 'Soal Latihan',
            'question_text' => 'Berapa hasil 4 + 4?',
            'options' => [
                ['key' => 'a', 'text' => '8'],
                ['key' => 'b', 'text' => '9'],
                ['key' => 'c', 'text' => '10'],
                ['key' => 'd', 'text' => '11'],
            ],
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

        $this->assertSame('A', $payload['questions'][0]['correct_answer']);
        $this->assertSame('A', $payload['questions'][0]['options'][0]['key']);
        $this->assertSame('mudah', $payload['questions'][0]['difficulty']);
    }

    public function test_it_limits_generated_questions_to_five_items(): void
    {
        $questions = collect(range(1, 7))->map(fn (int $number) => [
            'question_text' => 'Soal '.$number,
            'options' => [
                ['key' => 'A', 'text' => 'Pilihan A'],
                ['key' => 'B', 'text' => 'Pilihan B'],
                ['key' => 'C', 'text' => 'Pilihan C'],
                ['key' => 'D', 'text' => 'Pilihan D'],
            ],
            'correct_answer' => 'A',
            'explanation' => 'Pembahasan soal '.$number,
            'difficulty' => 'sedang',
            'hots_level' => 'sedang',
        ])->all();
        $service = $this->serviceWithResponse(json_encode([
            'title' => 'Lima Soal Latihan',
            'questions' => $questions,
        ], JSON_THROW_ON_ERROR));

        $payload = $service->generate('question', ['question_count' => 5], $this->context())['payload'];

        $this->assertCount(5, $payload['questions']);
    }

    public function test_it_rejects_fewer_questions_than_requested(): void
    {
        $service = $this->serviceWithResponse(json_encode([
            'title' => 'Soal Belum Lengkap',
            'questions' => [[
                'question_text' => 'Berapa hasil 2 + 2?',
                'options' => [
                    ['key' => 'A', 'text' => '4'],
                    ['key' => 'B', 'text' => '5'],
                    ['key' => 'C', 'text' => '6'],
                    ['key' => 'D', 'text' => '7'],
                ],
                'correct_answer' => 'A',
                'explanation' => 'Dua ditambah dua adalah empat.',
                'difficulty' => 'mudah',
                'hots_level' => 'rendah',
            ]],
        ], JSON_THROW_ON_ERROR));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Hasil AI belum lengkap');

        $service->generate('question', ['question_count' => 5], $this->context());
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
