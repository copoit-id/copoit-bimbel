<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEssayCorrection;
use App\Models\EssayCorrectionJob;
use App\Models\Package;
use App\Models\ProctoringSnapshot;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use App\Models\Question;
use App\Models\FeedbackQuestion;
use App\Models\FeedbackSubmission;
use App\Models\TryoutUserTimeAdjustment;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use App\Models\UserPackageAcces;
use App\Models\UserTryoutAccess;
use App\Models\QuestionOption;
use App\Services\PlanQuotaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\ToeflScoringService;
use App\Services\ActivityLogger;

class TryoutController extends Controller
{
    public function __construct()
    {
        // Set timezone untuk semua method dalam controller ini
        Carbon::setLocale('id');
        date_default_timezone_set('Asia/Jakarta');
    }

    private function tryoutAvailabilityError(Tryout $tryout, Carbon $now): ?string
    {
        if (! $tryout->is_active) {
            return 'Tryout tidak aktif.';
        }

        if ($tryout->start_date && $tryout->start_date->gt($now)) {
            return 'Tryout belum dimulai.';
        }

        if ($tryout->end_date && $tryout->end_date->lt($now)) {
            return 'Tryout sudah berakhir.';
        }

        return null;
    }

    private function getSubtestIndex(array $subtests, array $currentSubtest): int
    {
        foreach ($subtests as $index => $info) {
            if (($info['tryout_detail_id'] ?? null) === ($currentSubtest['tryout_detail_id'] ?? null)) {
                return $index;
            }
        }

        return 0;
    }

    private function normalizeQuestionType(?string $type): string
    {
        $resolved = $type ?: 'multiple_choice';
        return $resolved === 'multiple_select' ? 'multiple_answer' : $resolved;
    }

    private function maybeShowSubtestBreak(
        Tryout $tryout,
        ?Package $package,
        array $currentSubtest,
        int $currentSubtestIndex,
        string $attemptToken,
        int $questionNumber,
        array $subtestInfo
    ) {
        $breakSeconds = (int) ($tryout->section_break_duration ?? 0);
        if ($breakSeconds <= 0 || $currentSubtestIndex <= 0) {
            return null;
        }

        if ($questionNumber !== ($currentSubtest['start_number'] ?? $questionNumber)) {
            return null;
        }

        $sessionPrefix = sprintf(
            'tryout_break_%s_%s',
            $attemptToken,
            $currentSubtest['tryout_detail_id'] ?? 'subtest'
        );

        if (session($sessionPrefix . '_done')) {
            return null;
        }

        $now = Carbon::now('Asia/Jakarta');
        $breakUntilIso = session($sessionPrefix . '_until');

        if (!$breakUntilIso) {
            $breakUntil = $now->copy()->addSeconds($breakSeconds);
            session([$sessionPrefix . '_until' => $breakUntil->toIso8601String()]);
        } else {
            $breakUntil = Carbon::parse($breakUntilIso, 'Asia/Jakarta');
        }

        if ($now->lt($breakUntil)) {
            $remainingSeconds = max(1, $now->diffInSeconds($breakUntil));

            return view('user.pages.tryout.break', [
                'package' => $package,
                'tryout' => $tryout,
                'subtest' => $currentSubtest,
                'subtestIndex' => $currentSubtestIndex + 1,
                'totalSubtests' => count($subtestInfo),
                'countdownSeconds' => $remainingSeconds,
                'continueUrl' => route('user.tryout.index', [
                    $package ? $package->package_id : 'free',
                    $tryout->tryout_id,
                    $questionNumber
                ]),
            ]);
        }

        session()->forget($sessionPrefix . '_until');
        session([$sessionPrefix . '_done' => true]);

        return null;
    }

    private function processAnswerByType(array $data, Question $question, ?UserAnswerDetail $existingDetail): array
    {
        $type = $this->normalizeQuestionType($question->question_type ?? 'multiple_choice');

        if ($type === 'true_false') {
            $type = 'multiple_choice';
        }

        switch ($type) {
            case 'multiple_answer':
                return $this->handleMultipleAnswer($data, $question);
            case 'multiple_true_false':
                return $this->handleMultipleTrueFalseAnswer($data, $question);
            case 'matching':
                return $this->handleMatchingAnswer($data, $question);
            case 'short_answer':
            case 'essay':
                return $this->handleShortAnswer($data, $question);
            case 'audio':
                return $this->handleAudioAnswer($data, $question, $existingDetail);
            default:
                return $this->handleMultipleChoiceAnswer($data, $question);
        }
    }

