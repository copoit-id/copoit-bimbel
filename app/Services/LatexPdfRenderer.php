<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class LatexPdfRenderer
{
    private const MAX_FORMULA_LENGTH = 1_000;

    /**
     * @param array<string, string|null> $fragments
     * @return array<string, string|null>
     */
    public function renderMany(array $fragments): array
    {
        $expressions = [];
        $prepared = [];

        foreach ($fragments as $key => $html) {
            $prepared[$key] = $this->replaceMathPlaceholders((string) $html, $expressions);
        }

        if ($expressions === []) {
            return $fragments;
        }

        $imageByExpression = $this->renderExpressions($expressions);

        foreach ($prepared as $key => $html) {
            $fragments[$key] = preg_replace_callback(
                '/@@PDF_MATH_(\d+)@@/',
                function (array $matches) use ($expressions, $imageByExpression): string {
                    $expression = $expressions[(int) $matches[1]];
                    $image = $imageByExpression[$expression['cache_key']] ?? null;

                    return $image
                        ? $this->pngImage($image, $expression['display'])
                        : $expression['original'];
                },
                $html,
            );
        }

        return $fragments;
    }

    /**
     * @param array<int, array{latex: string, display: bool, original: string, cache_key: string}> $expressions
     */
    private function replaceMathPlaceholders(string $html, array &$expressions): string
    {
        $replace = function (string $latex, bool $display, string $original) use (&$expressions): string {
            $latex = trim(html_entity_decode(strip_tags($latex), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($latex === '' || mb_strlen($latex) > self::MAX_FORMULA_LENGTH) {
                return $original;
            }

            $expressions[] = [
                'latex' => $latex,
                'display' => $display,
                'original' => $original,
                'cache_key' => hash('sha256', ($display ? 'display:' : 'inline:').$latex),
            ];

            return '@@PDF_MATH_'.(count($expressions) - 1).'@@';
        };

        $html = preg_replace_callback(
            '/<span\b(?=[^>]*\bclass=["\'][^"\']*\bmath-tex\b[^"\']*["\'])[^>]*>(.*?)<\/span>/is',
            function (array $matches) use ($replace): string {
                $content = trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if (preg_match('/^\\\\\[(.*)\\\\\]$/s', $content, $latex)) {
                    return $replace($latex[1], true, $matches[0]);
                }

                if (preg_match('/^\\\\\((.*)\\\\\)$/s', $content, $latex)) {
                    return $replace($latex[1], false, $matches[0]);
                }

                return $replace($content, false, $matches[0]);
            },
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\\\\\[(.+?)\\\\\]/s',
            fn (array $matches): string => $replace($matches[1], true, $matches[0]),
            $html,
        ) ?? $html;

        return preg_replace_callback(
            '/\\\\\((.+?)\\\\\)/s',
            fn (array $matches): string => $replace($matches[1], false, $matches[0]),
            $html,
        ) ?? $html;
    }

    /**
     * @param array<int, array{latex: string, display: bool, original: string, cache_key: string}> $expressions
     * @return array<string, array{png: string, widthEx: float, heightEx: float, verticalAlignEx: float}>
     */
    private function renderExpressions(array $expressions): array
    {
        $rendered = [];
        $missing = [];

        foreach ($expressions as $expression) {
            if (isset($rendered[$expression['cache_key']])) {
                continue;
            }

            $image = Cache::get('pdf-math-png:'.$expression['cache_key']);
            if (is_array($image) && isset($image['png'], $image['widthEx'], $image['heightEx'], $image['verticalAlignEx'])) {
                $rendered[$expression['cache_key']] = $image;
                continue;
            }

            $missing[$expression['cache_key']] = $expression;
        }

        if ($missing === []) {
            return $rendered;
        }

        $process = new Process(
            ['node', base_path('scripts/render-mathjax-svg.mjs')],
            base_path(),
            null,
            json_encode(array_values($missing), JSON_THROW_ON_ERROR),
            20,
        );
        $process->setIdleTimeout(15);
        $process->run();

        if (! $process->isSuccessful()) {
            report(new \RuntimeException('MathJax PDF renderer failed: '.$process->getErrorOutput()));

            return $rendered;
        }

        $svgs = json_decode($process->getOutput(), true);
        if (! is_array($svgs)) {
            return $rendered;
        }

        foreach (array_values($missing) as $index => $expression) {
            $image = $svgs[$index] ?? null;
            if (! is_array($image) || ! isset($image['png'], $image['widthEx'], $image['heightEx'], $image['verticalAlignEx'])) {
                continue;
            }

            $renderedImage = [
                'png' => (string) $image['png'],
                'widthEx' => (float) $image['widthEx'],
                'heightEx' => (float) $image['heightEx'],
                'verticalAlignEx' => (float) $image['verticalAlignEx'],
            ];
            $rendered[$expression['cache_key']] = $renderedImage;
            Cache::put('pdf-math-png:'.$expression['cache_key'], $renderedImage, now()->addWeek());
        }

        return $rendered;
    }

    /** @param array{png: string, widthEx: float, heightEx: float, verticalAlignEx: float} $image */
    private function pngImage(array $image, bool $display): string
    {
        [$width, $height, $verticalAlign] = $this->pdfDimensions($image, $display);
        $element = '<img class="math-equation" alt="Rumus matematika" src="data:image/png;base64,'
            .$image['png'].'" style="width: '.$width.'pt; height: '.$height.'pt; vertical-align: '
            .$verticalAlign.'pt;">';

        return $display ? '<span class="math-equation-display">'.$element.'</span>' : $element;
    }

    /**
     * MathJax writes SVG dimensions in `ex`, a browser-relative unit that
     * Dompdf positions inconsistently. Convert it to explicit PDF points and
     * preserve MathJax's own baseline offset for inline equations.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    /** @param array{widthEx: float, heightEx: float, verticalAlignEx: float} $image */
    private function pdfDimensions(array $image, bool $display): array
    {
        // The source document is 10pt; MathJax's x-height is approximately
        // 0.43em. Keeping the conversion here makes equation placement
        // deterministic in every Dompdf export.
        $pointsPerEx = 4.3;
        $width = max(4.3, $image['widthEx'] * $pointsPerEx);
        $height = max(4.3, $image['heightEx'] * $pointsPerEx);
        $verticalAlign = $display ? 0.0 : $image['verticalAlignEx'] * $pointsPerEx;

        return [round($width, 2), round($height, 2), round($verticalAlign, 2)];
    }
}
