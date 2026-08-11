<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class TkaCertificateRenderer
{
    private const LAYOUT = 'tka_sma';

    private const TEMPLATE_PATH = 'certificates/templates/sertif-template.jpeg';

    /**
     * Build the immutable TKA data stored with a certificate at issuance time.
     * Returns null for every non-TKA attempt so existing certificate flows remain unchanged.
     */
    public function buildMetadata(Collection $userAnswers, User $user): ?array
    {
        $scores = $this->tkaScoresFromAnswers($userAnswers);

        if ($scores === null) {
            return null;
        }

        return [
            'certificate_layout' => self::LAYOUT,
            'tka_scores' => $scores,
            'tka_destination_institution' => trim((string) $user->participant_destination_institution_name),
        ];
    }

    public function usesTkaLayout(Certificate $certificate): bool
    {
        return data_get($certificate->metadata, 'certificate_layout') === self::LAYOUT
            && is_file($this->templatePath());
    }

    public function response(Certificate $certificate, bool $download): Response
    {
        $metadata = is_array($certificate->metadata) ? $certificate->metadata : [];
        $scores = (array) data_get($metadata, 'tka_scores', []);
        $manager = new ImageManager(new Driver);
        $image = $manager->read($this->templatePath());

        $this->writeText($image, (string) data_get($metadata, 'user_name', '—'), 410, 578, $this->fieldFontSize((string) data_get($metadata, 'user_name', '')));
        $this->writeText($image, (string) data_get($metadata, 'tka_destination_institution', '—'), 410, 630, $this->fieldFontSize((string) data_get($metadata, 'tka_destination_institution', '')));
        $this->writeText($image, $this->formatDate($certificate->date_of_birth), 410, 682, 20);

        $rows = [
            'mathematics' => 928,
            'indonesian' => 974,
            'english' => 1019,
        ];

        foreach ($rows as $subject => $y) {
            $score = array_key_exists($subject, $scores) ? (float) $scores[$subject] : null;
            $this->writeText($image, $this->formatScore($score), 592, $y, 22, 'center', 'middle');
            $this->writeText($image, $this->categoryFor($score) ?? '—', 820, $y, 16, 'center', 'middle');
        }

        $filename = 'Sertifikat_TKA_'.str_replace(['/', '-'], '_', $certificate->certificate_number).'.png';

        return response($image->toPng()->toString(), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function categoryFor(?float $score): ?string
    {
        if ($score === null || $score < 0 || $score > 100) {
            return null;
        }

        return match (true) {
            $score <= 45 => 'Kurang',
            $score <= 70 => 'Memadai',
            $score <= 95 => 'Bagus',
            default => 'Istimewa',
        };
    }

    private function tkaScoresFromAnswers(Collection $userAnswers): ?array
    {
        if ($userAnswers->count() !== 3) {
            return null;
        }

        $scores = [];

        foreach ($userAnswers as $userAnswer) {
            $subject = $this->subjectFor($userAnswer->tryoutDetail);

            if ($subject === null || array_key_exists($subject, $scores)) {
                return null;
            }

            $scores[$subject] = (float) ($userAnswer->score ?? 0);
        }

        return count($scores) === 3
            && isset($scores['mathematics'], $scores['indonesian'], $scores['english'])
            ? $scores
            : null;
    }

    private function subjectFor(mixed $tryoutDetail): ?string
    {
        if ($tryoutDetail === null) {
            return null;
        }

        $source = $this->normalize(implode(' ', [
            (string) ($tryoutDetail->type_subtest ?? ''),
            (string) ($tryoutDetail->materialCategory?->name ?? ''),
        ]));

        return match (true) {
            str_contains($source, 'matematika') => 'mathematics',
            str_contains($source, 'bahasa indonesia') => 'indonesian',
            str_contains($source, 'bahasa inggris') => 'english',
            default => null,
        };
    }

    private function templatePath(): string
    {
        return storage_path('app/private/'.self::TEMPLATE_PATH);
    }

    private function writeText(mixed $image, string $text, int $x, int $y, int $size, string $align = 'start', string $valign = 'top'): void
    {
        $image->text($text, $x, $y, function ($font) use ($size, $align, $valign): void {
            $font->file(public_path('fonts/Poppins-SemiBold.ttf'));
            $font->size($size);
            $font->color('#102f63');
            $font->align($align);
            $font->valign($valign);
        });
    }

    private function fieldFontSize(string $value): int
    {
        $length = Str::length(trim($value));

        return match (true) {
            $length > 42 => 13,
            $length > 32 => 16,
            $length > 24 => 18,
            default => 21,
        };
    }

    private function formatDate(mixed $date): string
    {
        return $date ? Carbon::parse($date)->translatedFormat('d F Y') : '—';
    }

    private function formatScore(?float $score): string
    {
        if ($score === null) {
            return '—';
        }

        return rtrim(rtrim(number_format($score, 2, ',', ''), '0'), ',');
    }

    private function normalize(string $value): string
    {
        return str_replace(['_', '-'], ' ', Str::lower(trim($value)));
    }
}
