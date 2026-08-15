<?php

namespace App\Services;

use App\Models\Certificate;
use Carbon\Carbon;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateTemplateRenderer
{
    public function canRender(Certificate $certificate): bool
    {
        $metadata = $this->metadata($certificate);

        return is_array($metadata['template_layout'] ?? null)
            && $this->templatePath($certificate) !== null;
    }

    public function render(Certificate $certificate): string
    {
        $metadata = $this->metadata($certificate);
        $templatePath = $this->templatePath($certificate);

        abort_unless($templatePath, 404, 'Template sertifikat tidak ditemukan.');

        $image = (new ImageManager(new Driver()))->read($templatePath);
        foreach ($metadata['template_layout'] as $field => $config) {
            if (! is_array($config) || ! ($config['enabled'] ?? false)) {
                continue;
            }

            $x = (int) round((float) ($config['x'] ?? 0));
            $y = (int) round((float) ($config['y'] ?? 0));

            $fieldType = str_starts_with($field, 'optional_')
                ? (string) ($config['field_type'] ?? '')
                : $field;

            if ($fieldType === 'qr_code') {
                $size = max(48, (int) round((float) ($config['font_size'] ?? 120)));
                $temporaryQrPath = tempnam(sys_get_temp_dir(), 'certificate-qr-');
                file_put_contents($temporaryQrPath, QrCode::format('png')->size($size)->generate($certificate->certificate_number));
                $qrCode = (new ImageManager(new Driver()))->read($temporaryQrPath);
                unlink($temporaryQrPath);
                $image->place($qrCode, 'top-left', $x, $y);

                continue;
            }

            $text = $this->fieldValue($field, $certificate, $metadata, $config);
            if ($text === '') {
                continue;
            }

            $fontPath = public_path('fonts/'.$this->fontFile($config, $field));
            $image->text($text, $x, $y, function ($font) use ($config, $fontPath): void {
                $font->file($fontPath);
                $font->size(max(8, (int) round((float) ($config['font_size'] ?? 16))));
                $font->color($config['color'] ?? '#1C3259');
                $font->align($config['align'] ?? 'left');
                $font->valign('top');
            });
        }

        return $image->toPng()->toString();
    }

    private function fieldValue(string $field, Certificate $certificate, array $metadata, array $config): string
    {
        if (str_starts_with($field, 'optional_')) {
            $fieldType = (string) ($config['field_type'] ?? '');

            if ($fieldType === 'subtest_score') {
                return $this->subtestScoreText($metadata['subtest_scores'] ?? [], (int) ($config['subtest_index'] ?? 1));
            }

            if ($fieldType === 'conditional_text') {
                return $this->conditionalText($metadata['subtest_scores'] ?? [], $config);
            }

            return $fieldType !== '' ? $this->fieldValue($fieldType, $certificate, $metadata, $config) : '';
        }

        if (str_starts_with($field, 'subtest_score_')) {
            return $this->subtestScoreText(
                $metadata['subtest_scores'] ?? [],
                (int) ($config['subtest_index'] ?? 1)
            );
        }

        if (str_starts_with($field, 'custom_text_')) {
            return trim((string) ($config['text'] ?? ''));
        }

        return match ($field) {
            'participant_name' => (string) ($metadata['user_name'] ?? ''),
            'participant_email' => (string) ($metadata['user_email'] ?? ''),
            'date_of_birth' => filled($certificate->date_of_birth)
                ? Carbon::parse($certificate->date_of_birth)->locale('id')->translatedFormat('d F Y')
                : '',
            'certificate_number' => (string) $certificate->certificate_number,
            'tryout_name' => (string) $certificate->certificate_name,
            'package_name' => (string) ($metadata['package_name'] ?? ''),
            'institution_name' => (string) $certificate->institution_name,
            'issued_date' => Carbon::parse($certificate->issued_date)->format('d F Y'),
            'completion_date' => filled($metadata['completion_date'] ?? null)
                ? Carbon::parse($metadata['completion_date'])->format('d F Y')
                : '',
            'exam_date' => filled($metadata['exam_date'] ?? null)
                ? Carbon::parse($metadata['exam_date'])->format('d F Y')
                : '',
            'total_score' => isset($metadata['score']) ? rtrim(rtrim(number_format((float) $metadata['score'], 2, '.', ''), '0'), '.') : '',
            'subtest_scores' => $this->subtestScoresText($metadata['subtest_scores'] ?? []),
            'custom_text' => trim((string) ($config['text'] ?? '')),
            default => '',
        };
    }

    private function subtestScoresText(mixed $subtestScores): string
    {
        if (! is_array($subtestScores)) {
            return '';
        }

        return collect($subtestScores)
            ->filter(fn ($subtest) => is_array($subtest) && filled($subtest['label'] ?? null))
            ->map(function (array $subtest): string {
                $score = $subtest['score'] ?? '-';

                return trim((string) $subtest['label']).': '.$score;
            })
            ->implode(PHP_EOL);
    }

    private function subtestScoreText(mixed $subtestScores, int $index): string
    {
        if (! is_array($subtestScores)) {
            return '';
        }

        return (string) (collect($subtestScores)->values()->get(max(0, $index - 1))['score'] ?? '');
    }

    private function conditionalText(mixed $subtestScores, array $config): string
    {
        $score = trim($this->subtestScoreText($subtestScores, max(1, (int) ($config['subtest_index'] ?? 1))));

        foreach ($config['rules'] ?? [] as $rule) {
            if (! is_array($rule) || ! filled($rule['value'] ?? null) || ! filled($rule['text'] ?? null)) {
                continue;
            }

            $expected = trim((string) $rule['value']);
            $operator = $rule['operator'] ?? 'equals';
            $matches = match ($operator) {
                'gte' => is_numeric($score) && is_numeric($expected) && (float) $score >= (float) $expected,
                'lte' => is_numeric($score) && is_numeric($expected) && (float) $score <= (float) $expected,
                default => mb_strtolower($score) === mb_strtolower($expected),
            };

            if ($matches) {
                return trim((string) $rule['text']);
            }
        }

        return trim((string) ($config['fallback_text'] ?? ''));
    }

    private function fontFile(array $config, string $field): string
    {
        $style = $config['font_style'] ?? ($field === 'participant_name' ? 'semibold' : 'regular');

        return [
            'regular' => 'Poppins-Regular.ttf',
            'semibold' => 'Poppins-SemiBold.ttf',
            'bold' => 'Poppins-Bold.ttf',
            'italic' => 'Poppins-Italic.ttf',
            'bold_italic' => 'Poppins-BoldItalic.ttf',
        ][$style] ?? 'Poppins-Regular.ttf';
    }

    private function metadata(Certificate $certificate): array
    {
        return is_array($certificate->metadata) ? $certificate->metadata : [];
    }

    private function templatePath(Certificate $certificate): ?string
    {
        if (blank($certificate->template_path)) {
            return null;
        }

        $root = realpath(storage_path('app/private'));
        $path = $root ? realpath($root.'/'.ltrim($certificate->template_path, '/')) : false;

        return $root && $path && str_starts_with($path, $root.DIRECTORY_SEPARATOR) ? $path : null;
    }
}
