@extends('admin.layout.admin')
@section('title', isset($question) ? 'Edit Soal' : 'Tambah Soal')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.tryout.index') }}" title="Manajemen Tryout" />
            <x-breadcrumb-item href="{{ route('admin.question.index', $tryout_detail->tryout_detail_id) }}"
                title="Soal" />
            <x-breadcrumb-item href="" title="{{ isset($question) ? 'Edit Soal' : 'Tambah Soal' }}" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="{{ isset($question) ? 'Edit Soal' : 'Tambah Soal' }} - {{ $tryout->name }}">
    <x-slot name="description">
        Subtest: {{ strtoupper($tryout_detail->type_subtest) }} • Durasi: {{ $tryout_detail->duration }} menit
    </x-slot>
</x-page-desc>

<div class="space-y-6">
    <div class="bg-white rounded-lg border border-gray-200">
        <form
            action="{{ isset($question) ? route('admin.question.update', [$tryout_detail->tryout_detail_id, $question->question_id]) : route('admin.question.store', $tryout_detail->tryout_detail_id) }}"
            method="POST" enctype="multipart/form-data" novalidate>
            @csrf
            @if(isset($question))
            @method('PUT')
            @endif

            @php
            $rawType = old('question_type', isset($question) ? $question->question_type : 'multiple_choice');
            $currentType = in_array($rawType, ['short_answer', 'essay', 'multiple_true_false']) ? $rawType : (in_array($rawType, ['true_false', 'multiple_answer']) ?
            'multiple_choice' : $rawType);

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
                $essayScoringMode = old(
                    'essay_scoring_mode',
                    $shortAnswerMeta['evaluation_mode'] ?? (($shortAnswerMeta['manual_review'] ?? true) ? 'manual' : 'auto')
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
                while (count($mtfStatements) < 2) {
                    $mtfStatements[] = [
                        'id' => 'stmt_' . (count($mtfStatements) + 1),
                        'text' => '',
                        'correct' => 'true',
                    ];
                }
                @endphp

                <div class="p-6 space-y-6">
                    <div>
                        <label for="question_type" class="block text-sm font-medium text-gray-700 mb-2">Jenis Soal <span
                                class="text-red-500">*</span></label>
                        <select id="question_type" name="question_type"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="multiple_choice" {{ $rawType==='multiple_choice' ? 'selected' : '' }}>
                                Multiple
                                Choice</option>
                            <option value="multiple_answer" {{ $rawType==='multiple_answer' ? 'selected' : '' }}>
                                Multiple Answer (Lebih dari 1 benar)</option>
                            <option value="true_false" {{ $rawType==='true_false' ? 'selected' : '' }}>Benar/Salah
                            </option>
                            <option value="matching" {{ $rawType==='matching' ? 'selected' : '' }}>Pencocokan</option>
                            <option value="multiple_true_false" {{ $rawType==='multiple_true_false' ? 'selected' : '' }}>Multiple True/False</option>
                            <option value="essay" {{ $rawType==='essay' ? 'selected' : '' }}>Essay</option>
                            <option value="audio" {{ $rawType==='audio' ? 'selected' : '' }}>Jawaban Audio</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-2">Pilih tipe soal untuk menampilkan form yang sesuai.</p>
                    </div>

                    <div id="questionScoreContainer"
                        class="space-y-2 {{ in_array($rawType, ['short_answer','essay','audio']) ? '' : 'hidden' }}">
                        <label for="question_score" class="block text-sm font-medium text-gray-700">Skor Soal (Custom)</label>
                        <input type="number" id="question_score" name="question_score" min="0" step="0.1"
                            value="{{ old('question_score', isset($question) ? $question->default_weight : $tryout_detail->default_weight ?? 1) }}"
                            class="w-full md:w-1/3 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Contoh: 5">
                        <p class="text-xs text-gray-500">Dipakai untuk tipe short answer, essay, dan audio.</p>
                    </div>

                    <!-- Question Text -->
                    <div>
                        <label for="question_text" class="block text-sm font-medium text-gray-700 mb-2">Teks Soal <span
                                class="text-red-500">*</span></label>
                        <textarea id="question_text" name="question_text" required rows="4"
                            class="ckeditor w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Masukkan teks soal...">{{ isset($question) ? $question->question_text : old('question_text') }}</textarea>
                    </div>

                    <!-- Audio Upload -->
                    <div class="grid grid-cols-1 gap-6">
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
                    <div class="space-y-4 question-type-section" data-question-type="multiple_choice"
                        style="display:none;">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-800">Pilihan Jawaban</h3>
                            @if ($tryout->is_toefl !== 1)
                            @if($tryout_detail->type_subtest !== 'tkp')
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
                        <div id="multipleAnswerScoreContainer"
                            class="space-y-2 {{ $rawType === 'multiple_answer' ? '' : 'hidden' }}">
                            <label class="block text-sm font-medium text-gray-700">Skor Multiple Answer</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:w-full">
                                @php
                                    $existingMultiMeta = isset($question) && is_array($question->metadata) ? ($question->metadata['multiple_answer'] ?? []) : [];
                                    $multiScoreCorrect = old('multiple_answer_score_correct', $existingMultiMeta['score_correct'] ?? 1);
                                    $multiScoreWrong = old('multiple_answer_score_wrong', $existingMultiMeta['score_wrong'] ?? 0);
                                    $multiScoringMode = old('multiple_answer_scoring_mode', $existingMultiMeta['scoring_mode'] ?? 'fullscore');
                                @endphp
                                <div>
                                    <div class="flex items-center gap-1 mb-1">
                                        <label for="multiple_answer_scoring_mode" class="block text-xs font-medium text-gray-600">Mode Penilaian</label>
                                        <div class="relative group">
                                            <i class="ri-information-line text-gray-400 text-sm cursor-help"></i>
                                            <div class="pointer-events-none absolute left-1/2 top-full z-20 mt-1 hidden w-72 -translate-x-1/2 rounded-md border border-gray-200 bg-white p-2 text-[11px] leading-relaxed text-gray-600 shadow-sm group-hover:block">
                                                Fullscore: nilai pakai skor benar+salah. Partial: jika ada benar, nilai proporsional; jika salah semua, pakai skor salah.
                                            </div>
                                        </div>
                                    </div>
                                    <select id="multiple_answer_scoring_mode" name="multiple_answer_scoring_mode"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        <option value="fullscore" {{ $multiScoringMode === 'fullscore' ? 'selected' : '' }}>Benar/Salah Fullscore</option>
                                        <option value="partial" {{ $multiScoringMode === 'partial' ? 'selected' : '' }}>Partial</option>
                                    </select>
                                </div>
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
                        if (isset($question) && $question->questionOptions && isset($question->questionOptions[$index]))
                        {
                        $optionData = $question->questionOptions[$index];
                        $isCorrect = $optionData->is_correct == 1;
                        }
                        @endphp
                        <div class="option-row flex gap-3 items-start" data-option-key="{{ $optionKey }}">
                            <div class="pt-8">
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
                                    @if($optionKey !== 'E')<span class="text-red-500">*</span>@endif
                                </label>
                                <textarea id="option_{{ strtolower($optionKey) }}"
                                    name="option_{{ strtolower($optionKey) }}" {{ $optionKey==='E' ? '' : 'required' }}
                                    class="ckeditor-option w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="Pilihan {{ $optionKey }}">{{ $optionData ? $optionData->option_text : old('option_' . strtolower($optionKey)) }}</textarea>
                            </div>
                            <div class="custom-score-field w-full sm:w-1/4"
                                style="{{ ($tryout_detail->type_subtest === 'tkp') || (isset($question) && $question->custom_score == 'yes') || old('use_custom_scores') ? '' : 'display: none;' }}">
                                <label for="score_{{ strtolower($optionKey) }}"
                                    class="block text-sm font-medium text-gray-700 mb-2">
                                    @if($tryout_detail->type_subtest === 'tkp')
                                    Skor {{ $optionKey }} (1-5)
                                    @else
                                    Skor {{ $optionKey }}
                                    @endif
                                </label>
                                <input type="number" id="score_{{ strtolower($optionKey) }}"
                                    name="score_{{ strtolower($optionKey) }}"
                                    value="{{ $optionData ? $optionData->weight : old('score_' . strtolower($optionKey), $tryout_detail->type_subtest === 'tkp' ? 1 : 0) }}"
                                    min="0" max="5" step="0.1"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="{{ $tryout_detail->type_subtest === 'tkp' ? '1-5' : '0' }}">
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Matching -->
                    <div class="space-y-4 question-type-section" data-question-type="matching" style="display:none;">
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
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:w-full">
                            <div>
                                <div class="flex items-center gap-1 mb-1">
                                    <label for="matching_scoring_mode" class="block text-sm font-medium text-gray-700">Mode Penilaian</label>
                                    <div class="relative group">
                                        <i class="ri-information-line text-gray-400 text-sm cursor-help"></i>
                                        <div class="pointer-events-none absolute left-1/2 top-full z-20 mt-1 hidden w-72 -translate-x-1/2 rounded-md border border-gray-200 bg-white p-2 text-[11px] leading-relaxed text-gray-600 shadow-sm group-hover:block">
                                            Fullscore: nilai pakai skor benar+salah. Partial: jika ada benar, nilai proporsional; jika salah semua, pakai skor salah.
                                        </div>
                                    </div>
                                </div>
                                @php
                                    $matchingScoringMode = old('matching_scoring_mode', $matchingScores['scoring_mode'] ?? 'fullscore');
                                @endphp
                                <select id="matching_scoring_mode" name="matching_scoring_mode"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <option value="fullscore" {{ $matchingScoringMode === 'fullscore' ? 'selected' : '' }}>Benar/Salah Fullscore</option>
                                    <option value="partial" {{ $matchingScoringMode === 'partial' ? 'selected' : '' }}>Partial (seperti multiple)</option>
                                </select>
                            </div>
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
                            <div class="matching-pair-row flex flex-col sm:flex-row gap-3 items-start"
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
                    <div class="space-y-4 question-type-section" data-question-type="multiple_true_false" style="display:none;">
                        <div>
                            <h3 class="text-lg font-medium text-gray-800">Multiple True/False</h3>
                            <p class="text-sm text-gray-600">Isi beberapa pernyataan. Peserta akan memilih salah satu dari dua opsi pada tiap baris.</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label for="mtf_true_label" class="block text-sm font-medium text-gray-700 mb-1">Teks Opsi Kolom 1</label>
                                <input type="text" id="mtf_true_label" name="mtf_true_label" value="{{ $mtfTrueLabel }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="Contoh: Benar / Setuju">
                            </div>
                            <div>
                                <label for="mtf_false_label" class="block text-sm font-medium text-gray-700 mb-1">Teks Opsi Kolom 2</label>
                                <input type="text" id="mtf_false_label" name="mtf_false_label" value="{{ $mtfFalseLabel }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="Contoh: Salah / Tidak Setuju">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:w-full">
                            <div>
                                <label for="mtf_scoring_mode" class="block text-sm font-medium text-gray-700 mb-1">Mode Penilaian</label>
                                <select id="mtf_scoring_mode" name="mtf_scoring_mode"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <option value="fullscore" {{ $mtfScoringMode === 'fullscore' ? 'selected' : '' }}>Benar/Salah Fullscore</option>
                                    <option value="partial" {{ $mtfScoringMode === 'partial' ? 'selected' : '' }}>Partial</option>
                                </select>
                            </div>
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
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100 text-gray-700">
                                    <tr>
                                        <th class="px-5 py-3.5 text-left font-semibold w-[75%]">Pernyataan</th>
                                        <th class="px-5 py-3.5 text-center font-semibold whitespace-nowrap w-[10%]" id="mtfHeaderTrue">{{ $mtfTrueLabel !== '' ? $mtfTrueLabel : 'Kolom 1' }}</th>
                                        <th class="px-5 py-3.5 text-center font-semibold whitespace-nowrap w-[10%]" id="mtfHeaderFalse">{{ $mtfFalseLabel !== '' ? $mtfFalseLabel : 'Kolom 2' }}</th>
                                        <th class="px-5 py-3.5 text-center font-semibold w-[5%] min-w-[72px]">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="mtfStatementsContainer">
                                    @foreach($mtfStatements as $index => $statement)
                                    <tr class="mtf-row border-t border-gray-200" data-index="{{ $index }}">
                                        <td class="px-5 py-3.5 align-top">
                                            <input type="hidden" name="mtf_statements[{{ $index }}][id]" value="{{ $statement['id'] }}">
                                            <input type="hidden" name="mtf_statements[{{ $index }}][correct]" value="{{ $statement['correct'] === 'false' ? 'false' : 'true' }}" class="mtf-correct-input">
                                            <textarea name="mtf_statements[{{ $index }}][text]" rows="2"
                                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
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
                        <button type="button" id="addMtfRow"
                            class="px-4 py-2 border border-dashed border-primary text-primary rounded-lg hover:bg-primary/10 transition-colors flex items-center gap-2">
                            <i class="ri-add-line"></i>
                            Tambah Pernyataan
                        </button>
                    </div>

                    <!-- Short Answer / Essay -->
                    <div class="space-y-4 question-type-section" data-question-type="short_answer"
                        style="display:none;">
                        <div>
                            <h3 class="text-lg font-medium text-gray-800">Pengaturan Jawaban Teks</h3>
                            <p class="text-sm text-gray-600" data-expected-hint>Isi daftar jawaban benar jika ingin penilaian otomatis.
                                Kosongkan untuk penilaian manual.</p>
                        </div>
                        <div class="space-y-2" data-essay-scoring style="display:none;">
                            <span class="text-sm font-medium text-gray-700">Mode Koreksi Essay</span>
                            <div class="flex flex-wrap gap-4">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="radio" name="essay_scoring_mode" value="auto" {{ $essayScoringMode === 'auto' ? 'checked' : '' }}
                                        class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                                    Otomatis (berdasarkan jawaban referensi)
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="radio" name="essay_scoring_mode" value="manual" {{ $essayScoringMode !== 'auto' ? 'checked' : '' }}
                                        class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                                    Manual (perlu dikoreksi)
                                </label>
                            </div>
                        </div>
                        <div>
                            <label for="short_answer_expected"
                                class="block text-sm font-medium text-gray-700 mb-2">Daftar
                                Jawaban Benar (Opsional)</label>
                            <textarea id="short_answer_expected" name="short_answer_expected" rows="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Masukkan satu jawaban per baris">{{ $shortAnswerExpected }}</textarea>
                            <p class="text-xs text-gray-500 mt-2">Pisahkan dengan baris baru untuk jawaban alternatif
                                (contoh:
                                &quot;Jakarta&quot; kemudian baris berikutnya &quot;DKI Jakarta&quot;).</p>
                        </div>
                        <div class="flex items-center gap-2" data-short-answer-case>
                            <input type="checkbox" id="short_answer_case_sensitive" name="short_answer_case_sensitive"
                                value="1" {{ $shortAnswerCaseSensitive ? 'checked' : '' }}
                                class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                            <label for="short_answer_case_sensitive" class="text-sm text-gray-700">Perhatikan huruf
                                besar-kecil
                                saat menilai otomatis</label>
                        </div>
                        <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
                            <i class="ri-information-line mr-1"></i>
                            Soal essay akan ditandai sebagai butuh penilaian manual jika tidak ada jawaban benar yang
                            ditentukan.
                        </div>
                    </div>

                    <!-- Audio Answer -->
                    <div class="space-y-4 question-type-section" data-question-type="audio" style="display:none;">
                        <div>
                            <h3 class="text-lg font-medium text-gray-800">Pengaturan Jawaban Audio</h3>
                            <p class="text-sm text-gray-600">Peserta akan mengunggah jawaban dalam bentuk rekaman suara.
                            </p>
                        </div>
                        <div>
                            <label for="audio_instructions"
                                class="block text-sm font-medium text-gray-700 mb-2">Instruksi
                                untuk Peserta (Opsional)</label>
                            <textarea id="audio_instructions" name="audio_instructions" rows="3"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Contoh: Ceritakan pendapatmu selama 1 menit">{{ $audioInstructions }}</textarea>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="audio_max_duration"
                                    class="block text-sm font-medium text-gray-700 mb-2">Durasi
                                    Maksimal (detik)</label>
                                <input type="number" id="audio_max_duration" name="audio_max_duration" min="5" max="600"
                                    value="{{ $audioMaxDuration }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="Contoh: 90">
                                <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak dibatasi (maks. 10 menit).
                                </p>
                            </div>
                            <div>
                                <label for="audio_max_size" class="block text-sm font-medium text-gray-700 mb-2">Batas
                                    Ukuran
                                    File (MB)</label>
                                <input type="number" id="audio_max_size" name="audio_max_size" min="1" max="100"
                                    value="{{ $audioMaxSize }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="Contoh: 10">
                                <p class="text-xs text-gray-500 mt-1">Format yang didukung: MP3, WAV, M4A.</p>
                            </div>
                        </div>
                        <div class="rounded-lg bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 text-sm">
                            <i class="ri-information-line mr-1"></i>
                            Penilaian jawaban audio dilakukan manual. Admin dapat mengunduh file jawaban peserta dari
                            halaman
                            hasil tryout.
                        </div>
                    </div>

                    <!-- Explanation -->
                    <div>
                        <label for="explanation" class="block text-sm font-medium text-gray-700 mb-2">Pembahasan
                            (Opsional)</label>
                        <textarea id="explanation" name="explanation" rows="4"
                            class="ckeditor w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Masukkan pembahasan soal...">{{ isset($question) ? $question->explanation : old('explanation') }}</textarea>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex justify-end gap-4 pt-6 border-t">
                        <a href="{{ route('admin.question.index', $tryout_detail->tryout_detail_id) }}"
                            class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            Batal
                        </a>
                        <button type="submit"
                            class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
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
        const tryoutType = '{{ $tryout_detail->type_subtest }}';
        const form = document.querySelector('form');
        const matchingContainer = document.getElementById('matchingPairsContainer');
        const addMatchingPairBtn = document.getElementById('addMatchingPair');
        const mtfContainer = document.getElementById('mtfStatementsContainer');
        const addMtfRowBtn = document.getElementById('addMtfRow');
        const mtfTrueLabelInput = document.getElementById('mtf_true_label');
        const mtfFalseLabelInput = document.getElementById('mtf_false_label');
        const mtfHeaderTrue = document.getElementById('mtfHeaderTrue');
        const mtfHeaderFalse = document.getElementById('mtfHeaderFalse');
        const optionRows = document.querySelectorAll('.option-row');
        const questionScoreContainer = document.getElementById('questionScoreContainer');
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
            const shortAnswerCase = document.querySelector('[data-short-answer-case]');
            const expectedHint = document.querySelector('[data-expected-hint]');

            if (essayScoring && shortAnswerCase && expectedHint) {
                const isEssay = currentType === 'essay';
                essayScoring.style.display = isEssay ? '' : 'none';
                shortAnswerCase.style.display = isEssay ? 'none' : '';
                expectedHint.textContent = isEssay
                    ? 'Isi daftar jawaban referensi jika memilih koreksi otomatis.'
                    : 'Isi daftar jawaban benar jika ingin penilaian otomatis. Kosongkan untuk penilaian manual.';
            }

            if (customScoreToggle) {
                const showCustomToggle = currentType === 'multiple_choice' || currentType === 'true_false';
                customScoreToggle.style.display = showCustomToggle ? '' : 'none';
            }

            if (questionScoreContainer) {
                const useQuestionScore = ['short_answer', 'essay', 'audio'].includes(currentType);
                questionScoreContainer.classList.toggle('hidden', !useQuestionScore);
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
                mtfIndex += 1;
            });

            mtfContainer.addEventListener('click', function(event) {
                const removeButton = event.target.closest('.remove-mtf-row');
                if (!removeButton) {
                    return;
                }

                const rows = mtfContainer.querySelectorAll('.mtf-row');
                if (rows.length <= 2) {
                    alert('Minimal harus ada dua pernyataan.');
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
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
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
                        if (textarea && !textarea.value.trim()) {
                            textarea.value = key === 'A' ? 'Benar' : 'Salah';
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
                            textarea.value = '';
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
                        textarea.required = key !== 'E';
                    }
                    if (radio) {
                        radio.required = !isMultipleAnswer && key !== 'E';
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
