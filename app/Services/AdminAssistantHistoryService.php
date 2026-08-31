<?php

namespace App\Services;

use App\Models\AdminAssistantMessage;
use App\Models\User;
use Illuminate\Support\Str;

class AdminAssistantHistoryService
{
    /** @return array<string, mixed>|null */
    public function reusable(User $user, string $message, string $contextHash): ?array
    {
        $normalized = $this->normalize($message);
        $hash = hash('sha256', $normalized);
        $query = AdminAssistantMessage::query()
            ->where('user_id', $user->id)
            ->where('portal', (string) request()->route('portal', 'admin'))
            ->where('source', 'project_context')
            ->where('context_hash', $contextHash)
            ->where('created_at', '>=', now()->subDays(30))
            ->latest();

        $exact = (clone $query)->where('question_hash', $hash)->first();
        if ($exact) {
            return $this->toResponse($exact, true);
        }

        $tokens = $this->tokens($normalized);
        if (count($tokens) < 2) {
            return null;
        }

        foreach ($query->limit(50)->get() as $candidate) {
            $candidateTokens = array_values(array_filter((array) $candidate->question_token_hashes));
            $similarity = $this->jaccard($tokens, $candidateTokens);
            if ($similarity >= 0.86 && count(array_intersect($tokens, $candidateTokens)) >= 2) {
                return $this->toResponse($candidate, true, $similarity);
            }
        }

        return null;
    }

    public function store(User $user, string $message, array $response, string $contextHash): void
    {
        $normalized = $this->normalize($message);
        if ($normalized === '' || blank($response['answer'] ?? null)) {
            return;
        }

        AdminAssistantMessage::query()->create([
            'user_id' => $user->id,
            'portal' => (string) request()->route('portal', 'admin'),
            'question_hash' => hash('sha256', $normalized),
            'question_token_hashes' => $this->tokens($normalized),
            'question_text' => $message,
            'answer_text' => (string) $response['answer'],
            'answer_type' => (string) ($response['intent'] ?? 'assistant'),
            'source' => (string) ($response['source'] ?? 'local'),
            'confidence' => (string) ($response['confidence'] ?? ''),
            'usage_total' => (int) data_get($response, 'usage.total', 0),
            'context_hash' => $contextHash,
        ]);
    }

    /** @return array<int, array{role: string, text: string, created_at: string|null}> */
    public function recent(User $user, int $limit = 20): array
    {
        return AdminAssistantMessage::query()
            ->where('user_id', $user->id)
            ->where('portal', (string) request()->route('portal', 'admin'))
            ->latest()
            ->limit(max(1, min($limit, 50)))
            ->get()
            ->reverse()
            ->flatMap(fn (AdminAssistantMessage $message): array => [
                ['role' => 'user', 'text' => (string) $message->question_text, 'created_at' => $message->created_at?->toIso8601String()],
                ['role' => 'assistant', 'text' => (string) $message->answer_text, 'created_at' => $message->created_at?->toIso8601String()],
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function toResponse(AdminAssistantMessage $message, bool $cacheHit, float $similarity = 1): array
    {
        return [
            'answer' => (string) $message->answer_text,
            'intent' => (string) $message->answer_type,
            'source' => 'cache',
            'confidence' => (string) ($message->confidence ?: 'verified'),
            'cache_hit' => $cacheHit,
            'similarity' => round($similarity, 3),
            'usage' => ['input' => 0, 'output' => 0, 'total' => 0],
        ];
    }

    private function normalize(string $message): string
    {
        $message = Str::lower(trim(strip_tags($message)));
        $message = preg_replace('/[^a-z0-9\s]+/u', ' ', $message) ?? $message;
        $words = preg_split('/\s+/', trim($message), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $aliases = [
            'bagaimana' => 'cara', 'gimana' => 'cara', 'langkah' => 'cara',
            'menambahkan' => 'tambah', 'tambahkan' => 'tambah', 'menambah' => 'tambah',
            'membuat' => 'tambah', 'membuatkan' => 'tambah', 'buat' => 'tambah',
            'dibandingkan' => 'banding', 'perbandingan' => 'banding',
        ];
        $stopWords = ['yang', 'untuk', 'dengan', 'dan', 'di', 'ke', 'itu', 'ini', 'saya', 'aku', 'dong'];

        return collect($words)
            ->map(fn (string $word): string => $aliases[$word] ?? $word)
            ->reject(fn (string $word): bool => in_array($word, $stopWords, true))
            ->unique()
            ->sort()
            ->implode(' ');
    }

    /** @return array<int, string> */
    private function tokens(string $normalized): array
    {
        return collect(preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->map(fn (string $token): string => hash('sha256', $token))
            ->values()
            ->all();
    }

    /** @param array<int, string> $left @param array<int, string> $right */
    private function jaccard(array $left, array $right): float
    {
        $union = array_unique([...$left, ...$right]);
        return count($union) > 0 ? count(array_intersect($left, $right)) / count($union) : 0;
    }
}