    private function handleMultipleAnswer(array $data, Question $question): array
    {
        Validator::make($data, [
            'option_ids' => 'required|array|min:1',
            'option_ids.*' => 'required|exists:question_options,question_option_id'
        ])->validate();

        $selectedIds = collect($data['option_ids'])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $validIds = $question->questionOptions()
            ->whereIn('question_option_id', $selectedIds)
            ->pluck('question_option_id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        sort($selectedIds);
        sort($validIds);
        if ($selectedIds !== $validIds) {
            throw ValidationException::withMessages([
                'option_ids' => 'Pilihan jawaban tidak valid untuk soal ini.'
            ]);
        }

        $correctIds = $question->questionOptions()
            ->where('is_correct', true)
            ->pluck('question_option_id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();
        sort($correctIds);

        $multipleAnswerMeta = is_array($question->metadata) ? ($question->metadata['multiple_answer'] ?? []) : [];
        $scoreCorrect = (float) ($multipleAnswerMeta['score_correct'] ?? 1);
        $scoreWrong = (float) ($multipleAnswerMeta['score_wrong'] ?? 0);
        $scoringMode = in_array(($multipleAnswerMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
            ? $multipleAnswerMeta['scoring_mode']
            : 'fullscore';

        $matchedCorrect = count(array_intersect($selectedIds, $correctIds));
        $isCorrect = $selectedIds === $correctIds;
        $totalCorrect = max(1, count($correctIds));
        $wrongSelected = max(0, count($selectedIds) - $matchedCorrect);
        $missedCorrect = max(0, $totalCorrect - $matchedCorrect);
        $wrongCount = $missedCorrect + $wrongSelected;
        $fullScore = $scoreCorrect;
        $scoreObtained = 0.0;
        if ($scoringMode === 'partial') {
            $scoreObtained = $matchedCorrect > 0
                ? ($matchedCorrect / $totalCorrect) * $fullScore
                : $scoreWrong;
        } else {
            $scoreObtained = $isCorrect ? $scoreCorrect : $scoreWrong;
        }
        $scoreObtained = max(0, $scoreObtained);
        $ratio = $totalCorrect > 0 ? ($matchedCorrect / $totalCorrect) : 0;

        return [
            'detail' => [
                'question_option_id' => null,
                'answer_text' => null,
                'answer_json' => [
                    'selected_option_ids' => $selectedIds,
                    'correct_matched' => $matchedCorrect,
                    'correct_total' => $totalCorrect,
                    'wrong_selected' => $wrongSelected,
                    'wrong_count' => $wrongCount,
                    'scoring_mode' => $scoringMode,
                    'score_ratio' => $ratio,
                    'score_obtained' => $scoreObtained,
                ],
                'answer_file_path' => null,
                'is_correct' => $isCorrect,
            ],
            'response' => [
                'option_ids' => $selectedIds,
                'is_correct' => $isCorrect,
                'score_obtained' => $scoreObtained,
            ],
            'delete_file' => false,
        ];
    }

    private function resolveMultipleAnswerAwardedScore(Question $question, UserAnswerDetail $detail): float
    {
        $defaultWeight = (float) ($question->default_weight ?? 1);
        $maxWeight = $defaultWeight > 0 ? $defaultWeight : 1;
        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $selectedIds = collect($meta['selected_option_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (!empty($selectedIds)) {
            $correctIds = $question->questionOptions()
                ->where('is_correct', true)
                ->pluck('question_option_id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            sort($selectedIds);
            sort($correctIds);
            $matchedCorrect = count(array_intersect($selectedIds, $correctIds));
            $wrongSelected = max(0, count($selectedIds) - $matchedCorrect);
            $multipleAnswerMeta = is_array($question->metadata) ? ($question->metadata['multiple_answer'] ?? []) : [];
            $scoreCorrect = (float) ($multipleAnswerMeta['score_correct'] ?? (($maxWeight > 0 && count($correctIds) > 0) ? ($maxWeight / count($correctIds)) : 1));
            $scoreWrong = (float) ($multipleAnswerMeta['score_wrong'] ?? 0);
            $scoringMode = in_array(($multipleAnswerMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
                ? $multipleAnswerMeta['scoring_mode']
                : 'fullscore';
            $totalCorrectCount = max(1, count($correctIds));
            $missedCorrect = max(0, $totalCorrectCount - $matchedCorrect);
            $wrongCount = $missedCorrect + $wrongSelected;
            $isExactCorrect = ($selectedIds === $correctIds);
            $fullScore = $scoreCorrect;
            $score = 0.0;
            if ($scoringMode === 'partial') {
                $score = $matchedCorrect > 0
                    ? ($matchedCorrect / $totalCorrectCount) * $fullScore
                    : $scoreWrong;
            } else {
                $score = $isExactCorrect ? $scoreCorrect : $scoreWrong;
            }

            return max(0, $score);
        }

        $storedScore = $meta['score_obtained'] ?? null;

        if (is_numeric($storedScore)) {
            return max(0, min((float) $storedScore, $maxWeight));
        }

        return $detail->is_correct ? $maxWeight : 0;
    }

    private function handleMultipleChoiceAnswer(array $data, Question $question): array
    {
        Validator::make($data, [
            'option_id' => 'required|exists:question_options,question_option_id'
        ])->validate();

        $selectedOption = $question->questionOptions()
            ->where('question_option_id', $data['option_id'])
            ->first();

        if (!$selectedOption) {
            throw ValidationException::withMessages([
                'option_id' => 'Pilihan jawaban tidak valid untuk soal ini.'
            ]);
        }

        $isCorrect = $this->determineCorrectAnswer($question, $selectedOption);

        return [
            'detail' => [
                'question_option_id' => $selectedOption->question_option_id,
                'answer_text' => null,
                'answer_json' => null,
                'answer_file_path' => null,
                'is_correct' => $isCorrect,
            ],
            'response' => [
                'option_id' => $selectedOption->question_option_id,
                'is_correct' => $isCorrect,
            ],
            'delete_file' => false,
        ];
    }

    private function handleShortAnswer(array $data, Question $question): array
    {
        Validator::make($data, [
            'answer_text' => 'required|string'
        ])->validate();

        $answerText = trim((string) ($data['answer_text'] ?? ''));
        $metadata = is_array($question->metadata) ? $question->metadata : [];
        $shortMeta = isset($metadata['short_answer']) && is_array($metadata['short_answer']) ? $metadata['short_answer'] : [];

        $expectedAnswers = isset($shortMeta['expected_answers']) && is_array($shortMeta['expected_answers']) ? $shortMeta['expected_answers'] : [];
        $caseSensitive = $shortMeta['case_sensitive'] ?? false;
        $evaluationMode = $shortMeta['evaluation_mode'] ?? null;
        $manualReview = $shortMeta['manual_review'] ?? false;

        $isCorrect = false;

        // Untuk essay, SELALU pending review (baik mode auto maupun manual)
        // Mode auto = AI yang akan koreksi via webhook
        // Mode manual = Admin yang akan koreksi
        if ($question->question_type === 'essay') {
            $manualReview = true; // Essay selalu pending review
        } elseif (empty($expectedAnswers)) {
            // Short answer tanpa expected answers = manual review
            $manualReview = true;
        }

        // Hanya evaluate otomatis untuk short answer (bukan essay)
        // Essay selalu pending untuk AI atau manual review
        if (!$manualReview && !empty($expectedAnswers) && $question->question_type !== 'essay') {
            foreach ($expectedAnswers as $expected) {
                $expectedValue = trim((string) $expected);
                $expectedComparable = $caseSensitive ? $expectedValue : mb_strtolower($expectedValue);
                $userComparable = $caseSensitive ? $answerText : mb_strtolower($answerText);
                $isCorrect = $userComparable === $expectedComparable;

                if ($isCorrect) {
                    break;
                }
            }
        }

        // Untuk essay, SELALU pending_review = true dan is_correct = false (placeholder)
        // Karena essay akan dikoreksi oleh AI (mode auto) atau admin (mode manual)
        if ($question->question_type === 'essay') {
            $finalIsCorrect = false; // Placeholder, akan diupdate oleh AI/admin
            $manualReview = true;    // Selalu pending untuk essay
            $isCorrect = false;      // Jangan tampilkan sebagai benar sebelum dikoreksi
        } else {
            $finalIsCorrect = $isCorrect;
        }
        
        $answerJson = [
            'pending_review' => $manualReview,
            'case_sensitive' => $caseSensitive,
            'evaluation_mode' => $evaluationMode,
            'expected_answers' => $expectedAnswers,
        ];

        // DEBUG LOG
        \Log::info('Essay answer saved', [
            'question_id' => $question->question_id,
            'question_type' => $question->question_type,
            'manual_review' => $manualReview,
            'evaluation_mode' => $evaluationMode,
            'expected_answers_count' => count($expectedAnswers),
            'answer_json' => $answerJson,
        ]);

        return [
            'detail' => [
                'question_option_id' => null,
                'answer_text' => $answerText,
                'answer_json' => $answerJson,
                'answer_file_path' => null,
                'is_correct' => $finalIsCorrect,
            ],
            'response' => [
                'is_correct' => $isCorrect,
                'manual_review' => $manualReview,
            ],
            'delete_file' => false,
        ];
    }

    private function matchesEssayAnswer(string $userAnswer, string $expectedAnswer): bool
    {
        $userNormalized = $this->normalizeEssayAnswer($userAnswer);
        $expectedNormalized = $this->normalizeEssayAnswer($expectedAnswer);

        if (is_numeric($userNormalized) && is_numeric($expectedNormalized)) {
            return (float) $userNormalized == (float) $expectedNormalized;
        }

        return $userNormalized === $expectedNormalized;
    }

    private function normalizeEssayAnswer(string $value): string
    {
        $normalized = trim($value);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return mb_strtolower($normalized);
    }

    private function handleMatchingAnswer(array $data, Question $question): array
    {
        $input = $data['matching_answers'] ?? null;

        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input = $decoded;
            }
        }

        if (!is_array($input)) {
            throw ValidationException::withMessages([
                'matching_answers' => 'Jawaban pencocokan tidak valid.'
            ]);
        }

        $metadata = is_array($question->metadata) ? $question->metadata : [];
        $pairs = isset($metadata['matching_pairs']) && is_array($metadata['matching_pairs']) ? $metadata['matching_pairs'] : [];

        if (empty($pairs)) {
            throw ValidationException::withMessages([
                'matching_answers' => 'Soal pencocokan belum memiliki pasangan jawaban.'
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

        $selected = [];
        $correctCount = 0;

        foreach ($correctMap as $left => $right) {
            if (!array_key_exists($left, $input)) {
                throw ValidationException::withMessages([
                    'matching_answers' => 'Harap lengkapi jawaban untuk semua pasangan.'
                ]);
            }

            $chosen = trim((string) $input[$left]);
            $selected[$left] = $chosen;

            if ($chosen !== '' && $chosen === $right) {
                $correctCount++;
            }
        }

        $total = count($correctMap);
        $isCorrect = $total > 0 ? $correctCount === $total : false;
        $wrongCount = max(0, $total - $correctCount);

        $questionMeta = is_array($question->metadata) ? $question->metadata : [];
        $matchingMeta = is_array($questionMeta['matching_scores'] ?? null) ? $questionMeta['matching_scores'] : [];
        $scoreCorrect = (float) ($matchingMeta['score_correct'] ?? 1);
        $scoreWrong = (float) ($matchingMeta['score_wrong'] ?? 0);
        $scoringMode = in_array(($matchingMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
            ? $matchingMeta['scoring_mode']
            : 'fullscore';
        $fullScore = max(0, $scoreCorrect);
        $scoreObtained = 0.0;
        if ($scoringMode === 'partial') {
            $scoreObtained = $correctCount > 0
                ? ($correctCount / max(1, $total)) * $fullScore
                : $scoreWrong;
        } else {
            $scoreObtained = $isCorrect ? $fullScore : $scoreWrong;
        }
        $scoreObtained = max(0, $scoreObtained);

        return [
            'detail' => [
                'question_option_id' => null,
                'answer_text' => null,
                'answer_json' => [
                    'matches' => $selected,
                    'summary' => [
                        'correct' => $correctCount,
                        'total' => $total,
                        'wrong' => $wrongCount,
                    ],
                    'scoring_mode' => $scoringMode,
                    'score_obtained' => $scoreObtained,
                ],
                'answer_file_path' => null,
                'is_correct' => $isCorrect,
            ],
            'response' => [
                'is_correct' => $isCorrect,
                'correct' => $correctCount,
                'total' => $total,
                'score_obtained' => $scoreObtained,
            ],
            'delete_file' => false,
        ];
    }

    private function resolveMatchingAwardedScore(Question $question, UserAnswerDetail $detail): float
    {
        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $questionMeta = is_array($question->metadata) ? $question->metadata : [];
        $matchingMeta = is_array($questionMeta['matching_scores'] ?? null) ? $questionMeta['matching_scores'] : [];
        $scoreCorrect = (float) ($matchingMeta['score_correct'] ?? 1);
        $scoreWrong = (float) ($matchingMeta['score_wrong'] ?? 0);
        $scoringMode = in_array(($matchingMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
            ? $matchingMeta['scoring_mode']
            : 'fullscore';

        $summary = is_array($meta['summary'] ?? null) ? $meta['summary'] : [];
        $correctCount = (int) ($summary['correct'] ?? 0);
        $totalCount = (int) ($summary['total'] ?? 0);
        $wrongCount = max(0, $totalCount - $correctCount);

        if ($totalCount > 0) {
            $fullScore = max(0, $scoreCorrect);
            $isExactCorrect = ($correctCount === $totalCount);
            $score = 0.0;
            if ($scoringMode === 'partial') {
                $score = $correctCount > 0
                    ? ($correctCount / $totalCount) * $fullScore
                    : $scoreWrong;
            } else {
                $score = $isExactCorrect ? $fullScore : $scoreWrong;
            }

            return max(0, $score);
        }

        $storedScore = $meta['score_obtained'] ?? null;
        if (is_numeric($storedScore)) {
            return max(0, (float) $storedScore);
        }

        $weight = (float) ($question->default_weight ?? 1);
        return $detail->is_correct ? max(0, $weight) : 0;
    }

    private function handleMultipleTrueFalseAnswer(array $data, Question $question): array
    {
        $input = $data['mtf_answers'] ?? null;

        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input = $decoded;
            }
        }

        if (!is_array($input)) {
            $input = [];
        }

        $meta = is_array($question->metadata) ? ($question->metadata['multiple_true_false'] ?? []) : [];
        $statements = isset($meta['statements']) && is_array($meta['statements']) ? $meta['statements'] : [];
        if (empty($statements)) {
            throw ValidationException::withMessages([
                'mtf_answers' => 'Soal multiple true/false belum memiliki daftar pernyataan.',
            ]);
        }

        $normalizedAnswers = [];
        $correctCount = 0;
        $total = 0;

        foreach ($statements as $index => $statement) {
            $statementId = trim((string) ($statement['id'] ?? 'stmt_' . ($index + 1)));
            $correct = in_array(($statement['correct'] ?? null), ['true', 'false'], true)
                ? (string) $statement['correct']
                : 'true';

            if (!array_key_exists($statementId, $input)) {
                $normalizedAnswers[$statementId] = '';
                $total++;
                continue;
            }

            $selected = strtolower(trim((string) $input[$statementId]));
            if (!in_array($selected, ['true', 'false'], true)) {
                $normalizedAnswers[$statementId] = '';
                $total++;
                continue;
            }

            $normalizedAnswers[$statementId] = $selected;
            $total++;
            if ($selected === $correct) {
                $correctCount++;
            }
        }

        $isCorrect = $total > 0 ? $correctCount === $total : false;
        $wrongCount = max(0, $total - $correctCount);
        $scoreCorrect = (float) ($meta['score_correct'] ?? 1);
        $scoreWrong = (float) ($meta['score_wrong'] ?? 0);
        $scoringMode = in_array(($meta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
            ? $meta['scoring_mode']
            : 'fullscore';

        $fullScore = max(0, $scoreCorrect);
        $scoreObtained = 0.0;
        if ($scoringMode === 'partial') {
            $scoreObtained = $correctCount > 0
                ? ($correctCount / max(1, $total)) * $fullScore
                : $scoreWrong;
        } else {
            $scoreObtained = $isCorrect ? $fullScore : $scoreWrong;
        }
        $scoreObtained = max(0, $scoreObtained);

        return [
            'detail' => [
                'question_option_id' => null,
                'answer_text' => null,
                'answer_json' => [
                    'answers' => $normalizedAnswers,
                    'summary' => [
                        'correct' => $correctCount,
                        'total' => $total,
                        'wrong' => $wrongCount,
                    ],
                    'scoring_mode' => $scoringMode,
                    'score_obtained' => $scoreObtained,
                ],
                'answer_file_path' => null,
                'is_correct' => $isCorrect,
            ],
            'response' => [
                'is_correct' => $isCorrect,
                'correct' => $correctCount,
                'total' => $total,
                'score_obtained' => $scoreObtained,
            ],
            'delete_file' => false,
        ];
    }

    private function resolveMultipleTrueFalseAwardedScore(Question $question, UserAnswerDetail $detail): float
    {
        $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $questionMeta = is_array($question->metadata) ? ($question->metadata['multiple_true_false'] ?? []) : [];
        $scoreCorrect = (float) ($questionMeta['score_correct'] ?? ($question->default_weight ?? 1));
        $scoreWrong = (float) ($questionMeta['score_wrong'] ?? 0);
        $scoringMode = in_array(($questionMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
            ? $questionMeta['scoring_mode']
            : 'fullscore';

        $summary = is_array($meta['summary'] ?? null) ? $meta['summary'] : [];
        $correctCount = (int) ($summary['correct'] ?? 0);
        $totalCount = (int) ($summary['total'] ?? 0);

        if ($totalCount > 0) {
            $fullScore = max(0, $scoreCorrect);
            $isExactCorrect = $correctCount === $totalCount;
            if ($scoringMode === 'partial') {
                return max(0, $correctCount > 0 ? ($correctCount / $totalCount) * $fullScore : $scoreWrong);
            }

            return max(0, $isExactCorrect ? $fullScore : $scoreWrong);
        }

        $storedScore = $meta['score_obtained'] ?? null;
        if (is_numeric($storedScore)) {
            return max(0, (float) $storedScore);
        }

        $weight = (float) ($question->default_weight ?? 1);
        return $detail->is_correct ? max(0, $weight) : 0;
    }

    private function handleAudioAnswer(array $data, Question $question, ?UserAnswerDetail $existingDetail): array
    {
        $metadata = is_array($question->metadata) ? $question->metadata : [];
        $audioMeta = isset($metadata['audio_answer']) && is_array($metadata['audio_answer']) ? $metadata['audio_answer'] : [];

        $maxSizeMb = isset($audioMeta['max_size']) ? (int) $audioMeta['max_size'] : 15;
        $allowedExtensions = ['mp3', 'wav', 'm4a'];

        $file = $data['answer_audio_file'] ?? null;

        if ($file) {
            Validator::make([
                'answer_audio' => $file,
            ], [
                'answer_audio' => 'required|file|mimes:' . implode(',', $allowedExtensions) . '|max:' . ($maxSizeMb * 1024),
            ], [], [
                'answer_audio' => 'jawaban audio'
            ])->validate();

            $storagePath = $file->store('user_answers/audio/' . Auth::id(), 'public');

            $answerJson = [
                'pending_review' => true,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ];

            return [
                'detail' => [
                    'question_option_id' => null,
                    'answer_text' => null,
                    'answer_json' => $answerJson,
                    'answer_file_path' => $storagePath,
                    'is_correct' => false,
                ],
                'response' => [
                    'file_url' => Storage::disk('public')->url($storagePath),
                    'manual_review' => true,
                ],
                'delete_file' => true,
            ];
        }

        $base64Data = $data['answer_audio_base64'] ?? null;
        $fileName = $data['answer_audio_name'] ?? ('audio_' . Str::uuid()->toString() . '.mp3');
        $mimeType = $data['answer_audio_mime'] ?? 'audio/mpeg';

        if (!$base64Data) {
            throw ValidationException::withMessages([
                'answer_audio' => 'Jawaban audio tidak ditemukan.'
            ]);
        }

        if (preg_match('/^data:(.*?);base64,(.*)$/', $base64Data, $matches)) {
            $mimeType = $matches[1];
            $base64Data = $matches[2];
        }

        $extension = explode('/', $mimeType)[1] ?? 'mp3';
        if (!in_array(strtolower($extension), $allowedExtensions)) {
            throw ValidationException::withMessages([
                'answer_audio' => 'Format audio tidak didukung.'
            ]);
        }

        $decoded = base64_decode($base64Data);
        if ($decoded === false) {
            throw ValidationException::withMessages([
                'answer_audio' => 'Data audio tidak valid.'
            ]);
        }

        $maxBytes = $maxSizeMb * 1024 * 1024;
        if (strlen($decoded) > $maxBytes) {
            throw ValidationException::withMessages([
                'answer_audio' => 'Ukuran audio melebihi batas ' . $maxSizeMb . ' MB.'
            ]);
        }

        $storagePath = 'user_answers/audio/' . Auth::id() . '/' . Str::uuid()->toString() . '.' . $extension;
        Storage::disk('public')->put($storagePath, $decoded);

        $answerJson = [
            'pending_review' => true,
            'original_name' => $fileName,
            'size' => strlen($decoded),
        ];

        return [
            'detail' => [
                'question_option_id' => null,
                'answer_text' => null,
                'answer_json' => $answerJson,
                'answer_file_path' => $storagePath,
                'is_correct' => false,
            ],
            'response' => [
                'file_url' => Storage::disk('public')->url($storagePath),
                'manual_review' => true,
            ],
            'delete_file' => true,
        ];
    }

    public function indexLobby($id_package, $id_tryout)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Silakan login terlebih dahulu untuk mengakses tryout.');
        }
        
        if ($id_package === 'free') {
            // Free route also handles direct tryout access granted by admin.
            $now = Carbon::now('Asia/Jakarta');
            $hasDirectAccess = $this->hasDirectTryoutAccess((int) $id_tryout, Auth::id());
            $tryoutQuery = Tryout::where('tryout_id', $id_tryout)
                ->where('is_active', true);

            if (!$hasDirectAccess) {
                $tryoutQuery
                    ->where(function ($query) use ($now) {
                        $query->whereNull('start_date')
                            ->orWhere('start_date', '<=', $now);
                    })
                    ->where(function ($query) use ($now) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', $now);
                    });
            }

            $tryout = $tryoutQuery->firstOrFail();
            $package = null;
        } else {
            $hasDirectAccess = false;
            $package = Package::findOrFail($id_package);
            $tryout = Tryout::findOrFail($id_tryout);

            // Check if user has access to package
            $hasAccess = UserPackageAcces::where('user_id', Auth::id())
                ->where('package_id', $id_package)
                ->where('status', 'active')
                ->where(function ($query) {
                    $now = Carbon::now('Asia/Jakarta');
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', $now);
                })
                ->exists();

            if (!$hasAccess) {
                return redirect()->route('user.package.index')
                    ->with('error', 'Anda tidak memiliki akses ke paket ini');
            }
        }

        // Tanggal yang kosong berarti tryout tidak dibatasi pada sisi tersebut.
        $now = Carbon::now('Asia/Jakarta');
        if (! $hasDirectAccess && ($availabilityError = $this->tryoutAvailabilityError($tryout, $now))) {
            return redirect()->back()->with('error', $availabilityError);
        }

        // Get tryout details untuk menampilkan info di lobby
        $tryoutDetails = $tryout->tryoutDetails()->orderBy('tryout_detail_id')->get();

        // Calculate total duration dan questions untuk SKD Full
        $extraMinutes = $this->getExtraMinutesForUser($tryout->tryout_id, Auth::id());
        $totalDuration = $tryoutDetails->sum('duration') + $extraMinutes;
        $totalQuestions = Question::whereIn(
            'tryout_detail_id',
            $tryoutDetails->pluck('tryout_detail_id')
        )->count();

        // Ringkas status percobaan dalam satu query. Sebelumnya lobby menjalankan
        // query hitung percobaan yang sama hingga tiga kali untuk setiap peserta.
        $attemptsByStatus = $tryout->userAnswers()
            ->where('user_id', Auth::id())
            ->whereIn('status', ['completed', 'pending_release', 'in_progress'])
            ->selectRaw('status, COUNT(DISTINCT attempt_token) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $attempts = (int) ($attemptsByStatus['completed'] ?? 0)
            + (int) ($attemptsByStatus['pending_release'] ?? 0);
        $hasInProgressAttempt = (int) ($attemptsByStatus['in_progress'] ?? 0) > 0;
        $maxAttempts = (int) ($tryout->max_attempts ?? 0);
        $remainingAttempts = $maxAttempts > 0 ? max(0, $maxAttempts - $attempts) : null;
        $isAttemptLimitReached = $maxAttempts > 0
            && $attempts >= $maxAttempts
            && ! $hasInProgressAttempt;

        ActivityLogger::log('tryout_lobby_opened', 'success', Auth::user(), [
            'package_id' => $id_package,
            'tryout_id' => $id_tryout,
            'attempts' => $attempts,
        ]);
        $effectiveProctoringSettings = $this->effectiveProctoringSettings($tryout);

        return view('user.pages.tryout.lobby', compact(
            'package',
            'tryout',
            'attempts',
            'tryoutDetails',
            'totalDuration',
            'totalQuestions',
            'effectiveProctoringSettings',
            'remainingAttempts',
            'hasInProgressAttempt',
            'isAttemptLimitReached'
        ));
    }

    public function indexTryout($id_package, $id_tryout, $number)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Silakan login terlebih dahulu untuk mengerjakan tryout.');
        }
        
        $now = Carbon::now('Asia/Jakarta');

        // Handle free tryouts or package tryouts
        if ($id_package === 'free') {
            $hasDirectAccess = $this->hasDirectTryoutAccess((int) $id_tryout, Auth::id());
            $tryoutQuery = Tryout::where('tryout_id', $id_tryout)
                ->where('is_active', true);

            if (!$hasDirectAccess) {
                $tryoutQuery
                    ->where(function ($query) use ($now) {
                        $query->whereNull('start_date')
                            ->orWhere('start_date', '<=', $now);
                    })
                    ->where(function ($query) use ($now) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', $now);
                    });
            }

            $tryout = $tryoutQuery->firstOrFail();
            $package = null;
        } else {
            $hasDirectAccess = false;
            $package = Package::findOrFail($id_package);
            $tryout = Tryout::findOrFail($id_tryout);

            // Check access
            $hasAccess = UserPackageAcces::where('user_id', Auth::id())
                ->where('package_id', $id_package)
                ->where('status', 'active')
                ->where(function ($query) use ($now) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', $now);
                })
                ->exists();

            if (!$hasAccess) {
                return redirect()->route('user.package.index')
                    ->with('error', 'Anda tidak memiliki akses ke paket ini');
            }
        }

        if (! $hasDirectAccess && ($availabilityError = $this->tryoutAvailabilityError($tryout, $now))) {
            return redirect()->back()->with('error', $availabilityError);
        }

        // Get all tryout details dalam urutan yang benar
        $tryoutDetails = $tryout->tryoutDetails()->get(); // ambil semua dulu

        if ($tryout->system_tryout === 'toefl') {
            // Tentukan urutan khusus TOEFL
            $order = ['listening', 'writing', 'reading']; // type_subtest sesuai database
            $tryoutDetails = $tryoutDetails->sortBy(function ($detail) use ($order) {
                return array_search($detail->type_subtest, $order);
            });
        } else {
            // Default: urutkan berdasarkan tryout_detail_id
            $tryoutDetails = $tryoutDetails->sortBy('tryout_detail_id');
        }

        $allQuestions = collect();
        $subtestInfo = [];

        // The navigation only needs IDs and subtest order. Loading question text,
        // options, metadata, and media for every subtest here made the first page
        // unnecessarily large. Full question data is loaded below for the active
        // subtest only (or for all questions in deliberate combined-view mode).
        $questionSummariesByDetail = Question::whereIn(
            'tryout_detail_id',
            $tryoutDetails->pluck('tryout_detail_id')
        )
            ->select(['question_id', 'tryout_detail_id'])
            ->orderBy('question_id')
            ->get()
            ->groupBy('tryout_detail_id');

        foreach ($tryoutDetails as $detail) {
            $questions = $questionSummariesByDetail->get($detail->tryout_detail_id, collect());

            foreach ($questions as $question) {
                $question->subtest_type = $detail->type_subtest;
                $question->subtest_name = $this->getSubtestName($detail->type_subtest);
                $question->tryout_detail = $detail;
            }

            $allQuestions = $allQuestions->concat($questions);

            $subtestInfo[] = [
                'type' => $detail->type_subtest,
                'name' => $this->getSubtestName($detail->type_subtest),
                'alias' => $this->getSubtestAlias($detail->type_subtest),
                'start_number' => $allQuestions->count() - $questions->count() + 1,
                'end_number' => $allQuestions->count(),
                'duration' => $detail->duration,
                'passing_score' => $detail->passing_score,
                'tryout_detail_id' => $detail->tryout_detail_id
            ];
        }

        $allQuestions = $allQuestions->values();
        $allQuestions->each(function (Question $question, int $index): void {
            $question->setAttribute('tryout_number', $index + 1);
        });

        $isCombinedSubtestView = count($subtestInfo) > 1
            && ($tryout->subtest_display_mode ?? 'per_subtest') === 'combined';

        if ($allQuestions->isEmpty()) {
            return redirect()->back()->with('error', 'Tryout belum memiliki soal');
        }

        // Load all in-progress subtest attempts once. The previous implementation
        // queried the same table once for every subtest, then queried it again for
        // the current subtest and the navigation state.
        $inProgressAnswersByDetail = UserAnswer::where('user_id', Auth::id())
            ->where('tryout_id', $id_tryout)
            ->where('status', 'in_progress')
            ->get()
            ->keyBy('tryout_detail_id');
        $existingUserAnswer = $inProgressAnswersByDetail->first();

        if ($existingUserAnswer) {
            $inProgressAnswersByDetail = $inProgressAnswersByDetail
                ->where('attempt_token', $existingUserAnswer->attempt_token)
                ->keyBy('tryout_detail_id');
        }

        if (! $existingUserAnswer && $tryout->hasReachedAttemptLimitForUser(Auth::id())) {
            return redirect()
                ->route('user.tryout.lobby', [$package ? $package->package_id : 'free', $tryout->tryout_id])
                ->with('error', 'Batas pengerjaan tryout ini sudah habis.');
        }

        if ($number > $allQuestions->count()) {
            return $this->finishTryout(request(), $id_package, $id_tryout);
        }

        $totalQuestions = $allQuestions->count();

        // Tentukan subtest saat ini
        $currentSubtest = null;
        foreach ($subtestInfo as $subtest) {
            if ($number >= $subtest['start_number'] && $number <= $subtest['end_number']) {
                $currentSubtest = $subtest;
                break;
            }
        }

        if (!$currentSubtest) {
            $currentSubtest = $subtestInfo[0] ?? null;
        }

        if (!$currentSubtest) {
            return redirect()->back()->with('error', 'Subtest tryout tidak ditemukan');
        }

        // Per-subtest tryouts navigate to the next subtest as a separate page.
        // Rendering only the active subtest keeps the initial response small,
        // while combined mode intentionally preserves its all-question SPA view.
        $renderedDetailIds = $isCombinedSubtestView
            ? $tryoutDetails->pluck('tryout_detail_id')
            : collect([$currentSubtest['tryout_detail_id']]);
        $renderedQuestionsByDetail = Question::whereIn('tryout_detail_id', $renderedDetailIds)
            ->with('questionOptions')
            ->orderBy('question_id')
            ->get()
            ->groupBy('tryout_detail_id');
        $questionNumbersById = $allQuestions->pluck('tryout_number', 'question_id');
        $renderedQuestions = collect();

        foreach ($tryoutDetails as $detail) {
            if (! $renderedDetailIds->contains($detail->tryout_detail_id)) {
                continue;
            }

            $questions = $renderedQuestionsByDetail->get($detail->tryout_detail_id, collect());
            foreach ($questions as $question) {
                $question->subtest_type = $detail->type_subtest;
                $question->subtest_name = $this->getSubtestName($detail->type_subtest);
                $question->tryout_detail = $detail;
                $question->setAttribute('tryout_number', $questionNumbersById->get($question->question_id));
            }

            $renderedQuestions = $renderedQuestions->concat($questions);
        }

        $renderedQuestions = $renderedQuestions->values();
        $currentQuestion = $renderedQuestions->firstWhere('tryout_number', $number);

        if (! $currentQuestion) {
            return redirect()->back()->with('error', 'Soal tryout tidak ditemukan');
        }

        // Get or create every subtest attempt in one read and (when needed) one
        // bulk insert. This runs when participants first enter a tryout, so it
        // avoids multiplying small queries during a concurrent start burst.
        $attemptWasStarted = ! $existingUserAnswer;
        $attemptToken = $existingUserAnswer ? $existingUserAnswer->attempt_token : Str::uuid()->toString();

        $missingAttemptRows = $tryoutDetails
            ->reject(fn (TryoutDetail $detail): bool => $inProgressAnswersByDetail->has($detail->tryout_detail_id))
            ->map(fn (TryoutDetail $detail): array => [
                'user_id' => Auth::id(),
                'tryout_id' => $id_tryout,
                'tryout_detail_id' => $detail->tryout_detail_id,
                'attempt_token' => $attemptToken,
                'started_at' => $now,
                'status' => 'in_progress',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if ($missingAttemptRows !== []) {
            UserAnswer::insert($missingAttemptRows);

            $inProgressAnswersByDetail = UserAnswer::where('user_id', Auth::id())
                ->where('tryout_id', $id_tryout)
                ->where('attempt_token', $attemptToken)
                ->where('status', 'in_progress')
                ->get()
                ->keyBy('tryout_detail_id');
        }

        $allUserAnswers = $inProgressAnswersByDetail->values();

        $currentSubtestIndex = $this->getSubtestIndex($subtestInfo, $currentSubtest ?? []);
        if (count($subtestInfo) > 1 && !$isCombinedSubtestView) {
            $progressKey = sprintf('tryout_subtest_progress_%s', $attemptToken);
            $maxVisitedIndex = (int) session($progressKey, 0);

            if ($currentSubtestIndex < $maxVisitedIndex) {
                $targetSubtest = $subtestInfo[$maxVisitedIndex] ?? null;
                $targetNumber = $targetSubtest['start_number'] ?? $number;

                return redirect()->route('user.tryout.index', [
                    $package ? $package->package_id : 'free',
                    $tryout->tryout_id,
                    $targetNumber
                ])->with('error', 'Tidak bisa kembali ke subtest sebelumnya.');
            }

            if ($currentSubtestIndex > $maxVisitedIndex) {
                session([$progressKey => $currentSubtestIndex]);
            }
        }

        if (!$isCombinedSubtestView) {
            if ($response = $this->maybeShowSubtestBreak($tryout, $package, $currentSubtest, $currentSubtestIndex, $attemptToken, $number, $subtestInfo)) {
                return $response;
            }
        }

        // Get current subtest's UserAnswer dari hasil query awal.
        $currentUserAnswer = $inProgressAnswersByDetail->get($currentSubtest['tryout_detail_id']);

        if (!$currentUserAnswer) {
            return redirect()->back()->with('error', 'Session tryout tidak ditemukan');
        }

        // Calculate total time for the ENTIRE tryout based on the very first subtest started
        $extraMinutes = $this->getExtraMinutesForUser($tryout->tryout_id, Auth::id());
        $totalDuration = $tryoutDetails->sum('duration') + $extraMinutes;

        // Find the earliest started_at from the already loaded attempt rows.
        $firstStartTime = $allUserAnswers->min('started_at');
        $startTime = Carbon::parse($firstStartTime, 'Asia/Jakarta');
        $endTime = $startTime->copy()->addMinutes($totalDuration);

        // Check if time is up - auto finish if time exceeded
        if ($now->gt($endTime)) {
            return $this->finishTryout(request(), $id_package, $id_tryout);
        }

        $remainingSeconds = (int) $now->diffInSeconds($endTime, false);
        if ($remainingSeconds <= 0) $remainingSeconds = 1;

        // Hitung timer per subtest untuk tampilan
        $subtestDurationMinutes = max(1, (int) ($currentSubtest['duration'] ?? 60));
        $subtestTimerKey = sprintf('tryout_subtest_timer_%s_%s', $attemptToken, $currentSubtest['tryout_detail_id']);
        $subtestStartIso = session($subtestTimerKey);
        if (!$subtestStartIso) {
            $subtestStart = Carbon::now('Asia/Jakarta');
            session([$subtestTimerKey => $subtestStart->toIso8601String()]);
        } else {
            $subtestStart = Carbon::parse($subtestStartIso, 'Asia/Jakarta');
        }
        $subtestEnd = $subtestStart->copy()->addMinutes($subtestDurationMinutes);
        $subtestRemainingSeconds = $subtestEnd->greaterThan($now)
            ? $now->diffInSeconds($subtestEnd)
            : 0;
        $displayRemainingSeconds = $isCombinedSubtestView ? $remainingSeconds : max(1, (int) $subtestRemainingSeconds);

        // Get all user's answer details for this attempt
        $allAnswerDetails = UserAnswerDetail::whereIn('user_answer_id', $allUserAnswers->pluck('user_answer_id'))
            ->with('questionOption')
            ->get()
            ->keyBy('question_id');

        $userAnswerDetail = $allAnswerDetails->get($currentQuestion->question_id);

        $userAnswerDetails = $allAnswerDetails->pluck('question_id')->toArray();

        // Get flagged questions dari session dengan attempt_token
        $flaggedQuestions = session('flagged_questions_' . $attemptToken, []);

        $totalSubtests = count($subtestInfo);
        $hasNextSubtest = $currentSubtestIndex < ($totalSubtests - 1);
        $currentSubtestRange = $isCombinedSubtestView
            ? [1, $totalQuestions]
            : [
                $currentSubtest['start_number'] ?? 1,
                $currentSubtest['end_number'] ?? $number,
            ];
        $isLastQuestionOfSubtest = $number === ($currentSubtest['end_number'] ?? $number);

        if ($attemptWasStarted) {
            ActivityLogger::log('tryout_started', 'success', Auth::user(), [
                'package_id' => $id_package,
                'tryout_id' => $id_tryout,
                'question_number' => $number,
                'attempt_token' => $attemptToken,
            ]);
        }
        $effectiveProctoringSettings = $this->effectiveProctoringSettings($tryout);

        return view('user.pages.tryout.index', compact(
            'package',
            'tryout',
            'tryoutDetails',
            'currentQuestion',
            'userAnswerDetail',
            'currentUserAnswer',
            'number',
            'totalQuestions',
            'allQuestions',
            'renderedQuestions',
            'userAnswerDetails',
            'allAnswerDetails',
            'flaggedQuestions',
            'subtestInfo',
            'currentSubtest',
            'currentSubtestIndex',
            'totalSubtests',
            'hasNextSubtest',
            'isCombinedSubtestView',
            'currentSubtestRange',
            'isLastQuestionOfSubtest',
            'remainingSeconds',
            'subtestRemainingSeconds',
            'displayRemainingSeconds',
            'attemptToken',
            'extraMinutes',
            'effectiveProctoringSettings'
        ));
    }

    private function effectiveProctoringSettings(Tryout $tryout): array
    {
        $globalSettings = PlanQuotaService::getDefaultProctoringSettings();

        return [
            'enable_anti_copy' => (bool) $tryout->enable_anti_copy && (bool) ($globalSettings['enable_anti_copy'] ?? true),
            'enable_tab_switch_detection' => (bool) $tryout->enable_tab_switch_detection && (bool) ($globalSettings['enable_tab_switch_detection'] ?? true),
            'enable_webcam_check' => (bool) $tryout->enable_webcam_check && (bool) ($globalSettings['enable_webcam_check'] ?? false),
            'enable_screen_check' => (bool) $tryout->enable_screen_check && (bool) ($globalSettings['enable_screen_check'] ?? false),
        ];
    }


    private function getSubtestName($type)
    {
        switch ($type) {
            case 'twk':
                return 'Tes Wawasan Kebangsaan';
            case 'tiu':
                return 'Tes Intelegensi Umum';
            case 'tkp':
                return 'Tes Karakteristik Pribadi';
            case 'tpa':
                return 'TPA';
            case 'tbi':
                return 'TBI';
            case 'tob':
                return 'TOB';
            case 'writing':
                return 'Writing Test';
            case 'reading':
                return 'Reading Comprehension';
            case 'listening':
                return 'Listening Test';
            case 'general':
                return 'General Test';
            case 'teknis':
                return 'Tes Teknis';
            case 'social culture':
                return 'Sosial-Kultural';
            case 'management':
                return 'Manajerial';
            case 'interview':
                return 'Wawancara';
            case 'word':
                return 'Microsoft Word';
            case 'excel':
                return 'Microsoft Excel';
            case 'ppt':
                return 'Microsoft PowerPoint';
            case 'penalaran_umum':
                return 'Penalaran Umum';
            case 'pengetahuan_umum':
                return 'Pengetahuan & Pemahaman Umum';
            case 'pengetahuan_kuantitatif':
                return 'Pengetahuan Kuantitatif';
            case 'pemahaman_bacaan_menulis':
                return 'Pemahaman Bacaan & Menulis';
            case 'literasi_bahasa_indonesia':
                return 'Literasi Bahasa Indonesia';
            case 'literasi_bahasa_inggris':
                return 'Literasi Bahasa Inggris';
            case 'penalaran_matematika':
                return 'Penalaran Matematika';
            default:
                return ucfirst($type);
        }
    }

    private function getSubtestAlias($type)
    {
        $map = [
            'penalaran_umum' => 'PU',
            'pengetahuan_umum' => 'PPU',
            'pengetahuan_kuantitatif' => 'PK',
            'pemahaman_bacaan_menulis' => 'PBM',
            'literasi_bahasa_indonesia' => 'LBI',
            'literasi_bahasa_inggris' => 'LBE',
            'penalaran_matematika' => 'PM',
            'twk' => 'TWK',
            'tiu' => 'TIU',
            'tkp' => 'TKP',
            'tpa' => 'TPA',
            'tbi' => 'TBI',
            'writing' => 'WT',
            'reading' => 'RD',
            'listening' => 'LS',
        ];

        return $map[$type] ?? strtoupper((string) $type);
    }

    public function saveAnswer(Request $request, $id_package, $id_tryout, $number)
    {
        try {
            $request->validate([
                'question_id' => 'required|exists:questions,question_id',
            ]);

            $now = Carbon::now('Asia/Jakarta');

            $question = Question::with(['questionOptions', 'tryoutDetail'])->find($request->question_id);

            if (!$question) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Question not found'], 404);
                }
                return redirect()->back()->with('error', 'Soal tidak ditemukan');
            }

            $userAnswer = UserAnswer::where('user_id', Auth::id())
                ->where('tryout_id', $id_tryout)
                ->where('tryout_detail_id', $question->tryout_detail_id)
                ->where('status', 'in_progress')
                ->first();

            if (!$userAnswer) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Session not found'], 404);
                }
                return redirect()->back()->with('error', 'Session tryout tidak ditemukan');
            }

            $tryout = Tryout::findOrFail($id_tryout);
            $tryoutDetails = $tryout->tryoutDetails()->get();
            $extraMinutes = $this->getExtraMinutesForUser($tryout->tryout_id, Auth::id());
            $totalDuration = $tryoutDetails->sum('duration') + $extraMinutes;

            $firstStartTime = UserAnswer::where('user_id', Auth::id())
                ->where('tryout_id', $id_tryout)
                ->where('attempt_token', $userAnswer->attempt_token)
                ->min('started_at');
            $startTime = Carbon::parse($firstStartTime ?: $userAnswer->started_at, 'Asia/Jakarta');
            $endTime = $startTime->copy()->addMinutes($totalDuration);

            if ($now->gte($endTime)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Time is up',
                        'redirect' => route('user.tryout.result', [$id_package, $id_tryout])
                    ], 400);
                }

                return redirect()->route('user.tryout.result', [$id_package, $id_tryout])
                    ->with('error', 'Waktu ujian telah habis');
            }

            $existingDetail = UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
                ->where('question_id', $question->question_id)
                ->first();

            $payload = $request->all();
            if ($request->hasFile('answer_audio')) {
                $payload['answer_audio_file'] = $request->file('answer_audio');
            }

            $result = $this->processAnswerByType($payload, $question, $existingDetail);

            $detailPayload = $result['detail'];
            $detailPayload['answered_at'] = $now;

            $userAnswerDetail = UserAnswerDetail::updateOrCreate(
                [
                    'user_answer_id' => $userAnswer->user_answer_id,
                    'question_id' => $question->question_id
                ],
                $detailPayload
            );

            if (!empty($result['delete_file']) && $existingDetail && $existingDetail->answer_file_path && Storage::disk('public')->exists($existingDetail->answer_file_path)) {
                Storage::disk('public')->delete($existingDetail->answer_file_path);
            }

            $this->updateSingleSubtestStats($userAnswer);
            
            // Trigger AI correction untuk essay mode auto
            if ($question->question_type === 'essay') {
                $this->triggerAiCorrectionIfNeeded($userAnswerDetail, $question);
            }

            $responsePayload = array_merge([
                'success' => true,
                'message' => 'Jawaban berhasil disimpan',
                'question_id' => $question->question_id,
                'question_type' => $question->question_type,
                'answered_at' => $now->toDateTimeString(),
            ], $result['response'] ?? []);

            if ($request->expectsJson()) {
                return response()->json($responsePayload);
            }

            return redirect()->route('user.tryout.index', [$id_package, $id_tryout, $number])
                ->with('success', 'Jawaban berhasil disimpan');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Gagal menyimpan jawaban',
                    'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Gagal menyimpan jawaban');
        }
    }

    private function decodeAnswersPayload(?string $answersPayload): array
    {
        if (empty($answersPayload)) {
            return [];
        }

        $decoded = json_decode($answersPayload, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values($decoded);
    }

    private function persistAnswersPayload(
        int $tryoutId,
        string $attemptToken,
        array $answers,
        Carbon $now,
        ?int $onlyTryoutDetailId = null
    ): array {
        if (empty($answers)) {
            return [];
        }

        $updatedDetailIds = [];

        DB::transaction(function () use ($tryoutId, $attemptToken, $answers, $now, $onlyTryoutDetailId, &$updatedDetailIds) {
            foreach ($answers as $answer) {
                if (!is_array($answer) || empty($answer['question_id'])) {
                    continue;
                }

                $question = Question::with('tryoutDetail')->find($answer['question_id']);
                if (!$question) {
                    continue;
                }

                if ($onlyTryoutDetailId && (int) $question->tryout_detail_id !== $onlyTryoutDetailId) {
                    continue;
                }

                $userAnswer = UserAnswer::where('user_id', Auth::id())
                    ->where('tryout_id', $tryoutId)
                    ->where('tryout_detail_id', $question->tryout_detail_id)
                    ->where('attempt_token', $attemptToken)
                    ->where('status', 'in_progress')
                    ->first();

                if (!$userAnswer) {
                    $userAnswer = UserAnswer::create([
                        'user_id' => Auth::id(),
                        'tryout_id' => $tryoutId,
                        'tryout_detail_id' => $question->tryout_detail_id,
                        'attempt_token' => $attemptToken,
                        'started_at' => $now,
                        'status' => 'in_progress'
                    ]);
                }

                $existingDetail = UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
                    ->where('question_id', $question->question_id)
                    ->first();

                $result = $this->processAnswerByType($answer, $question, $existingDetail);
                $detailPayload = $result['detail'];
                $detailPayload['answered_at'] = $now;

                $userAnswerDetail = UserAnswerDetail::updateOrCreate(
                    [
                        'user_answer_id' => $userAnswer->user_answer_id,
                        'question_id' => $question->question_id
                    ],
                    $detailPayload
                );

                // Trigger AI correction untuk essay mode auto saat finish/submit
                if ($question->question_type === 'essay') {
                    $this->triggerAiCorrectionIfNeeded($userAnswerDetail, $question);
                }

                if (
                    !empty($result['delete_file'])
                    && $existingDetail
                    && $existingDetail->answer_file_path
                    && Storage::disk('public')->exists($existingDetail->answer_file_path)
                ) {
                    Storage::disk('public')->delete($existingDetail->answer_file_path);
                }

                $updatedDetailIds[$question->tryout_detail_id] = true;
            }
        });

        return array_map('intval', array_keys($updatedDetailIds));
    }

    public function flushSubtestAnswers(Request $request, $id_package, $id_tryout)
    {
        try {
            $validated = $request->validate([
                'tryout_detail_id' => 'required|integer|exists:tryout_details,tryout_detail_id',
                'attempt_token' => 'required|string',
                'answers_payload' => 'nullable|string',
            ]);

            $tryout = Tryout::findOrFail($id_tryout);
            if (($tryout->answer_persistence_mode ?? 'client_side') !== 'hybrid_subtest') {
                return response()->json([
                    'success' => true,
                    'flushed' => false,
                    'message' => 'Mode tryout bukan hybrid.',
                ]);
            }

            $tryoutDetailId = (int) $validated['tryout_detail_id'];
            $attemptToken = (string) $validated['attempt_token'];
            $answers = $this->decodeAnswersPayload($validated['answers_payload'] ?? null);
            $now = Carbon::now('Asia/Jakarta');

            $updatedDetailIds = $this->persistAnswersPayload(
                (int) $id_tryout,
                $attemptToken,
                $answers,
                $now,
                $tryoutDetailId
            );

            $userAnswer = UserAnswer::where('user_id', Auth::id())
                ->where('tryout_id', $id_tryout)
                ->where('tryout_detail_id', $tryoutDetailId)
                ->where('attempt_token', $attemptToken)
                ->where('status', 'in_progress')
                ->with('tryoutDetail')
                ->first();

            if (!$userAnswer) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session subtest tidak ditemukan.',
                ], 404);
            }

            if (in_array($tryoutDetailId, $updatedDetailIds, true)) {
                $this->updateSingleSubtestStats($userAnswer);
                $userAnswer->refresh();
            }

            $userAnswer->update([
                'subtest_submitted_at' => $now,
            ]);

            $rawScore = $this->calculateTotalScore($userAnswer, $userAnswer->tryoutDetail->type_subtest);
            $maxScore = $this->getMaxPossibleScoreForDetail(
                $userAnswer->tryout_detail_id,
                $userAnswer->tryoutDetail->type_subtest
            );
            $percentage = $maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0;
            $isPassed = $this->isSubtestPassed(
                $userAnswer->tryoutDetail,
                $rawScore,
                $maxScore,
                $userAnswer->tryoutDetail->type_subtest
            );

            return response()->json([
                'success' => true,
                'flushed' => true,
                'live_score' => [
                    'tryout_detail_id' => $tryoutDetailId,
                    'type' => $userAnswer->tryoutDetail->type_subtest,
                    'name' => $this->getSubtestName($userAnswer->tryoutDetail->type_subtest),
                    'raw_score' => $rawScore,
                    'max_score' => $maxScore,
                    'percentage' => round($percentage, 2),
                    'is_passed' => $isPassed,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Gagal flush jawaban subtest',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function trackTabSwitch(Request $request, $id_package, $id_tryout)
    {
        $tryout = Tryout::findOrFail($id_tryout);
        $effectiveProctoringSettings = $this->effectiveProctoringSettings($tryout);

        if (!$effectiveProctoringSettings['enable_tab_switch_detection']) {
            return response()->json([
                'success' => true,
                'message' => 'Deteksi pindah tab tidak aktif.',
            ]);
        }

        $validated = $request->validate([
            'attempt_token' => 'required|string',
            'question_id' => 'nullable|integer|exists:questions,question_id',
        ]);

        $query = UserAnswer::where('user_id', Auth::id())
            ->where('tryout_id', $id_tryout)
            ->where('attempt_token', $validated['attempt_token'])
            ->where('status', 'in_progress');

        if (!empty($validated['question_id'])) {
            $question = Question::find($validated['question_id']);
            if ($question) {
                $query->where('tryout_detail_id', $question->tryout_detail_id);
            }
        }

        $userAnswer = $query->first();

        if (!$userAnswer) {
            return response()->json([
                'success' => false,
                'message' => 'Session tryout tidak ditemukan.',
            ], 404);
        }

        $userAnswer->increment('tab_switch_count');
        $userAnswer->refresh();

        ActivityLogger::log('tryout_tab_switch_detected', 'warning', Auth::user(), [
            'package_id' => $id_package,
            'tryout_id' => $id_tryout,
            'attempt_token' => $validated['attempt_token'],
            'question_id' => $validated['question_id'] ?? null,
            'tab_switch_count' => $userAnswer->tab_switch_count,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pelanggaran tab tercatat.',
            'count' => $userAnswer->tab_switch_count,
        ]);
    }

    public function storeProctoringSnapshot(Request $request, $id_package, $id_tryout)
    {
        $tryout = Tryout::findOrFail($id_tryout);
        $effectiveProctoringSettings = $this->effectiveProctoringSettings($tryout);

        $validated = $request->validate([
            'attempt_token' => 'required|string',
            'type' => 'required|in:webcam,screen',
            'image' => 'required|string',
        ]);

        if (
            ($validated['type'] === 'webcam' && !$effectiveProctoringSettings['enable_webcam_check'])
            || ($validated['type'] === 'screen' && !$effectiveProctoringSettings['enable_screen_check'])
        ) {
            return response()->json([
                'success' => true,
                'stored' => false,
                'message' => 'Snapshot tidak aktif untuk tryout ini.',
            ]);
        }

        if (!preg_match('/^data:image\/(jpeg|webp);base64,([A-Za-z0-9+\/=]+)$/', $validated['image'], $matches)) {
            return response()->json([
                'success' => false,
                'message' => 'Format snapshot tidak valid.',
            ], 422);
        }

        $extension = $matches[1] === 'webp' ? 'webp' : 'jpg';
        $binary = base64_decode($matches[2], true);
        if ($binary === false || strlen($binary) > 180 * 1024) {
            return response()->json([
                'success' => false,
                'message' => 'Ukuran snapshot terlalu besar.',
            ], 422);
        }

        $userAnswer = UserAnswer::where('user_id', Auth::id())
            ->where('tryout_id', $id_tryout)
            ->where('attempt_token', $validated['attempt_token'])
            ->where('status', 'in_progress')
            ->first();

        if (!$userAnswer) {
            return response()->json([
                'success' => false,
                'message' => 'Session tryout tidak ditemukan.',
            ], 404);
        }

        $path = sprintf(
            'proctoring/%s/%s/%s_%s.%s',
            $id_tryout,
            $validated['attempt_token'],
            $validated['type'],
            now('Asia/Jakarta')->format('Ymd_His'),
            $extension
        );

        Storage::disk('public')->put($path, $binary);

        ProctoringSnapshot::create([
            'user_id' => Auth::id(),
            'tryout_id' => $id_tryout,
            'user_answer_id' => $userAnswer->user_answer_id,
            'attempt_token' => $validated['attempt_token'],
            'type' => $validated['type'],
            'file_path' => $path,
            'mime_type' => 'image/' . ($extension === 'jpg' ? 'jpeg' : 'webp'),
            'file_size' => strlen($binary),
            'captured_at' => now('Asia/Jakarta'),
        ]);

        return response()->json([
            'success' => true,
            'stored' => true,
        ]);
    }


    /**
     * Determine if answer is correct based on subtest type and rules
     */
    private function determineCorrectAnswer(Question $question, QuestionOption $selectedOption)
    {
        $subtestType = optional($question->tryoutDetail)->type_subtest;

        switch ($subtestType) {
            case 'twk':
            case 'tiu':
                return (bool) $selectedOption->is_correct;

            case 'tkp':
                return $selectedOption->weight > 0;

            case 'writing':
            case 'reading':
            case 'listening':
                return (bool) $selectedOption->is_correct;

            default:
                return (bool) $selectedOption->is_correct;
        }
    }

    /**
     * Calculate total score based on subtest type and rules
     */
    private function calculateTotalScore($userAnswer, $type_subtest)
    {
        $totalScore = 0;

        $userAnswerDetails = UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
            ->with(['questionOption', 'question'])
            ->get();

        foreach ($userAnswerDetails as $detail) {
            $question = $detail->question;
            if (!$question) {
                continue;
            }

            $questionType = $this->normalizeQuestionType($question->question_type ?? 'multiple_choice');
            $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
            $pendingReview = $answerMeta['pending_review'] ?? false;

            switch ($questionType) {
                case 'multiple_answer':
                    $totalScore += $this->resolveMultipleAnswerAwardedScore($question, $detail);
                    break;
                case 'multiple_true_false':
                    $totalScore += $this->resolveMultipleTrueFalseAwardedScore($question, $detail);
                    break;
                case 'matching':
                    $totalScore += $this->resolveMatchingAwardedScore($question, $detail);
                    break;

                case 'short_answer':
                case 'essay':
                    if ($pendingReview) {
                        // Essay masih menunggu koreksi, skip penambahan skor
                        break;
                    }

                    // Gunakan score_obtained dari answer_json (dari AI atau admin)
                    if (isset($answerMeta['score_obtained'])) {
                        $totalScore += (float) $answerMeta['score_obtained'];
                    } elseif ($detail->is_correct) {
                        // Fallback: kalau benar tapi belum ada score_obtained, gunakan essay_score_correct
                        $totalScore += $question->getEssayScoreCorrect();
                    }
                    break;

                case 'audio':
                    // Manual scoring
                    break;

                default:
                    if ($detail->questionOption) {
                        switch ($type_subtest) {
                            case 'twk':
                            case 'tiu':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 5) : 0;
                                break;

                            case 'tkp':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $w > 0 ? $w : 1;
                                break;

                            case 'writing':
                            case 'reading':
                            case 'listening':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 10) : 0;
                                break;

                            default:
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 1) : 0;
                                break;
                        }
                    }
                    break;
            }
        }

        return $totalScore;
    }

    /**
     * Get maximum possible score based on subtest type
     */
    private function getMaxPossibleScore($type_subtest, $totalQuestions)
    {
        switch ($type_subtest) {
            case 'twk':
            case 'tiu':
                return $totalQuestions * 5; // 5 poin per soal

            case 'tkp':
                return $totalQuestions * 5; // Maksimal 5 poin per soal

            case 'writing':
            case 'reading':
            case 'listening':
                return $totalQuestions * 10; // 10 poin per soal untuk certification

            default:
                return $totalQuestions; // 1 poin per soal untuk tryout biasa
        }
    }

    /**
     * Get maximum possible score for SKD Full or Certification Full
     */
    private function getMaxPossibleScoreForSKDFull($tryoutDetails)
    {
        $maxScore = 0;

        foreach ($tryoutDetails as $detail) {
            $maxScore += $this->getMaxPossibleScoreForDetail($detail->tryout_detail_id, $detail->type_subtest);
        }

        return $maxScore;
    }

    public function finishTryout(Request $request, $id_package, $id_tryout)
    {
        $now = Carbon::now('Asia/Jakarta');

        // Get tryout information
        $tryout = Tryout::findOrFail($id_tryout);

        // Get all user answers untuk tryout ini
        $userAnswers = UserAnswer::where('user_id', Auth::id())
            ->where('tryout_id', $id_tryout)
            ->where('status', 'in_progress')
            ->with(['tryoutDetail'])
            ->get();

        if ($userAnswers->isEmpty()) {
            return redirect()->route('user.tryout.result', [$id_package, $id_tryout]);
        }

        $answers = $this->decodeAnswersPayload($request->input('answers_payload'));

        if (!empty($answers)) {
            try {
                $attemptToken = (string) ($request->input('attempt_token') ?: ($userAnswers->first()->attempt_token ?? ''));
                if ($attemptToken !== '') {
                    $this->persistAnswersPayload((int) $id_tryout, $attemptToken, $answers, $now);
                }
            } catch (\Throwable $e) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Gagal menyimpan jawaban akhir',
                        'message' => $e->getMessage()
                    ], 500);
                }
                throw $e;
            }

            $userAnswers = UserAnswer::where('user_id', Auth::id())
                ->where('tryout_id', $id_tryout)
                ->where('status', 'in_progress')
                ->with(['tryoutDetail'])
                ->get();
        }

