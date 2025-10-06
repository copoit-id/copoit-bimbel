@extends('user.layout.user')
@section('title', 'Pembahasan Tryout')
@section('content')
<div class="package-bimbel flex flex-col gap-4">
    <div class="bg-white px-4 py-10 rounded-lg border border-border flex flex-col md:flex-row gap-4 text-dark">
        <div class="flex order-2 md:order-1 flex-col items-center gap-4 w-full">
            <p class="font-semibold">Pembahasan - {{ $tryout->name }}</p>
            <p class="text-5xl font-medium">{{ number_format($overallStats['total_score'], 0) }}</p>
            <span
                class="flex items-center gap-1 border px-6 py-0.5 rounded-lg {{ $overallStats['is_passed'] ? 'border-green bg-green-light text-green' : 'border-red bg-red-light text-red' }}">
                <i class="ri-checkbox-circle-fill text-lg"></i>
                <span>{{ $overallStats['is_passed'] ? 'Lulus' : 'Tidak Lulus' }}</span>
            </span>
            @if(isset($tryoutDetails) && $tryoutDetails->count() > 1)
            <div class="mt-2">
                <span class="inline-flex px-3 py-1 bg-primary/10 text-primary text-sm font-medium rounded-full">
                    SKD Full - {{ $tryoutDetails->count() }} Subtest
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
                    <p class="text-[12px] mt-[-6px] font-light">Total Soal</p>
                </div>
            </div>
            <div class="flex w-full items-center gap-3 bg-white p-4 rounded-lg border border-border">
                <i
                    class="ri-check-line text-[20px] flex items-center justify-center text-white font-medium bg-green w-10 h-10 rounded-lg"></i>
                <div>
                    <p class="text-[24px] font-bold">{{ $overallStats['correct_answers'] }}</p>
                    <p class="text-[12px] mt-[-6px] font-light">Jawaban Benar</p>
                </div>
            </div>
            <div class="flex w-full items-center gap-3 bg-white p-4 rounded-lg border border-border">
                <i
                    class="ri-close-line text-[20px] flex items-center justify-center text-white font-medium bg-red w-10 h-10 rounded-lg"></i>
                <div>
                    <p class="text-[24px] font-bold">{{ $overallStats['wrong_answers'] }}</p>
                    <p class="text-[12px] mt-[-6px] font-light">Jawaban Salah</p>
                </div>
            </div>
            <div class="flex w-full items-center gap-3 bg-white p-4 rounded-lg border border-border">
                <i
                    class="ri-question-mark-line text-[20px] flex items-center justify-center text-white font-medium bg-gray-500 w-10 h-10 rounded-lg"></i>
                <div>
                    <p class="text-[24px] font-bold">{{ $overallStats['unanswered'] }}</p>
                    <p class="text-[12px] mt-[-6px] font-light">Tidak Dijawab</p>
                </div>
            </div>
        </div>
    </div>

    <!-- SKD Full Subtest Summary (if multiple subtests) -->
    @if(isset($tryoutDetails) && $tryoutDetails->count() > 1)
    <div class="bg-white px-4 py-6 rounded-lg border border-border">
        <h3 class="text-lg font-bold mb-4 text-gray-800">Ringkasan Per Subtest</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($latestUserAnswers as $userAnswer)
            @php
            $subtestScore = 0;
            foreach($userAnswer->userAnswerDetails as $detail) {
            if($detail->questionOption) {
            switch($userAnswer->tryoutDetail->type_subtest) {
            case 'twk':
            case 'tiu':
            $subtestScore += $detail->is_correct ? 5 : 0;
            break;
            case 'tkp':
            $subtestScore += $detail->questionOption->weight;
            break;
            default:
            $subtestScore += $detail->questionOption->weight ?? ($detail->is_correct ? 1 : 0);
            break;
            }
            }
            }
            $maxSubtestScore = 0;
            $questionCount = \App\Models\Question::where('tryout_detail_id', $userAnswer->tryout_detail_id)->count();
            switch($userAnswer->tryoutDetail->type_subtest) {
            case 'twk':
            case 'tiu':
            $maxSubtestScore = $questionCount * 5;
            break;
            case 'tkp':
            $maxSubtestScore = $questionCount * 5;
            break;
            default:
            $maxSubtestScore = $questionCount;
            break;
            }
            $subtestPercentage = $maxSubtestScore > 0 ? ($subtestScore / $maxSubtestScore) * 100 : 0;
            $subtestPassed = $subtestPercentage >= 60;

            // Get subtest name using switch statement
            $subtestName = '';
            switch ($userAnswer->tryoutDetail->type_subtest) {
            case 'twk':
            $subtestName = 'Tes Wawasan Kebangsaan';
            break;
            case 'tiu':
            $subtestName = 'Tes Intelegensi Umum';
            break;
            case 'tkp':
            $subtestName = 'Tes Karakteristik Pribadi';
            break;
            default:
            $subtestName = ucfirst($userAnswer->tryoutDetail->type_subtest);
            break;
            }
            @endphp
            <div
                class="p-4 border rounded-lg {{ $subtestPassed ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
                <div class="text-center mb-3">
                    <h4 class="font-semibold text-gray-800">{{ strtoupper($userAnswer->tryoutDetail->type_subtest) }}
                    </h4>
                    <p class="text-sm text-gray-600">{{ $subtestName }}</p>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold {{ $subtestPassed ? 'text-green-600' : 'text-red-600' }}">
                        {{ $subtestScore }}/{{ $maxSubtestScore }}
                    </div>
                    <div class="text-sm {{ $subtestPassed ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($subtestPercentage, 1) }}% - {{ $subtestPassed ? 'LULUS' : 'TIDAK LULUS' }}
                    </div>
                </div>
                <div class="mt-2 text-xs text-gray-600 text-center">
                    {{ $userAnswer->correct_answers ?? 0 }} benar, {{ $userAnswer->wrong_answers ?? 0 }} salah
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="bg-white px-4 py-10 rounded-lg border border-border flex flex-col gap-8 text-dark">
        @php $currentSubtest = null; @endphp
        @foreach($allAnswerDetails as $index => $detail)
        @php
        $question = $detail->question;
        $correctOption = $question->questionOptions->where('is_correct', true)->first();
        $selectedOption = $detail->questionOption;
        $isCorrect = $detail->is_correct;
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
            <div class="flex items-center justify-start gap-4">
                <p class="font-semibold">Soal {{ $index + 1 }}</p>
                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                    {{ strtoupper($detail->subtest_type) }}
                </span>
                <span
                    class="flex items-center gap-1 border px-4 py-1 rounded-lg {{ $isCorrect ? 'bg-green text-white' : 'bg-red text-white' }}">
                    <i class="ri-checkbox-circle-fill"></i>
                    <p class="text-sm">{{ $isCorrect ? 'Benar' : 'Salah' }}</p>
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
                    class="flex items-center gap-1 border border-primary bg-primary/10 text-primary px-3 py-1 rounded-lg">
                    <i class="ri-star-fill text-sm"></i>
                    <p class="text-sm">+{{ $scoreEarned }} poin</p>
                </span>
                @endif
            </div>

            <div class="mt-2 font-light">
                {!! $question->question_text !!}
            </div>

            @if($question->sound)
            <div class="mt-4">
                <audio controls class="w-full">
                    <source src="{{ Storage::url($question->sound) }}" type="audio/mpeg">
                    Browser Anda tidak mendukung audio.
                </audio>
            </div>
            @endif

            <div class="flex flex-col gap-2 mt-4 w-full">
                @foreach($question->questionOptions as $option)
                @php
                $isSelected = $detail->question_option_id === $option->question_option_id;
                $isCorrectOption = $option->is_correct;
                @endphp

                @if($isCorrectOption)
                <!-- Correct answer - always GREEN -->
                <div
                    class="flex w-full items-center gap-1 font-light border px-4 py-2 rounded-lg transition-colors bg-green text-white border-green">
                    <input type="radio" disabled class="mr-2" {{ $isSelected ? 'checked' : '' }}>
                    <span class="font-medium mr-2">{{ $option->option_key }}.</span>
                    <p>{!! $option->option_text !!}</p>
                    <i class="ri-check-line text-lg"></i>
                    @if($detail->subtest_type === 'tkp')
                    <span class="text-xs bg-white/20 px-2 py-1 rounded">Bobot: {{ $option->weight }}</span>
                    @endif
                </div>
                @elseif($isSelected && !$isCorrect)
                <!-- User's wrong answer - RED -->
                <div
                    class="flex w-full items-center gap-1 font-light border px-4 py-2 rounded-lg transition-colors bg-red text-white border-red">
                    <input type="radio" disabled class="mr-2" checked>
                    <span class="font-medium mr-2">{{ $option->option_key }}.</span>
                    <p>{!! $option->option_text !!}</p>
                    <i class="ri-close-line text-lg"></i>
                    @if($detail->subtest_type === 'tkp')
                    <span class="text-xs bg-white/20 px-2 py-1 rounded">Bobot: {{ $option->weight }}</span>
                    @endif
                </div>
                @else
                <!-- All other options - NEUTRAL -->
                <div
                    class="flex w-full items-center gap-1 font-light border px-4 py-2 rounded-lg transition-colors border-gray-900/10 hover:bg-gray-50">
                    <input type="radio" disabled class="mr-2" {{ $isSelected ? 'checked' : '' }}>
                    <span class="font-medium mr-2">{{ $option->option_key }}.</span>
                    <p>{!! $option->option_text !!}</p>
                    @if($detail->subtest_type === 'tkp')
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">Bobot: {{ $option->weight
                        }}</span>
                    @endif
                </div>
                @endif
                @endforeach
            </div>

            @if(!$isCorrect && $correctOption && in_array($detail->subtest_type, ['twk', 'tiu']))
            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="font-semibold text-green-800 mb-1">Jawaban Yang Benar:</p>
                <p class="text-green-700">{{ $correctOption->option_key }}. {!! $correctOption->option_text !!}</p>
            </div>
            @endif

            @if($detail->subtest_type === 'tkp')
            <div class="mt-4 p-3 bg-primary/10 border border-primary/50 rounded-lg">
                <p class="font-semibold text-primary mb-1">Info TKP:</p>
                <p class="text-primary text-sm">Untuk TKP, setiap pilihan memiliki bobot nilai. Pilih jawaban yang
                    paling mencerminkan karakter positif.</p>
            </div>
            @endif

            @if($question->explanation)
            <div class="mt-4">
                <p class="font-semibold text-gray-800 mb-2"># Pembahasan</p>
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
            <p class="text-gray-500">Tidak ada data jawaban ditemukan.</p>
        </div>
        @endif
    </div>

    <!-- Summary Statistics -->
    <div class="bg-white px-4 py-6 rounded-lg border border-border">
        <h3 class="text-lg font-bold mb-4 text-gray-800">Ringkasan Hasil SKD Full</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold text-gray-700 mb-3">Detail Skor</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Skor:</span>
                        <span class="font-semibold">{{ number_format($overallStats['total_score'], 0) }}/{{
                            number_format($overallStats['max_score'], 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Persentase:</span>
                        <span class="font-semibold {{ $overallStats['is_passed'] ? 'text-green' : 'text-red' }}">
                            {{ number_format($overallStats['percentage'], 1) }}%
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-semibold {{ $overallStats['is_passed'] ? 'text-green' : 'text-red' }}">
                            {{ $overallStats['is_passed'] ? 'LULUS' : 'TIDAK LULUS' }}
                        </span>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="font-semibold text-dark mb-3">Statistik Jawaban</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="">Benar:</span>
                        <span class="font-semibold">{{ $overallStats['correct_answers'] }} soal</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="">Salah:</span>
                        <span class="font-semibold">{{ $overallStats['wrong_answers'] }} soal</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="">Tidak Dijawab:</span>
                        <span class="font-semibold">{{ $overallStats['unanswered'] }} soal</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-6">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>Progress Pengerjaan</span>
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
        <a href="{{ route('user.package.tryout', $package->package_id) }}"
            class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-center">
            <i class="ri-arrow-left-line mr-2"></i>Kembali ke Tryout
        </a>

        <a href="{{ route('user.package.tryout.riwayat', [$package->package_id, $tryout->tryout_id]) }}"
            class="px-6 py-3 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors text-center">
            <i class="ri-history-line mr-2"></i>Lihat Riwayat
        </a>

        <a href="{{ route('user.package.tryout.ranking', [$package->package_id, $tryout->tryout_id]) }}"
            class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-center">
            <i class="ri-trophy-line mr-2"></i>Lihat Ranking
        </a>

        <!-- Certificate Preview Button (only for certification tryouts) -->
        <a href="{{ route('user.certificate.preview', [$package->package_id, $tryout->tryout_id, 'token' => $token]) }}"
            class="px-6 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors text-center">
            <i class="ri-award-line mr-2"></i>Preview Sertifikat
        </a>

        <a href="{{ route('user.tryout.lobby', [$package->package_id, $tryout->tryout_id]) }}"
            class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-center">
            <i class="ri-refresh-line mr-2"></i>Coba Lagi
        </a>
    </div>
</div>

@endsection

@section('scripts')
<script>
    console.log('Pembahasan SKD Full loaded');
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