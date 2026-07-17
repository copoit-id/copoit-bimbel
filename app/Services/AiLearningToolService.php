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
        $this->assertCompletePayload($tool, $payload);

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
            'note' => 'Buat catatan materi yang LENGKAP dari soal aktif, bukan ringkasan singkat. Jelaskan konsep inti, alasan, langkah memahami, miskonsepsi umum bila relevan, dan kaitannya dengan soal. Isi summary dengan 6-10 paragraf yang jelas (boleh panjang), key_points minimal 8 butir, serta formulas berisi rumus/istilah penting bila ada. Balas HANYA JSON valid: {"title":"...","summary":"...","key_points":["..."],"formulas":["..."]}. Akurat dan jangan tambahkan URL.',
            'recommendation' => 'Analisis materi yang perlu dipelajari dari soal aktif. Balas HANYA JSON valid: {"title":"...","focus_topics":[{"topic":"...","reason":"...","priority":"tinggi|sedang|rendah"}],"study_plan":["..."]}. Jangan membuat URL atau nama sumber.',
            'question' => sprintf(
                'Buat satu soal serupa dengan tingkat %s, variasi %s, dan HOTS %s. Balas HANYA JSON valid: {"title":"...","question_text":"...","options":[{"key":"A","text":"..."}],"correct_answer":"A","explanation":"...","difficulty":"%s","hots_level":"%s"}. Jangan menyalin persis soal asli.',
                $options['difficulty'] ?? 'sedang',
                $options['variation'] ?? 'konteks',
                $options['hots_level'] ?? 'sedang',
                $options['difficulty'] ?? 'sedang',
                $options['hots_level'] ?? 'sedang',
            ),
            'flashcard' => 'Ubah konsep penting dari soal aktif menjadi 3 sampai 5 flashcard. Balas HANYA JSON valid: {"title":"...","cards":[{"front":"...","back":"..."}]}. Cocok untuk menghafal rumus, istilah, atau konsep; jangan tambahkan URL.',
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
            ],
            'question' => [
                'title' => $this->text($payload['title'] ?? 'Soal Serupa'),
                'question_text' => $this->text($payload['question_text'] ?? ''),
                'options' => collect(Arr::wrap($payload['options'] ?? []))
                    ->filter(fn ($item) => is_array($item))
                    ->take(6)
                    ->map(fn (array $item, int $index) => [
                        'key' => Str::upper(Str::limit($this->text($item['key'] ?? chr(65 + $index)), 3, '')),
                        'text' => $this->text($item['text'] ?? ''),
                    ])->filter(fn (array $item) => $item['text'] !== '')->values()->all(),
                'correct_answer' => Str::upper(Str::limit($this->text($payload['correct_answer'] ?? ''), 3, '')),
                'explanation' => $this->text($payload['explanation'] ?? ''),
                'difficulty' => $this->text($payload['difficulty'] ?? 'sedang'),
                'hots_level' => $this->text($payload['hots_level'] ?? 'sedang'),
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

    /** @param array<string, mixed> $payload */
    private function assertCompletePayload(string $tool, array $payload): void
    {
        $isComplete = match ($tool) {
            'note' => $payload['summary'] !== ''
                && count($payload['key_points']) > 0,
            'recommendation' => count($payload['focus_topics']) > 0
                || count($payload['study_plan']) > 0,
            'question' => $payload['question_text'] !== ''
                && count($payload['options']) >= 2
                && $payload['correct_answer'] !== ''
                && $payload['explanation'] !== '',
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
