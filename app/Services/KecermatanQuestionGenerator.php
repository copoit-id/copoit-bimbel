<?php

namespace App\Services;

use App\Models\KecermatanColumn;
use App\Models\KecermatanQuestion;

class KecermatanQuestionGenerator
{
    public function regenerate(KecermatanColumn $column): void
    {
        $column->questions()->delete();
        $type = $column->kecermatan?->type ?? 'kecermatan_polri';

        for ($index = 1; $index <= $column->questions_count; $index++) {
            $payload = $type === 'kecermatan_tni'
                ? $this->generateTniPayload()
                : $this->generatePolriPayload($column->references ?? []);

            KecermatanQuestion::create([
                'kecermatan_column_id' => $column->id,
                'sort_order' => $index,
                'payload' => $payload['payload'],
                'correct_answer' => (string) $payload['correct_answer'],
            ]);
        }
    }

    private function generatePolriPayload(array $references): array
    {
        $references = array_values(array_filter($references, fn ($item) => trim((string) $item) !== ''));
        $references = count($references) === 5 ? $references : ['A', 'B', 'C', 'D', 'E'];
        $missingIndex = random_int(0, 4);
        $questionItems = $references;
        $correctAnswer = $questionItems[$missingIndex];
        unset($questionItems[$missingIndex]);
        $questionItems = array_values($questionItems);
        shuffle($questionItems);

        return [
            'payload' => $questionItems,
            'correct_answer' => $correctAnswer,
        ];
    }

    private function generateTniPayload(): array
    {
        do {
            $first = random_int(1, 9);
            $second = random_int(1, 9);
        } while (($first + $second) > 10);

        return [
            'payload' => [$first, $second],
            'correct_answer' => $first + $second,
        ];
    }
}
