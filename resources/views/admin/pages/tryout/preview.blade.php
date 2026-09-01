@extends('admin.layout.admin')
@section('title', 'Preview Tryout')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.tryout.index') }}" title="Manajemen Tryout" />
            <x-breadcrumb-item href="" title="Preview Tryout" />
        </x-slot>
    </x-breadcrumb>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-tryout.question-download-menu
            :questions-url="route('admin.tryout.download-questions', ['tryout' => $tryout, 'type' => 'soal'])"
            :explanations-url="route('admin.tryout.download-questions', ['tryout' => $tryout, 'type' => 'pembahasan'])"
            label="Unduh Laporan" />
        <a href="{{ route('admin.tryout.index') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
    </div>
</div>

<x-page-desc title="Preview Tryout - {{ $tryout->name }}">
    <x-slot name="description">
        {{ ucfirst($tryout->type_tryout) }} • {{ $tryout->tryoutDetails->count() }} Subtest •
        {{ $tryout->tryoutDetails->sum(function($detail) { return $detail->questions->count(); }) }} Total Soal
    </x-slot>
</x-page-desc>

<!-- Tryout Info Card -->
<div class="bg-white p-6 rounded-lg border border-border mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="text-center p-4 border border-primary bg-primary/10 rounded-lg">
            <h3 class="text-2xl font-bold text-primary">{{ $tryout->tryoutDetails->count() }}</h3>
            <p class="text-sm text-primary">Subtest</p>
        </div>
        <div class="text-center p-4 border border-primary bg-primary/10 rounded-lg">
            <h3 class="text-2xl font-bold text-primary">{{ $tryout->tryoutDetails->sum(function($detail) { return
                $detail->questions->count(); }) }}</h3>
            <p class="text-sm text-primary">Total Soal</p>
        </div>
        <div class="text-center p-4 border border-primary bg-primary/10 rounded-lg">
            <h3 class="text-2xl font-bold text-primary">{{ $tryout->tryoutDetails->sum('duration') }}</h3>
            <p class="text-sm text-primary">Total Durasi (menit)</p>
        </div>
        <div class="text-center p-4 border border-primary bg-primary/10 rounded-lg">
            <h3 class="text-2xl font-bold text-primary">
                @if($tryout->is_certification)
                <i class="ri-award-line"></i> Sertifikasi
                @else
                <i class="ri-file-list-line"></i> Reguler
                @endif
            </h3>
            <p class="text-sm text-primary">Jenis Tryout</p>
        </div>
    </div>
</div>

<!-- Subtest Navigation -->
@if($tryout->tryoutDetails->count() > 1)
<div class="bg-white p-4 rounded-lg border border-border mb-6">
    <h3 class="text-lg font-semibold mb-3">Navigasi Subtest</h3>
    <div class="flex flex-wrap gap-2">
        @foreach($tryout->tryoutDetails as $index => $detail)
        <a href="#subtest-{{ $detail->tryout_detail_id }}"
            class="px-4 py-2 bg-primary/10 text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
            {{ $detail->display_name }} ({{ $detail->questions->count() }} soal)
        </a>
        @endforeach
    </div>
</div>
@endif

