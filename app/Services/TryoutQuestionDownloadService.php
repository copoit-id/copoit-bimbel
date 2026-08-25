<?php

namespace App\Services;

use App\Models\Tryout;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TryoutQuestionDownloadService
{
    /**
     * Generate a PDF containing questions, with optional answer discussions.
     *
     * @param Collection<int, \App\Models\Question> $questions
     */
    public function download(Tryout $tryout, Collection $questions, string $type = 'soal'): Response
    {
        if (! in_array($type, ['soal', 'pembahasan'], true)) {
            throw new InvalidArgumentException('Tipe unduhan soal tidak valid.');
        }

        $options = new Options;
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('pdf.tryout-questions', [
            'tryout' => $tryout,
            'questions' => $questions,
            'type' => $type,
            'brandName' => config('client.branding.name', config('app.name')),
            'brandLogoDataUrl' => $this->brandLogoDataUrl(),
            'brandPrimaryColor' => $this->brandPrimaryColor(),
            'brandPrimaryDarkColor' => $this->darkenColor($this->brandPrimaryColor(), 0.12),
        ])->render());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.Str::slug($tryout->name).'-'.$type.'.pdf"',
        ]);
    }

    private function brandLogoDataUrl(): ?string
    {
        $logo = ltrim((string) config('client.branding.logo', 'img/logo/logo-copoit.png'), '/');

        if ($logo === '' || Str::startsWith($logo, ['http://', 'https://', '//'])) {
            return null;
        }

        $candidates = [
            public_path($logo),
            public_path(Str::after($logo, 'storage/')),
            storage_path('app/public/'.Str::after($logo, 'storage/')),
        ];

        foreach (array_unique($candidates) as $path) {
            if (! is_file($path) || ! is_readable($path)) {
                continue;
            }

            $mimeType = mime_content_type($path) ?: 'image/png';

            return 'data:'.$mimeType.';base64,'.base64_encode((string) file_get_contents($path));
        }

        return null;
    }

    private function brandPrimaryColor(): string
    {
        $color = (string) config('client.branding.primary_color', '#1C3259');

        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#1C3259';
    }

    private function darkenColor(string $hexColor, float $amount): string
    {
        $amount = max(0, min(1, $amount));
        $hexColor = ltrim($hexColor, '#');
        $channels = str_split($hexColor, 2);

        $darkened = array_map(function (string $channel) use ($amount): string {
            $value = hexdec($channel);
            $value *= 1 - $amount;

            return str_pad(dechex((int) round($value)), 2, '0', STR_PAD_LEFT);
        }, $channels);

        return '#'.implode('', $darkened);
    }
}
