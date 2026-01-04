<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\PracticeAnswer;
use App\Models\QuestionBankQuestion;
use App\Models\QuestionBankQuestionOption;
use App\Services\PracticeProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PracticeController extends Controller
{
    public function __construct(private PracticeProgressService $practiceProgress)
    {
        parent::__construct();
    }

    public function index()
    {
        $userId = Auth::id();
        $session = $this->practiceProgress->getOrCreateSession($userId);
        $stats = $this->practiceProgress->getStatsForUser($userId);
        $totalQuestions = $stats['total_questions'] ?? 0;
        $answered = $stats['answered_count'] ?? 0;
        $nextQuestionNumber = $totalQuestions > 0
            ? min($totalQuestions, max(1, $answered + 1))
            : 1;

        return view('user.pages.practice.index', [
            'stats' => $stats,
            'nextQuestionNumber' => $nextQuestionNumber,
            'hasQuestions' => $totalQuestions > 0,
        ]);
    }

    public function play(Request $request, int $number = 1)
    {
        $userId = Auth::id();
        $session = $this->practiceProgress->getOrCreateSession($userId);
        $stats = $this->practiceProgress->getStatsForUser($userId);

        $questions = QuestionBankQuestion::with([
            'options' => fn ($query) => $query->orderBy('position'),
        ])->orderBy('question_bank_id')
            ->orderBy('id')
            ->get();

        if ($questions->isEmpty()) {
            return redirect()->route('user.practice.index')->with('error', 'Belum ada soal latihan.');
        }

        $totalQuestions = $questions->count();
        $number = max(1, min($number, $totalQuestions));
        $question = $questions[$number - 1];

        $answers = PracticeAnswer::where('practice_session_id', $session->id)
            ->get()
            ->keyBy('question_bank_question_id');

        $currentAnswer = $answers->get($question->id);

        $navigation = $questions->map(function ($q, $idx) use ($answers) {
            return [
                'number' => $idx + 1,
                'question_id' => $q->id,
                'answered' => $answers->has($q->id),
            ];
        });

        $initialFeedback = $this->makeFeedbackPayload($question, $currentAnswer);

        return view('user.pages.practice.play', [
            'question' => $question,
            'currentAnswer' => $currentAnswer,
            'number' => $number,
            'totalQuestions' => $totalQuestions,
            'navigation' => $navigation,
            'stats' => $stats,
            'initialFeedback' => $initialFeedback,
        ]);
    }

    public function saveAnswer(Request $request, QuestionBankQuestion $question): JsonResponse
    {
        $userId = Auth::id();
        $session = $this->practiceProgress->getOrCreateSession($userId);

        $existingAnswer = PracticeAnswer::where('practice_session_id', $session->id)
            ->where('question_bank_question_id', $question->id)
            ->first();

        [$attributes, $deleteOldFile] = $this->prepareAnswerPayload($request, $question, $existingAnswer);

        if ($deleteOldFile && $existingAnswer?->answer_file_path && Storage::disk('public')->exists($existingAnswer->answer_file_path)) {
            Storage::disk('public')->delete($existingAnswer->answer_file_path);
        }

        $isNewAnswer = !$existingAnswer;
        $answer = PracticeAnswer::updateOrCreate(
            [
                'practice_session_id' => $session->id,
                'question_bank_question_id' => $question->id,
            ],
            array_merge($attributes, [
                'question_type' => $question->question_type,
                'answered_at' => now(),
            ])
        );

        $session = $this->practiceProgress->incrementAnsweredCount($session, $isNewAnswer);
        $stats = $this->practiceProgress->getStatsForUser($userId);

        return response()->json([
            'success' => true,
            'message' => 'Jawaban disimpan.',
            'answer_id' => $answer->id,
            'answered_count' => $stats['answered_count'],
            'total_questions' => $stats['total_questions'],
            'progress_percent' => $stats['progress_percent'],
            'unlocked_tryout_ids' => $stats['unlocked_tryout_ids'],
            'unlocked_count' => $stats['unlocked_count'],
            'tryout_count' => $stats['tryout_count'],
            'next_unlock_remaining' => $stats['next_unlock_remaining'],
            'threshold_per_tryout' => $stats['threshold_per_tryout'],
            'feedback' => $this->makeFeedbackPayload($question, $answer),
        ]);
    }

    private function prepareAnswerPayload(Request $request, QuestionBankQuestion $question, ?PracticeAnswer $existing): array
    {
        return match ($question->question_type) {
            'matching' => $this->handleMatchingAnswer($request, $question),
            'essay', 'short_answer' => $this->handleShortAnswer($request, $question),
            'audio' => $this->handleAudioAnswer($request, $question, $existing),
            default => $this->handleMultipleChoiceAnswer($request, $question),
        };
    }

    private function handleMultipleChoiceAnswer(Request $request, QuestionBankQuestion $question): array
    {
        $request->validate([
            'option_id' => 'required|exists:question_bank_question_options,id',
        ], [
            'option_id.required' => 'Silakan pilih jawaban terlebih dahulu.',
        ]);

        $option = QuestionBankQuestionOption::where('question_bank_question_id', $question->id)
            ->where('id', $request->integer('option_id'))
            ->first();

        if (!$option) {
            throw ValidationException::withMessages([
                'option_id' => 'Pilihan jawaban tidak valid.',
            ]);
        }

        return [[
            'question_bank_question_option_id' => $option->id,
            'answer_text' => null,
            'answer_json' => null,
            'answer_file_path' => null,
            'is_correct' => $option->is_correct,
        ], false];
    }

    private function handleShortAnswer(Request $request, QuestionBankQuestion $question): array
    {
        $request->validate([
            'answer_text' => 'required|string',
        ], [
            'answer_text.required' => 'Jawaban belum diisi.',
        ]);

        $answerText = trim($request->input('answer_text', ''));
        $metadata = is_array($question->metadata) ? $question->metadata : [];
        $shortMeta = isset($metadata['short_answer']) && is_array($metadata['short_answer']) ? $metadata['short_answer'] : [];
        $expectedAnswers = isset($shortMeta['expected_answers']) && is_array($shortMeta['expected_answers'])
            ? $shortMeta['expected_answers']
            : [];
        $caseSensitive = $shortMeta['case_sensitive'] ?? false;

        $isCorrect = false;
        foreach ($expectedAnswers as $expected) {
            $expectedValue = trim((string) $expected);
            if ($expectedValue === '') {
                continue;
            }

            $candidate = $caseSensitive ? $answerText : mb_strtolower($answerText);
            $target = $caseSensitive ? $expectedValue : mb_strtolower($expectedValue);

            if ($candidate === $target) {
                $isCorrect = true;
                break;
            }
        }

        $answerJson = [
            'expected_answers' => $expectedAnswers,
            'case_sensitive' => $caseSensitive,
            'manual_review' => $question->question_type === 'essay' || empty($expectedAnswers),
        ];

        return [[
            'question_bank_question_option_id' => null,
            'answer_text' => $answerText,
            'answer_json' => $answerJson,
            'answer_file_path' => null,
            'is_correct' => $isCorrect,
        ], false];
    }

    private function handleMatchingAnswer(Request $request, QuestionBankQuestion $question): array
    {
        $rawInput = $request->input('matching_answers');
        if (is_string($rawInput)) {
            $decoded = json_decode($rawInput, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $rawInput = $decoded;
            }
        }

        if (!is_array($rawInput)) {
            throw ValidationException::withMessages([
                'matching_answers' => 'Format jawaban tidak valid.',
            ]);
        }

        $metadata = is_array($question->metadata) ? $question->metadata : [];
        $pairs = isset($metadata['matching_pairs']) && is_array($metadata['matching_pairs'])
            ? $metadata['matching_pairs']
            : [];

        if (empty($pairs)) {
            throw ValidationException::withMessages([
                'matching_answers' => 'Soal ini belum memiliki pasangan kunci.',
            ]);
        }

        $correctMap = [];
        foreach ($pairs as $pair) {
            $left = trim((string) ($pair['left'] ?? ''));
            $right = trim((string) ($pair['right'] ?? ''));

            if ($left === '' || $right === '') {
                continue;
            }

            $correctMap[$left] = $right;
        }

        $matches = [];
        $correctCount = 0;
        foreach ($correctMap as $left => $right) {
            if (!array_key_exists($left, $rawInput)) {
                throw ValidationException::withMessages([
                    'matching_answers' => 'Harap isi semua pasangan.',
                ]);
            }

            $answerValue = trim((string) $rawInput[$left]);
            if ($answerValue === '') {
                throw ValidationException::withMessages([
                    'matching_answers' => 'Semua pasangan harus diisi.',
                ]);
            }

            $matches[$left] = $answerValue;

            if ($answerValue === $right) {
                $correctCount++;
            }
        }

        $isCorrect = count($correctMap) > 0 && $correctCount === count($correctMap);

        return [[
            'question_bank_question_option_id' => null,
            'answer_text' => null,
            'answer_json' => ['matches' => $matches],
            'answer_file_path' => null,
            'is_correct' => $isCorrect,
        ], false];
    }

    private function handleAudioAnswer(Request $request, QuestionBankQuestion $question, ?PracticeAnswer $existing): array
    {
        $request->validate([
            'answer_audio' => 'required|file|mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/m4a,audio/x-m4a|max:10240',
        ], [
            'answer_audio.required' => 'Silakan unggah rekaman jawaban.',
        ]);

        $path = $request->file('answer_audio')->store('practice/audio', 'public');

        $meta = [
            'original_name' => $request->file('answer_audio')->getClientOriginalName(),
            'size' => $request->file('answer_audio')->getSize(),
        ];

        return [[
            'question_bank_question_option_id' => null,
            'answer_text' => null,
            'answer_json' => $meta,
            'answer_file_path' => $path,
            'is_correct' => null,
        ], true];
    }

    private function makeFeedbackPayload(QuestionBankQuestion $question, ?PracticeAnswer $answer = null): ?array
    {
        $correctAnswerHtml = $this->buildCorrectAnswerHtml($question);
        $explanationHtml = $question->explanation ?: null;

        if (!$answer && !$correctAnswerHtml && !$explanationHtml) {
            return null;
        }

        return [
            'is_correct' => $answer?->is_correct,
            'correct_answer_html' => $correctAnswerHtml,
            'explanation_html' => $explanationHtml,
        ];
    }

    private function buildCorrectAnswerHtml(QuestionBankQuestion $question): ?string
    {
        $metadata = is_array($question->metadata) ? $question->metadata : [];

        if (in_array($question->question_type, ['multiple_choice', 'true_false'], true)) {
            $question->loadMissing('options');
            $correctOptions = $question->options->where('is_correct', true);

            if ($correctOptions->isEmpty()) {
                return null;
            }

            return $correctOptions->map(function (QuestionBankQuestionOption $option) {
                return '<div class="py-1">' . ($option->option_text ?? '') . '</div>';
            })->implode('');
        }

        if ($question->question_type === 'short_answer') {
            $shortMeta = isset($metadata['short_answer']) && is_array($metadata['short_answer'])
                ? $metadata['short_answer']
                : [];
            $expectedAnswers = isset($shortMeta['expected_answers']) && is_array($shortMeta['expected_answers'])
                ? $shortMeta['expected_answers']
                : [];

            $items = array_filter(array_map(function ($value) {
                $text = trim((string) $value);
                if ($text === '') {
                    return null;
                }

                return '<li>' . e($text) . '</li>';
            }, $expectedAnswers));

            if (empty($items)) {
                return null;
            }

            return '<ul class="list-disc pl-5 space-y-1">' . implode('', $items) . '</ul>';
        }

        if ($question->question_type === 'matching') {
            $pairs = isset($metadata['matching_pairs']) && is_array($metadata['matching_pairs'])
                ? $metadata['matching_pairs']
                : [];

            $items = [];
            foreach ($pairs as $pair) {
                $left = trim((string) ($pair['left'] ?? ''));
                $right = trim((string) ($pair['right'] ?? ''));

                if ($left === '' || $right === '') {
                    continue;
                }

                $items[] = '<li><span class="font-semibold text-gray-800">' . e($left) . '</span> → <span class="text-gray-700">' . e($right) . '</span></li>';
            }

            if (empty($items)) {
                return null;
            }

            return '<ul class="space-y-1 pl-5 list-disc">' . implode('', $items) . '</ul>';
        }

        return null;
    }
}
