@extends('user.layout.user')
@section('title', __('Pembahasan Tryout'))
@section('content')
<div class="package-bimbel flex flex-col gap-4" data-anticopy="pembahasan">
    <div class="bg-white px-4 py-10 rounded-lg border border-border flex flex-col md:flex-row gap-4 text-dark">
        <div class="flex order-2 md:order-1 flex-col items-center gap-4 w-full">
            <p class="font-semibold">{{ __('Pembahasan') }} - {{ $tryout->name }}</p>
            <p class="text-5xl font-medium">{{ number_format($overallStats['total_score'], 0) }}</p>
            <span
                class="flex items-center gap-1 border px-6 py-0.5 rounded-lg {{ $overallStats['is_passed'] ? 'border-green bg-green-light text-green' : 'border-red bg-red-light text-red' }}">
                <i class="ri-checkbox-circle-fill text-lg"></i>
                <span>{{ $overallStats['is_passed'] ? __('Lulus') : __('Tidak Lulus') }}</span>
            </span>
            @if(isset($tryoutDetails) && $tryoutDetails->count() > 1)
            <div class="mt-2">
                <span class="inline-flex px-3 py-1 bg-primary/10 text-primary text-sm font-medium rounded-full">
                    {{ __('SKD Full') }} - {{ $tryoutDetails->count() }} {{ __('Subtest') }}
                </span>
            </div>
            @endif
        </div>
        <span class="self-strech hidden md:block md:order-2 w-px border-l border-dashed border-gray-400"></span>
        <div class="grid order-1 md:order-3 grid-cols-2 gap-2 w-full">
            <div class="flex w-full items-center gap-3 bg-white p-4 rounded-lg border border-border">
                <i
                    class="ri-question-line text-[20px] flex items-center justify-center text-white font-medium bg-primary w-10 h-10 rounded-lg"></i>
                <div>
                    <p class="text-[24px] font-bold">{{ $overallStats['total_questions'] }}</p>
                    <p class="text-[12px] mt-[-6px] font-light">{{ __('Total Soal') }}</p>
                </div>
            </div>
            <div class="flex w-full items-center gap-3 bg-white p-4 rounded-lg border border-border">
                <i
                    class="ri-check-line text-[20px] flex items-center justify-center text-white font-medium bg-green w-10 h-10 rounded-lg"></i>
                <div>
                    <p class="text-[24px] font-bold">{{ $overallStats['correct_answers'] }}</p>
                    <p class="text-[12px] mt-[-6px] font-light">{{ __('Jawaban Benar') }}</p>
                </div>
            </div>
            <div class="flex w-full items-center gap-3 bg-white p-4 rounded-lg border border-border">
                <i
                    class="ri-close-line text-[20px] flex items-center justify-center text-white font-medium bg-red w-10 h-10 rounded-lg"></i>
                <div>
                    <p class="text-[24px] font-bold">{{ $overallStats['wrong_answers'] }}</p>
                    <p class="text-[12px] mt-[-6px] font-light">{{ __('Jawaban Salah') }}</p>
                </div>
            </div>
            <div class="flex w-full items-center gap-3 bg-white p-4 rounded-lg border border-border">
                <i
                    class="ri-question-mark-line text-[20px] flex items-center justify-center text-white font-medium bg-gray-500 w-10 h-10 rounded-lg"></i>
                <div>
                    <p class="text-[24px] font-bold">{{ $overallStats['unanswered'] }}</p>
                    <p class="text-[12px] mt-[-6px] font-light">{{ __('Tidak Dijawab') }}</p>
                </div>
            </div>
            @if(!empty($overallStats['pending_review']))
            <div class="flex w-full items-center gap-3 bg-white p-4 rounded-lg border border-border">
                <i
                    class="ri-time-line text-[20px] flex items-center justify-center text-white font-medium bg-amber-500 w-10 h-10 rounded-lg"></i>
                <div>
                    <p class="text-[24px] font-bold">{{ $overallStats['pending_review'] }}</p>
                    <p class="text-[12px] mt-[-6px] font-light">{{ __('Belum Dikoreksi') }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- SKD Full Subtest Summary (if multiple subtests) -->
    @if(!empty($subtestSummaries))
    <div class="bg-white px-4 py-6 rounded-lg border border-border select-none pembahasan-protected" oncontextmenu="return false;">
        <h3 class="text-lg font-bold mb-4 text-gray-800">{{ __('Ringkasan Per Subtest') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($subtestSummaries as $summary)
            <div
                class="p-4 border rounded-lg {{ $summary['is_passed'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
                <div class="text-center mb-3">
                    <h4 class="font-semibold text-gray-800">{{ strtoupper($summary['type']) }}
                    </h4>
                    <p class="text-sm text-gray-600">{{ $summary['name'] }}</p>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold {{ $summary['is_passed'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($summary['score'], 0) }}/{{ number_format($summary['max_score'], 0) }}
                    </div>
                    <div class="text-sm {{ $summary['is_passed'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($summary['percentage'], 1) }}% - {{ $summary['is_passed'] ? __('LULUS') : __('TIDAK LULUS') }}
                    </div>
                </div>
                <div class="mt-2 text-xs text-gray-600 text-center">
                    {{ $summary['correct_answers'] }} {{ __('benar') }}, {{ $summary['wrong_answers'] }} {{ __('salah') }}
                </div>
                <div class="mt-1 text-xs text-gray-500 text-center">
                    {{ __('Passing grade') }}:
                    @if(($summary['passing_type'] ?? 'score') === 'percentage')
                        {{ number_format($summary['passing_score'] ?? 0, 1) }}%
                    @else
                        {{ $summary['passing_score'] ?? '-' }}
                        @if(!is_null($summary['passing_percentage'] ?? null))
                            ({{ number_format($summary['passing_percentage'], 1) }}%)
                        @endif
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="bg-white px-4 py-10 rounded-lg border border-border flex flex-col gap-8 text-dark select-none pembahasan-protected" oncontextmenu="return false;">
        @php $currentSubtest = null; @endphp
        @foreach($allAnswerDetails as $index => $detail)
        @php
        $question = $detail->question;
        $correctOption = $question->questionOptions->where('is_correct', true)->first();
        $selectedOption = $detail->questionOption;
        $isCorrect = $detail->is_correct;
        $answerMeta = is_array($detail->answer_json) ? $detail->answer_json : [];
        $isPendingReview = !empty($answerMeta['pending_review']);
        @endphp

        {{-- Subtest Header --}}
        @if($currentSubtest !== $detail->subtest_type)
        @php $currentSubtest = $detail->subtest_type; @endphp
        <div class="border-t-2 border-primary pt-6 -mt-2">
            <div class="flex items-center gap-3 mb-4">
                <div
                    class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-bold text-sm">
                    {{ strtoupper($detail->subtest_type) }}
                </div>
                <h3 class="text-xl font-bold text-primary">{{ $detail->subtest_name }}</h3>
            </div>
        </div>
        @endif

        <div
            class="card-pembahasan w-full border border-dashed p-4 rounded-lg {{ $isCorrect ? 'border-green bg-green-light/30' : 'border-red bg-red-light/30' }}">
            <div class="flex flex-wrap items-center justify-start gap-2 md:gap-4">
                <p class="font-semibold">{{ __('Soal') }} {{ $index + 1 }}</p>
                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                    {{ strtoupper($detail->subtest_type) }}
                </span>
                <span
                    class="flex items-center gap-1 border px-3 py-1 rounded-lg {{ $isPendingReview ? 'bg-amber-100 text-amber-700 border-amber-200' : ($isCorrect ? 'bg-green text-white' : 'bg-red text-white') }}">
                    <i class="ri-checkbox-circle-fill"></i>
                    <p class="text-sm">{{ $isPendingReview ? __('Belum Dikoreksi') : ($isCorrect ? __('Benar') : __('Salah')) }}</p>
                </span>
                @php
                // Calculate score earned for this question
                $scoreEarned = 0;
                if($selectedOption) {
                switch($detail->subtest_type) {
                case 'twk':
                case 'tiu':
                $scoreEarned = $isCorrect ? 5 : 0;
                break;
                case 'tkp':
                $scoreEarned = $selectedOption->weight ?? 0;
                break;
                default:
                $scoreEarned = $selectedOption->weight ?? ($isCorrect ? 1 : 0);
                break;
                }
                }
                @endphp
                @if($scoreEarned > 0)
                <span
                    class="flex items-center gap-1 border border-primary bg-primary/10 text-primary px-3 py-1 rounded-lg flex-shrink-0">
                    <i class="ri-star-fill text-sm"></i>
                    <p class="text-sm">+{{ $scoreEarned }} {{ __('poin') }}</p>
                </span>
                @endif
            </div>

            <div class="question-rich-text mt-2 font-light">
                {!! $question->question_text !!}
            </div>

            @if($question->sound)
            <div class="mt-4">
                <audio controls class="w-full">
                    <source src="{{ Storage::url($question->sound) }}" type="audio/mpeg">
                    {{ __('Browser Anda tidak mendukung audio.') }}
                </audio>
            </div>
            @endif

            @if(in_array($question->question_type ?? '', ['short_answer', 'essay']))
            <div class="mt-4 p-3 bg-white border border-gray-200 rounded-lg">
                <p class="font-semibold text-gray-800 mb-1">{{ __('Jawaban Peserta') }}:</p>
                <p class="text-gray-700">{!! nl2br(e($detail->answer_text ?? '')) ?: '-' !!}</p>
                @if($detail->answer_json['pending_review'] ?? false)
                <p class="text-xs text-gray-500 mt-2">{{ __('Belum dikoreksi.') }}</p>
                @endif
            </div>
            @else
            <div class="flex flex-col gap-2 mt-4 w-full">
                @foreach($question->questionOptions as $option)
                @php
                $isSelected = $detail->question_option_id === $option->question_option_id;
                $isCorrectOption = $option->is_correct;
                $optionKey = $option->option_key ?? chr(65 + $loop->index);
                @endphp

                @if($isCorrectOption)
                <!-- Correct answer - always GREEN -->
                <div
                    class="flex w-full items-start gap-2 font-light border px-4 py-2 rounded-lg transition-colors bg-green text-white border-green">
                    <input type="radio" disabled class="mr-1 mt-1 flex-shrink-0" {{ $isSelected ? 'checked' : '' }}>
                    <span class="font-medium flex-shrink-0">{{ $optionKey }}.</span>
                    <p class="flex-1">{!! $option->option_text !!}</p>
                    <i class="ri-check-line text-lg flex-shrink-0"></i>
                    @if($detail->subtest_type === 'tkp')
                    <span class="text-xs bg-white/20 px-2 py-1 rounded flex-shrink-0">{{ __('Bobot') }}: {{ $option->weight }}</span>
                    @endif
                </div>
                @elseif($isSelected && !$isCorrect)
                <!-- User's wrong answer - RED -->
                <div
                    class="flex w-full items-start gap-2 font-light border px-4 py-2 rounded-lg transition-colors bg-red text-white border-red">
                    <input type="radio" disabled class="mr-1 mt-1 flex-shrink-0" checked>
                    <span class="font-medium flex-shrink-0">{{ $optionKey }}.</span>
                    <p class="flex-1">{!! $option->option_text !!}</p>
                    <i class="ri-close-line text-lg flex-shrink-0"></i>
                    @if($detail->subtest_type === 'tkp')
                    <span class="text-xs bg-white/20 px-2 py-1 rounded flex-shrink-0">{{ __('Bobot') }}: {{ $option->weight }}</span>
                    @endif
                </div>
                @else
                <!-- All other options - NEUTRAL -->
                <div
                    class="flex w-full items-start gap-2 font-light border px-4 py-2 rounded-lg transition-colors border-gray-900/10 hover:bg-gray-50">
                    <input type="radio" disabled class="mr-1 mt-1 flex-shrink-0" {{ $isSelected ? 'checked' : '' }}>
                    <span class="font-medium flex-shrink-0">{{ $optionKey }}.</span>
                    <p class="flex-1">{!! $option->option_text !!}</p>
                    @if($detail->subtest_type === 'tkp')
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded flex-shrink-0">{{ __('Bobot') }}: {{ $option->weight
                        }}</span>
                    @endif
                </div>
                @endif
                @endforeach
            </div>

            @if(!$isCorrect && $correctOption && in_array($detail->subtest_type, ['twk', 'tiu']))
            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="font-semibold text-green-800 mb-1">{{ __('Jawaban Yang Benar') }}:</p>
                <p class="text-green-700">{{ $correctOption->option_key ?? 'A' }}. {!! $correctOption->option_text !!}</p>
            </div>
            @endif
            @endif

            @if($detail->subtest_type === 'tkp')
            <div class="mt-4 p-3 bg-primary/10 border border-primary/50 rounded-lg">
                <p class="font-semibold text-primary mb-1">{{ __('Info TKP') }}:</p>
                <p class="text-primary text-sm">{{ __('Untuk TKP, setiap pilihan memiliki bobot nilai. Pilih jawaban yang paling mencerminkan karakter positif.') }}</p>
            </div>
            @endif

            @if($question->explanation)
            <div class="mt-4">
                <p class="font-semibold text-gray-800 mb-2">{{ __('Pembahasan') }}</p>
                <div class="font-light text-gray-700 bg-gray-50 p-3 rounded-lg">
                    {{-- {!! nl2br(e($question->explanation)) !!} --}}
                    {!! $question->explanation !!}
                </div>
            </div>
            @endif
        </div>
        @endforeach

        @if($allAnswerDetails->isEmpty())
        <div class="text-center py-8">
            <i class="ri-file-list-line text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-500">{{ __('Tidak ada data jawaban ditemukan.') }}</p>
        </div>
        @endif
    </div>

    <!-- Summary Statistics -->
    <div class="bg-white px-4 py-6 rounded-lg border border-border select-none pembahasan-protected" oncontextmenu="return false;">
        @php
            $summaryNames = collect($subtestSummaries ?? [])->pluck('name')->filter()->unique()->values();
            $summaryTitle = $summaryNames->isNotEmpty()
                ? __('Ringkasan Hasil') . ' ' . $summaryNames->implode(' - ')
                : __('Ringkasan Hasil') . ' ' . ($tryout->title ?? $tryout->name ?? __('Tryout'));
        @endphp
        <h3 class="text-lg font-bold mb-4 text-gray-800">{{ $summaryTitle }}</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold text-gray-700 mb-3">{{ __('Detail Skor') }}</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ __('Total Skor') }}:</span>
                        <span class="font-semibold">{{ number_format($overallStats['total_score'], 0) }}/{{
                            number_format($overallStats['max_score'], 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ __('Persentase') }}:</span>
                        <span class="font-semibold {{ $overallStats['is_passed'] ? 'text-green' : 'text-red' }}">
                            {{ number_format($overallStats['percentage'], 1) }}%
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ __('Status') }}:</span>
                        <span class="font-semibold {{ $overallStats['is_passed'] ? 'text-green' : 'text-red' }}">
                            {{ $overallStats['is_passed'] ? __('LULUS') : __('TIDAK LULUS') }}
                        </span>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="font-semibold text-dark mb-3">{{ __('Statistik Jawaban') }}</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="">{{ __('Benar') }}:</span>
                        <span class="font-semibold">{{ $overallStats['correct_answers'] }} {{ __('soal') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="">{{ __('Salah') }}:</span>
                        <span class="font-semibold">{{ $overallStats['wrong_answers'] }} {{ __('soal') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="">{{ __('Tidak Dijawab') }}:</span>
                        <span class="font-semibold">{{ $overallStats['unanswered'] }} {{ __('soal') }}</span>
                    </div>
                    @if(!empty($overallStats['pending_review']))
                    <div class="flex justify-between">
                        <span class="">{{ __('Belum Dikoreksi') }}:</span>
                        <span class="font-semibold">{{ $overallStats['pending_review'] }} {{ __('soal') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-6">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>{{ __('Progress Pengerjaan') }}</span>
                <span>{{ $overallStats['total_questions'] - $overallStats['unanswered'] }}/{{
                    $overallStats['total_questions'] }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-green h-3 rounded-full transition-all duration-500"
                    style="width: {{ $overallStats['total_questions'] > 0 ? (($overallStats['total_questions'] - $overallStats['unanswered']) / $overallStats['total_questions']) * 100 : 100 }}%">
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="{{ $backUrl }}"
            class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-center">
            <i class="ri-arrow-left-line mr-2"></i>{{ __('Kembali ke Tryout') }}
        </a>

        <a href="{{ route('user.package.tryout.riwayat', [$packageId, $tryout->tryout_id]) }}"
            class="px-6 py-3 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors text-center">
            <i class="ri-history-line mr-2"></i>{{ __('Lihat Riwayat') }}
        </a>

        <a href="{{ route('user.package.tryout.ranking', [$packageId, $tryout->tryout_id]) }}"
            class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-center">
            <i class="ri-trophy-line mr-2"></i>{{ __('Lihat Ranking') }}
        </a>

        @if($clientBranding['certificate_management_enabled'] ?? true)
        <a href="{{ route('user.certificate.preview', [$packageId, $tryout->tryout_id, 'token' => $token]) }}"
            class="px-6 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors text-center">
            <i class="ri-award-line mr-2"></i>{{ __('Preview Sertifikat') }}
        </a>
        @endif

        <a href="{{ route('user.tryout.lobby', [$packageId, $tryout->tryout_id]) }}"
            class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-center">
            <i class="ri-refresh-line mr-2"></i>{{ __('Coba Lagi') }}
        </a>
    </div>
</div>

@endsection

@section('scripts')
<script>
    console.log('Pembahasan SKD Full loaded');

    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-anticopy="pembahasan"]') || document.body;
        const protectedBlocks = root.querySelectorAll('.pembahasan-protected');
        const blockHandler = (event) => {
            event.preventDefault();
        };

        ['copy', 'cut', 'paste', 'contextmenu', 'selectstart', 'dragstart'].forEach((eventName) => {
            protectedBlocks.forEach((block) => {
                block.addEventListener(eventName, blockHandler, true);
            });
        });

        const isSelectionInsideProtected = () => {
            const selection = window.getSelection();
            if (!selection || selection.rangeCount === 0) {
                return false;
            }

            const anchorParent = selection.anchorNode?.parentElement;
            const focusParent = selection.focusNode?.parentElement;

            return !!(anchorParent?.closest('.pembahasan-protected') || focusParent?.closest('.pembahasan-protected'));
        };

        document.addEventListener('keydown', (event) => {
            if (!isSelectionInsideProtected()) {
                return;
            }

            if ((event.ctrlKey || event.metaKey) && ['c', 'x', 'v', 'a'].includes(event.key.toLowerCase())) {
                event.preventDefault();
            }
        }, true);
    });
</script>
@endsection

@section('styles')
<style>
    /* Custom styles for pembahasan */
    .card-pembahasan {
        transition: all 0.3s ease;
    }

    .card-pembahasan:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    /* Color definitions */
    .bg-green {
        background-color: #059669;
    }

    .text-green {
        color: #059669;
    }

    .border-green {
        border-color: #059669;
    }

    .bg-green-light {
        background-color: #d1fae5;
    }

    .text-red {
        color: #dc2626;
    }

    .bg-red {
        background-color: #dc2626;
    }

    .border-red {
        border-color: #dc2626;
    }

    .bg-red-light {
        background-color: #fee2e2;
    }

    /* Animation for progress bar */
    @keyframes progressFill {
        0% {
            width: 0%;
        }

        100% {
            width: var(--progress-width);
        }
    }

    .progress-bar {
        animation: progressFill 1.5s ease-out;
    }
</style>
@endsection
