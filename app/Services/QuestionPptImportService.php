<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class QuestionPptImportService
{
    public function parse(UploadedFile|string $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getPathname() : $file;
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('File PPT tidak bisa dibuka.');
        }

        $slides = $this->extractSlides($zip);
        $zip->close();

        $questions = [];
        $errors = [];

        foreach ($slides as $slideNumber => $slide) {
            $parsed = $this->parseSlide($slide['text'], $slideNumber, $slide['images']);

            if (!$parsed) {
                continue;
            }

            if (!empty($parsed['errors'])) {
                foreach ($parsed['errors'] as $error) {
                    $errors[] = "Slide {$slideNumber}: {$error}";
                }
            }

            $questions[] = $parsed;
        }

        return [
            'questions' => array_slice($questions, 0, 100),
            'errors' => $errors,
            'total_slides' => count($slides),
        ];
    }

    private function extractSlides(ZipArchive $zip): array
    {
        $slideNames = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^ppt/slides/slide(\d+)\.xml$#', $name, $matches)) {
                $slideNames[(int) $matches[1]] = $name;
            }
        }

        ksort($slideNames);

        $slideMediaTargets = [];
        $mediaUsageCounts = [];
        foreach ($slideNames as $slideNumber => $name) {
            $targets = $this->extractSlideMediaTargets($zip, $name);
            $slideMediaTargets[$slideNumber] = $targets;

            foreach ($targets as $target) {
                $mediaUsageCounts[$target] = ($mediaUsageCounts[$target] ?? 0) + 1;
            }
        }

        $slides = [];
        foreach ($slideNames as $slideNumber => $name) {
            $xml = $zip->getFromName($name);
            if (!$xml) {
                continue;
            }

            $slides[$slideNumber] = [
                'text' => $this->extractTextFromSlideXml($xml),
                'images' => $this->extractSlideImages(
                    $zip,
                    $slideMediaTargets[$slideNumber] ?? [],
                    $mediaUsageCounts
                ),
            ];
        }

        return $slides;
    }

    private function extractSlideMediaTargets(ZipArchive $zip, string $slideName): array
    {
        $relsName = dirname($slideName) . '/_rels/' . basename($slideName) . '.rels';
        $relsXml = $zip->getFromName($relsName);
        if (!$relsXml) {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        $rels = simplexml_load_string($relsXml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($rels === false) {
            return [];
        }

        $targets = [];
        foreach ($rels->Relationship ?? [] as $relationship) {
            $target = (string) $relationship['Target'];
            if (!str_contains($target, 'media/')) {
                continue;
            }

            $targets[] = $this->normalizeZipPath(dirname($slideName) . '/' . $target);
        }

        return array_values(array_unique($targets));
    }

    private function extractSlideImages(ZipArchive $zip, array $targets, array $mediaUsageCounts): array
    {
        $images = [];

        foreach ($targets as $target) {
            if (($mediaUsageCounts[$target] ?? 0) > 3) {
                continue;
            }

            $data = $zip->getFromName($target);
            if (!$data) {
                continue;
            }

            $imageInfo = @getimagesizefromstring($data);
            if (!is_array($imageInfo)) {
                continue;
            }

            [$width, $height] = $imageInfo;
            if ($width < 80 || $height < 80) {
                continue;
            }

            $extension = match ($imageInfo['mime'] ?? '') {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => null,
            };

            if (!$extension) {
                continue;
            }

            $images[] = [
                'url' => 'data:' . $imageInfo['mime'] . ';base64,' . base64_encode($data),
                'width' => $width,
                'height' => $height,
            ];
        }

        return $images;
    }

    private function extractTextFromSlideXml(string $xml): string
    {
        $previous = libxml_use_internal_errors(true);
        $slide = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($slide === false) {
            return '';
        }

        $slide->registerXPathNamespace('p', 'http://schemas.openxmlformats.org/presentationml/2006/main');
        $slide->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');

        $blocks = [];
        foreach ($slide->xpath('//p:sp[.//a:t] | //p:graphicFrame[.//a:t] | //p:cxnSp[.//a:t]') ?: [] as $index => $shape) {
            $offset = $shape->xpath('.//a:xfrm/a:off')[0] ?? null;
            $paragraphs = [];

            foreach ($shape->xpath('.//a:p') ?: [] as $paragraph) {
                $parts = [];
                foreach ($paragraph->xpath('.//a:t') ?: [] as $textNode) {
                    $text = $this->normalizeLine((string) $textNode);
                    if ($text !== '') {
                        $parts[] = $text;
                    }
                }

                $line = $this->normalizeLine(implode('', $parts));
                if ($line !== '') {
                    $paragraphs[] = $line;
                }
            }

            if (empty($paragraphs)) {
                continue;
            }

            $blocks[] = [
                'x' => $offset ? (int) $offset['x'] : 0,
                'y' => $offset ? (int) $offset['y'] : 0,
                'index' => $index,
                'lines' => $paragraphs,
            ];
        }

        usort($blocks, function ($first, $second) {
            return [$first['y'], $first['x'], $first['index']] <=> [$second['y'], $second['x'], $second['index']];
        });

        $lines = [];
        foreach ($blocks as $block) {
            array_push($lines, ...$block['lines']);
        }

        return implode("\n", $lines);
    }

    private function parseSlide(string $text, int $slideNumber, array $images = []): ?array
    {
        $lines = collect(preg_split('/\R/u', $text) ?: [])
            ->map(fn ($line) => $this->normalizeLine($line))
            ->filter()
            ->values()
            ->all();

        if (empty($lines) || !$this->looksLikeQuestionSlide($lines)) {
            return null;
        }

        $questionNumber = null;
        $questionLines = [];
        $optionLines = [];
        $answer = null;
        $explanationLines = [];
        $currentOption = null;
        $answerFound = false;
        $waitingAnswerLetter = false;

        foreach ($lines as $line) {
            if (preg_match('/^soal\s+(?:nomor\s+)?(\d+)/iu', $line, $matches)) {
                $questionNumber = (int) $matches[1];
                continue;
            }

            if ($waitingAnswerLetter && preg_match('/^\(?\s*([A-E])\s*\)?\.?$/iu', $line, $matches)) {
                $answer = strtoupper($matches[1]);
                $answerFound = true;
                $waitingAnswerLetter = false;
                $currentOption = null;
                continue;
            }

            $answerLine = $this->parseAnswerLine($line);
            if (!$answerFound && $answerLine['is_answer']) {
                if ($answerLine['answer']) {
                    $answer = $answerLine['answer'];
                    $answerFound = true;
                    $currentOption = null;

                    if ($answerLine['rest'] !== '') {
                        $explanationLines[] = $this->stripExplanationPrefix($answerLine['rest']);
                    }
                } else {
                    $waitingAnswerLetter = true;
                    $currentOption = null;
                }

                continue;
            }

            if (!$questionNumber && !$currentOption && !$answerFound && preg_match('/^(\d+)\s*[\.\)]\s*(.+)$/u', $line, $matches)) {
                $questionNumber = (int) $matches[1];
                $questionLines[] = trim($matches[2]);
                continue;
            }

            if (!$answerFound && preg_match('/^([A-E])\s*[\.\)]\s*(.*)$/iu', $line, $matches)) {
                $currentOption = strtoupper($matches[1]);
                $optionLines[$currentOption] = [trim($matches[2])];
                continue;
            }

            if ($answerFound) {
                $explanationLines[] = $this->stripExplanationPrefix($line);
                continue;
            }

            if ($currentOption) {
                $optionLines[$currentOption][] = $line;
                continue;
            }

            $questionLines[] = $line;
        }

        $options = [];
        foreach (range('A', 'E') as $letter) {
            if (empty($optionLines[$letter])) {
                continue;
            }

            $options[$letter] = $this->normalizeParagraph(implode(' ', $optionLines[$letter]));
        }

        $questionText = $this->normalizeParagraph(implode(' ', $questionLines));
        $explanation = $this->normalizeParagraph(implode("\n", array_filter($explanationLines)));
        $errors = [];

        if ($questionText === '' && $questionNumber) {
            $questionText = "Soal nomor {$questionNumber} (lihat gambar).";
        }

        if (!empty($images)) {
            $questionText = $this->appendImagesToHtml($questionText, $images);
        }

        if ($questionText === '') {
            $errors[] = 'Teks soal tidak terbaca.';
        }

        if (count($options) < 2 && $answer && !empty($images)) {
            $options = collect(range('A', 'E'))->mapWithKeys(fn ($letter) => [$letter => "Pilihan {$letter}"])->all();
        }

        if (count($options) < 2) {
            $errors[] = 'Minimal 2 opsi jawaban harus terbaca.';
        }

        if (!$answer) {
            $errors[] = 'Jawaban benar tidak terbaca.';
        } elseif (!empty($options) && !array_key_exists($answer, $options)) {
            $errors[] = 'Jawaban benar tidak sesuai opsi.';
        }

        return [
            'slide' => $slideNumber,
            'number' => $questionNumber,
            'question_type' => 'multiple_choice',
            'question_text' => $questionText,
            'options' => $options,
            'correct_answer' => $answer,
            'explanation' => $explanation,
            'default_weight' => 1,
            'images' => $images,
            'errors' => $errors,
        ];
    }

    private function looksLikeQuestionSlide(array $lines): bool
    {
        $text = implode("\n", $lines);

        return (bool) preg_match('/soal\s+(?:nomor\s+)?\d+/iu', $text)
            || (bool) preg_match('/\n[A-E]\s*[\.\)]/iu', "\n" . $text);
    }

    private function normalizeLine(string $line): string
    {
        $line = html_entity_decode($line, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $line = preg_replace('/\x{00A0}/u', ' ', $line) ?? $line;
        $line = preg_replace('/[ \t]+/u', ' ', $line) ?? $line;

        return trim($line);
    }

    private function normalizeParagraph(string $text): string
    {
        $text = Str::of($text)
            ->replaceMatches('/[ \t]+/u', ' ')
            ->replaceMatches('/\s+\n/u', "\n")
            ->replaceMatches('/\n\s+/u', "\n")
            ->trim()
            ->toString();

        return $text;
    }

    private function stripExplanationPrefix(string $line): string
    {
        return trim(preg_replace('/^(pembahasan|penjelasan)\s*:?\s*/iu', '', $line) ?? $line);
    }

    private function appendImagesToHtml(string $text, array $images): string
    {
        $html = trim($text);

        foreach ($images as $index => $image) {
            $url = e($image['url'] ?? '');
            if ($url === '') {
                continue;
            }

            $alt = e('Gambar soal ' . ($index + 1));
            $html .= "\n<p><img src=\"{$url}\" alt=\"{$alt}\" style=\"max-width:100%;height:auto;\"></p>";
        }

        return trim($html);
    }

    private function normalizeZipPath(string $path): string
    {
        $segments = [];

        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private function parseAnswerLine(string $line): array
    {
        if (!preg_match('/^(?:jawaban|kunci(?:\s+jawaban)?)\s*:?\s*(.*)$/iu', $line, $matches)) {
            return [
                'is_answer' => false,
                'answer' => null,
                'rest' => '',
            ];
        }

        $rest = trim($matches[1] ?? '');
        if (preg_match('/^\(?\s*([A-E])\s*\)?(?:\b|\.|\))\s*(.*)$/iu', $rest, $answerMatches)) {
            return [
                'is_answer' => true,
                'answer' => strtoupper($answerMatches[1]),
                'rest' => trim($answerMatches[2] ?? ''),
            ];
        }

        return [
            'is_answer' => true,
            'answer' => null,
            'rest' => $rest,
        ];
    }
}
