@extends($questionForm['layout'])
@section('title', $questionForm['title'])
@section('content')

<style>
    .question-editor-form .question-type-section {
        border: 1px solid rgb(226 232 240);
        border-radius: 0.875rem;
        background: rgb(248 250 252);
        padding: 1.25rem;
    }

    .question-editor-form .question-type-section > :first-child h3 {
        color: rgb(15 23 42);
        font-weight: 700;
    }

    .question-editor-form .option-row,
    .question-editor-form .matching-pair-row {
        border: 1px solid rgb(226 232 240);
        border-radius: 0.75rem;
        background: white;
        padding: 1rem;
    }

    .question-editor-form .option-row:focus-within,
    .question-editor-form .matching-pair-row:focus-within {
        border-color: color-mix(in srgb, var(--primary-color, #2563eb) 45%, white);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary-color, #2563eb) 10%, transparent);
    }

    .question-editor-form .score-settings {
        border: 1px solid rgb(226 232 240);
        border-radius: 0.75rem;
        background: white;
        padding: 1rem;
    }

    @media (max-width: 639px) {
        .question-editor-form .question-type-section {
            padding: 1rem;
        }

        .question-editor-form .option-row {
            gap: 0.75rem;
            padding: 0.875rem;
        }
    }
</style>

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            @foreach ($questionForm['breadcrumbs'] as $breadcrumb)
                <x-breadcrumb-item href="{{ $breadcrumb['url'] ?? '' }}" title="{{ $breadcrumb['title'] }}" />
            @endforeach
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="{{ $questionForm['pageTitle'] }}">
    <x-slot name="description">
        {{ $questionForm['description'] }}
    </x-slot>
</x-page-desc>

<div class="space-y-6">
    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <form action="{{ $questionForm['action'] }}" method="POST" enctype="multipart/form-data" novalidate class="question-editor-form">
            @csrf
            @if($questionForm['method'] !== 'POST')
            @method('PUT')
            @endif
            @if($questionForm['importTarget'])
                <input type="hidden" name="import_for" value="{{ $questionForm['importTarget'] }}">
            @endif

            @php
            $rawType = old('question_type', isset($question) ? $question->question_type : 'multiple_choice');
            $currentType = $rawType === 'true_false' ? 'multiple_choice' : $rawType;

            $metadata = isset($question) ? ($question->metadata ?? []) : [];

            $matchingPairsInput = old('matching_pairs', $metadata['matching_pairs'] ?? []);
            $normalizedPairs = [];
            if (is_array($matchingPairsInput)) {
            foreach ($matchingPairsInput as $pair) {
            $normalizedPairs[] = [
            'left' => is_array($pair) ? ($pair['left'] ?? '') : '',
            'right' => is_array($pair) ? ($pair['right'] ?? '') : '',
            ];
            }
            }
            while (count($normalizedPairs) < 2) { $normalizedPairs[]=['left'=> '', 'right' => ''];
                }

                $shortAnswerMeta = $metadata['short_answer'] ?? [];
                $shortAnswerExpected = old('short_answer_expected', isset($shortAnswerMeta['expected_answers']) ?
                implode("\n", $shortAnswerMeta['expected_answers']) : '');
                $shortAnswerCaseSensitive = filter_var(old('short_answer_case_sensitive',
                $shortAnswerMeta['case_sensitive'] ?? false), FILTER_VALIDATE_BOOLEAN);
                
                // Essay AI Quota Check
                $essayAI = $planQuota['essay_ai'] ?? \App\Services\PlanQuotaService::canUseEssayAI();
                $essayAutoAvailable = (bool) ($essayAI['allowed'] ?? false);
                
                $essayEvaluationMode = old(
                    'essay_evaluation_mode',
                    $shortAnswerMeta['evaluation_mode'] ?? 'manual'
                );
                // Force manual jika AI tidak tersedia
                if (!$essayAutoAvailable && $essayEvaluationMode === 'auto') {
                    $essayEvaluationMode = 'manual';
                }
                
                $essayScoringMode = old(
                    'essay_scoring_mode',
                    isset($question) ? $question->essay_scoring_mode : 'full'
                );

                $audioMeta = $metadata['audio_answer'] ?? [];
                $audioInstructions = old('audio_instructions', $audioMeta['instructions'] ?? '');
                $audioMaxDuration = old('audio_max_duration', $audioMeta['max_duration'] ?? '');
                $audioMaxSize = old('audio_max_size', $audioMeta['max_size'] ?? '');

                $mtfMeta = is_array($metadata['multiple_true_false'] ?? null) ? $metadata['multiple_true_false'] : [];
                $mtfTrueLabel = old('mtf_true_label', $mtfMeta['true_label'] ?? 'Benar');
                $mtfFalseLabel = old('mtf_false_label', $mtfMeta['false_label'] ?? 'Salah');
                $mtfScoringMode = old('mtf_scoring_mode', $mtfMeta['scoring_mode'] ?? 'fullscore');
                $mtfScoreCorrect = old('mtf_score_correct', $mtfMeta['score_correct'] ?? 1);
                $mtfScoreWrong = old('mtf_score_wrong', $mtfMeta['score_wrong'] ?? 0);
                $mtfStatementsInput = old('mtf_statements', $mtfMeta['statements'] ?? []);
                $mtfStatements = [];
                if (is_array($mtfStatementsInput)) {
                    foreach ($mtfStatementsInput as $idx => $stmt) {
                        $mtfStatements[] = [
                            'id' => is_array($stmt) ? ($stmt['id'] ?? ('stmt_' . ($idx + 1))) : ('stmt_' . ($idx + 1)),
                            'text' => is_array($stmt) ? ($stmt['text'] ?? '') : '',
                            'correct' => is_array($stmt) && in_array(($stmt['correct'] ?? ''), ['true', 'false'], true) ? $stmt['correct'] : 'true',
                        ];
                    }
                }
                while (count($mtfStatements) < 1) {
                    $mtfStatements[] = [
                        'id' => 'stmt_' . (count($mtfStatements) + 1),
                        'text' => '',
                        'correct' => 'true',
                    ];
                }
                @endphp

                <div class="space-y-7 p-5 sm:p-6 lg:p-7">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5">
                        <label for="question_type" class="block text-sm font-medium text-gray-700 mb-2">Jenis Soal <span
                                class="text-red-500">*</span></label>
                        <select id="question_type" name="question_type"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="multiple_choice" {{ $currentType==='multiple_choice' ? 'selected' : '' }}>
                                Multiple
                                Choice</option>
                            <option value="multiple_answer" {{ $rawType==='multiple_answer' ? 'selected' : '' }}>
                                Multiple Answer (Lebih dari 1 benar)</option>
                            <option value="matching" {{ $rawType==='matching' ? 'selected' : '' }}>Pencocokan</option>
                            <option value="multiple_true_false" {{ $rawType==='multiple_true_false' ? 'selected' : '' }}>Multiple True/False</option>
                            <option value="short_answer" {{ $rawType==='short_answer' ? 'selected' : '' }}>Jawaban Singkat</option>
                            <option value="essay" {{ $rawType==='essay' ? 'selected' : '' }}>Essay</option>
                            <option value="audio" {{ $rawType==='audio' ? 'selected' : '' }}>Jawaban Audio</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-2">Pilih tipe soal untuk menampilkan form yang sesuai.</p>
                    </div>

                    <!-- Question Text -->
                    <div class="rounded-xl border border-slate-200 p-4 sm:p-5">
                        <label for="question_text" class="block text-sm font-medium text-gray-700 mb-2">Teks Soal <span
                                class="text-red-500">*</span></label>
                        <textarea id="question_text" name="question_text" required rows="4"
                            class="ckeditor w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Masukkan teks soal...">{{ old('question_text', isset($question) ? $question->question_text : '') }}</textarea>
                    </div>

                    <!-- Audio Upload -->
                    <div class="grid grid-cols-1 gap-6 rounded-xl border border-slate-200 p-4 sm:p-5">
                        <div>
                            <label for="sound" class="block text-sm font-medium text-gray-700 mb-2">Audio Soal
                                (Opsional)</label>
                            @if(isset($question) && $question->sound)
                            <div class="mb-3">
                                <p class="text-sm text-gray-600">File audio saat ini:</p>
                                <audio controls class="mt-1">
                                    <source src="{{ Storage::url($question->sound) }}" type="audio/mpeg">
                                    Browser Anda tidak mendukung audio.
                                </audio>
                            </div>
                            @endif
                            <input type="file" id="sound" name="sound" accept="audio/*"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <p class="text-sm text-gray-500 mt-1">Format: MP3, WAV, M4A (maks. 5MB)</p>
                        </div>
                    </div>

                    <!-- Multiple Choice -->
                    <div class="space-y-5 question-type-section" data-question-type="multiple_choice"
                        style="display:none;">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-800">Pilihan Jawaban</h3>
                            @if (! $questionForm['isToefl'])
                            @if($questionForm['subtestType'] !== 'tkp')
                            <div class="flex items-center" id="customScoreToggle">
                                <input type="checkbox" id="use_custom_scores" name="use_custom_scores" value="1" {{
                                    (isset($question) && $question->custom_score == 'yes') || old('use_custom_scores') ?
                                'checked' : '' }}
                                class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary
                                focus:ring-2">
                                <label for="use_custom_scores" class="ml-2 text-sm font-medium text-gray-700">
                                    Custom Score (Opsional)
                                </label>
                            </div>
                            @else
                            <div class="text-sm text-blue-600 font-medium">
                                <i class="ri-information-line mr-1"></i>
                                Mode TKP: Semua opsi dapat diberi skor 1-5
                            </div>
                            <input type="hidden" name="use_custom_scores" value="1">
                            @endif
                            @endif
                        </div>
                        <p class="text-sm text-gray-500">Isi minimal dua opsi (A dan B). Pilihan C sampai E bersifat opsional.</p>
                        <div id="multipleAnswerScoreContainer"
                            class="score-settings space-y-3 {{ $rawType === 'multiple_answer' ? '' : 'hidden' }}">
                            <label class="block text-sm font-medium text-gray-700">Skor Multiple Answer</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:w-full">
                                @php
                                    $existingMultiMeta = isset($question) && is_array($question->metadata) ? ($question->metadata['multiple_answer'] ?? []) : [];
                                    $multiScoreCorrect = old('multiple_answer_score_correct', $existingMultiMeta['score_correct'] ?? 1);
                                    $multiScoreWrong = old('multiple_answer_score_wrong', $existingMultiMeta['score_wrong'] ?? 0);
                                    $multiScoringMode = old('multiple_answer_scoring_mode', $existingMultiMeta['scoring_mode'] ?? 'fullscore');
                                @endphp
                                <x-form.scoring-mode id="multiple_answer_scoring_mode" name="multiple_answer_scoring_mode" :value="$multiScoringMode" />
                                <div>
                                    <label for="multiple_answer_score_correct" class="block text-xs font-medium text-gray-600 mb-1">Skor Benar</label>
                                    <input type="number" id="multiple_answer_score_correct" name="multiple_answer_score_correct" step="0.1"
                                        value="{{ $multiScoreCorrect }}"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                                <div>
                                    <label for="multiple_answer_score_wrong" class="block text-xs font-medium text-gray-600 mb-1">Skor Salah</label>
                                    <input type="number" id="multiple_answer_score_wrong" name="multiple_answer_score_wrong" step="0.1"
                                        value="{{ $multiScoreWrong }}"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                            </div>
                            <p class="text-xs text-gray-500">Skor akhir dihitung terpusat dari jumlah pilihan benar/salah yang dipilih peserta.</p>
                        </div>

                        @foreach(['A', 'B', 'C', 'D', 'E'] as $index => $optionKey)
                        @php
                        $optionData = null;
                        $isCorrect = false;
                        if (isset($question) && isset($questionOptions[$index]))
                        {
                        $optionData = $questionOptions[$index];
                        $isCorrect = $optionData->is_correct == 1;
                        }
                        @endphp
                        <div class="option-row flex gap-3 items-start" data-option-key="{{ $optionKey }}">
                            <div class="pt-8 sm:pt-8">
                                <input type="radio" id="correct_{{ strtolower($optionKey) }}" name="correct_answer"
                                    value="{{ $optionKey }}" {{ $isCorrect || old('correct_answer')==$optionKey
                                    ? 'checked' : '' }} {{ $optionKey==='E' ? '' : 'required' }}
                                    class="single-correct w-4 h-4 text-primary bg-gray-100 border-gray-300 focus:ring-primary focus:ring-2">
                                <input type="checkbox" id="correct_multi_{{ strtolower($optionKey) }}" name="correct_answers[]"
                                    value="{{ $optionKey }}"
                                    {{ in_array($optionKey, old('correct_answers', [])) || (!old('correct_answers') && $isCorrect) ? 'checked' : '' }}
                                    class="multi-correct hidden w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                            </div>
                            <div class="option-input w-full">
                                <label for="option_{{ strtolower($optionKey) }}"
                                    class="block text-sm font-medium text-gray-700 mb-2">
                                    Pilihan {{ $optionKey }}
                                    @if(in_array($optionKey, ['A', 'B'], true))<span class="text-red-500">*</span>@endif
                                </label>
                                <textarea id="option_{{ strtolower($optionKey) }}"
                                    name="option_{{ strtolower($optionKey) }}" {{ in_array($optionKey, ['A', 'B'], true) ? 'required' : '' }}
                                    class="summernote-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    data-height="180"
                                    placeholder="Pilihan {{ $optionKey }}">{{ $optionData ? $optionData->option_text : old('option_' . strtolower($optionKey)) }}</textarea>
                            </div>
                            <div class="custom-score-field w-full sm:w-1/4"
                                style="{{ ($questionForm['subtestType'] === 'tkp') || (isset($question) && $question->custom_score == 'yes') || old('use_custom_scores') ? '' : 'display: none;' }}">
                                <label for="score_{{ strtolower($optionKey) }}"
                                    class="block text-sm font-medium text-gray-700 mb-2">
                                    @if($questionForm['subtestType'] === 'tkp')
                                    Skor {{ $optionKey }} (1-5)
                                    @else
                                    Skor {{ $optionKey }}
                                    @endif
                                </label>
                                <input type="number" id="score_{{ strtolower($optionKey) }}"
                                    name="score_{{ strtolower($optionKey) }}"
                                    value="{{ $optionData ? $optionData->weight : old('score_' . strtolower($optionKey), $questionForm['subtestType'] === 'tkp' ? 1 : 0) }}"
                                    min="0" max="5" step="0.1"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="{{ $questionForm['subtestType'] === 'tkp' ? '1-5' : '0' }}">
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Matching -->
                    <div class="space-y-5 question-type-section" data-question-type="matching" style="display:none;">
                        <div>
                            <h3 class="text-lg font-medium text-gray-800">Pasangan Pencocokan</h3>
                            <p class="text-sm text-gray-600">Masukkan minimal dua pasangan jawaban. Peserta akan
                                memasangkan
                                kolom kiri dengan kolom kanan.</p>
                        </div>
                        @php
                            $matchingScores = is_array($metadata['matching_scores'] ?? null) ? $metadata['matching_scores'] : [];
                            $matchingScoreCorrect = old('matching_score_correct', $matchingScores['score_correct'] ?? 1);
                            $matchingScoreWrong = old('matching_score_wrong', $matchingScores['score_wrong'] ?? 0);
                        @endphp
                        <div class="score-settings grid grid-cols-1 gap-3 md:w-full md:grid-cols-3">
                            @php
                                $matchingScoringMode = old('matching_scoring_mode', $matchingScores['scoring_mode'] ?? 'fullscore');
                            @endphp
                            <x-form.scoring-mode id="matching_scoring_mode" name="matching_scoring_mode" :value="$matchingScoringMode" />
                            <div>
                                <label for="matching_score_correct" class="block text-sm font-medium text-gray-700 mb-1">Skor Benar</label>
                                <input type="number" id="matching_score_correct" name="matching_score_correct" step="0.1"
                                    value="{{ $matchingScoreCorrect }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                            <div>
                                <label for="matching_score_wrong" class="block text-sm font-medium text-gray-700 mb-1">Skor Salah</label>
                                <input type="number" id="matching_score_wrong" name="matching_score_wrong" step="0.1"
                                    value="{{ $matchingScoreWrong }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                        </div>
                        <div id="matchingPairsContainer" class="space-y-3">
                            @foreach($normalizedPairs as $index => $pair)
                            <div class="matching-pair-row flex flex-col gap-3 sm:flex-row sm:items-start"
                                data-index="{{ $index }}">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Item Kiri {{ $index + 1
                                        }}</label>
                                    <input type="text" name="matching_pairs[{{ $index }}][left]"
                                        value="{{ $pair['left'] }}"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        placeholder="Contoh: Ibukota Indonesia">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Pasangan Benar {{ $index
                                        + 1 }}</label>
                                    <input type="text" name="matching_pairs[{{ $index }}][right]"
                                        value="{{ $pair['right'] }}"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        placeholder="Contoh: Jakarta">
                                </div>
                                <button type="button"
                                    class="remove-matching-pair mt-2 sm:mt-6 px-3 py-2 border border-red text-red rounded-lg hover:bg-red hover:text-white transition-colors">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" id="addMatchingPair"
                            class="px-4 py-2 border border-dashed border-primary text-primary rounded-lg hover:bg-primary/10 transition-colors flex items-center gap-2">
                            <i class="ri-add-line"></i>
                            Tambah Pasangan
                        </button>
                    </div>

                    <!-- Multiple True/False -->
                    <div class="space-y-5 question-type-section" data-question-type="multiple_true_false" style="display:none;">
                        <div>
                            <h3 class="text-lg font-medium text-gray-800">Multiple True/False</h3>
                            <p class="text-sm text-gray-600">Isi satu atau lebih pernyataan. Peserta akan memilih salah satu dari dua opsi pada tiap baris.</p>
                        </div>
                        <div class="score-settings grid grid-cols-1 gap-3 md:w-full md:grid-cols-3">
                            <x-form.scoring-mode id="mtf_scoring_mode" name="mtf_scoring_mode" :value="$mtfScoringMode" />
                            <div>
                                <label for="mtf_score_correct" class="block text-sm font-medium text-gray-700 mb-1">Skor Benar (Total)</label>
                                <input type="number" id="mtf_score_correct" name="mtf_score_correct" step="0.1"
                                    value="{{ $mtfScoreCorrect }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                            <div>
                                <label for="mtf_score_wrong" class="block text-sm font-medium text-gray-700 mb-1">Skor Salah</label>
                                <input type="number" id="mtf_score_wrong" name="mtf_score_wrong" step="0.1"
                                    value="{{ $mtfScoreWrong }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 text-slate-700">
                                    <tr>
                                        <th class="px-5 py-3.5 text-left font-semibold w-[75%]">Pernyataan</th>
                                        <th class="w-[10%] whitespace-nowrap px-5 py-3.5 text-center font-semibold">
                                            <span id="mtfHeaderTrue">{{ $mtfTrueLabel !== '' ? $mtfTrueLabel : 'Kolom 1' }}</span>
                                        </th>
                                        <th class="w-[10%] whitespace-nowrap px-5 py-3.5 text-center font-semibold">
                                            <div class="flex items-center justify-center gap-1">
                                                <span id="mtfHeaderFalse">{{ $mtfFalseLabel !== '' ? $mtfFalseLabel : 'Kolom 2' }}</span>
                                                <button type="button" id="openMtfLabelSettings"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-500 transition-colors hover:bg-slate-200 hover:text-primary"
                                                    title="Atur teks kolom" aria-label="Atur teks kolom" aria-controls="mtfLabelSettingsModal">
                                                    <i class="ri-settings-3-line"></i>
                                                </button>
                                            </div>
                                        </th>
                                        <th class="w-[5%] min-w-[72px] px-5 py-3.5 text-center font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="mtfStatementsContainer">
                                    @foreach($mtfStatements as $index => $statement)
                                    <tr class="mtf-row border-t border-gray-200" data-index="{{ $index }}">
                                        <td class="px-5 py-3.5 align-top">
                                            <input type="hidden" name="mtf_statements[{{ $index }}][id]" value="{{ $statement['id'] }}">
                                            <input type="hidden" name="mtf_statements[{{ $index }}][correct]" value="{{ $statement['correct'] === 'false' ? 'false' : 'true' }}" class="mtf-correct-input">
                                            <textarea name="mtf_statements[{{ $index }}][text]" rows="2"
                                                class="summernote-field w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                                data-height="180"
                                                placeholder="Tulis pernyataan...">{{ $statement['text'] }}</textarea>
                                        </td>
                                        <td class="px-5 py-3.5 text-center align-middle">
                                            <input type="radio" class="mtf-correct-radio w-4 h-4 text-primary border-gray-300 focus:ring-primary"
                                                name="mtf_display_correct_{{ $index }}" value="true" {{ $statement['correct'] === 'true' ? 'checked' : '' }}>
                                        </td>
                                        <td class="px-5 py-3.5 text-center align-middle">
                                            <input type="radio" class="mtf-correct-radio w-4 h-4 text-primary border-gray-300 focus:ring-primary"
                                                name="mtf_display_correct_{{ $index }}" value="false" {{ $statement['correct'] === 'false' ? 'checked' : '' }}>
                                        </td>
                                        <td class="px-5 py-3.5 text-center align-middle">
                                            <button type="button"
                                                class="remove-mtf-row inline-flex items-center justify-center w-9 h-9 border border-red text-red rounded-lg hover:bg-red hover:text-white transition-colors">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div id="mtfLabelSettingsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4" role="dialog" aria-modal="true" aria-labelledby="mtfLabelSettingsTitle">
                            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl sm:p-6">
                                <div class="mb-5 flex items-start justify-between gap-4">
                                    <div>
                                        <h4 id="mtfLabelSettingsTitle" class="text-lg font-semibold text-slate-900">Teks kolom jawaban</h4>
                                        <p class="mt-1 text-sm text-slate-500">Ubah nama pilihan yang tampil di header tabel.</p>
                                    </div>
                                    <button type="button" data-close-mtf-label-settings class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100" aria-label="Tutup pengaturan">
                                        <i class="ri-close-line text-xl"></i>
                                    </button>
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <label for="mtf_true_label" class="mb-1.5 block text-sm font-medium text-slate-700">Teks opsi kolom 1</label>
                                        <input type="text" id="mtf_true_label" name="mtf_true_label" value="{{ $mtfTrueLabel }}"
                                            class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                            placeholder="Contoh: Benar / Setuju">
                                    </div>
                                    <div>
                                        <label for="mtf_false_label" class="mb-1.5 block text-sm font-medium text-slate-700">Teks opsi kolom 2</label>
                                        <input type="text" id="mtf_false_label" name="mtf_false_label" value="{{ $mtfFalseLabel }}"
                                            class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                            placeholder="Contoh: Salah / Tidak Setuju">
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end">
                                    <button type="button" data-close-mtf-label-settings class="inline-flex min-h-10 items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary/90">
                                        Selesai
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="addMtfRow"
                            class="px-4 py-2 border border-dashed border-primary text-primary rounded-lg hover:bg-primary/10 transition-colors flex items-center gap-2">
                            <i class="ri-add-line"></i>
                            Tambah Pernyataan
                        </button>
                    </div>

                    <!-- Short Answer / Essay -->
                    <div class="space-y-5 question-type-section" data-question-type="short_answer"
                        style="display:none;">
                        <div>
                            <h3 class="text-lg font-medium text-gray-800">Pengaturan Jawaban Teks</h3>
                            <p class="text-sm text-gray-600" data-expected-hint>Isi daftar jawaban benar jika ingin penilaian otomatis.
                                Kosongkan untuk penilaian manual.</p>
                        </div>
                        <div class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 sm:p-5" data-essay-scoring style="display:none;">
                            <div>
                                <span class="text-sm font-semibold text-slate-800">Mode Koreksi Essay</span>
                                <p class="mt-1 text-xs text-slate-500">Pilih apakah jawaban dinilai otomatis dari referensi atau diperiksa secara manual.</p>
                            </div>
                            @if(!$essayAutoAvailable)
                                <div class="bg-amber-50 border border-amber-200 text-amber-700 px-3 py-2 rounded-lg text-sm">
                                    <i class="ri-information-line mr-1"></i>
                                    {{ $essayAI['reason'] ?? 'Essay AI belum diaktifkan oleh Super Admin. Mode otomatis dikunci.' }}
                                </div>
                            @endif
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm {{ $essayAutoAvailable ? 'text-gray-700' : 'cursor-not-allowed border-dashed bg-slate-50 text-gray-400' }}">
                                    <input type="radio" name="essay_evaluation_mode" value="auto" {{ $essayEvaluationMode === 'auto' ? 'checked' : '' }}
                                        {{ !$essayAutoAvailable ? 'disabled' : '' }}
                                        class="mt-0.5 h-4 w-4 text-primary border-gray-300 focus:ring-primary disabled:opacity-50">
                                    <span>Otomatis <span class="block mt-0.5 text-xs font-normal text-slate-500">Berdasarkan jawaban referensi</span></span>
                                    @if(!$essayAutoAvailable)
                                        <i class="ri-lock-line ml-auto text-gray-400" title="{{ $essayAI['reason'] ?? 'Essay AI belum diaktifkan oleh Super Admin' }}"></i>
                                    @endif
                                </label>
                                <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm text-gray-700">
                                    <input type="radio" name="essay_evaluation_mode" value="manual" {{ $essayEvaluationMode !== 'auto' ? 'checked' : '' }}
                                        class="mt-0.5 h-4 w-4 text-primary border-gray-300 focus:ring-primary">
                                    <span>Manual <span class="block mt-0.5 text-xs font-normal text-slate-500">Perlu dikoreksi oleh pengajar</span></span>
                                </label>
                            </div>
                        </div>
                        
                        {{-- Mode Penilaian Essay: FULL vs RANGE --}}
                        <div class="score-settings grid grid-cols-1 gap-3 md:grid-cols-3" data-essay-score-mode>
                            <x-form.scoring-mode id="essay_scoring_mode" name="essay_scoring_mode"
                                :value="old('essay_scoring_mode', isset($question) ? $question->essay_scoring_mode : 'full')" variant="essay" />
                            <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Skor jika benar</label>
                                    <input type="number" name="essay_score_correct" step="0.01" min="0"
                                        value="{{ old('essay_score_correct', isset($question) ? $question->essay_score_correct : '') }}"
                                        placeholder="{{ $questionForm['defaultWeight'] }}"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <p class="text-xs text-gray-500 mt-1">Kosongkan = pakai default weight</p>
                            </div>
                            <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Skor jika salah</label>
                                    <input type="number" name="essay_score_wrong" step="0.01" min="0"
                                        value="{{ old('essay_score_wrong', isset($question) ? $question->essay_score_wrong : 0) }}"
                                        placeholder="0"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                            <label for="short_answer_expected"
                                class="block text-sm font-semibold text-slate-800 mb-2">Jawaban referensi</label>
                            <textarea id="short_answer_expected" name="short_answer_expected" rows="4"
                                class="summernote-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                data-height="220"
                                placeholder="Masukkan jawaban benar atau referensi koreksi.">{{ $shortAnswerExpected }}</textarea>
                            <p class="text-xs text-gray-500 mt-2">Essay mendukung format dan gambar sebagai referensi koreksi otomatis.</p>
                        </div>
                        <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-3" data-short-answer-case>
                            <input type="checkbox" id="short_answer_case_sensitive" name="short_answer_case_sensitive"
                                value="1" {{ $shortAnswerCaseSensitive ? 'checked' : '' }}
                                class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                            <label for="short_answer_case_sensitive" class="text-sm text-gray-700">Perhatikan huruf
                                besar-kecil
                                saat menilai otomatis</label>
                        </div>
                        <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm" data-essay-manual-note>
                            <i class="ri-information-line mr-1"></i>
                            Soal essay akan ditandai sebagai butuh penilaian manual jika tidak ada jawaban benar yang
                            ditentukan.
                        </div>
                    </div>

                    <!-- Audio Answer -->
                    <div class="space-y-5 question-type-section" data-question-type="audio" style="display:none;">
                        <div>
                            <h3 class="text-lg font-medium text-gray-800">Pengaturan Jawaban Audio</h3>
                            <p class="text-sm text-gray-600">Peserta akan mengunggah jawaban dalam bentuk rekaman suara.
                            </p>
                        </div>
                        <x-ui.input.textarea name="audio_instructions" label="Instruksi untuk Peserta (Opsional)"
                            :value="$audioInstructions" rows="3" resize="vertical"
                            placeholder="Contoh: Ceritakan pendapatmu selama 1 menit" />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <x-ui.input name="audio_max_duration" type="number" label="Durasi Maksimal (detik)"
                                :value="$audioMaxDuration" placeholder="Contoh: 90"
                                helper="Kosongkan jika tidak dibatasi (maks. 10 menit)." min="5" max="600" />
                            <x-ui.input name="audio_max_size" type="number" label="Batas Ukuran File (MB)"
                                :value="$audioMaxSize" placeholder="Contoh: 10"
                                helper="Format yang didukung: MP3, WAV, M4A." min="1" max="100" />
                        </div>
                        <div class="rounded-lg bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 text-sm">
                            <i class="ri-information-line mr-1"></i>
                            Penilaian jawaban audio dilakukan manual. Admin dapat mengunduh file jawaban peserta dari
                            halaman
                            hasil tryout.
                        </div>
                    </div>

                    <!-- Explanation -->
                    <div class="rounded-xl border border-slate-200 p-4 sm:p-5">
                        <label for="explanation" class="block text-sm font-medium text-gray-700 mb-2">Pembahasan
                            (Opsional)</label>
                        <textarea id="explanation" name="explanation" rows="4"
                            class="ckeditor w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Masukkan pembahasan soal...">{{ isset($question) ? $question->explanation : old('explanation') }}</textarea>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                        <a href="{{ $questionForm['cancelUrl'] }}"
                            class="inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50">
                            Batal
                        </a>
                        <button type="submit"
                            class="inline-flex min-h-10 items-center justify-center rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary/90">
                            {{ isset($question) ? 'Perbarui' : 'Simpan' }} Soal
                        </button>
                    </div>
                </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const questionTypeSelect = document.getElementById('question_type');
        const typeSections = document.querySelectorAll('.question-type-section');
        const useCustomScores = document.getElementById('use_custom_scores');
        const customScoreFields = document.querySelectorAll('.custom-score-field');
        const customScoreToggle = document.getElementById('customScoreToggle');
        const tryoutType = @json($questionForm['subtestType']);
        const form = document.querySelector('form');
        const matchingContainer = document.getElementById('matchingPairsContainer');
        const addMatchingPairBtn = document.getElementById('addMatchingPair');
        const mtfContainer = document.getElementById('mtfStatementsContainer');
        const addMtfRowBtn = document.getElementById('addMtfRow');
        const mtfTrueLabelInput = document.getElementById('mtf_true_label');
        const mtfFalseLabelInput = document.getElementById('mtf_false_label');
        const mtfHeaderTrue = document.getElementById('mtfHeaderTrue');
        const mtfHeaderFalse = document.getElementById('mtfHeaderFalse');
        const mtfLabelSettingsModal = document.getElementById('mtfLabelSettingsModal');
        const openMtfLabelSettings = document.getElementById('openMtfLabelSettings');
        const closeMtfLabelSettingsButtons = document.querySelectorAll('[data-close-mtf-label-settings]');
        const optionRows = document.querySelectorAll('.option-row');
        const multipleAnswerScoreContainer = document.getElementById('multipleAnswerScoreContainer');

        function shouldShowSection(sectionType, currentType) {
            if (sectionType === 'multiple_choice') {
                return currentType === 'multiple_choice' || currentType === 'true_false' || currentType === 'multiple_answer';
            }
            if (sectionType === 'short_answer') {
                return currentType === 'short_answer' || currentType === 'essay';
            }
            return sectionType === currentType;
        }

        function updateTypeSections() {
            const currentType = questionTypeSelect.value;
            typeSections.forEach(section => {
                const shouldShow = shouldShowSection(section.dataset.questionType, currentType);
                section.style.display = shouldShow ? '' : 'none';
            });

            const essayScoring = document.querySelector('[data-essay-scoring]');
            const essayScoreMode = document.querySelector('[data-essay-score-mode]');
            const essayManualNote = document.querySelector('[data-essay-manual-note]');
            const shortAnswerCase = document.querySelector('[data-short-answer-case]');
            const expectedHint = document.querySelector('[data-expected-hint]');

            if (essayScoring && essayScoreMode && essayManualNote && shortAnswerCase && expectedHint) {
                const isEssay = currentType === 'essay';
                essayScoring.style.display = isEssay ? '' : 'none';
                essayScoreMode.style.display = isEssay ? '' : 'none';
                essayManualNote.style.display = isEssay ? '' : 'none';
                shortAnswerCase.style.display = isEssay ? 'none' : '';
                expectedHint.textContent = isEssay
                    ? 'Isi daftar jawaban referensi jika memilih koreksi otomatis.'
                    : 'Isi daftar jawaban benar jika ingin penilaian otomatis. Kosongkan untuk penilaian manual.';
            }

            if (customScoreToggle) {
                const showCustomToggle = currentType === 'multiple_choice' || currentType === 'true_false';
                customScoreToggle.style.display = showCustomToggle ? '' : 'none';
            }

            if (multipleAnswerScoreContainer) {
                multipleAnswerScoreContainer.classList.toggle('hidden', currentType !== 'multiple_answer');
            }

            if (currentType === 'multiple_choice' || currentType === 'true_false' || currentType === 'multiple_answer') {
                toggleScoreFields();
            } else if (useCustomScores) {
                useCustomScores.checked = false;
                customScoreFields.forEach(field => field.style.display = 'none');
            }

            configureOptionRows(currentType);
        }

        function toggleScoreFields() {
            if (!useCustomScores) {
                return;
            }

            if (tryoutType === 'tkp') {
                customScoreFields.forEach(field => {
                    field.style.display = '';
                });
                return;
            }

            const isChecked = useCustomScores.checked;
            customScoreFields.forEach(field => {
                field.style.display = isChecked ? '' : 'none';
            });

            configureOptionRows(questionTypeSelect.value);
        }

        if (useCustomScores) {
            useCustomScores.addEventListener('change', toggleScoreFields);
        }

        questionTypeSelect.addEventListener('change', updateTypeSections);

        updateTypeSections();

        if (tryoutType === 'tkp') {
            if (useCustomScores) {
                useCustomScores.checked = true;
            }
            customScoreFields.forEach(field => field.style.display = '');
        } else {
            toggleScoreFields();
        }

        if (tryoutType === 'tkp') {
            form.addEventListener('submit', function(e) {
                if (!['multiple_choice', 'true_false', 'multiple_answer'].includes(questionTypeSelect.value)) {
                    return;
                }

                const scoreInputs = document.querySelectorAll('input[name^="score_"]');
                let isValid = true;

                scoreInputs.forEach(input => {
                    const value = parseFloat(input.value);
                    if (Number.isNaN(value) || value < 1 || value > 5) {
                        isValid = false;
                        input.classList.add('border-red-500');
                    } else {
                        input.classList.remove('border-red-500');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Untuk TKP, skor harus antara 1-5 poin');
                }
            });
        }

        if (addMatchingPairBtn && matchingContainer) {
            let pairIndex = matchingContainer.querySelectorAll('.matching-pair-row').length;

            addMatchingPairBtn.addEventListener('click', function() {
                const row = createPairRow(pairIndex);
                matchingContainer.appendChild(row);
                pairIndex += 1;
            });

            matchingContainer.addEventListener('click', function(event) {
                const removeButton = event.target.closest('.remove-matching-pair');
                if (!removeButton) {
                    return;
                }

                const rows = matchingContainer.querySelectorAll('.matching-pair-row');
                if (rows.length <= 2) {
                    alert('Minimal harus ada dua pasangan.');
                    return;
                }

                removeButton.closest('.matching-pair-row').remove();
            });

            function createPairRow(index, leftValue = '', rightValue = '') {
                const row = document.createElement('div');
                row.className = 'matching-pair-row flex flex-col sm:flex-row gap-3 items-start';
                row.dataset.index = index.toString();

                const leftWrapper = document.createElement('div');
                leftWrapper.className = 'flex-1';
                const leftLabel = document.createElement('label');
                leftLabel.className = 'block text-sm font-medium text-gray-700 mb-1';
                leftLabel.textContent = `Item Kiri ${index + 1}`;
                const leftInput = document.createElement('input');
                leftInput.type = 'text';
                leftInput.name = `matching_pairs[${index}][left]`;
                leftInput.value = leftValue;
                leftInput.placeholder = 'Contoh: Ibukota Indonesia';
                leftInput.className = 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary';
                leftWrapper.appendChild(leftLabel);
                leftWrapper.appendChild(leftInput);

                const rightWrapper = document.createElement('div');
                rightWrapper.className = 'flex-1';
                const rightLabel = document.createElement('label');
                rightLabel.className = 'block text-sm font-medium text-gray-700 mb-1';
                rightLabel.textContent = `Pasangan Benar ${index + 1}`;
                const rightInput = document.createElement('input');
                rightInput.type = 'text';
                rightInput.name = `matching_pairs[${index}][right]`;
                rightInput.value = rightValue;
                rightInput.placeholder = 'Contoh: Jakarta';
                rightInput.className = 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary';
                rightWrapper.appendChild(rightLabel);
                rightWrapper.appendChild(rightInput);

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'remove-matching-pair mt-2 sm:mt-6 px-3 py-2 border border-red text-red rounded-lg hover:bg-red hover:text-white transition-colors';
                removeButton.innerHTML = '<i class="ri-delete-bin-line"></i>';

                row.appendChild(leftWrapper);
                row.appendChild(rightWrapper);
                row.appendChild(removeButton);

                return row;
            }
        }

        if (addMtfRowBtn && mtfContainer) {
            let mtfIndex = mtfContainer.querySelectorAll('.mtf-row').length;

            addMtfRowBtn.addEventListener('click', function() {
                const row = createMtfRow(mtfIndex);
                mtfContainer.appendChild(row);
                window.initSummernoteFields?.();
                mtfIndex += 1;
            });

            mtfContainer.addEventListener('click', function(event) {
                const removeButton = event.target.closest('.remove-mtf-row');
                if (!removeButton) {
                    return;
                }

                const rows = mtfContainer.querySelectorAll('.mtf-row');
                if (rows.length <= 1) {
                    alert('Minimal harus ada satu pernyataan.');
                    return;
                }

                removeButton.closest('.mtf-row').remove();
            });

            mtfContainer.addEventListener('change', function(event) {
                const radio = event.target.closest('.mtf-correct-radio');
                if (!radio) {
                    return;
                }

                const row = radio.closest('.mtf-row');
                if (!row) {
                    return;
                }

                const hiddenInput = row.querySelector('.mtf-correct-input');
                if (hiddenInput) {
                    hiddenInput.value = radio.value === 'false' ? 'false' : 'true';
                }
            });

            function createMtfRow(index, textValue = '', correctValue = 'true') {
                const row = document.createElement('tr');
                const normalizedCorrect = correctValue === 'false' ? 'false' : 'true';
                row.className = 'mtf-row border-t border-gray-200';
                row.dataset.index = index.toString();
                row.innerHTML = `
                    <td class="px-5 py-3.5 align-top">
                        <input type="hidden" name="mtf_statements[${index}][id]" value="stmt_${index + 1}">
                        <input type="hidden" name="mtf_statements[${index}][correct]" value="${normalizedCorrect}" class="mtf-correct-input">
                        <textarea name="mtf_statements[${index}][text]" rows="2"
                            class="summernote-field w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            data-height="180"
                            placeholder="Tulis pernyataan...">${textValue}</textarea>
                    </td>
                    <td class="px-5 py-3.5 text-center align-middle">
                        <input type="radio" class="mtf-correct-radio w-4 h-4 text-primary border-gray-300 focus:ring-primary"
                            name="mtf_display_correct_${index}" value="true" ${normalizedCorrect === 'true' ? 'checked' : ''}>
                    </td>
                    <td class="px-5 py-3.5 text-center align-middle">
                        <input type="radio" class="mtf-correct-radio w-4 h-4 text-primary border-gray-300 focus:ring-primary"
                            name="mtf_display_correct_${index}" value="false" ${normalizedCorrect === 'false' ? 'checked' : ''}>
                    </td>
                    <td class="px-5 py-3.5 text-center align-middle">
                        <button type="button"
                            class="remove-mtf-row inline-flex items-center justify-center w-9 h-9 border border-red text-red rounded-lg hover:bg-red hover:text-white transition-colors">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </td>
                `;
                return row;
            }
        }

        function syncMtfHeaderLabels() {
            if (mtfHeaderTrue && mtfTrueLabelInput) {
                const text = mtfTrueLabelInput.value.trim();
                mtfHeaderTrue.textContent = text !== '' ? text : 'Kolom 1';
            }
            if (mtfHeaderFalse && mtfFalseLabelInput) {
                const text = mtfFalseLabelInput.value.trim();
                mtfHeaderFalse.textContent = text !== '' ? text : 'Kolom 2';
            }
        }

        if (mtfTrueLabelInput) {
            mtfTrueLabelInput.addEventListener('input', syncMtfHeaderLabels);
        }
        if (mtfFalseLabelInput) {
            mtfFalseLabelInput.addEventListener('input', syncMtfHeaderLabels);
        }
        syncMtfHeaderLabels();

        function toggleMtfLabelSettings(isOpen) {
            if (!mtfLabelSettingsModal) {
                return;
            }

            mtfLabelSettingsModal.classList.toggle('hidden', !isOpen);
            mtfLabelSettingsModal.classList.toggle('flex', isOpen);

            if (isOpen) {
                mtfTrueLabelInput?.focus();
            }
        }

        openMtfLabelSettings?.addEventListener('click', function() {
            toggleMtfLabelSettings(true);
        });

        closeMtfLabelSettingsButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                toggleMtfLabelSettings(false);
            });
        });

        mtfLabelSettingsModal?.addEventListener('click', function(event) {
            if (event.target === mtfLabelSettingsModal) {
                toggleMtfLabelSettings(false);
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && mtfLabelSettingsModal?.classList.contains('flex')) {
                toggleMtfLabelSettings(false);
            }
        });

        function hasEditorContent(textarea) {
            const $ = window.jQuery || window.$;
            const value = $ && $(textarea).data('summernoteInitialized')
                ? $(textarea).summernote('code')
                : textarea.value;

            return value
                .replace(/<(?:br|\/?p|\/?div)[^>]*>/gi, '')
                .replace(/&nbsp;/gi, '')
                .trim() !== '';
        }

        function setEditorContent(textarea, value) {
            const $ = window.jQuery || window.$;
            if ($ && $(textarea).data('summernoteInitialized')) {
                $(textarea).summernote('code', value);
                return;
            }

            textarea.value = value;
        }

        function configureOptionRows(questionType) {
            const isTrueFalse = questionType === 'true_false';
            const isMultipleAnswer = questionType === 'multiple_answer';
            optionRows.forEach(row => {
                const key = row.dataset.optionKey;
                const textarea = row.querySelector('textarea');
                const radio = row.querySelector('input.single-correct');
                const multiCheckbox = row.querySelector('input.multi-correct');
                const scoreWrapper = row.querySelector('.custom-score-field');

                if (isTrueFalse) {
                    if (key === 'A' || key === 'B') {
                        row.style.display = '';
                        if (textarea && !hasEditorContent(textarea)) {
                            setEditorContent(textarea, key === 'A' ? 'Benar' : 'Salah');
                        }
                        if (textarea) {
                            textarea.required = false;
                        }
                        if (radio) {
                            radio.required = false;
                        }
                        if (multiCheckbox) {
                            multiCheckbox.checked = false;
                            multiCheckbox.classList.add('hidden');
                        }
                        if (scoreWrapper) {
                            if (tryoutType === 'tkp') {
                                scoreWrapper.style.display = '';
                            } else {
                                scoreWrapper.style.display = useCustomScores && useCustomScores.checked ? '' : 'none';
                            }
                        }
                    } else {
                        row.style.display = 'none';
                        if (textarea) {
                            textarea.required = false;
                            setEditorContent(textarea, '');
                        }
                        if (radio) {
                            radio.required = false;
                            radio.checked = false;
                        }
                        if (multiCheckbox) {
                            multiCheckbox.checked = false;
                            multiCheckbox.classList.add('hidden');
                        }
                        if (scoreWrapper) {
                            scoreWrapper.style.display = 'none';
                        }
                    }
                } else {
                    row.style.display = '';
                    if (textarea) {
                        textarea.required = key === 'A' || key === 'B';
                    }
                    if (radio) {
                        radio.required = !isMultipleAnswer && key === 'A';
                        radio.classList.toggle('hidden', isMultipleAnswer);
                    }
                    if (multiCheckbox) {
                        multiCheckbox.classList.toggle('hidden', !isMultipleAnswer);
                    }
                    if (scoreWrapper) {
                        if (isMultipleAnswer) {
                            scoreWrapper.style.display = 'none';
                        } else if (tryoutType === 'tkp') {
                            scoreWrapper.style.display = '';
                        } else {
                            scoreWrapper.style.display = useCustomScores && useCustomScores.checked ? '' : 'none';
                        }
                    }
                }
            });

            if (isTrueFalse) {
                const checked = document.querySelector('input[name="correct_answer"]:checked');
                if (!checked || !['A', 'B'].includes(checked.value)) {
                    const defaultRadio = document.getElementById('correct_a');
                    if (defaultRadio) {
                        defaultRadio.checked = true;
                    }
                }
            }
        }
        console.log('Question form ready for type', questionTypeSelect.value);
    });
</script>
@endsection