<!-- Questions by Subtest -->
@foreach($tryout->tryoutDetails as $subtestIndex => $detail)
<div id="subtest-{{ $detail->tryout_detail_id }}" class="bg-white rounded-lg border border-border mb-6">
    <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-900">
                </h2>
                <p class="text-gray-600">
                    {{ $detail->questions->count() }} soal • {{ $detail->duration }} menit •
                    Passing Score:
                    @if(($detail->passing_type ?? 'score') === 'percentage')
                        {{ number_format($detail->passing_score ?? 0, 1) }}%
                    @else
                        {{ $detail->passing_score }}
                    @endif
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.question.index', $detail->tryout_detail_id) }}"
                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2">
                    <i class="ri-edit-line"></i>
                    Kelola Soal
                </a>
            </div>
        </div>
    </div>

    <div class="p-6">
        @if($detail->questions->count() > 0)
        <div class="space-y-6">
            @foreach($detail->questions as $questionIndex => $question)
            <div class="border border-gray-200 rounded-lg p-4 sm:p-6">
                <div class="flex flex-wrap items-start gap-2 mb-3">
                    <span class="preview-pill inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-primary/10 text-primary">
                        Soal {{ $questionIndex + 1 }}
                    </span>
                    @php
                        // Tampilkan bobot tertinggi dari question_options.
                        // Jika tidak ada bobot/opsi, fallback ke default_weight.
                        $maxWeight = optional($question->questionOptions)->max(function($opt){
                            return is_null($opt->weight) ? 0 : (float)$opt->weight;
                        });
                        // Untuk essay/short_answer, gunakan essay_score_correct (field "Benar")
                        if (in_array($question->question_type ?? '', ['short_answer', 'essay'])) {
                            $displayWeight = (float) ($question->essay_score_correct ?? $question->default_weight ?? 0);
                        } else {
                            $displayWeight = ($maxWeight && $maxWeight > 0) ? $maxWeight : (float)($question->default_weight ?? 0);
                        }
                        $metadata = is_array($question->metadata) ? $question->metadata : [];
                        $multipleAnswerMeta = is_array($metadata['multiple_answer'] ?? null) ? $metadata['multiple_answer'] : [];
                        $matchingMeta = is_array($metadata['matching_scores'] ?? null) ? $metadata['matching_scores'] : [];
                        $mtfMeta = is_array($metadata['multiple_true_false'] ?? null) ? $metadata['multiple_true_false'] : [];
                        $multipleAnswerScoringMode = in_array(($multipleAnswerMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
                            ? $multipleAnswerMeta['scoring_mode']
                            : 'fullscore';
                        $matchingScoringMode = in_array(($matchingMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
                            ? $matchingMeta['scoring_mode']
                            : 'fullscore';
                        $mtfScoringMode = in_array(($mtfMeta['scoring_mode'] ?? null), ['fullscore', 'partial'], true)
                            ? $mtfMeta['scoring_mode']
                            : 'fullscore';
                        $multipleAnswerTotalScore = (float) ($multipleAnswerMeta['score_correct'] ?? $displayWeight);
                        $multipleAnswerCorrectCount = max(1, $question->questionOptions->where('is_correct', true)->count());
                        $multipleAnswerPerCorrectScore = $multipleAnswerCorrectCount > 0
                            ? ($multipleAnswerTotalScore / $multipleAnswerCorrectCount)
                            : $multipleAnswerTotalScore;
                        if (($question->question_type ?? '') === 'multiple_answer') {
                            $displayWeight = $multipleAnswerTotalScore;
                        } elseif (($question->question_type ?? '') === 'matching') {
                            $displayWeight = (float) ($matchingMeta['score_correct'] ?? $displayWeight);
                        } elseif (($question->question_type ?? '') === 'multiple_true_false') {
                            $displayWeight = (float) ($mtfMeta['score_correct'] ?? $displayWeight);
                        }
                        $questionTypeLabel = ucwords(str_replace('_', ' ', $question->question_type ?? 'multiple_choice'));
                    @endphp
                    <span class="preview-pill inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                        @if(($question->question_type ?? '') === 'multiple_answer')
                            Multiple Answer - [{{ $multipleAnswerScoringMode }}]
                        @elseif(($question->question_type ?? '') === 'matching')
                            Pencocokan - [{{ $matchingScoringMode }}]
                        @elseif(($question->question_type ?? '') === 'multiple_true_false')
                            Multiple True/False - [{{ $mtfScoringMode }}]
                        @else
                            {{ $questionTypeLabel }}
                        @endif
                    </span>
                    <span class="preview-pill inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                        {{ (float) $displayWeight }} poin
                    </span>
                    @if($question->sound)
                    <span
                        class="preview-pill inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-purple-100 text-purple-800">
                        <i class="ri-volume-up-line mr-1"></i>
                        Audio
                    </span>
                    @endif
                    @if($question->custom_score == 'yes')
                    <span class="preview-pill inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-amber-100 text-amber-800">
                        Custom Score
                    </span>
                    @endif
                </div>

                <div class="mb-4 question-rich-text">
                    <h4 class="font-semibold text-gray-900 mb-2">Pertanyaan:</h4>
                    <div class="text-gray-800">{!! $question->question_text !!}</div>
                </div>

                @if($question->sound)
                <div class="mb-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Audio:</h4>
                    <audio controls class="w-full max-w-md">
                        <source src="{{ Storage::url($question->sound) }}" type="audio/mpeg">
                        Browser Anda tidak mendukung audio.
                    </audio>
                </div>
                @endif

                <div class="mb-4">
                    @if(($question->question_type ?? '') === 'matching')
                    <h4 class="font-semibold text-gray-900 mb-2">Detail Jawaban:</h4>
                    @php
                        $matchingPairs = isset($metadata['matching_pairs']) && is_array($metadata['matching_pairs'])
                            ? $metadata['matching_pairs']
                            : [];
                    @endphp
                    @if(empty($matchingPairs))
                    <p class="text-sm text-gray-500">Belum ada pasangan pencocokan yang tersimpan.</p>
                    @else
                    <ul class="space-y-2 text-gray-600">
                        @foreach($matchingPairs as $pair)
                        <li class="flex items-center gap-2">
                            <span class="font-medium text-gray-800">{{ $pair['left'] ?? '-' }}</span>
                            <i class="ri-arrow-right-line text-gray-400"></i>
                            <span class="text-gray-600">{{ $pair['right'] ?? '-' }}</span>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                    @else
                    @if(($question->question_type ?? '') === 'multiple_true_false')
                    @php
                        $mtfStatements = is_array($mtfMeta['statements'] ?? null) ? $mtfMeta['statements'] : [];
                        $mtfTrueLabel = trim((string) ($mtfMeta['true_label'] ?? 'Benar'));
                        $mtfFalseLabel = trim((string) ($mtfMeta['false_label'] ?? 'Salah'));
                        $mtfTotalScore = (float) ($mtfMeta['score_correct'] ?? ($question->default_weight ?? 1));
                        $mtfPerStatement = count($mtfStatements) > 0 ? ($mtfTotalScore / count($mtfStatements)) : $mtfTotalScore;
                    @endphp
                    <h4 class="font-semibold text-gray-900 mb-2">Detail Jawaban:</h4>
                    @if(empty($mtfStatements))
                    <p class="text-sm text-gray-500">Belum ada pernyataan Multiple True/False yang tersimpan.</p>
                    @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-700 w-[68%]">Pernyataan</th>
                                    <th class="px-5 py-3 text-center font-semibold text-gray-700 whitespace-nowrap w-[10%]">{{ $mtfTrueLabel !== '' ? $mtfTrueLabel : 'Benar' }}</th>
                                    <th class="px-5 py-3 text-center font-semibold text-gray-700 whitespace-nowrap w-[10%]">{{ $mtfFalseLabel !== '' ? $mtfFalseLabel : 'Salah' }}</th>
                                    <th class="px-5 py-3 text-center font-semibold text-gray-700 whitespace-nowrap w-[12%]">Poin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mtfStatements as $statement)
                                @php
                                    $isTrue = ($statement['correct'] ?? 'true') === 'true';
                                @endphp
                                <tr class="border-t border-gray-200">
                                    <td class="px-5 py-3.5 text-gray-800">{{ $statement['text'] ?? '-' }}</td>
                                    <td class="px-5 py-3.5 text-center align-middle">
                                        @if($isTrue)<i class="ri-checkbox-circle-fill text-green-600 text-lg"></i>@endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center align-middle">
                                        @if(!$isTrue)<i class="ri-checkbox-circle-fill text-green-600 text-lg"></i>@endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center text-gray-600 whitespace-nowrap align-middle">
                                        @if($mtfScoringMode === 'partial')
                                        {{ number_format($mtfPerStatement, 2) }} poin
                                        @else
                                        Benar semua : {{ rtrim(rtrim(number_format($mtfTotalScore, 2, '.', ''), '0'), '.') }} poin
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                    @else
                    <h4 class="font-semibold text-gray-900 mb-2">Pilihan Jawaban:</h4>
                    <div class="space-y-2">
                        @foreach($question->questionOptions as $optionIndex => $option)
                        @php
                        $optionLabel = chr(65 + $optionIndex); // A, B, C, D, E
                        @endphp
                        <div
                            class="flex items-start gap-3 p-3 rounded-lg {{ $option->is_correct ? 'bg-green-50 border border-green-200' : 'bg-gray-50' }}">
                            <div class="flex-shrink-0">
                                @if($option->is_correct)
                                <i class="ri-checkbox-circle-fill text-green-600 text-lg sm:text-xl"></i>
                                @else
                                <i class="ri-checkbox-blank-circle-line text-gray-400 text-lg sm:text-xl"></i>
                                @endif
                            </div>
                            <div class="flex-grow">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-2">
                                    <div
                                        class="font-medium {{ $option->is_correct ? 'text-green-800' : 'text-gray-700' }} flex items-start gap-1 min-w-0">
                                        <span class="shrink-0">{{ $optionLabel }}.</span>
                                        <div class="option-inline-text">{!! $option->option_text !!}</div>
                                    </div>
                                    @if(($question->question_type ?? '') === 'multiple_answer')
                                        @if($multipleAnswerScoringMode === 'partial')
                                        <span
                                            class="text-xs sm:text-sm whitespace-nowrap {{ $option->is_correct ? 'text-green-600' : 'text-gray-500' }}">
                                            ({{ number_format($option->is_correct ? $multipleAnswerPerCorrectScore : 0, 2) }} poin)
                                        </span>
                                        @elseif($option->is_correct)
                                        <span class="text-xs sm:text-sm text-green-600 whitespace-nowrap">
                                            (Benar semua : {{ rtrim(rtrim(number_format($multipleAnswerTotalScore, 2, '.', ''), '0'), '.') }} poin)
                                        </span>
                                        @endif
                                    @elseif($question->custom_score == 'yes')
                                    <span
                                        class="text-xs sm:text-sm whitespace-nowrap {{ $option->is_correct ? 'text-green-600' : 'text-gray-500' }}">
                                        ({{ $option->weight }} poin)
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    @endif
                </div>

                @if($question->explanation)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-800 mb-2">Pembahasan:</h4>
                    <p class="text-blue-700">{!! $question->explanation !!}</p>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="ri-question-line text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada soal</h3>
            <p class="text-gray-500 mb-4">Subtest ini belum memiliki soal</p>
            <a href="{{ route('admin.question.index', $detail->tryout_detail_id) }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                <i class="ri-add-line"></i>
                Tambah Soal
            </a>
        </div>
        @endif
    </div>
</div>
@endforeach

@if($tryout->tryoutDetails->isEmpty())
<div class="bg-white rounded-lg border border-border p-12 text-center">
    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
        <i class="ri-file-list-line text-2xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada subtest</h3>
    <p class="text-gray-500">Tryout ini belum memiliki subtest</p>
</div>
@endif

@endsection

@section('styles')
<style>
    .option-inline-text p {
        display: inline;
        margin: 0;
    }

    @media (max-width: 640px) {
        .preview-pill {
            font-size: 12px;
            line-height: 1.2;
            padding: 6px 10px;
            border-radius: 10px;
        }
    }
</style>
@endsection

@section('scripts')
<script>
    // Smooth scrolling for subtest navigation
    document.querySelectorAll('a[href^="#subtest-"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
</script>
@endsection
