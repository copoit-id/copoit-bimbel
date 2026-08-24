<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;
use Smalot\PdfParser\Parser;

class AiReferencePdfService
{
    /** @return array<int, array{question: string, options: array<int, string>}> */
    public function examples(UploadedFile $file): array
    {
        try {
            $text = (new Parser())->parseFile($file->getRealPath())->getText();
        } catch (\Throwable $exception) {
            throw new RuntimeException('PDF referensi tidak dapat dibaca. Pastikan file bukan PDF rusak atau terkunci.');
        }

        $text = trim((string) preg_replace('/\s+/', ' ', $text));
        if ($text === '') {
            throw new RuntimeException('PDF referensi tidak memiliki teks yang dapat dibaca. Gunakan PDF berbasis teks, bukan hasil scan gambar.');
        }

        return collect(preg_split('/(?<=[.!?])\s+/', Str::limit($text, 12000, '')) ?: [])
            ->filter(fn (string $part): bool => mb_strlen(trim($part)) >= 80)
            ->chunk(3)
            ->map(fn ($parts): array => ['question' => Str::limit(trim($parts->implode(' ')), 2400, ''), 'options' => []])
            ->take(3)
            ->values()
            ->all() ?: [['question' => Str::limit($text, 2400, ''), 'options' => []]];
    }
}
