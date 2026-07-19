<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class AiLearningToolService
{
    public function __construct(private AiDiscussionService $discussionService) {}

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $context
     * @return array{payload: array<string, mixed>, provider: string, model: string, usage: array<string, int>, response_time_ms: int, quota: mixed}
     */
    public function generate(string $tool, array $options, array $context): array
    {
        $result = $this->discussionService->chat(
            $this->prompt($tool, $options),
            $context,
            feature: $this->gatewayFeature($tool),
        );
        $decoded = $this->decodeJson((string) ($result['message'] ?? ''));
        $payload = $this->normalize($tool, $decoded);
        $this->assertCompletePayload($tool, $payload, $options);

        return [
            'payload' => $payload,
            'provider' => (string) ($result['provider'] ?? 'gateway'),
            'model' => (string) ($result['model'] ?? 'gateway'),
            'usage' => [
                'input' => (int) data_get($result, 'usage.input', 0),
                'output' => (int) data_get($result, 'usage.output', 0),
                'total' => (int) data_get($result, 'usage.total', 0),
            ],
            'response_time_ms' => (int) ($result['response_time_ms'] ?? 0),
            'quota' => $result['quota'] ?? null,
        ];
    }

    /** @param array<string, mixed> $options */
    private function prompt(string $tool, array $options): string
    {
        return match ($tool) {
            'note' => 'Buat catatan materi yang LENGKAP, enak dipelajari, dan terstruktur dari materi atau soal yang diberikan; bukan satu paragraf panjang atau ringkasan singkat. Buat pengantar singkat pada summary, lalu pecah penjelasan menjadi 4-6 sections. Setiap section wajib memiliki title, 1-3 paragraphs pendek yang saling menyambung, dan bullets bila ada hal yang perlu diingat. Jelaskan konsep inti, alasan, langkah memahami, contoh atau konteks, serta miskonsepsi umum bila relevan. Isi key_points dengan minimal 6 butir inti. formulas berisi rumus, definisi, atau istilah penting yang layak ditonjolkan; kosongkan bila tidak relevan. Balas HANYA JSON valid: {"title":"...","summary":"...","sections":[{"title":"...","paragraphs":["..."],"bullets":["..."]}],"key_points":["..."],"formulas":["..."]}. Akurat dan jangan tambahkan URL atau Markdown.',
            'recommendation' => 'Analisis materi yang perlu dipelajari dari materi atau soal yang diberikan. WAJIB sertakan 2-3 rekomendasi video belajar berupa judul dan kata kunci pencarian yang spesifik, bukan URL atau nama channel yang dibuat-buat. Balas HANYA JSON valid: {"title":"...","focus_topics":[{"topic":"...","reason":"...","priority":"tinggi|sedang|rendah"}],"study_plan":["..."],"video_recommendations":[{"title":"...","search_query":"...","reason":"..."}]}.',
            'question' => sprintf(
                'Buat %d soal latihan serupa berdasarkan konsep yang diberikan dengan tingkat %s, variasi %s, dan HOTS %s. Setiap soal wajib memiliki tepat empat opsi A-D, satu jawaban benar, dan pembahasan. Balas HANYA JSON valid: {"title":"...","questions":[{"question_text":"...","options":[{"key":"A","text":"..."},{"key":"B","text":"..."},{"key":"C","text":"..."},{"key":"D","text":"..."}],"correct_answer":"A","explanation":"...","difficulty":"%s","hots_level":"%s"}]}. Jangan menyalin persis input asli.',
                max(1, min(5, (int) ($options['question_count'] ?? 1))),
                $options['difficulty'] ?? 'sedang',
                $options['variation'] ?? 'konteks',
                $options['hots_level'] ?? 'sedang',
                $options['difficulty'] ?? 'sedang',
                $options['hots_level'] ?? 'sedang',
            ),
            'flashcard' => 'Ubah konsep penting dari materi atau soal yang diberikan menjadi 3 sampai 5 flashcard. Balas HANYA JSON valid: {"title":"...","cards":[{"front":"...","back":"..."}]}. Cocok untuk menghafal rumus, istilah, atau konsep; jangan tambahkan URL.',
            default => throw new RuntimeException('Fitur AI tidak dikenali.'),
        };
    }

    private function gatewayFeature(string $tool): string
    {
        return match ($tool) {
            'note' => 'learning_note',
            'recommendation' => 'learning_recommendation',
            'question' => 'learning_question',
            'flashcard' => 'learning_flashcard',
            default => throw new RuntimeException('Fitur AI tidak dikenali.'),
        };
    }

    /** @return array<string, mixed> */
    private function decodeJson(string $message): array
    {
        $message = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($message)) ?? '');
        $start = strpos($message, '{');
        $end = strrpos($message, '}');

        if ($start !== false && $end !== false && $end >= $start) {
            $message = substr($message, $start, $end - $start + 1);
        }

        $decoded = json_decode($message, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Format hasil AI belum valid. Silakan coba generate kembali.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalize(string $tool, array $payload): array
    {
        return match ($tool) {
            'note' => [
                'title' => $this->text($payload['title'] ?? 'Catatan Materi'),
                'summary' => $this->text($payload['summary'] ?? ''),
                'sections' => $this->noteSections($payload['sections'] ?? []),
                'key_points' => $this->stringList($payload['key_points'] ?? [], 12),
                'formulas' => $this->stringList($payload['formulas'] ?? [], 8),
            ],
            'recommendation' => [
                'title' => $this->text($payload['title'] ?? 'Rekomendasi Belajar'),
                'focus_topics' => collect(Arr::wrap($payload['focus_topics'] ?? []))
                    ->filter(fn ($item) => is_array($item))
                    ->take(6)
                    ->map(fn (array $item) => [
                        'topic' => $this->text($item['topic'] ?? ''),
                        'reason' => $this->text($item['reason'] ?? ''),
                        'priority' => in_array(($item['priority'] ?? ''), ['tinggi', 'sedang', 'rendah'], true)
                            ? $item['priority']
                            : 'sedang',
                    ])->filter(fn (array $item) => $item['topic'] !== '')->values()->all(),
                'study_plan' => $this->stringList($payload['study_plan'] ?? [], 8),
                'video_recommendations' => collect(Arr::wrap($payload['video_recommendations'] ?? []))
                    ->filter(fn ($item) => is_array($item))
                    ->take(3)
                    ->map(fn (array $item) => [
                        'title' => $this->text($item['title'] ?? ''),
                        'search_query' => $this->text($item['search_query'] ?? ''),
                        'reason' => $this->text($item['reason'] ?? ''),
                    ])->filter(fn (array $item) => $item['title'] !== '' && $item['search_query'] !== '')
                    ->values()
                    ->all(),
            ],
            'question' => [
                'title' => $this->text($payload['title'] ?? 'Soal Serupa'),
                'questions' => collect(Arr::wrap(
                    $payload['questions'] ?? (filled($payload['question_text'] ?? null) ? [$payload] : [])
                ))
                    ->filter(fn ($item) => is_array($item))
                    ->take(5)
                    ->map(fn (array $item) => $this->normalizeQuestion($item))
                    ->filter(fn (array $item) => $item['question_text'] !== ''
                        && count($item['options']) === 4
                        && $item['correct_answer'] !== ''
                        && $item['explanation'] !== '')
                    ->values()
                    ->all(),
            ],
            'flashcard' => [
                'title' => $this->text($payload['title'] ?? 'Flashcard'),
                'cards' => collect(Arr::wrap($payload['cards'] ?? []))
                    ->filter(fn ($item) => is_array($item))
                    ->take(5)
                    ->map(fn (array $item) => [
                        'front' => $this->text($item['front'] ?? ''),
                        'back' => $this->text($item['back'] ?? ''),
                    ])->filter(fn (array $item) => $item['front'] !== '' && $item['back'] !== '')->values()->all(),
            ],
            default => throw new RuntimeException('Fitur AI tidak dikenali.'),
        };
    }

    private function text(mixed $value): string
    {
        return Str::limit(trim(strip_tags(is_scalar($value) ? (string) $value : '')), 5000, '');
    }

    /** @return array<int, array{title: string, paragraphs: array<int, string>, bullets: array<int, string>}> */
    private function noteSections(mixed $sections): array
    {
        return collect(Arr::wrap($sections))
            ->filter(fn ($section) => is_array($section))
            ->take(6)
            ->map(fn (array $section) => [
                'title' => $this->text($section['title'] ?? ''),
                'paragraphs' => $this->stringList($section['paragraphs'] ?? [], 3),
                'bullets' => $this->stringList($section['bullets'] ?? [], 6),
            ])
            ->filter(fn (array $section) => $section['title'] !== '' && $section['paragraphs'] !== [])
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $question */
    private function normalizeQuestion(array $question): array
    {
        return [
            'question_text' => $this->text($question['question_text'] ?? ''),
            'options' => collect(Arr::wrap($question['options'] ?? []))
                ->filter(fn ($item) => is_array($item))
                ->take(4)
                ->map(fn (array $item, int $index) => [
                    'key' => chr(65 + $index),
                    'text' => $this->text($item['text'] ?? ''),
                ])
                ->filter(fn (array $item) => $item['text'] !== '')
                ->values()
                ->all(),
            'correct_answer' => Str::upper(Str::limit($this->text($question['correct_answer'] ?? ''), 1, '')),
            'explanation' => $this->text($question['explanation'] ?? ''),
            'difficulty' => $this->text($question['difficulty'] ?? 'sedang'),
            'hots_level' => $this->text($question['hots_level'] ?? 'sedang'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $options
     */
    private function assertCompletePayload(string $tool, array $payload, array $options): void
    {
        $requestedQuestionCount = max(1, min(5, (int) ($options['question_count'] ?? 1)));
        $isComplete = match ($tool) {
            'note' => $payload['summary'] !== ''
                && count($payload['key_points']) > 0,
            'recommendation' => (count($payload['focus_topics']) > 0
                || count($payload['study_plan']) > 0)
                && count($payload['video_recommendations']) > 0,
            'question' => count($payload['questions']) >= $requestedQuestionCount,
            'flashcard' => count($payload['cards']) > 0,
            default => false,
        };

        if (! $isComplete) {
            throw new RuntimeException('Hasil AI belum lengkap. Silakan generate kembali.');
        }
    }

    /** @return array<int, string> */
    private function stringList(mixed $items, int $limit): array
    {
        return collect(Arr::wrap($items))
            ->filter(fn ($item) => is_scalar($item))
            ->map(fn ($item) => $this->text($item))
            ->filter()
            ->take($limit)
            ->values()
            ->all();
    }
}
