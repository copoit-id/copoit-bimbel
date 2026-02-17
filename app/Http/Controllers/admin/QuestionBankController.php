<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionBankQuestion;
use App\Models\QuestionBankQuestionOption;
use App\Models\QuestionOption;
use App\Models\TryoutDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuestionBankController extends Controller
{
    public function index(Request $request)
    {
        $importTarget = $request->integer('import_for');
        $search = trim((string) $request->query('q', ''));
        $tryoutDetail = $importTarget
            ? TryoutDetail::with('tryout')->find($importTarget)
            : null;

        $rootBanks = QuestionBank::withCount('questions')
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $aggregateCounts = $this->aggregateQuestionCounts();
        foreach ($rootBanks as $rootBank) {
            $rootBank->setAttribute('aggregate_questions_count', $aggregateCounts[$rootBank->id] ?? 0);
        }

        $stats = [
            'total_banks' => QuestionBank::count(),
            'total_questions' => QuestionBankQuestion::count(),
            'child_banks' => QuestionBank::whereNotNull('parent_id')->count(),
        ];

        $bankOptions = QuestionBank::orderBy('name')->get();
        $searchResults = collect();

        if ($search !== '') {
            $searchResults = QuestionBankQuestion::query()
                ->with('bank')
                ->where(function ($query) use ($search) {
                    $query->where('question_text', 'like', '%' . $search . '%')
                        ->orWhere('explanation', 'like', '%' . $search . '%')
                        ->orWhereHas('bank', function ($bankQuery) use ($search) {
                            $bankQuery->where('name', 'like', '%' . $search . '%');
                        });
                })
                ->latest()
                ->paginate(15)
                ->withQueryString();
        }

        return view('admin.pages.question-bank.index', compact(
            'rootBanks',
            'stats',
            'bankOptions',
            'tryoutDetail',
            'importTarget',
            'search',
            'searchResults'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:question_banks,id'],
        ]);

        QuestionBank::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Bank soal berhasil disimpan.');
    }

    public function edit(Request $request, QuestionBank $questionBank)
    {
        $importTarget = $request->integer('import_for');
        $excludedIds = array_merge([$questionBank->id], $this->descendantIds($questionBank->id));

        $parentOptions = QuestionBank::query()
            ->whereNotIn('id', $excludedIds)
            ->orderBy('name')
            ->get();

        return view('admin.pages.question-bank.edit', [
            'bank' => $questionBank,
            'parentOptions' => $parentOptions,
            'importTarget' => $importTarget,
        ]);
    }

    public function update(Request $request, QuestionBank $questionBank)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:question_banks,id'],
        ]);

        $parentId = $validated['parent_id'] ?? null;
        if ($parentId) {
            $invalidParents = array_merge([$questionBank->id], $this->descendantIds($questionBank->id));
            if (in_array((int) $parentId, $invalidParents, true)) {
                return back()->withErrors([
                    'parent_id' => 'Parent bank tidak valid.',
                ])->withInput();
            }
        }

        $questionBank->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $parentId,
        ]);

        return redirect()
            ->route('admin.question-bank.show', ['questionBank' => $questionBank->id, 'import_for' => $request->integer('import_for')])
            ->with('success', 'Bank soal berhasil diperbarui.');
    }

    public function destroy(Request $request, QuestionBank $questionBank)
    {
        if ($questionBank->children()->exists()) {
            return back()->with('error', 'Bank tidak dapat dihapus karena masih memiliki sub bank.');
        }

        if ($questionBank->questions()->exists()) {
            return back()->with('error', 'Bank tidak dapat dihapus karena masih memiliki soal.');
        }

        $questionBank->delete();

        return redirect()
            ->route('admin.question-bank.index', ['import_for' => $request->integer('import_for')])
            ->with('success', 'Bank soal berhasil dihapus.');
    }

    public function show(QuestionBank $questionBank, Request $request)
    {
        $importTarget = $request->integer('import_for');
        $tryoutDetail = $importTarget
            ? TryoutDetail::with('tryout')->find($importTarget)
            : null;

        $questionBank->load(['children' => function ($query) {
            $query->withCount('questions')->orderBy('name');
        }]);

        $aggregateCounts = $this->aggregateQuestionCounts();
        $questionBank->setAttribute('aggregate_questions_count', $aggregateCounts[$questionBank->id] ?? 0);
        foreach ($questionBank->children as $child) {
            $child->setAttribute('aggregate_questions_count', $aggregateCounts[$child->id] ?? 0);
        }

        $questions = $questionBank->questions()
            ->with('options')
            ->latest()
            ->paginate(15);

        $breadcrumbs = $this->buildBreadcrumbs($questionBank);

        return view('admin.pages.question-bank.show', [
            'bank' => $questionBank,
            'questions' => $questions,
            'breadcrumbs' => $breadcrumbs,
            'tryoutDetail' => $tryoutDetail,
            'importTarget' => $importTarget,
        ]);
    }

    public function createQuestionForm(Request $request, QuestionBank $questionBank)
    {
        $importTarget = $request->integer('import_for');
        $matchingPairs = old('matching_pairs', [
            ['left' => '', 'right' => ''],
            ['left' => '', 'right' => ''],
        ]);

        if (is_array($matchingPairs) && count($matchingPairs) < 2) {
            $matchingPairs = array_pad($matchingPairs, 2, ['left' => '', 'right' => '']);
        }

        return view('admin.pages.question-bank.create-question', [
            'bank' => $questionBank,
            'importTarget' => $importTarget,
            'matchingPairs' => $matchingPairs,
        ]);
    }

    public function editQuestionForm(Request $request, QuestionBankQuestion $question)
    {
        $importTarget = $request->integer('import_for');
        $question->load('options', 'bank');

        $metadata = is_array($question->metadata) ? $question->metadata : [];
        $matchingPairs = $metadata['matching_pairs'] ?? [
            ['left' => '', 'right' => ''],
            ['left' => '', 'right' => ''],
        ];

        if (is_array($matchingPairs) && count($matchingPairs) < 2) {
            $matchingPairs = array_pad($matchingPairs, 2, ['left' => '', 'right' => '']);
        }

        return view('admin.pages.question-bank.edit-question', [
            'bank' => $question->bank,
            'question' => $question,
            'importTarget' => $importTarget,
            'matchingPairs' => $matchingPairs,
        ]);
    }

    public function storeQuestion(Request $request, QuestionBank $questionBank)
    {
        $questionType = $request->input('question_type', 'multiple_choice');
        $importTarget = $request->integer('import_for');

        $baseRules = [
            'question_type' => ['required', 'in:multiple_choice,true_false,matching,essay,short_answer,audio'],
            'question_text' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'default_weight' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'custom_score' => ['nullable', 'boolean'],
            'sound' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/m4a,audio/x-m4a', 'max:5120'],
        ];

        switch ($questionType) {
            case 'multiple_choice':
                $this->validateMultipleChoice($request);
                break;
            case 'true_false':
                $this->validateTrueFalse($request);
                break;
            case 'matching':
                $this->validateMatching($request);
                break;
            case 'essay':
            case 'short_answer':
                $this->validateShortAnswer($request);
                break;
            case 'audio':
                $this->validateAudio($request);
                break;
        }

        $validated = $request->validate($baseRules);

        $metadata = $this->buildMetadata($request, $questionType);

        $soundPath = null;
        if ($request->hasFile('sound')) {
            $soundPath = $request->file('sound')->store('question-bank/audio', 'public');
        }

        DB::transaction(function () use ($questionBank, $validated, $metadata, $questionType, $request, $soundPath) {
            $bankQuestion = QuestionBankQuestion::create([
                'question_bank_id' => $questionBank->id,
                'question_type' => $questionType,
                'question_text' => $validated['question_text'],
                'explanation' => $validated['explanation'] ?? null,
                'default_weight' => $validated['default_weight'] ?? 1,
                'custom_score' => $request->boolean('use_custom_scores') ? 'yes' : 'no',
                'metadata' => $metadata ?: null,
                'sound' => $soundPath,
                'created_by' => Auth::id(),
            ]);

            if (in_array($questionType, ['multiple_choice', 'true_false'])) {
                $options = $this->prepareOptions($request, $questionType);
                foreach ($options as $index => $option) {
                    QuestionBankQuestionOption::create([
                        'question_bank_question_id' => $bankQuestion->id,
                        'option_text' => $option['text'],
                        'weight' => $option['weight'],
                        'is_correct' => $option['is_correct'],
                        'position' => $index + 1,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.question-bank.show', ['questionBank' => $questionBank->id, 'import_for' => $importTarget])
            ->with('success', 'Soal berhasil ditambahkan ke bank.');
    }

    public function updateQuestion(Request $request, QuestionBankQuestion $question)
    {
        $questionType = $request->input('question_type', 'multiple_choice');
        $importTarget = $request->integer('import_for');

        $baseRules = [
            'question_type' => ['required', 'in:multiple_choice,true_false,matching,essay,short_answer,audio'],
            'question_text' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'default_weight' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'custom_score' => ['nullable', 'boolean'],
            'sound' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/m4a,audio/x-m4a', 'max:5120'],
        ];

        switch ($questionType) {
            case 'multiple_choice':
                $this->validateMultipleChoice($request);
                break;
            case 'true_false':
                $this->validateTrueFalse($request);
                break;
            case 'matching':
                $this->validateMatching($request);
                break;
            case 'essay':
            case 'short_answer':
                $this->validateShortAnswer($request);
                break;
            case 'audio':
                $this->validateAudio($request);
                break;
        }

        $validated = $request->validate($baseRules);
        $metadata = $this->buildMetadata($request, $questionType);

        $soundPath = $question->sound;
        if ($request->hasFile('sound')) {
            if ($soundPath) {
                Storage::disk('public')->delete($soundPath);
            }
            $soundPath = $request->file('sound')->store('question-bank/audio', 'public');
        }

        DB::transaction(function () use ($question, $validated, $metadata, $questionType, $request, $soundPath) {
            $question->update([
                'question_type' => $questionType,
                'question_text' => $validated['question_text'],
                'explanation' => $validated['explanation'] ?? null,
                'default_weight' => $validated['default_weight'] ?? 1,
                'custom_score' => $request->boolean('use_custom_scores') ? 'yes' : 'no',
                'metadata' => $metadata ?: null,
                'sound' => $soundPath,
            ]);

            $question->options()->delete();

            if (in_array($questionType, ['multiple_choice', 'true_false'])) {
                $options = $this->prepareOptions($request, $questionType);
                foreach ($options as $index => $option) {
                    QuestionBankQuestionOption::create([
                        'question_bank_question_id' => $question->id,
                        'option_text' => $option['text'],
                        'weight' => $option['weight'],
                        'is_correct' => $option['is_correct'],
                        'position' => $index + 1,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.question-bank.show', ['questionBank' => $question->question_bank_id, 'import_for' => $importTarget])
            ->with('success', 'Soal berhasil diperbarui.');
    }

    public function cloneToTryout(Request $request, QuestionBankQuestion $question)
    {
        $validated = $request->validate([
            'tryout_detail_id' => ['required', 'exists:tryout_details,tryout_detail_id'],
        ]);

        $tryoutDetail = TryoutDetail::findOrFail($validated['tryout_detail_id']);

        DB::transaction(function () use ($question, $tryoutDetail) {
            $newQuestion = Question::create([
                'tryout_detail_id' => $tryoutDetail->tryout_detail_id,
                'question_type' => $question->question_type,
                'question_text' => $question->question_text,
                'sound' => $question->sound,
                'explanation' => $question->explanation,
                'metadata' => $question->metadata,
                'default_weight' => $question->default_weight ?? 1,
                'custom_score' => $question->custom_score ?? 'no',
            ]);

            foreach ($question->options as $option) {
                QuestionOption::create([
                    'question_id' => $newQuestion->question_id,
                    'option_text' => $option->option_text,
                    'weight' => $option->weight,
                    'is_correct' => $option->is_correct,
                ]);
            }
        });

        return redirect()
            ->route('admin.question.index', $validated['tryout_detail_id'])
            ->with('success', 'Soal dari bank berhasil ditambahkan.');
    }

    public function bulkCloneToTryout(Request $request)
    {
        $validated = $request->validate([
            'tryout_detail_id' => ['required', 'exists:tryout_details,tryout_detail_id'],
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['exists:question_bank_questions,id'],
        ], [], [
            'question_ids' => 'Daftar soal',
        ]);

        $tryoutDetail = TryoutDetail::findOrFail($validated['tryout_detail_id']);

        $questions = QuestionBankQuestion::with('options')
            ->whereIn('id', $validated['question_ids'])
            ->get();

        DB::transaction(function () use ($questions, $tryoutDetail) {
            foreach ($questions as $question) {
                $newQuestion = Question::create([
                    'tryout_detail_id' => $tryoutDetail->tryout_detail_id,
                    'question_type' => $question->question_type,
                    'question_text' => $question->question_text,
                    'sound' => $question->sound,
                    'explanation' => $question->explanation,
                    'metadata' => $question->metadata,
                    'default_weight' => $question->default_weight ?? 1,
                    'custom_score' => $question->custom_score ?? 'no',
                ]);

                foreach ($question->options as $option) {
                    QuestionOption::create([
                        'question_id' => $newQuestion->question_id,
                        'option_text' => $option->option_text,
                        'weight' => $option->weight,
                        'is_correct' => $option->is_correct,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.question.index', $validated['tryout_detail_id'])
            ->with('success', 'Soal dari bank berhasil ditambahkan.');
    }

    public function destroyQuestion(QuestionBankQuestion $question)
    {
        $question->delete();

        return back()->with('success', 'Soal berhasil dihapus dari bank.');
    }

    private function buildBreadcrumbs(QuestionBank $bank): array
    {
        $breadcrumbs = [];
        $current = $bank;

        while ($current) {
            $breadcrumbs[] = $current;
            $current = $current->parent;
        }

        return array_reverse($breadcrumbs);
    }

    private function descendantIds(int $bankId): array
    {
        $allBanks = QuestionBank::query()->select('id', 'parent_id')->get();
        $childrenByParent = [];
        foreach ($allBanks as $bank) {
            $childrenByParent[(int) $bank->parent_id][] = (int) $bank->id;
        }

        $result = [];
        $stack = $childrenByParent[$bankId] ?? [];
        while (!empty($stack)) {
            $current = array_pop($stack);
            $result[] = $current;
            foreach ($childrenByParent[$current] ?? [] as $childId) {
                $stack[] = $childId;
            }
        }

        return array_values(array_unique($result));
    }

    private function aggregateQuestionCounts(): array
    {
        $banks = QuestionBank::query()->select('id', 'parent_id')->get();
        $directQuestionCounts = QuestionBankQuestion::query()
            ->selectRaw('question_bank_id, COUNT(*) as total')
            ->groupBy('question_bank_id')
            ->pluck('total', 'question_bank_id')
            ->map(fn ($value) => (int) $value)
            ->all();

        $childrenByParent = [];
        foreach ($banks as $bank) {
            $parentKey = (int) ($bank->parent_id ?? 0);
            $childrenByParent[$parentKey][] = (int) $bank->id;
        }

        $memo = [];
        $countFor = function (int $bankId) use (&$countFor, &$memo, $childrenByParent, $directQuestionCounts): int {
            if (array_key_exists($bankId, $memo)) {
                return $memo[$bankId];
            }

            $total = $directQuestionCounts[$bankId] ?? 0;
            foreach ($childrenByParent[$bankId] ?? [] as $childId) {
                $total += $countFor($childId);
            }

            $memo[$bankId] = $total;

            return $total;
        };

        foreach ($banks as $bank) {
            $countFor((int) $bank->id);
        }

        return $memo;
    }

    private function validateMultipleChoice(Request $request): void
    {
        $request->validate([
            'option_a' => ['required', 'string'],
            'option_b' => ['required', 'string'],
            'option_c' => ['required', 'string'],
            'option_d' => ['required', 'string'],
            'option_e' => ['nullable', 'string'],
            'correct_answer' => ['required', 'in:A,B,C,D,E'],
            'score_a' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'score_b' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'score_c' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'score_d' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'score_e' => ['nullable', 'numeric', 'min:0', 'max:5'],
        ], [], [
            'option_a' => 'Pilihan A',
            'option_b' => 'Pilihan B',
            'option_c' => 'Pilihan C',
            'option_d' => 'Pilihan D',
            'option_e' => 'Pilihan E',
            'correct_answer' => 'Jawaban benar',
        ]);
    }

    private function validateTrueFalse(Request $request): void
    {
        $request->validate([
            'correct_answer' => ['required', 'in:A,B'],
        ], [], [
            'correct_answer' => 'Jawaban benar',
        ]);
    }

    private function validateMatching(Request $request): void
    {
        $request->validate([
            'matching_pairs' => ['required', 'array', 'min:2'],
            'matching_pairs.*.left' => ['required', 'string'],
            'matching_pairs.*.right' => ['required', 'string'],
        ], [], [
            'matching_pairs' => 'Pasangan pencocokan',
            'matching_pairs.*.left' => 'Kolom kiri',
            'matching_pairs.*.right' => 'Kolom kanan',
        ]);
    }

    private function validateShortAnswer(Request $request): void
    {
        $request->validate([
            'short_answer_expected' => ['nullable', 'string'],
            'short_answer_case_sensitive' => ['nullable', 'boolean'],
            'essay_scoring_mode' => ['nullable', 'in:auto,manual'],
        ]);
    }

    private function validateAudio(Request $request): void
    {
        $request->validate([
            'audio_instructions' => ['nullable', 'string'],
            'audio_max_duration' => ['nullable', 'integer', 'min:5', 'max:600'],
            'audio_max_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }

    private function buildMetadata(Request $request, string $type): array
    {
        return match ($type) {
            'matching' => $this->buildMatchingMetadata($request),
            'short_answer', 'essay' => $this->buildShortAnswerMetadata($request, $type),
            'audio' => $this->buildAudioMetadata($request),
            default => [],
        };
    }

    private function buildMatchingMetadata(Request $request): array
    {
        $pairs = [];
        foreach ($request->input('matching_pairs', []) as $pair) {
            $left = trim($pair['left'] ?? '');
            $right = trim($pair['right'] ?? '');
            if ($left === '' || $right === '') {
                continue;
            }
            $pairs[] = ['left' => $left, 'right' => $right];
        }

        return ['matching_pairs' => $pairs];
    }

    private function buildShortAnswerMetadata(Request $request, string $type): array
    {
        $expectedRaw = $request->input('short_answer_expected', '');
        $expectedAnswers = collect(preg_split("/\r\n|\r|\n/", $expectedRaw))
            ->filter(fn ($line) => filled(trim($line)))
            ->map(fn ($line) => trim($line))
            ->values()
            ->all();

        $evaluationMode = $type === 'essay'
            ? $request->input('essay_scoring_mode', 'manual')
            : 'auto';

        if (!in_array($evaluationMode, ['auto', 'manual'], true)) {
            $evaluationMode = 'manual';
        }

        $caseSensitive = $type === 'essay'
            ? false
            : $request->boolean('short_answer_case_sensitive');

        return [
            'short_answer' => [
                'expected_answers' => $expectedAnswers,
                'case_sensitive' => $caseSensitive,
                'evaluation_mode' => $evaluationMode,
                'manual_review' => $type === 'essay'
                    ? ($evaluationMode !== 'auto' || empty($expectedAnswers))
                    : empty($expectedAnswers),
            ],
        ];
    }

    private function buildAudioMetadata(Request $request): array
    {
        return [
            'audio_answer' => [
                'instructions' => $request->input('audio_instructions'),
                'max_duration' => $request->filled('audio_max_duration') ? (int) $request->input('audio_max_duration') : null,
                'max_size' => $request->filled('audio_max_size') ? (int) $request->input('audio_max_size') : null,
                'allowed_mimes' => [
                    'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/m4a', 'audio/x-m4a',
                ],
            ],
        ];
    }

    private function prepareOptions(Request $request, string $type): array
    {
        $options = [];

        if ($type === 'true_false') {
            $options = [
                ['key' => 'A', 'text' => $request->option_a ?: 'Benar'],
                ['key' => 'B', 'text' => $request->option_b ?: 'Salah'],
            ];
        } else {
            $options = [
                ['key' => 'A', 'text' => $request->option_a],
                ['key' => 'B', 'text' => $request->option_b],
                ['key' => 'C', 'text' => $request->option_c],
                ['key' => 'D', 'text' => $request->option_d],
            ];

            if ($request->filled('option_e')) {
                $options[] = ['key' => 'E', 'text' => $request->option_e];
            }
        }

        $useCustomScores = $request->boolean('use_custom_scores');
        $correctAnswer = $request->input('correct_answer', 'A');

        return collect($options)->map(function ($option) use ($useCustomScores, $correctAnswer, $request, $type) {
            $scoreField = 'score_' . strtolower($option['key']);
            $weight = $useCustomScores ? (float) ($request->input($scoreField, 0)) : ($option['key'] === $correctAnswer ? 1 : 0);

            if ($type === 'true_false') {
                $weight = $option['key'] === $correctAnswer ? 1 : 0;
            }

            return [
                'text' => $option['text'],
                'weight' => $weight,
                'is_correct' => $option['key'] === $correctAnswer,
            ];
        })->toArray();
    }
}