        if ($userAnswers->isEmpty()) {
            return redirect()->route('user.tryout.index', [$id_package, $id_tryout, 1])
                ->with('error', 'Jawaban belum ditemukan. Silakan lanjutkan tryout.');
        }

        $currentQuestionNumber = (int) $request->input('current_question_number', 0);
        if (
            $currentQuestionNumber > 0
            && ($tryout->subtest_display_mode ?? 'per_subtest') === 'per_subtest'
        ) {
            $tryoutDetails = $tryout->tryoutDetails()->get();
            if ($tryout->system_tryout === 'toefl') {
                $order = ['listening', 'writing', 'reading'];
                $tryoutDetails = $tryoutDetails->sortBy(function ($detail) use ($order) {
                    $position = array_search($detail->type_subtest, $order, true);
                    return $position === false ? PHP_INT_MAX : $position;
                })->values();
            } else {
                $tryoutDetails = $tryoutDetails->sortBy('tryout_detail_id')->values();
            }

            if ($tryoutDetails->count() > 1) {
                $questionCounts = Question::whereIn('tryout_detail_id', $tryoutDetails->pluck('tryout_detail_id'))
                    ->select('tryout_detail_id', DB::raw('count(*) as total'))
                    ->groupBy('tryout_detail_id')
                    ->pluck('total', 'tryout_detail_id');

                $startNumber = 1;
                $subtestRanges = [];
                foreach ($tryoutDetails as $detail) {
                    $questionCount = (int) ($questionCounts[$detail->tryout_detail_id] ?? 0);
                    if ($questionCount <= 0) {
                        continue;
                    }

                    $endNumber = $startNumber + $questionCount - 1;
                    $subtestRanges[] = [
                        'start_number' => $startNumber,
                        'end_number' => $endNumber,
                    ];
                    $startNumber = $endNumber + 1;
                }

                $totalQuestions = $startNumber - 1;
                if ($totalQuestions > 0 && $currentQuestionNumber < $totalQuestions) {
                    $targetNumber = $currentQuestionNumber;
                    foreach ($subtestRanges as $index => $range) {
                        if ($currentQuestionNumber >= $range['start_number'] && $currentQuestionNumber <= $range['end_number']) {
                            if ($currentQuestionNumber >= $range['end_number'] && isset($subtestRanges[$index + 1])) {
                                $targetNumber = $subtestRanges[$index + 1]['start_number'];
                            }
                            break;
                        }
                    }

                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'redirect' => route('user.tryout.index', [$id_package, $id_tryout, $targetNumber]),
                            'message' => 'Selesaikan semua subtest sebelum mengakhiri tryout.',
                        ], 422);
                    }

