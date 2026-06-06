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

        foreach ($slides as $slideNumber => $text) {
            $parsed = $this->parseSlide($text, $slideNumber);

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

        $slides = [];
        foreach ($slideNames as $slideNumber => $name) {
            $xml = $zip->getFromName($name);
            if (!$xml) {
                continue;
            }

            $slides[$slideNumber] = $this->extractTextFromSlideXml($xml);
        }

        return $slides;
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

    private function parseSlide(string $text, int $slideNumber): ?array
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
            $questionText = "Soal nomor {$questionNumber} (lihat slide PPT).";
        }

        if ($questionText === '') {
            $errors[] = 'Teks soal tidak terbaca.';
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
