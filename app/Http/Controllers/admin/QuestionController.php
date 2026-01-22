<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\TryoutDetail;
use App\Models\Tryout;
use App\Models\UserAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class QuestionController extends Controller
{
    private const SUPPORTED_TYPES = [
        'multiple_choice',
        'matching',
        'short_answer',
        'essay',
        'audio',
        'true_false',
    ];

    public function index($tryout_detail_id)
    {
        try {
            $tryout_detail = TryoutDetail::findOrFail($tryout_detail_id);
            $tryout = Tryout::with('tryoutDetails')->where('tryout_id', $tryout_detail->tryout_id)->first();
            $questions = Question::with('questionOptions')->where('tryout_detail_id', $tryout_detail_id)->get();

            return view('admin.pages.question.index', compact('tryout', 'tryout_detail', 'questions'));
        } catch (\Exception $e) {
            return redirect()->route('admin.tryout.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }

    public function create($tryout_detail_id)
    {
        try {
            $tryout_detail = TryoutDetail::findOrFail($tryout_detail_id);
            $tryout = Tryout::with('tryoutDetails')->where('tryout_id', $tryout_detail->tryout_id)->first();

            return view('admin.pages.question.create', compact('tryout', 'tryout_detail'));
        } catch (\Exception $e) {
            return redirect()->route('admin.tryout.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }

    public function store(Request $request, $tryout_detail_id)
    {
        try {
            $tryoutDetail = TryoutDetail::findOrFail($tryout_detail_id);

            $questionType = $request->input('question_type', 'multiple_choice');

            $baseRules = [
                'question_text' => 'required|string',
                'question_type' => 'required|in:' . implode(',', self::SUPPORTED_TYPES),
                'explanation' => 'nullable|string',
                'sound' => 'nullable|file|max:512000',
            ];

            $request->validate($baseRules);

            $soundPath = $this->handleSoundUpload($request);

            switch ($questionType) {
                case 'matching':
                    $this->validateMatchingQuestion($request);
                    $metadata = $this->buildMatchingMetadata($request);

                    Question::create([
                        'tryout_detail_id' => $tryout_detail_id,
                        'question_type' => 'matching',
                        'question_text' => $request->question_text,
                        'sound' => $soundPath,
                        'explanation' => $request->explanation,
                        'default_weight' => $this->getDefaultWeight($tryoutDetail->type_subtest),
                        'custom_score' => 'no',
                        'metadata' => $metadata,
                    ]);
                    break;

                case 'short_answer':
                case 'essay':
                    $this->validateShortAnswerQuestion($request);
                    $metadata = $this->buildShortAnswerMetadata($request, $questionType);

                    Question::create([
                        'tryout_detail_id' => $tryout_detail_id,
                        'question_type' => $questionType,
                        'question_text' => $request->question_text,
                        'sound' => $soundPath,
                        'explanation' => $request->explanation,
                        'default_weight' => $this->getDefaultWeight($tryoutDetail->type_subtest),
                        'custom_score' => 'no',
                        'metadata' => $metadata,
                    ]);
                    break;

                case 'audio':
                    $this->validateAudioAnswerQuestion($request);
                    $metadata = $this->buildAudioMetadata($request);

                    Question::create([
                        'tryout_detail_id' => $tryout_detail_id,
                        'question_type' => 'audio',
                        'question_text' => $request->question_text,
                        'sound' => $soundPath,
                        'explanation' => $request->explanation,
                        'default_weight' => $this->getDefaultWeight($tryoutDetail->type_subtest),
                        'custom_score' => 'no',
                        'metadata' => $metadata,
                    ]);
                    break;

                default:
                    $this->validateMultipleChoiceQuestion($request, $tryoutDetail, $questionType);

                    if ($questionType !== 'true_false' && $request->correct_answer === 'E' && empty($request->option_e)) {
                        throw ValidationException::withMessages([
                            'option_e' => 'Pilihan E tidak boleh kosong jika dipilih sebagai jawaban benar',
                        ]);
                    }

                    $isSKDType = in_array($tryoutDetail->type_subtest, ['twk', 'tiu', 'tkp']);
                    $useCustomScores = $request->boolean('use_custom_scores') || ($isSKDType && $tryoutDetail->type_subtest === 'tkp');

                    if ($questionType === 'true_false') {
                        $useCustomScores = false;
                        $request->merge([
                            'option_a' => $request->filled('option_a') ? $request->option_a : 'Benar',
                            'option_b' => $request->filled('option_b') ? $request->option_b : 'Salah',
                            'option_c' => null,
                            'option_d' => null,
                            'option_e' => null,
                            'correct_answer' => in_array($request->correct_answer, ['A', 'B']) ? $request->correct_answer : 'A',
                        ]);
                    }

                    $question = Question::create([
                        'tryout_detail_id' => $tryout_detail_id,
                        'question_type' => $questionType,
                        'question_text' => $request->question_text,
                        'sound' => $soundPath,
                        'explanation' => $request->explanation,
                        'default_weight' => $this->getDefaultWeight($tryoutDetail->type_subtest),
                        'custom_score' => $useCustomScores ? 'yes' : 'no',
                        'metadata' => [],
                    ]);

                    foreach ($this->prepareMultipleChoiceOptions($request, $questionType) as $option) {
                        $isCorrect = ($option['key'] === $request->correct_answer);
                        $weight = $this->calculateOptionWeight(
                            $tryoutDetail->type_subtest,
                            $option['key'],
                            $isCorrect,
                            $request,
                            $useCustomScores
                        );

                        QuestionOption::create([
                            'question_id' => $question->question_id,
                            'option_text' => $option['text'],
                            'weight' => $weight,
                            'is_correct' => $this->determineIsCorrect($tryoutDetail->type_subtest, $isCorrect, $weight),
                        ]);
                    }

                    $maxWeight = QuestionOption::where('question_id', $question->question_id)->max('weight');
                    $question->update(['default_weight' => $maxWeight]);
                    break;
            }

            return redirect()->route('admin.question.index', $tryout_detail_id)
                ->with('success', 'Soal berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menyimpan soal: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit($tryout_detail_id, $question_id)
    {
        try {
            $tryout_detail = TryoutDetail::findOrFail($tryout_detail_id);
            $tryout = Tryout::with('tryoutDetails')->where('tryout_id', $tryout_detail->tryout_id)->first();
            $question = Question::with(['questionOptions' => function ($query) {
                $query->orderBy('question_option_id');
            }])->where('question_id', $question_id)
                ->where('tryout_detail_id', $tryout_detail_id)
                ->firstOrFail();

            return view('admin.pages.question.create', compact('tryout', 'tryout_detail', 'question'));
        } catch (\Exception $e) {
            return redirect()->route('admin.tryout.index')
                ->with('error', 'Data tidak ditemukan: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $tryout_detail_id, $question_id)
    {
        try {
            $question = Question::where('question_id', $question_id)
                ->where('tryout_detail_id', $tryout_detail_id)
                ->firstOrFail();

            $tryoutDetail = TryoutDetail::findOrFail($tryout_detail_id);
            $questionType = $request->input('question_type', $question->question_type);

            $baseRules = [
                'question_text' => 'required|string',
                'question_type' => 'required|in:' . implode(',', self::SUPPORTED_TYPES),
                'explanation' => 'nullable|string',
                'sound' => 'nullable|file|max:512000',
            ];

            $request->validate($baseRules);

            $soundPath = $this->handleSoundUpload($request, $question->sound);

            switch ($questionType) {
                case 'matching':
                    $this->validateMatchingQuestion($request);
                    $metadata = $this->buildMatchingMetadata($request);

                    QuestionOption::where('question_id', $question->question_id)->delete();

                    $question->update([
                        'question_type' => 'matching',
                        'question_text' => $request->question_text,
                        'sound' => $soundPath,
                        'explanation' => $request->explanation,
                        'default_weight' => $this->getDefaultWeight($tryoutDetail->type_subtest),
                        'custom_score' => 'no',
                        'metadata' => $metadata,
                    ]);
                    break;

                case 'short_answer':
                case 'essay':
                    $this->validateShortAnswerQuestion($request);
                    $metadata = $this->buildShortAnswerMetadata($request, $questionType);

                    QuestionOption::where('question_id', $question->question_id)->delete();

                    $question->update([
                        'question_type' => $questionType,
                        'question_text' => $request->question_text,
                        'sound' => $soundPath,
                        'explanation' => $request->explanation,
                        'default_weight' => $this->getDefaultWeight($tryoutDetail->type_subtest),
                        'custom_score' => 'no',
                        'metadata' => $metadata,
                    ]);
                    break;

                case 'audio':
                    $this->validateAudioAnswerQuestion($request);
                    $metadata = $this->buildAudioMetadata($request);

                    QuestionOption::where('question_id', $question->question_id)->delete();

                    $question->update([
                        'question_type' => 'audio',
                        'question_text' => $request->question_text,
                        'sound' => $soundPath,
                        'explanation' => $request->explanation,
                        'default_weight' => $this->getDefaultWeight($tryoutDetail->type_subtest),
                        'custom_score' => 'no',
                        'metadata' => $metadata,
                    ]);
                    break;

                default:
                    $this->validateMultipleChoiceQuestion($request, $tryoutDetail, $questionType);

                    if ($questionType !== 'true_false' && $request->correct_answer === 'E' && empty($request->option_e)) {
                        throw ValidationException::withMessages([
                            'option_e' => 'Pilihan E tidak boleh kosong jika dipilih sebagai jawaban benar',
                        ]);
                    }

                    $isSKDType = in_array($tryoutDetail->type_subtest, ['twk', 'tiu', 'tkp']);
                    $useCustomScores = $request->boolean('use_custom_scores') || ($isSKDType && $tryoutDetail->type_subtest === 'tkp');

                    if ($questionType === 'true_false') {
                        $useCustomScores = false;
                        $request->merge([
                            'option_a' => $request->filled('option_a') ? $request->option_a : 'Benar',
                            'option_b' => $request->filled('option_b') ? $request->option_b : 'Salah',
                            'option_c' => null,
                            'option_d' => null,
                            'option_e' => null,
                            'correct_answer' => in_array($request->correct_answer, ['A', 'B']) ? $request->correct_answer : 'A',
                        ]);
                    }

                    $question->update([
                        'question_type' => $questionType,
                        'question_text' => $request->question_text,
                        'sound' => $soundPath,
                        'explanation' => $request->explanation,
                        'custom_score' => $useCustomScores ? 'yes' : 'no',
                        'metadata' => [],
                    ]);

                    QuestionOption::where('question_id', $question->question_id)->delete();

                    foreach ($this->prepareMultipleChoiceOptions($request, $questionType) as $option) {
                        $isCorrect = ($option['key'] === $request->correct_answer);
                        $weight = $this->calculateOptionWeight(
                            $tryoutDetail->type_subtest,
                            $option['key'],
                            $isCorrect,
                            $request,
                            $useCustomScores
                        );

                        QuestionOption::create([
                            'question_id' => $question->question_id,
                            'option_text' => $option['text'],
                            'weight' => $weight,
                            'is_correct' => $this->determineIsCorrect($tryoutDetail->type_subtest, $isCorrect, $weight),
                        ]);
                    }

                    $maxWeight = QuestionOption::where('question_id', $question->question_id)->max('weight');
                    $question->update(['default_weight' => $maxWeight]);
                    break;
            }

            $this->recalculateTryoutDetailScores($tryoutDetail);

            return redirect()->route('admin.question.index', $tryout_detail_id)
                ->with('success', 'Soal berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui soal: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($tryout_detail_id, $question_id)
    {
        try {
            $question = Question::where('question_id', $question_id)
                ->where('tryout_detail_id', $tryout_detail_id)
                ->firstOrFail();

            // Delete related question options first
            QuestionOption::where('question_id', $question_id)->delete();

            // Delete audio file if exists
            if ($question->sound && Storage::disk('public')->exists($question->sound)) {
                Storage::disk('public')->delete($question->sound);
            }

            // Delete the question
            $question->delete();

            return redirect()->route('admin.question.index', $tryout_detail_id)
                ->with('success', 'Soal berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus soal: ' . $e->getMessage());
        }
    }

    private function handleSoundUpload(Request $request, ?string $existingSound = null): ?string
    {
        if ($request->hasFile('sound')) {
            $soundPath = $request->file('sound')->store('questions/audio', 'public');

            if ($existingSound && $existingSound !== $soundPath && Storage::disk('public')->exists($existingSound)) {
                Storage::disk('public')->delete($existingSound);
            }

            return $soundPath;
        }

        return $existingSound;
    }

    private function validateMultipleChoiceQuestion(Request $request, TryoutDetail $tryoutDetail, string $questionType = 'multiple_choice'): void
    {
        $rules = [
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
            'option_d' => 'required|string',
            'option_e' => 'nullable|string',
            'correct_answer' => 'required|in:A,B,C,D,E',
            'use_custom_scores' => 'nullable|boolean',
            'score_a' => 'nullable|numeric',
            'score_b' => 'nullable|numeric',
            'score_c' => 'nullable|numeric',
            'score_d' => 'nullable|numeric',
            'score_e' => 'nullable|numeric',
        ];

        if ($questionType === 'true_false') {
            $rules = [
                'option_a' => 'nullable|string',
                'option_b' => 'nullable|string',
                'option_c' => 'nullable|string',
                'option_d' => 'nullable|string',
                'option_e' => 'nullable|string',
                'correct_answer' => 'required|in:A,B',
            ];
        }

        $attributes = [
            'option_a' => 'Opsi A',
            'option_b' => 'Opsi B',
            'option_c' => 'Opsi C',
            'option_d' => 'Opsi D',
            'option_e' => 'Opsi E',
            'correct_answer' => 'Jawaban benar',
        ];

        $request->validate($rules, [], $attributes);
    }

    private function prepareMultipleChoiceOptions(Request $request, string $questionType = 'multiple_choice'): array
    {
        if ($questionType === 'true_false') {
            return [
                ['key' => 'A', 'text' => $request->option_a ?: 'Benar'],
                ['key' => 'B', 'text' => $request->option_b ?: 'Salah'],
            ];
        }

        $options = [
            ['key' => 'A', 'text' => $request->option_a],
            ['key' => 'B', 'text' => $request->option_b],
            ['key' => 'C', 'text' => $request->option_c],
            ['key' => 'D', 'text' => $request->option_d],
        ];

        if ($request->filled('option_e')) {
            $options[] = ['key' => 'E', 'text' => $request->option_e];
        }

        return $options;
    }

    private function validateMatchingQuestion(Request $request): void
    {
        $rules = [
            'matching_pairs' => 'required|array|min:2',
            'matching_pairs.*.left' => 'required|string|min:1',
            'matching_pairs.*.right' => 'required|string|min:1',
        ];

        $attributes = [
            'matching_pairs' => 'Pasangan pencocokan',
            'matching_pairs.*.left' => 'Item kiri',
            'matching_pairs.*.right' => 'Item kanan',
        ];

        $request->validate($rules, [], $attributes);
    }

    private function buildMatchingMetadata(Request $request): array
    {
        $pairs = [];
        foreach ($request->input('matching_pairs', []) as $pair) {
            $left = isset($pair['left']) ? trim($pair['left']) : '';
            $right = isset($pair['right']) ? trim($pair['right']) : '';

            if ($left === '' || $right === '') {
                continue;
            }

            $pairs[] = [
                'left' => $left,
                'right' => $right,
            ];
        }

        return [
            'matching_pairs' => $pairs,
        ];
    }

    private function validateShortAnswerQuestion(Request $request): void
    {
        $rules = [
            'short_answer_expected' => 'nullable|string',
            'short_answer_case_sensitive' => 'nullable|boolean',
            'essay_scoring_mode' => 'nullable|in:auto,manual',
        ];

        $request->validate($rules);
    }

    private function buildShortAnswerMetadata(Request $request, string $questionType): array
    {
        $expectedRaw = $request->input('short_answer_expected');
        $expectedAnswers = [];

        if ($expectedRaw !== null) {
            $lines = preg_split("/\r\n|\r|\n/", $expectedRaw);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') {
                    $expectedAnswers[] = $trimmed;
                }
            }
        }

        $evaluationMode = $questionType === 'essay'
            ? $request->input('essay_scoring_mode', 'manual')
            : 'auto';

        if (!in_array($evaluationMode, ['auto', 'manual'], true)) {
            $evaluationMode = 'manual';
        }

        $caseSensitive = $questionType === 'essay'
            ? false
            : $request->boolean('short_answer_case_sensitive');

        $manualReview = $questionType === 'essay'
            ? ($evaluationMode !== 'auto' || empty($expectedAnswers))
            : empty($expectedAnswers);

        return [
            'short_answer' => [
                'expected_answers' => array_values($expectedAnswers),
                'case_sensitive' => $caseSensitive,
                'evaluation_mode' => $evaluationMode,
                'manual_review' => $manualReview,
            ],
        ];
    }

    private function validateAudioAnswerQuestion(Request $request): void
    {
        $rules = [
            'audio_instructions' => 'nullable|string',
            'audio_max_duration' => 'nullable|integer|min:5|max:600',
            'audio_max_size' => 'nullable|integer|min:1|max:100',
        ];

        $request->validate($rules);
    }

    private function buildAudioMetadata(Request $request): array
    {
        $config = [
            'instructions' => $request->input('audio_instructions'),
            'max_duration' => $request->filled('audio_max_duration') ? (int) $request->input('audio_max_duration') : null,
            'max_size' => $request->filled('audio_max_size') ? (int) $request->input('audio_max_size') : null,
            'allowed_mimes' => [
                'audio/mpeg',
                'audio/mp3',
                'audio/wav',
                'audio/x-wav',
                'audio/m4a',
                'audio/x-m4a',
            ],
        ];

        $config = array_filter(
            $config,
            function ($value, $key) {
                if ($key === 'allowed_mimes') {
                    return true;
                }
                return $value !== null && $value !== '';
            },
            ARRAY_FILTER_USE_BOTH
        );

        return [
            'audio_answer' => $config,
        ];
    }

    /**
     * Get default weight based on subtest type
     */
    private function getDefaultWeight($type_subtest)
    {
        switch ($type_subtest) {
            case 'twk':
            case 'tiu':
                return 5.00; // SKD TWK/TIU: 5 poin per soal
            case 'tkp':
                return 5.00; // SKD TKP: maksimal 5 poin per soal
            default:
                return 1.00; // Tryout biasa: 1 poin per soal
        }
    }

    /**
     * Calculate option weight based on subtest type and rules
     */
    private function calculateOptionWeight($type_subtest, $optionKey, $isCorrect, $request, $useCustomScores)
    {
        switch ($type_subtest) {
            case 'twk':
            case 'tiu':
                // SKD TWK/TIU: Benar = 5 poin, Salah = 0 poin
                return $isCorrect ? 5.00 : 0.00;

            case 'tkp':
                // SKD TKP: Semua opsi bisa diberi skor 1-5
                if ($useCustomScores) {
                    $scoreField = 'score_' . strtolower($optionKey);
                    return (float)($request->$scoreField ?? 1);
                }
                // Default TKP: jawaban benar = 5, lainnya = 1
                return $isCorrect ? 5.00 : 1.00;

            default:
                // Tryout biasa
                if ($useCustomScores) {
                    $scoreField = 'score_' . strtolower($optionKey);
                    return (float)($request->$scoreField ?? 0);
                }
                return $isCorrect ? 1.00 : 0.00;
        }
    }

    /**
     * Determine if option is considered "correct" based on subtest type
     */
    private function determineIsCorrect($type_subtest, $isCorrect, $weight)
    {
        switch ($type_subtest) {
            case 'tkp':
                // TKP: Semua opsi dengan weight > 0 dianggap "benar"
                return $weight > 0;
            default:
                // TWK, TIU, dan tryout biasa: hanya jawaban yang benar-benar correct
                return $isCorrect;
        }
    }

    private function recalculateTryoutDetailScores(TryoutDetail $tryoutDetail): void
    {
        $tryout = $tryoutDetail->tryout;
        if ($tryout && ($tryout->requiresIrtScoring() || $tryout->is_toefl)) {
            return;
        }

        $totalQuestions = Question::where('tryout_detail_id', $tryoutDetail->tryout_detail_id)->count();

        UserAnswer::where('tryout_detail_id', $tryoutDetail->tryout_detail_id)
            ->whereIn('status', ['completed', 'pending_release'])
            ->orderBy('user_answer_id')
            ->chunkById(200, function ($answers) use ($tryoutDetail, $totalQuestions) {
                foreach ($answers as $answer) {
                    $answer->loadMissing(['userAnswerDetails.question', 'userAnswerDetails.questionOption', 'tryoutDetail']);
                    $details = $answer->userAnswerDetails;
                    $correctAnswers = 0;
                    $wrongAnswers = 0;
                    $totalScore = 0.0;

                    foreach ($details as $detail) {
                        $question = $detail->question;
                        if (! $question) {
                            continue;
                        }

                        $questionType = $question->question_type ?? 'multiple_choice';
                        $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
                        $pendingReview = (bool) ($answerMeta['pending_review'] ?? false);

                        if ($detail->questionOption) {
                            $newIsCorrect = $this->determineIsCorrect(
                                $tryoutDetail->type_subtest,
                                (bool) $detail->questionOption->is_correct,
                                (float) ($detail->questionOption->weight ?? 0)
                            );
                            if ($detail->is_correct !== $newIsCorrect) {
                                $detail->update(['is_correct' => $newIsCorrect]);
                            }
                        }

                        switch ($questionType) {
                            case 'matching':
                                if ($detail->is_correct) {
                                    $correctAnswers++;
                                } else {
                                    $wrongAnswers++;
                                }

                                $weight = (float) ($question->default_weight ?? 1);
                                if ($weight <= 0) {
                                    $pairs = isset($question->metadata['matching_pairs']) && is_array($question->metadata['matching_pairs'])
                                        ? count($question->metadata['matching_pairs'])
                                        : 1;
                                    $weight = max(1, $pairs);
                                }
                                $totalScore += $detail->is_correct ? $weight : 0;
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
                                continue 2;

                            default:
                                if ($detail->questionOption) {
                                    if ($detail->is_correct) {
                                        $correctAnswers++;
                                    } else {
                                        $wrongAnswers++;
                                    }

                                    switch ($tryoutDetail->type_subtest) {
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

                    $unanswered = max(0, $totalQuestions - $details->count());
                    $maxScore = $this->getMaxPossibleScoreForDetail($tryoutDetail->tryout_detail_id, $tryoutDetail->type_subtest);
                    $percentage = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;
                    $isPassed = $this->isSubtestPassed($tryoutDetail, $totalScore, $maxScore, $tryoutDetail->type_subtest);

                    $answer->update([
                        'correct_answers' => $correctAnswers,
                        'wrong_answers' => $wrongAnswers,
                        'unanswered' => $unanswered,
                        'score' => $percentage,
                        'is_passed' => $isPassed,
                    ]);
                }
            }, 'user_answer_id');
    }

    private function getMaxPossibleScoreForDetail(int $tryoutDetailId, string $type_subtest): float
    {
        $questions = Question::where('tryout_detail_id', $tryoutDetailId)
            ->with('questionOptions')
            ->get();

        if ($questions->isEmpty()) {
            return 0;
        }

        $total = 0.0;

        foreach ($questions as $question) {
            $questionType = $question->question_type ?? 'multiple_choice';

            switch ($questionType) {
                case 'matching':
                    $weight = (float) ($question->default_weight ?? 0);
                    if ($weight <= 0) {
                        $pairs = isset($question->metadata['matching_pairs']) && is_array($question->metadata['matching_pairs'])
                            ? count($question->metadata['matching_pairs'])
                            : 1;
                        $weight = max(1, $pairs);
                    }
                    $total += $weight;
                    break;

                case 'short_answer':
                case 'essay':
                    $weight = (float) ($question->default_weight ?? 1);
                    $total += $weight > 0 ? $weight : 1;
                    break;

                case 'audio':
                    $total += (float) ($question->default_weight ?? 0);
                    break;

                default:
                    $options = $question->questionOptions;
                    switch ($type_subtest) {
                        case 'tkp':
                            $maxWeight = (float) ($options->max('weight') ?? 0);
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

    private function getDefaultPassingScore(?string $type_subtest): int
    {
        return match ($type_subtest) {
            'word', 'excel', 'ppt' => 70,
            'teknis', 'social culture', 'management', 'interview' => 65,
            default => 60,
        };
    }

    private function isSubtestPassed($detail, float $rawScore, float $maxScore, ?string $type): bool
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
}
