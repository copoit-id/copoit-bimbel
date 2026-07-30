<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionBankQuestion;
use App\Models\QuestionOption;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use App\Services\AdminQuestionGeneratorQuotaService;
use App\Services\AiQuestionGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TryoutAiQuestionGeneratorController extends Controller
{
    public function form(
        TryoutDetail $tryoutDetail,
        AiQuestionGeneratorService $aiGeneratorService,
        AdminQuestionGeneratorQuotaService $quotaService
    ): View {
        abort_unless($aiGeneratorService->isEnabled(), 404);

        $tryoutDetail->load('tryout');
        $models = $aiGeneratorService->availableModels();
        abort_if(empty($models), 404);

        $quota = null;
        $gatewayError = null;

        if (! $quotaService->isConfigured()) {
            $gatewayError = 'Gateway AI Generator Soal belum dikonfigurasi. Hubungi Super Admin untuk melengkapi konfigurasi Gateway AI.';
        } else {
            try {
                $quota = $quotaService->summary(Auth::user());
            } catch (\Throwable $exception) {
                report($exception);
                $gatewayError = 'Gateway AI Generator Soal belum dapat dihubungi. Silakan coba lagi atau hubungi Super Admin.';
            }
        }

        if ($gatewayError !== null) {
            session()->flash('warning', $gatewayError);
        }

        return view('admin.pages.question-bank.ai-generator', [
            'generatorMode' => 'tryout',
            'tryoutDetail' => $tryoutDetail,
            'importTarget' => null,
            'models' => $models,
            'defaultModel' => $aiGeneratorService->defaultModel(),
            'preview' => session($this->previewSessionKey($tryoutDetail)),
            'referenceBanks' => QuestionBank::query()->withCount('questions')->orderBy('name')->get(['id', 'name']),
            'referenceTryouts' => $this->referenceTryouts(),
            'quota' => $quota,
            'gatewayError' => $gatewayError,
        ]);
    }

    public function preview(
        Request $request,
        TryoutDetail $tryoutDetail,
        AiQuestionGeneratorService $aiGeneratorService,
        AdminQuestionGeneratorQuotaService $quotaService
    ): RedirectResponse {
        abort_unless($aiGeneratorService->isEnabled(), 404);

        $models = $aiGeneratorService->availableModels();
        abort_if(empty($models), 404);
        $model = $aiGeneratorService->defaultModel();

        $validated = $this->validatedGeneratorRequest($request);

        try {
            $quotaService->ensureAvailable(Auth::user());
            $validated = [
                ...$validated,
                'model' => $model,
                ...$this->resolveReference($validated),
            ];
            $preview = $aiGeneratorService->generate($validated);
            $preview['request'] = $validated;

            session()->put($this->previewSessionKey($tryoutDetail), $preview);

            return redirect()
                ->route('admin.question.ai-generator', $tryoutDetail)
                ->with('success', count($preview['questions']).' soal berhasil dibuat sebagai preview. Review dulu sebelum disimpan.');
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }
    }

    public function store(
        Request $request,
        TryoutDetail $tryoutDetail,
        AiQuestionGeneratorService $aiGeneratorService
    ): RedirectResponse {
        abort_unless($aiGeneratorService->isEnabled(), 404);

        $models = $aiGeneratorService->availableModels();
        abort_if(empty($models), 404);
        $model = $aiGeneratorService->defaultModel();

        $validated = $request->validate([
            'questions_json' => ['required', 'string'],
        ]);

        $questions = json_decode($validated['questions_json'], true);
        if (! is_array($questions)) {
            return back()->with('error', 'Data preview AI tidak valid. Silakan generate ulang.');
        }

        $questions = $this->normalizePreviewQuestions($questions);
        if (empty($questions)) {
            return back()->with('error', 'Tidak ada soal valid untuk disimpan.');
        }

        $storedCount = 0;
        DB::transaction(function () use ($tryoutDetail, $questions, $model, &$storedCount): void {
            foreach ($questions as $question) {
                $questionScore = (float) ($question['question_score'] ?? 1);
                $storedQuestion = Question::create([
                    'tryout_detail_id' => $tryoutDetail->tryout_detail_id,
                    'question_type' => 'multiple_choice',
                    'question_text' => $question['question_text'],
                    'explanation' => $question['explanation'] ?: null,
                    'default_weight' => $questionScore,
                    'custom_score' => 'yes',
                    'metadata' => [
                        'source' => 'ai_generator',
                        'model' => $model,
                        'generated_at' => now()->toDateTimeString(),
                    ],
                ]);

                foreach ($question['options'] as $option) {
                    $isCorrect = $option['label'] === $question['correct_option'];

                    QuestionOption::create([
                        'question_id' => $storedQuestion->question_id,
                        'option_text' => $option['text'],
                        'weight' => (float) ($option['score'] ?? ($isCorrect ? $questionScore : 0)),
                        'is_correct' => $isCorrect,
                    ]);
                }

                $storedCount++;
            }
        });

        session()->forget($this->previewSessionKey($tryoutDetail));

        return redirect()
            ->route('admin.question.index', $tryoutDetail)
            ->with('success', "{$storedCount} soal AI berhasil disimpan ke subtest ini.");
    }

    public function reset(TryoutDetail $tryoutDetail): RedirectResponse
    {
        session()->forget($this->previewSessionKey($tryoutDetail));

        return redirect()
            ->route('admin.question.ai-generator', $tryoutDetail)
            ->with('success', 'Preview AI berhasil direset.');
    }

    private function validatedGeneratorRequest(Request $request): array
    {
        return $request->validate([
            'subject' => ['required', 'string', 'max:120'],
            'topic' => ['required', 'string', 'max:180'],
            'difficulty' => ['required', Rule::in(['mudah', 'sedang', 'sulit', 'campuran'])],
            'question_count' => ['required', 'integer', 'min:1', 'max:25'],
            'option_count' => ['required', 'integer', 'min:2', 'max:5'],
            'explanation_style' => ['required', Rule::in(['singkat', 'normal', 'detail'])],
            'instruction' => ['nullable', 'string', 'max:1500'],
            'use_reference' => ['nullable', 'boolean'],
            'reference_source' => ['nullable', Rule::in(['question_bank', 'tryout'])],
            'reference_bank_id' => ['nullable', 'integer'],
            'reference_tryout_id' => ['nullable', 'integer'],
            'reference_tryout_detail_id' => ['nullable', 'integer'],
            'reference_note' => ['nullable', 'string', 'max:1500'],
        ], [], [
            'subject' => 'mata pelajaran/kategori',
            'topic' => 'topik',
            'question_count' => 'jumlah soal',
            'option_count' => 'jumlah opsi',
            'explanation_style' => 'gaya pembahasan',
            'instruction' => 'instruksi tambahan',
        ]);
    }

    /** @param array<string, mixed> $input */
    private function resolveReference(array $input): array
    {
        if (! filter_var($input['use_reference'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return ['reference_examples' => [], 'reference_label' => null, 'reference_note' => null];
        }

        $source = $input['reference_source'] ?? null;
        $note = trim((string) ($input['reference_note'] ?? ''));

        if (! $source) {
            throw new \RuntimeException('Pilih sumber referensi gaya soal.');
        }

        if ($source === 'question_bank') {
            $bankId = (int) ($input['reference_bank_id'] ?? 0);
            if ($bankId < 1) {
                throw new \RuntimeException('Pilih bank soal yang akan dijadikan referensi.');
            }

            $bank = QuestionBank::query()->findOrFail($bankId, ['id', 'name']);
            $examples = QuestionBankQuestion::query()
                ->with('options:id,question_bank_question_id,option_text')
                ->where('question_bank_id', $bank->id)
                ->latest('id')
                ->limit(3)
                ->get(['id', 'question_text'])
                ->map(fn (QuestionBankQuestion $question): array => [
                    'question' => Str::limit(strip_tags((string) $question->question_text), 900),
                    'options' => $question->options->pluck('option_text')->map(fn ($option) => Str::limit(strip_tags((string) $option), 180))->all(),
                ])->all();

            if (empty($examples)) {
                throw new \RuntimeException('Bank soal referensi belum memiliki soal yang dapat dipakai.');
            }

            return [
                'reference_examples' => $examples,
                'reference_label' => 'Bank soal: '.$bank->name,
                'reference_note' => $note,
            ];
        }

        $tryoutId = (int) ($input['reference_tryout_id'] ?? 0);
        $tryoutDetailId = (int) ($input['reference_tryout_detail_id'] ?? 0);
        if ($tryoutId < 1 || $tryoutDetailId < 1) {
            throw new \RuntimeException('Pilih tryout dan subtest yang akan dijadikan referensi.');
        }

        $tryout = Tryout::query()->findOrFail($tryoutId, ['tryout_id', 'name']);
        $referenceDetail = TryoutDetail::query()
            ->where('tryout_id', $tryout->tryout_id)
            ->findOrFail($tryoutDetailId, ['tryout_detail_id', 'tryout_id', 'type_subtest']);
        $examples = Question::query()
            ->with('questionOptions:question_option_id,question_id,option_text')
            ->where('tryout_detail_id', $referenceDetail->tryout_detail_id)
            ->latest('question_id')
            ->limit(3)
            ->get(['question_id', 'question_text'])
            ->map(fn (Question $question): array => [
                'question' => Str::limit(strip_tags((string) $question->question_text), 900),
                'options' => $question->questionOptions->pluck('option_text')->map(fn ($option) => Str::limit(strip_tags((string) $option), 180))->all(),
            ])->all();

        if (empty($examples)) {
            throw new \RuntimeException('Subtest tryout referensi belum memiliki soal yang dapat dipakai.');
        }

        return [
            'reference_examples' => $examples,
            'reference_label' => 'Tryout: '.$tryout->name.' · '.strtoupper((string) $referenceDetail->type_subtest),
            'reference_note' => $note,
        ];
    }

    private function previewSessionKey(TryoutDetail $tryoutDetail): string
    {
        return 'ai_tryout_question_preview_'.$tryoutDetail->tryout_detail_id;
    }

    /** @return array<int, array<string, mixed>> */
    private function normalizePreviewQuestions(array $questions): array
    {
        $letters = ['A', 'B', 'C', 'D', 'E'];

        return collect($questions)
            ->take(50)
            ->map(function ($question) use ($letters): array {
                $options = collect($question['options'] ?? [])
                    ->values()
                    ->map(function ($option, $index) use ($letters): array {
                        return [
                            'label' => strtoupper(trim((string) ($option['label'] ?? $letters[$index] ?? ''))),
                            'text' => trim((string) ($option['text'] ?? '')),
                            'score' => max(0, min(999, (float) ($option['score'] ?? 0))),
                        ];
                    })
                    ->filter(fn (array $option): bool => $option['label'] !== '' && $option['text'] !== '')
                    ->unique('label')
                    ->values()
                    ->all();

                return [
                    'question_text' => trim((string) ($question['question_text'] ?? '')),
                    'question_score' => max(0, min(999, (float) ($question['question_score'] ?? 1))),
                    'options' => $options,
                    'correct_option' => strtoupper(trim((string) ($question['correct_option'] ?? ''))),
                    'explanation' => trim((string) ($question['explanation'] ?? '')),
                ];
            })
            ->filter(fn (array $question): bool => $question['question_text'] !== ''
                && count($question['options']) >= 2
                && collect($question['options'])->contains('label', $question['correct_option']))
            ->values()
            ->all();
    }

    private function referenceTryouts()
    {
        return Tryout::query()
            ->with(['tryoutDetails' => fn ($query) => $query
                ->select(['tryout_detail_id', 'tryout_id', 'type_subtest'])
                ->orderBy('type_subtest')])
            ->latest('created_at')
            ->limit(100)
            ->get(['tryout_id', 'name']);
    }
}
