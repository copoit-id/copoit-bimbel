@extends('admin.layout.admin')
@section('title', 'Manajemen Soal')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.tryout.index') }}" title="Manajemen Tryout" />
            <x-breadcrumb-item href="" title="Soal" />
        </x-slot>
    </x-breadcrumb>

    <x-btn title="Tambah Soal" route="{{ route('admin.question.create', $tryout_detail->tryout_detail_id) }}"
        icon="ri-add-fill">
    </x-btn>
</div>
<div class="package-bimbel bg-white p-8 rounded-lg border border-border flex justify-center text-center">
    <x-page-desc direction='item-center' title="Manajemen Soal - {{ $tryout->name }}">
        <x-slot name="description">
            Subtest: {{ $tryout_detail->type_subtest == 'social culture' ? 'Sosial Kultural & Manajerial' :
            strtoupper($tryout_detail->type_subtest) }} • Durasi: {{ $tryout_detail->duration }} menit
        </x-slot>
    </x-page-desc>
</div>
<div class="bg-white p-6 rounded-lg border border-border">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Daftar Soal</h2>
            <p class="text-gray-600 text-sm mt-1">{{ $tryout_detail->type_subtest ?? 'Unknown' }} - {{
                $questions->count() }} soal</p>
        </div>
        <div class="flex w-full sm:w-auto flex-col sm:flex-row gap-2">
            <!-- Import Excel Button -->
            <button type="button" id="importBtn"
                class="w-full sm:w-auto justify-center px-4 py-2 bg-green text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                <i class="ri-file-excel-2-line"></i>
                Import Excel
            </button>

            <!-- Download Template Button -->
            <a href="{{ asset('template/template_soal.xlsx') }}"
                class="w-full sm:w-auto justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                <i class="ri-download-line"></i>
                Download Template
            </a>

            <a href="{{ route('admin.question.create', $tryout_detail->tryout_detail_id) }}"
                class="w-full sm:w-auto justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2">
                <i class="ri-add-line"></i>
                Tambah Soal
            </a>
            <a href="{{ route('admin.question-bank.index', ['import_for' => $tryout_detail->tryout_detail_id]) }}"
                class="w-full sm:w-auto justify-center px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary/5 transition-colors flex items-center gap-2">
                <i class="ri-folder-transfer-line"></i>
                Ambil dari Bank
            </a>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="importModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Import Soal dari Excel</h3>
                <button type="button" id="closeModal" class="text-gray-400 hover:text-gray-600">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>

            <form action="{{ route('admin.question-import.import', $tryout_detail->tryout_detail_id) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label for="excel_file" class="block text-sm font-medium text-gray-700 mb-2">
                        File Excel
                    </label>
                    <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                        required>
                    <p class="text-xs text-gray-500 mt-1">Format: .xlsx atau .xls, maksimal 2MB</p>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <h4 class="font-medium text-blue-800 mb-2">Petunjuk:</h4>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• Download template Excel terlebih dahulu</li>
                        <li>• Isi data sesuai format yang sudah disediakan</li>
                        <li>• Hapus baris instruksi sebelum import</li>
                        <li>• Maksimal 100 soal per file</li>
                    </ul>
                </div>

                <div class="flex gap-3">
                    <button type="button" id="cancelBtn"
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-4 space-y-4">
        @forelse ($questions as $index => $question)
        <div class="bg-white border border-border rounded-xl p-6 flex flex-col">
            <div class="flex items-center gap-2 mb-2">
                <span
                    class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-[#0B2B9A]/10 text-[#0B2B9A] border border-[#0B2B9A]/10">
                    Soal #{{ $index + 1 }}
                </span>
                @php
                $maxWeight = optional($question->questionOptions)->max(function($opt){
                return is_null($opt->weight) ? 0 : (float)$opt->weight;
                });
                $metadata = is_array($question->metadata) ? $question->metadata : [];
                $multipleAnswerMeta = is_array($metadata['multiple_answer'] ?? null) ? $metadata['multiple_answer'] : [];
                $matchingMeta = is_array($metadata['matching_scores'] ?? null) ? $metadata['matching_scores'] : [];
                $displayWeight = ($maxWeight && $maxWeight > 0) ? $maxWeight : (float)($question->default_weight ?? 0);
                if (($question->question_type ?? '') === 'multiple_answer' && isset($multipleAnswerMeta['score_correct'])) {
                    $displayWeight = (float) $multipleAnswerMeta['score_correct'];
                } elseif (($question->question_type ?? '') === 'matching' && isset($matchingMeta['score_correct'])) {
                    $displayWeight = (float) $matchingMeta['score_correct'];
                }
                $typeLabels = [
                'multiple_choice' => 'Multiple Choice',
                'multiple_answer' => 'Multiple Answer',
                'true_false' => 'Benar/Salah',
                'matching' => 'Pencocokan',
                'essay' => 'Essay',
                'audio' => 'Jawaban Audio',
                ];
                @endphp
                <span
                    class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                    @if(($question->question_type ?? '') === 'multiple_answer')
                        {{ $typeLabels[$question->question_type] ?? 'Multiple Answer' }}
                        - {{ in_array(($multipleAnswerMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true) ? $multipleAnswerMeta['scoring_mode'] : 'fullscore' }}
                    @elseif(($question->question_type ?? '') === 'matching')
                        {{ $typeLabels[$question->question_type] ?? 'Pencocokan' }}
                        - {{ in_array(($matchingMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true) ? $matchingMeta['scoring_mode'] : 'fullscore' }}
                    @else
                        {{ $typeLabels[$question->question_type] ?? ucwords(str_replace('_', ' ', $question->question_type)) }}
                    @endif
                </span>
                <span
                    class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                    {{ (float) $displayWeight }} poin
                </span>
                @if($question->sound)
                <span
                    class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 border border-purple-200">
                    <i class="ri-volume-up-line mr-1"></i>
                    Audio
                </span>
                @endif
            </div>

            <div class="question-rich-text font-semibold text-gray-900 leading-relaxed mb-2">
                {!! $question->question_text !!}
            </div>

            @if($question->sound)
            <div class="mb-3">
                <audio controls controlsList="nodownload" oncontextmenu="return false;" class="w-full max-w-md">
                    <source src="{{ Storage::url($question->sound) }}" type="audio/mpeg">
                    Browser Anda tidak mendukung audio.
                </audio>
            </div>
            @endif

            <div class="mb-2">
                <div class="font-semibold text-gray-700 mb-1">Detail Jawaban:</div>
                @switch($question->question_type)
                @case('matching')
                @php $pairs = isset($metadata['matching_pairs']) && is_array($metadata['matching_pairs']) ?
                $metadata['matching_pairs'] : []; @endphp
                @if(!empty($pairs))
                <ul class="space-y-2 text-gray-600">
                    @foreach($pairs as $pair)
                    <li class="flex items-center gap-2">
                        <span class="font-medium text-gray-800">{{ $pair['left'] ?? '-' }}</span>
                        <i class="ri-arrow-right-line text-gray-400"></i>
                        <span class="text-gray-600">{{ $pair['right'] ?? '-' }}</span>
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="text-sm text-gray-500">Belum ada pasangan pencocokan yang tersimpan.</p>
                @endif
                @break

                @case('short_answer')
                @case('essay')
                @php
                $shortMeta = isset($metadata['short_answer']) && is_array($metadata['short_answer']) ?
                $metadata['short_answer'] : [];
                $expectedAnswers = isset($shortMeta['expected_answers']) && is_array($shortMeta['expected_answers']) ?
                $shortMeta['expected_answers'] : [];
                $caseSensitive = $shortMeta['case_sensitive'] ?? false;
                $evaluationMode = $shortMeta['evaluation_mode'] ?? (($shortMeta['manual_review'] ?? true) ? 'manual' : 'auto');
                $manualReview = $question->question_type === 'essay'
                    ? ($evaluationMode !== 'auto' || empty($expectedAnswers))
                    : ($shortMeta['manual_review'] ?? empty($expectedAnswers));
                $showAutoAnswers = !empty($expectedAnswers) && ($question->question_type !== 'essay' || $evaluationMode === 'auto');
                @endphp
                @if($showAutoAnswers)
                <ul class="list-disc list-inside text-gray-600 space-y-1">
                    @foreach($expectedAnswers as $answer)
                    <li>{{ $answer }}</li>
                    @endforeach
                </ul>
                <p class="text-xs text-gray-500 mt-2">
                    @if($question->question_type === 'essay')
                        Penilaian otomatis mengabaikan huruf besar-kecil.
                    @else
                        Penilaian otomatis {{ $caseSensitive ? 'memperhatikan' : 'mengabaikan' }} huruf besar-kecil.
                    @endif
                </p>
                @elseif(!empty($expectedAnswers))
                <ul class="list-disc list-inside text-gray-600 space-y-1">
                    @foreach($expectedAnswers as $answer)
                    <li>{{ $answer }}</li>
                    @endforeach
                </ul>
                <p class="text-xs text-gray-500 mt-2">Penilaian dilakukan manual.</p>
                @else
                <p class="text-sm text-gray-500">Tidak ada jawaban referensi. Penilaian dilakukan manual.</p>
                @endif
                @if($manualReview)
                <span
                    class="inline-flex mt-2 px-2 py-1 text-xs font-medium rounded bg-amber-100 text-amber-700 border border-amber-200">
                    Perlu penilaian manual
                </span>
                @endif
                @break

                @case('audio')
                @php
                $audioConfig = isset($metadata['audio_answer']) && is_array($metadata['audio_answer']) ?
                $metadata['audio_answer'] : [];
                $allowedMimes = isset($audioConfig['allowed_mimes']) && is_array($audioConfig['allowed_mimes']) ?
                implode(', ', $audioConfig['allowed_mimes']) : 'audio/mpeg, audio/wav, audio/m4a';
                @endphp
                <ul class="space-y-1 text-gray-600 text-sm">
                    @if(!empty($audioConfig['instructions']))
                    <li><span class="font-semibold text-gray-700">Instruksi:</span> {{ $audioConfig['instructions'] }}
                    </li>
                    @endif
                    @if(!empty($audioConfig['max_duration']))
                    <li><span class="font-semibold text-gray-700">Durasi maks:</span> {{ $audioConfig['max_duration'] }}
                        detik</li>
                    @endif
                    @if(!empty($audioConfig['max_size']))
                    <li><span class="font-semibold text-gray-700">Ukuran maks:</span> {{ $audioConfig['max_size'] }} MB
                    </li>
                    @endif
                    <li><span class="font-semibold text-gray-700">Format:</span> {{ $allowedMimes }}</li>
                </ul>
                <p class="text-xs text-gray-500 mt-2">Jawaban audio memerlukan evaluasi manual.</p>
                @break

                @default
                @php
                $isMultipleAnswer = ($question->question_type ?? '') === 'multiple_answer';
                $multipleAnswerScoringMode = in_array(($multipleAnswerMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
                    ? $multipleAnswerMeta['scoring_mode']
                    : 'fullscore';
                $multipleAnswerTotalScore = (float) ($multipleAnswerMeta['score_correct'] ?? ($question->default_weight ?? 1));
                $multipleAnswerCorrectCount = max(1, $question->questionOptions->where('is_correct', true)->count());
                $multipleAnswerPerCorrectScore = $multipleAnswerCorrectCount > 0
                    ? ($multipleAnswerTotalScore / $multipleAnswerCorrectCount)
                    : $multipleAnswerTotalScore;
                @endphp
                <ul class="space-y-2 text-gray-600">
                    @foreach ($question->questionOptions as $optIndex => $option)
                    @php
                    $optionLabel = chr(65 + $optIndex);
                    $partialOptionScore = $option->is_correct ? $multipleAnswerPerCorrectScore : 0;
                    @endphp
                    <li class="flex items-start gap-2 {{ $option->is_correct == 1 ? 'text-green font-medium' : '' }}">
                        <i
                            class="mt-0.5 {{ $option->is_correct == 1 ? 'ri-checkbox-circle-fill' : 'ri-checkbox-blank-circle-line' }}"></i>
                        <div class="flex-1 min-w-0 flex items-start gap-1">
                            <span class="shrink-0">{{ $optionLabel }}.</span>
                            <div class="option-inline-text">{!! $option->option_text !!}</div>
                        </div>
                        @if($isMultipleAnswer && $multipleAnswerScoringMode === 'partial')
                        <span class="text-xs text-gray-500 whitespace-nowrap ml-2">
                            ({{ number_format($partialOptionScore, 2) }} poin)
                        </span>
                        @elseif($isMultipleAnswer && $multipleAnswerScoringMode === 'fullscore' && $option->is_correct)
                        <span class="text-xs text-gray-500 whitespace-nowrap ml-2">
                            (Benar semua : {{ rtrim(rtrim(number_format($multipleAnswerTotalScore, 2, '.', ''), '0'), '.') }} poin)
                        </span>
                        @elseif($question->custom_score == 'yes')
                        <span class="text-xs text-gray-500 whitespace-nowrap ml-2">({{ $option->weight }} poin)</span>
                        @endif
                    </li>
                    @endforeach
                </ul>
                @break
                @endswitch
            </div>

            @if ($question->explanation)
            <div class="bg-blue-50 border border-primary border-dashed rounded-lg p-4 mt-2">
                <div class="font-semibold text-primary mb-1">Pembahasan</div>
                <div class="text-primary">{!! $question->explanation !!}</div>
            </div>
            @endif

            <div class="flex flex-col sm:flex-row gap-3 mt-6">
                <a href="{{ route('admin.question.edit', [$tryout_detail->tryout_detail_id, $question->question_id]) }}"
                    class="w-full sm:w-auto bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors flex items-center justify-center gap-2">
                    <i class="ri-pencil-fill"></i>
                    Edit Soal
                </a>
                <form
                    action="{{ route('admin.question.destroy', [$tryout_detail->tryout_detail_id, $question->question_id]) }}"
                    method="POST" onsubmit="return confirmDelete(event)" class="w-full sm:w-auto">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="w-full sm:w-auto px-4 py-2 bg-red text-white rounded-lg hover:bg-red/90 transition-colors flex items-center justify-center gap-2">
                        <i class="ri-delete-bin-5-fill"></i>
                        Hapus Soal
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="bg-white border border-border rounded-xl p-12 text-center">
            <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="ri-question-line text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada soal</h3>
            <p class="text-gray-500 mb-4">Mulai dengan membuat soal pertama untuk subtest ini</p>
            <a href="{{ route('admin.question.create', $tryout_detail->tryout_detail_id) }}"
                class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 inline-flex items-center gap-2">
                <i class="ri-add-line"></i>
                Tambah Soal
            </a>
        </div>
        @endforelse
    </div>
</div>
@endsection

@section('styles')
<style>
    .option-inline-text p {
        display: inline;
        margin: 0;
    }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const importBtn = document.getElementById('importBtn');
    const importModal = document.getElementById('importModal');
    const closeModal = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');

    // Show modal
    importBtn.addEventListener('click', function() {
        importModal.classList.remove('hidden');
    });

    // Hide modal
    function hideModal() {
        importModal.classList.add('hidden');
    }

    closeModal.addEventListener('click', hideModal);
    cancelBtn.addEventListener('click', hideModal);

    // Hide modal when clicking outside
    importModal.addEventListener('click', function(e) {
        if (e.target === importModal) {
            hideModal();
        }
    });
});

    // Delete confirmation function
    function confirmDelete(event) {
        event.preventDefault();

        if (confirm('Apakah Anda yakin ingin menghapus soal ini? Tindakan ini tidak dapat dibatalkan.')) {
            event.target.submit();
            return true;
        }
        return false;
    }
</script>
@endsection