                    return redirect()->route('user.tryout.index', [$id_package, $id_tryout, $targetNumber])
                        ->with('error', 'Selesaikan semua subtest sebelum mengakhiri tryout.');
                }
            }
        }

        if ($tryout->requiresIrtScoring()) {
            $this->processUtbkDeferredScoring($userAnswers, $tryout, $now);
        } elseif ($tryout->is_toefl == 1) {
            // Use TOEFL scoring system
            $this->processToeflScoring($userAnswers, $now);
        } else {
            // Use regular scoring system
            $this->processRegularScoring($userAnswers, $now);
        }

        ActivityLogger::log('tryout_finished', 'success', Auth::user(), [
            'package_id' => $id_package,
            'tryout_id' => $id_tryout,
            'attempt_token' => (string) ($request->input('attempt_token') ?: ($userAnswers->first()->attempt_token ?? '')),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('user.tryout.result', [$id_package, $id_tryout])
            ]);
        }

        return redirect()->route('user.tryout.result', [$id_package, $id_tryout]);
    }

    /**
     * Process TOEFL scoring system
     */
    private function processToeflScoring($userAnswers, $now)
    {
        foreach ($userAnswers as $userAnswer) {
            $this->updateSingleSubtestStats($userAnswer);
        }

        $toeflResults = ToeflScoringService::processToeflScoring($userAnswers);

        foreach ($userAnswers as $userAnswer) {
            $sectionType = $userAnswer->tryoutDetail->type_subtest;
            $sectionKey = $this->mapSectionType($sectionType);

            if (isset($toeflResults[$sectionKey])) {
                $userAnswer->update([
                    'finished_at'       => $now,
                    'subtest_submitted_at' => $userAnswer->subtest_submitted_at ?? $now,
                    'raw_score'         => $toeflResults[$sectionKey]['raw_score'],
                    'scaled_score'      => $toeflResults[$sectionKey]['scaled_score'],
                    'toefl_total_score' => $toeflResults['total_score'],
                    // SIMPAN skor per subtest = scaled_score subtest tsb (BUKAN total)
                    'score'             => $toeflResults[$sectionKey]['scaled_score'],
                    'status'            => 'completed',
                    // Lulus berdasarkan total (threshold bisa kamu atur)
                    'is_passed'         => $toeflResults['total_score'] >= 217,
                ]);
            }
        }
    }

    /**
     * Process regular scoring system
     */
    private function processRegularScoring($userAnswers, $now)
    {
        foreach ($userAnswers as $userAnswer) {
            $this->updateSingleSubtestStats($userAnswer);

            // Determine if passed untuk subtest ini
            $rawScore = $this->calculateTotalScore($userAnswer, $userAnswer->tryoutDetail->type_subtest);
            $maxScore = $this->getMaxPossibleScoreForDetail(
                $userAnswer->tryout_detail_id,
                $userAnswer->tryoutDetail->type_subtest
            );
            $isPassed = $this->isSubtestPassed(
                $userAnswer->tryoutDetail,
                $rawScore,
                $maxScore,
                $userAnswer->tryoutDetail->type_subtest
            );

            // Update user answer
            $userAnswer->update([
                'finished_at' => $now,
                'subtest_submitted_at' => $userAnswer->subtest_submitted_at ?? $now,
                'is_passed' => $isPassed,
                'status' => 'completed'
            ]);
        }
    }

    private function processUtbkDeferredScoring($userAnswers, Tryout $tryout, Carbon $now): void
    {
        foreach ($userAnswers as $userAnswer) {
            $this->updateSingleSubtestStats($userAnswer);

            $userAnswer->update([
                'finished_at' => $now,
                'subtest_submitted_at' => $userAnswer->subtest_submitted_at ?? $now,
                'status' => 'pending_release',
                'utbk_total_score' => null,
                'is_passed' => false,
            ]);
        }

        $tryout->update([
            // Tanpa tanggal selesai, hasil IRT menunggu rilis manual admin.
            'results_release_at' => $tryout->end_date,
        ]);
    }

    /**
     * Map section type to TOEFL section key
     */
    private function mapSectionType($sectionType)
    {
        switch ($sectionType) {
            case 'listening':
                return 'section1';
            case 'writing':
                return 'section2';
            case 'reading':
                return 'section3';
            case 'word':
                return 'section1';
            case 'excel':
                return 'section2';
            case 'ppt':
                return 'section3';
            case 'teknis':
                return 'section1';
            case 'social culture':
                return 'section2';
            case 'management':
                return 'section3';
            case 'interview':
                return 'section4';
            default:
                return null;
        }
    }

    private function buildFeedbackContext(Tryout $tryout, string $attemptToken): array
    {
        $feedbackQuestions = FeedbackQuestion::where('tryout_id', $tryout->tryout_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $feedbackSubmitted = FeedbackSubmission::where('user_id', Auth::id())
            ->where('tryout_id', $tryout->tryout_id)
            ->exists();

        return [
            'feedbackQuestions' => $feedbackQuestions,
            'feedbackSubmitted' => $feedbackSubmitted,
            'feedbackAttemptToken' => $attemptToken,
        ];
    }

    private function getExtraMinutesForUser(int $tryoutId, int $userId): int
    {
        return (int) (TryoutUserTimeAdjustment::where('tryout_id', $tryoutId)
            ->where('user_id', $userId)
            ->value('extra_minutes') ?? 0);
    }

    private function hasDirectTryoutAccess(int $tryoutId, int $userId): bool
    {
        return UserTryoutAccess::where('tryout_id', $tryoutId)
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function indexResult($id_package, $id_tryout)
    {
        $now = Carbon::now('Asia/Jakarta');

        // Handle free tryouts or package tryouts
        if ($id_package === 'free') {
            $package = null;
        } else {
            $package = Package::findOrFail($id_package);

            // Check access for package tryouts
            $hasAccess = UserPackageAcces::where('user_id', Auth::id())
                ->where('package_id', $id_package)
                ->where('status', 'active')
                ->where(function ($query) use ($now) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', $now);
                })
                ->exists();

            if (!$hasAccess) {
                return redirect()->route('user.package.index')
                    ->with('error', 'Anda tidak memiliki akses ke paket ini');
            }
        }

        // Get tryout information
        $tryout = Tryout::findOrFail($id_tryout);

        // Get all completed/pending user answers untuk tryout ini dengan attempt_token yang sama
        $requestedAttemptToken = trim((string) request('attempt', ''));
        $userAnswers = UserAnswer::where('user_id', Auth::id())
            ->where('tryout_id', $id_tryout)
            ->whereIn('status', ['completed', 'pending_release'])
            ->when($requestedAttemptToken !== '', fn ($query) => $query->where('attempt_token', $requestedAttemptToken))
            ->with(['tryout.tryoutDetails', 'userAnswerDetails.question.questionOptions', 'tryoutDetail'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($userAnswers->isEmpty()) {
            return redirect()->route('user.tryout.lobby', [$id_package, $id_tryout])
                ->with('error', 'Belum ada hasil tryout yang dapat ditampilkan');
        }

        // Group by attempt_token untuk mendapatkan hasil terbaru
        $latestAttemptToken = $userAnswers->first()->attempt_token;
        $latestUserAnswers = $userAnswers->where('attempt_token', $latestAttemptToken);

        $tryoutDetails = $tryout->tryoutDetails;

        $latestStatus = $latestUserAnswers->first()->status ?? 'completed';

        ActivityLogger::log('tryout_result_viewed', 'success', Auth::user(), [
            'package_id' => $id_package,
            'tryout_id' => $id_tryout,
            'attempt_token' => $latestAttemptToken,
            'status' => $latestStatus,
        ]);

        if ($tryout->requiresIrtScoring()) {
            if ($latestStatus === 'pending_release') {
                $releaseTime = $tryout->results_release_at ?? $tryout->end_date;
                if ($releaseTime) {
                    $releaseTime = $releaseTime->copy()->setTimezone('Asia/Jakarta');
                }
                return view('user.pages.tryout.waiting', [
                    'package' => $package,
                    'tryout' => $tryout,
                    'releaseTime' => $releaseTime,
                ]);
            }

            return $this->processUtbkResults($package, $tryout, $latestUserAnswers, $latestAttemptToken);
        }

        // Check if this is TOEFL test and calculate accordingly
        if ($tryout->is_toefl == 1) {
            return $this->processToeflResults($package, $tryout, $latestUserAnswers, $latestAttemptToken);
        } else {
            return $this->processRegularResults($package, $tryout, $latestUserAnswers, $latestAttemptToken, $tryoutDetails);
        }
    }

    /**
     * Process TOEFL test results
     */
    private function processToeflResults($package, $tryout, $latestUserAnswers, $latestAttemptToken)
    {
        $toeflResults = ToeflScoringService::processToeflScoring($latestUserAnswers);

        // Siapkan hasil per seksi
        $sectionResults = [];
        foreach ($latestUserAnswers as $userAnswer) {
            $sectionType = $userAnswer->tryoutDetail->type_subtest;
            $sectionKey = $this->mapSectionType($sectionType);

            if ($sectionKey && isset($toeflResults[$sectionKey])) {
                $totalQuestions = Question::where('tryout_detail_id', $userAnswer->tryout_detail_id)->count();
                $answeredCount = $userAnswer->userAnswerDetails->count();
                $correctCount = $userAnswer->userAnswerDetails->filter(function ($detail) {
                    $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
                    return empty($meta['pending_review']) && $detail->is_correct;
                })->count();
                $wrongCount = $userAnswer->userAnswerDetails->filter(function ($detail) {
                    $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
                    return empty($meta['pending_review']) && !$detail->is_correct;
                })->count();
                $sectionResults[] = [
                    'type'             => $sectionType,
                    'name'             => $this->getSubtestName($sectionType),
                    'raw_score'        => $toeflResults[$sectionKey]['raw_score'],
                    'scaled_score'     => $toeflResults[$sectionKey]['scaled_score'],
                    'correct_answers'  => $correctCount,
                    'wrong_answers'    => $wrongCount,
                    'unanswered'       => max(0, $totalQuestions - $answeredCount),
                    'total_questions'  => $totalQuestions,
                    // TAMPILKAN skor seksi = scaled_score seksi tsb
                    'score'            => $toeflResults[$sectionKey]['scaled_score'],
                ];
            }
        }

        // Simpan juga total untuk ditampilkan di header/summary
        $overallTotal = $toeflResults['total_score'] ?? null;

        $feedbackContext = $this->buildFeedbackContext($tryout, $latestAttemptToken);

        return view('user.pages.tryout.result-toefl', array_merge(compact(
            'package',
            'tryout',
            'toeflResults',     // masih berisi total_score + detail
            'sectionResults',   // per-seksi
            'latestAttemptToken',
            'overallTotal'      // opsional dipakai di blade
        ), $feedbackContext));
    }

    private function processUtbkResults($package, $tryout, $latestUserAnswers, $latestAttemptToken)
    {
        $order = [
            'penalaran_umum' => 1,
            'pengetahuan_umum' => 2,
            'pengetahuan_kuantitatif' => 3,
            'pemahaman_bacaan_menulis' => 4,
            'literasi_bahasa_indonesia' => 5,
            'literasi_bahasa_inggris' => 6,
            'penalaran_matematika' => 7,
        ];

        $sortedAnswers = $latestUserAnswers->sortBy(function ($answer) use ($order) {
            $type = $answer->tryoutDetail->type_subtest ?? '';
            return $order[$type] ?? 99;
        });

        $sortedAnswers->loadMissing(['userAnswerDetails', 'tryoutDetail']);

        $subtests = $sortedAnswers->map(function ($answer) {
            $detail = $answer->tryoutDetail;
            $passingScore = $detail->passing_score ?? null;
            $passingType = $detail->passing_type ?? 'score';
            $subtestScore = (int) round((float) ($answer->score ?? 0));
            $percentage = $passingType === 'percentage' ? ($subtestScore / 1000) * 100 : null;
            $isPassed = !is_null($passingScore)
                ? ($passingType === 'percentage'
                    ? ($percentage >= $passingScore)
                    : ($subtestScore >= $passingScore))
                : false;
            $totalQuestions = Question::where('tryout_detail_id', $answer->tryout_detail_id)->count();
            $answeredCount = $answer->userAnswerDetails->count();
            $correctCount = $answer->userAnswerDetails->where('is_correct', true)->count();
            $wrongCount = max(0, $answeredCount - $correctCount);
            $unansweredCount = max(0, $totalQuestions - $answeredCount);

            return [
                'type' => $answer->tryoutDetail->type_subtest,
                'name' => $this->getSubtestName($answer->tryoutDetail->type_subtest),
                'correct' => $correctCount,
                'wrong' => $wrongCount,
                'unanswered' => $unansweredCount,
                'score' => $subtestScore,
                'passing_score' => $passingScore,
                'passing_type' => $passingType,
                'is_passed' => $isPassed,
            ];
        })->values();

        $totalScore = (int) ($sortedAnswers->first()->utbk_total_score ?? 0);
        $overallPassed = $subtests->every('is_passed');

        $feedbackContext = $this->buildFeedbackContext($tryout, $latestAttemptToken);

        return view('user.pages.tryout.result-utbk', array_merge([
            'package' => $package,
            'tryout' => $tryout,
            'subtests' => $subtests,
            'totalScore' => $totalScore,
            'attemptToken' => $latestAttemptToken,
            'overallPassed' => $overallPassed,
        ], $feedbackContext));
    }

    /**
     * Process regular test results
     */
    private function processRegularResults($package, $tryout, $latestUserAnswers, $latestAttemptToken, $tryoutDetails)
    {
        // Calculate overall statistics
        $latestUserAnswers->loadMissing(['userAnswerDetails']);
        $questionCounts = Question::whereIn('tryout_detail_id', $latestUserAnswers->pluck('tryout_detail_id'))
            ->select('tryout_detail_id', \DB::raw('count(*) as total'))
            ->groupBy('tryout_detail_id')
            ->pluck('total', 'tryout_detail_id');
        $totalQuestions = $latestUserAnswers->sum(function ($ua) use ($questionCounts) {
            return (int) ($questionCounts[$ua->tryout_detail_id] ?? 0);
        });
        $answeredCount = 0;
        $correctAnswers = 0;
        $wrongAnswers = 0;
        $pendingReviewCount = 0;
        foreach ($latestUserAnswers as $userAnswer) {
            $details = $userAnswer->userAnswerDetails;
            $answeredCount += $details->count();
            foreach ($details as $detail) {
                $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
                if (!empty($meta['pending_review'])) {
                    $pendingReviewCount++;
                    continue;
                }

                if ($detail->is_correct) {
                    $correctAnswers++;
                } else {
                    $wrongAnswers++;
                }
            }
        }
        $unansweredCount = max(0, $totalQuestions - $answeredCount);

        if ($tryoutDetails->count() > 1) {
            // Multiple subtest calculation
            $rawScore = $this->calculateTotalScoreForSKDFullFromUserAnswers($latestUserAnswers);
            $maxScore = $this->getMaxPossibleScoreForSKDFull($tryoutDetails);

            // Calculate per subtest results
            $subtestResults = $this->calculateSubtestResultsFromUserAnswers($latestUserAnswers);
            $singleIsPassed = null;
        } else {
            // Single subtest calculation
            $singleUserAnswer = $latestUserAnswers->first();
            $rawScore = $this->calculateTotalScore($singleUserAnswer, $singleUserAnswer->tryoutDetail->type_subtest);
            $maxScore = $this->getMaxPossibleScoreForDetail($singleUserAnswer->tryout_detail_id, $singleUserAnswer->tryoutDetail->type_subtest);
            $subtestResults = $this->calculateSubtestResultsFromUserAnswers($latestUserAnswers);
            $singleIsPassed = $this->isSubtestPassed(
                $singleUserAnswer->tryoutDetail,
                $rawScore,
                $maxScore,
                $singleUserAnswer->tryoutDetail->type_subtest
            );
        }

        // Calculate overall percentage
        $overallPercentage = $maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0;

        $feedbackContext = $this->buildFeedbackContext($tryout, $latestAttemptToken);

        return view('user.pages.tryout.result', array_merge(compact(
            'package',
            'latestUserAnswers',
            'latestAttemptToken',
            'totalQuestions',
            'correctAnswers',
            'wrongAnswers',
            'unansweredCount',
            'pendingReviewCount',
            'rawScore',
            'maxScore',
            'tryout',
            'tryoutDetails',
            'subtestResults',
            'singleIsPassed',
            'overallPercentage'
        ), $feedbackContext));
    }

    /**
     * Calculate total score for SKD Full from multiple UserAnswer records
     */
    private function calculateTotalScoreForSKDFullFromUserAnswers($userAnswers)
    {
        $totalScore = 0;

        foreach ($userAnswers as $userAnswer) {
            $totalScore += $this->calculateTotalScore($userAnswer, $userAnswer->tryoutDetail->type_subtest);
        }

        return $totalScore;
    }

    /**
     * Calculate results per subtest from UserAnswer records
     */
    private function calculateSubtestResultsFromUserAnswers($userAnswers)
    {
        $subtestResults = [];

        foreach ($userAnswers as $userAnswer) {
            $detail = $userAnswer->tryoutDetail;
            $totalQuestions = Question::where('tryout_detail_id', $detail->tryout_detail_id)->count();
            $answeredCount = $userAnswer->userAnswerDetails->count();

            $subtestScore = $this->calculateTotalScore($userAnswer, $detail->type_subtest);
            $maxSubtestScore = $this->getMaxPossibleScoreForDetail($detail->tryout_detail_id, $detail->type_subtest);
            $percentage = $maxSubtestScore > 0 ? ($subtestScore / $maxSubtestScore) * 100 : 0;

            // Set passing score based on subtest type
            $passingScore = $detail->passing_score ?? $this->getDefaultPassingScore($detail->type_subtest);
            $passingType = $detail->passing_type ?? 'score';
            $isPassed = $this->isSubtestPassed($detail, $subtestScore, $maxSubtestScore, $detail->type_subtest);
            $passingScorePercentage = $passingType === 'percentage'
                ? $passingScore
                : ($maxSubtestScore > 0 ? ($passingScore / $maxSubtestScore) * 100 : null);

            $correctCount = $userAnswer->userAnswerDetails->filter(function ($detail) {
                $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
                return empty($meta['pending_review']) && $detail->is_correct;
            })->count();
            $wrongCount = $userAnswer->userAnswerDetails->filter(function ($detail) {
                $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
                return empty($meta['pending_review']) && !$detail->is_correct;
            })->count();
            $pendingCount = $userAnswer->userAnswerDetails->filter(function ($detail) {
                $meta = is_array($detail->answer_json) ? $detail->answer_json : [];
                return !empty($meta['pending_review']);
            })->count();

            $subtestResults[] = [
                'type' => $detail->type_subtest,
                'name' => $this->getSubtestName($detail->type_subtest),
                'alias' => $this->getSubtestAlias($detail->type_subtest),
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctCount,
                'wrong_answers' => $wrongCount,
                'pending_count' => $pendingCount,
                'unanswered' => max(0, $totalQuestions - $answeredCount),
                'raw_score' => $subtestScore,
                'max_score' => $maxSubtestScore,
                'percentage' => $percentage,
                'passing_score' => $passingScore,
                'passing_type' => $passingType,
                'passing_percentage' => $passingScorePercentage,
                'is_passed' => $isPassed
            ];
        }

        return $subtestResults;
    }

    /**
     * Get default passing score for each subtest type
     */
    private function getDefaultPassingScore($type_subtest)
    {
        switch ($type_subtest) {
            case 'word':
            case 'excel':
            case 'ppt':
                return 70; // Computer applications: 70%
            case 'teknis':
            case 'social culture':
            case 'management':
            case 'interview':
                return 65; // PPPK subtests: 65%
            default:
                return 60; // Default: 60%
        }
    }

    private function isSubtestPassed($detail, float $rawScore, float $maxScore, string $type): bool
    {
        $passingScore = $detail?->passing_score ?? $this->getDefaultPassingScore($type);
        if ($passingScore === null) {
            return false;
        }

        $passingType = $detail?->passing_type ?? 'score';
        if ($passingType === 'percentage') {
            $percentage = $maxScore > 0 ? ($rawScore / $maxScore) * 100 : 0;
            return $percentage >= $passingScore;
        }

        return $rawScore >= $passingScore;
    }


    public function toggleFlag(Request $request, $id_package, $id_tryout)
    {
        try {
            $request->validate([
                'question_id' => 'required|exists:questions,question_id'
            ]);

            // Get attempt token dari session atau dari user answer
            $userAnswer = UserAnswer::where('user_id', Auth::id())
                ->where('tryout_id', $id_tryout)
                ->where('status', 'in_progress')
                ->first();

            if (!$userAnswer) {
                return response()->json(['error' => 'Session not found'], 404);
            }

            $questionId = $request->question_id;
            $sessionKey = 'flagged_questions_' . $userAnswer->attempt_token;
            $flaggedQuestions = session($sessionKey, []);

            if (in_array($questionId, $flaggedQuestions)) {
                // Remove flag
                $flaggedQuestions = array_diff($flaggedQuestions, [$questionId]);
                $isFlagged = false;
            } else {
                // Add flag
                $flaggedQuestions[] = $questionId;
                $isFlagged = true;
            }

            session([$sessionKey => array_values($flaggedQuestions)]);

            return response()->json([
                'success' => true,
                'flagged' => $isFlagged,
                'message' => $isFlagged ? 'Soal berhasil ditandai' : 'Tanda soal berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal mengubah status tandai',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update statistics for a single subtest UserAnswer
     */
    private function updateSingleSubtestStats($userAnswer)
    {
        $userAnswerDetails = UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
            ->with(['questionOption', 'question'])
            ->get();

        $totalQuestions = Question::where('tryout_detail_id', $userAnswer->tryout_detail_id)->count();
        $correctAnswers = 0;
        $wrongAnswers = 0;
        $totalScore = 0;

        foreach ($userAnswerDetails as $detail) {
            $question = $detail->question;
            if (!$question) {
                continue;
            }

            $questionType = $this->normalizeQuestionType($question->question_type ?? 'multiple_choice');
            $pendingReview = false;
            $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
            if (isset($answerMeta['pending_review'])) {
                $pendingReview = (bool) $answerMeta['pending_review'];
            }

            switch ($questionType) {
                case 'multiple_answer':
                    if ($detail->is_correct) {
                        $correctAnswers++;
                    } else {
                        $wrongAnswers++;
                    }

                    $totalScore += $this->resolveMultipleAnswerAwardedScore($question, $detail);
                    break;
                case 'multiple_true_false':
                    if ($detail->is_correct) {
                        $correctAnswers++;
                    } else {
                        $wrongAnswers++;
                    }

                    $totalScore += $this->resolveMultipleTrueFalseAwardedScore($question, $detail);
                    break;

                case 'matching':
                    if ($detail->is_correct) {
                        $correctAnswers++;
                    } else {
                        $wrongAnswers++;
                    }

                    $totalScore += $this->resolveMatchingAwardedScore($question, $detail);
                    break;

                case 'short_answer':
                case 'essay':
                    if ($pendingReview) {
                        continue 2;
                    }

                    if ($detail->is_correct) {
                        $correctAnswers++;
                    } else {
                        $wrongAnswers++;
                    }

                    $weight = (float) ($question->default_weight ?? 1);
                    $totalScore += $detail->is_correct ? ($weight > 0 ? $weight : 1) : 0;
                    break;

                case 'audio':
                    // Audio answers require manual review, skip scoring for now
                    continue 2;

                default:
                    if ($detail->questionOption) {
                        if ($detail->is_correct) {
                            $correctAnswers++;
                        } else {
                            $wrongAnswers++;
                        }

                        switch ($userAnswer->tryoutDetail->type_subtest) {
                            case 'twk':
                            case 'tiu':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 5) : 0;
                                break;
                            case 'tkp':
                                $totalScore += (float) ($detail->questionOption->weight ?? 0);
                                break;
                            case 'writing':
                            case 'reading':
                            case 'listening':
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 10) : 0;
                                break;
                            default:
                                $w = (float) ($detail->questionOption->weight ?? 0);
                                $totalScore += $detail->is_correct ? ($w > 0 ? $w : 1) : 0;
                                break;
                        }
                    }
                    break;
            }
        }

        $unanswered = $totalQuestions - $userAnswerDetails->count();

        $tryout = $userAnswer->tryoutDetail?->tryout;
        $payload = [
            'correct_answers' => $correctAnswers,
            'wrong_answers' => $wrongAnswers,
            'unanswered' => $unanswered,
        ];

        if (! $tryout || ! $tryout->requiresIrtScoring()) {
            // Non-UTBK: simpan score sebagai persentase (skala 0-100)
            $maxScore = $this->getMaxPossibleScoreForDetail($userAnswer->tryout_detail_id, $userAnswer->tryoutDetail->type_subtest);
            $payload['score'] = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;
        }

        $userAnswer->update($payload);
    }

    // Maksimum skor dinamis berdasarkan bobot pada template
    private function getMaxPossibleScoreForDetail(int $tryoutDetailId, string $type_subtest)
    {
        $questions = Question::where('tryout_detail_id', $tryoutDetailId)
            ->with('questionOptions')
            ->get();

        if ($questions->isEmpty()) {
            return 0;
        }

        $total = 0;

        foreach ($questions as $question) {
            $questionType = $this->normalizeQuestionType($question->question_type ?? 'multiple_choice');

            switch ($questionType) {
                case 'multiple_answer':
                    $weight = (float) ($question->default_weight ?? 1);
                    $total += $weight > 0 ? $weight : 1;
                    break;
                case 'multiple_true_false':
                    $mtfMeta = is_array($question->metadata['multiple_true_false'] ?? null) ? $question->metadata['multiple_true_false'] : [];
                    $weight = (float) ($mtfMeta['score_correct'] ?? ($question->default_weight ?? 0));
                    $total += $weight > 0 ? $weight : 1;
                    break;

                case 'matching':
                    $matchingMeta = is_array($question->metadata['matching_scores'] ?? null) ? $question->metadata['matching_scores'] : [];
                    $weight = (float) ($matchingMeta['score_correct'] ?? ($question->default_weight ?? 0));
                    if ($weight <= 0) {
                        $weight = 1;
                    }
                    $total += $weight;
                    break;

                case 'short_answer':
                case 'essay':
                    // Untuk essay, gunakan essay_score_correct dari model
                    $total += $question->getEssayScoreCorrect();
                    break;

                case 'audio':
                    $total += (float) ($question->default_weight ?? 0);
                    break;

                default:
                    $options = $question->questionOptions;
                    switch ($type_subtest) {
                        case 'tkp':
                            $maxWeight = $options->max(function ($opt) {
                                return (float) ($opt->weight ?? 0);
                            });
                            $maxWeight = (float) ($maxWeight ?? 0);
                            $total += $maxWeight > 0 ? $maxWeight : 1;
                            break;

                        case 'twk':
                        case 'tiu':
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 5;
                            break;

                        case 'writing':
                        case 'reading':
                        case 'listening':
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 10;
                            break;

                        default:
                            $weight = $options->where('is_correct', true)->pluck('weight')->first();
                            $weightValue = (float) ($weight ?? 0);
                            $total += $weightValue > 0 ? $weightValue : 1;
                            break;
                    }
                    break;
            }
        }

        return $total;
    }

    public function markPlayed($id_package, $id_tryout, $question_id)
    {
        $userId = Auth::id();

        $answerDetail = UserAnswerDetail::where('question_id', $question_id)
            ->whereHas('userAnswer', function ($query) use ($userId, $id_tryout) {
                $query->where('user_id', $userId)
                    ->where('tryout_id', $id_tryout);
            })
            ->first();

        if (!$answerDetail) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        if ($answerDetail->is_played) {
            return response()->json(['status' => 'already_played']);
        }

        $answerDetail->is_played = true;
        $answerDetail->save();

        return response()->json(['status' => 'success']);
    }

    /**
     * Trigger AI correction untuk essay dengan mode auto
     * PER ATTEMPT TOKEN - setiap submit membuat job terpisah
     * Dipanggil setelah essay disimpan
     */
    private function triggerAiCorrectionIfNeeded(UserAnswerDetail $detail, Question $question): void
    {
        // Cek apakah essay mode auto dan ada expected answers
        $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $evaluationMode = $answerMeta['evaluation_mode'] ?? 'manual';
        $expectedAnswers = $answerMeta['expected_answers'] ?? [];
        
        \Log::info("AI Correction: Checking trigger", [
            'detail_id' => $detail->user_answer_detail_id,
            'evaluation_mode' => $evaluationMode,
            'expected_answers_count' => count($expectedAnswers),
            'question_type' => $question->question_type,
        ]);
        
        if ($evaluationMode !== 'auto' || empty($expectedAnswers)) {
            \Log::info("AI Correction: Skipping - mode={$evaluationMode}, has_answers=" . (empty($expectedAnswers) ? 'no' : 'yes'));
            return; // Hanya proses yang auto dan ada kunci jawaban
        }
        
        // Cek quota Essay AI - backend validation
        $quotaCheck = PlanQuotaService::canUseEssayAI();
        if (!$quotaCheck['allowed']) {
            \Log::info("AI Correction: Skipping - quota exceeded or feature disabled");
            return; // Skip AI correction jika quota habis atau fitur tidak tersedia
        }
        
        try {
            $userAnswer = $detail->userAnswer;
            if (!$userAnswer) {
                return;
            }
            
            $tryoutId = $userAnswer->tryout_id;
            $userId = $userAnswer->user_id;
            
            // PER ATTEMPT: Selalu buat job baru untuk setiap attempt
            // Tidak digabung dengan job yang sudah ada
            $job = EssayCorrectionJob::create([
                'tryout_id' => $tryoutId,
                'user_id' => $userId,
                'user_answer_id' => $userAnswer->user_answer_id, // Link ke attempt spesifik
                'job_type' => 'single', // Per attempt/token
                'status' => 'pending',
                'total_essays' => 1, // Selalu 1 essay per job untuk clarity
                'method' => 'semantic',
                'threshold' => config('services.ai_similarity.threshold', 0.6),
                'callback_url' => config('services.ai_similarity.callback_url'),
            ]);
            
            // Dispatch ke queue
            ProcessEssayCorrection::dispatch($job->id);
            
            \Log::info("AI Correction: Created job {$job->id} for attempt {$userAnswer->user_answer_id}, detail {$detail->user_answer_detail_id}");
            
        } catch (\Exception $e) {
            \Log::error("AI Correction: Failed to trigger for detail {$detail->user_answer_detail_id}: " . $e->getMessage());
        }
    }

    /**
     * Check essay correction status for real-time updates
     */
    public function checkEssayStatus(Request $request)
    {
        $questionIds = $request->get('question_ids', []);
        $attemptToken = $request->get('attempt_token');
        $countOnly = $request->get('count_only', false);
        
        if (is_string($questionIds)) {
            $questionIds = explode(',', $questionIds);
        }
        
        $questionIds = array_filter(array_map('intval', (array) $questionIds));
        
        if (empty($attemptToken)) {
            return response()->json([]);
        }
        
        // Get user answer by attempt token
        $userAnswer = UserAnswer::where('attempt_token', $attemptToken)
            ->where('user_id', Auth::id())
            ->first();
        
        if (!$userAnswer) {
            return response()->json([]);
        }
        
        // Build query
        $query = UserAnswerDetail::where('user_answer_id', $userAnswer->user_answer_id)
            ->whereHas('question', fn($q) => $q->where('question_type', 'essay'));
        
        // Filter by question IDs if provided
        if (!empty($questionIds)) {
            $query->whereIn('question_id', $questionIds);
        }
        
        // If count only, return pending count
        if ($countOnly) {
            $pendingCount = $query->whereRaw("JSON_EXTRACT(answer_json, '$.pending_review') = true")
                ->count();
            return response()->json(['pending_count' => $pendingCount]);
        }
        
        // Get answer details for the questions
        $results = $query->get()
            ->map(function ($detail) {
                $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
                
                return [
                    'question_id' => $detail->question_id,
                    'pending_review' => $answerMeta['pending_review'] ?? false,
                    'is_correct' => $detail->is_correct,
                    'score_obtained' => $answerMeta['score_obtained'] ?? 0,
                    'ai_similarity' => $answerMeta['ai_similarity'] ?? 0,
                    'evaluation_mode' => $answerMeta['evaluation_mode'] ?? 'manual',
                ];
            });
        
        return response()->json($results);
    }
}
