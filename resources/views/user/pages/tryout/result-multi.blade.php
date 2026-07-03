@extends('user.layout.user')
@section('title', __('Hasil') . ' ' . $tryout->name)
@section('content')

<div class="package-bimbel bg-white p-4 rounded-lg border border-border">
    <div class="text-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ __('Hasil') }} {{ $tryout->name }}</h1>
        <p class="text-gray-600">{{ $tryout->description }}</p>
    </div>

    <!-- Overall Score Card -->
    <div class="bg-gradient-to-r from-primary to-blue-600 text-white rounded-lg p-6 mb-6">
        <div class="text-center">
            <h2 class="text-2xl font-bold mb-2">{{ __('Skor Total') }}</h2>
            <div class="text-5xl font-bold mb-2">{{ number_format($overallPercentage, 1) }}%</div>
            <p class="text-lg">{{ __(':score dari :max poin', ['score' => $rawScore, 'max' => $maxScore]) }}</p>

            @if($tryout->type_tryout === 'computer')
            @if($overallPercentage >= 70)
            <div class="mt-4 inline-block bg-green-500 text-white px-4 py-2 rounded-full">
                <i class="ri-check-double-line mr-2"></i>{{ __('Kompeten') }}
            </div>
            @else
            <div class="mt-4 inline-block bg-red-500 text-white px-4 py-2 rounded-full">
                <i class="ri-close-line mr-2"></i>{{ __('Belum Kompeten') }}
            </div>
            @endif
            @elseif($tryout->type_tryout === 'pppk_full')
            @if($overallPercentage >= 65)
            <div class="mt-4 inline-block bg-green-500 text-white px-4 py-2 rounded-full">
                <i class="ri-check-double-line mr-2"></i>{{ __('Lulus') }}
            </div>
            @else
            <div class="mt-4 inline-block bg-red-500 text-white px-4 py-2 rounded-full">
                <i class="ri-close-line mr-2"></i>{{ __('Tidak Lulus') }}
            </div>
            @endif
            @endif
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $totalQuestions }}</div>
            <div class="text-sm text-blue-600">{{ __('Total Soal') }}</div>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $correctAnswers }}</div>
            <div class="text-sm text-green-600">{{ __('Benar') }}</div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-red-600">{{ $wrongAnswers }}</div>
            <div class="text-sm text-red-600">{{ __('Salah') }}</div>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-600">{{ $unansweredCount ?? 0 }}</div>
            <div class="text-sm text-gray-600">{{ __('Tidak Dijawab') }}</div>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-amber-600">{{ $pendingReviewCount ?? 0 }}</div>
            <div class="text-sm text-amber-600">{{ __('Belum Dikoreksi') }}</div>
        </div>
    </div>

    <!-- Subtest Results -->
    <div class="mb-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">{{ __('Hasil Per Subtest') }}</h3>
        <div class="space-y-4">
            @foreach($subtestResults as $subtest)
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-lg font-medium text-gray-800">{{ $subtest['name'] }}</h4>
                    <div class="flex items-center space-x-2">
                        <span class="text-2xl font-bold
                            @if($subtest['is_passed']) text-green-600
                            @else text-red-600 @endif">
                            {{ number_format($subtest['percentage'], 1) }}%
                        </span>
                        @if($subtest['is_passed'])
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                            <i class="ri-check-line mr-1"></i>{{ __('Lulus') }}
                        </span>
                        @else
                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">
                            <i class="ri-close-line mr-1"></i>{{ __('Tidak Lulus') }}
                        </span>
                        @endif
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 rounded-full h-3 mb-3">
                    <div class="h-3 rounded-full transition-all duration-500
                        @if($subtest['is_passed']) bg-green-500
                        @else bg-red-500 @endif" style="width: {{ min($subtest['percentage'], 100) }}%">
                    </div>
                </div>

                @if(!is_null($subtest['passing_score']))
                <div class="text-xs text-gray-500 mb-3">
                    {{ __('Passing Grade:') }}
                    @if(($subtest['passing_type'] ?? 'score') === 'percentage')
                        {{ number_format($subtest['passing_score'], 1) }}%
                    @else
                        {{ $subtest['passing_score'] }}
                        @if(!is_null($subtest['passing_percentage']))
                            ({{ number_format($subtest['passing_percentage'], 1) }}%)
                        @endif
                    @endif
                </div>
                @endif

                <!-- Subtest Statistics -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
                    <div class="text-center">
                        <div class="font-medium text-gray-600">{{ $subtest['total_questions'] }}</div>
                        <div class="text-gray-500">{{ __('Total Soal') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="font-medium text-green-600">{{ $subtest['correct_answers'] }}</div>
                        <div class="text-gray-500">{{ __('Benar') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="font-medium text-red-600">{{ $subtest['wrong_answers'] }}</div>
                        <div class="text-gray-500">{{ __('Salah') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="font-medium text-gray-600">{{ $subtest['unanswered'] }}</div>
                        <div class="text-gray-500">{{ __('Kosong') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="font-medium text-blue-600">{{ $subtest['raw_score'] }}/{{ $subtest['max_score'] }}
                        </div>
                        <div class="text-gray-500">{{ __('Skor') }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Recommendations -->
    @if($tryout->type_tryout === 'computer')
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h4 class="font-medium text-blue-800 mb-2">
            <i class="ri-lightbulb-line mr-2"></i>{{ __('Rekomendasi Peningkatan') }}
        </h4>
        <ul class="text-sm text-blue-700 space-y-1">
            @foreach($subtestResults as $subtest)
            @if($subtest['percentage'] < 70) <li>• {{ __('Tingkatkan kemampuan :name - skor saat ini :score%', ['name' => $subtest['name'], 'score' => number_format($subtest['percentage'], 1)]) }}</li>
            @endif
            @endforeach
            @if($overallPercentage >= 70)
                <li>• {{ __('Pertahankan kemampuan yang sudah baik dan terus berlatih') }}</li>
                @endif
        </ul>
    </div>
    @elseif($tryout->type_tryout === 'pppk_full')
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <h4 class="font-medium text-yellow-800 mb-2">
            <i class="ri-information-line mr-2"></i>{{ __('Analisis Hasil PPPK') }}
        </h4>
        <ul class="text-sm text-yellow-700 space-y-1">
            @foreach($subtestResults as $subtest)
            @if($subtest['is_passed'])
            <li>• {{ $subtest['name'] }}: <strong>{{ __('Lulus') }}</strong> ({{ number_format($subtest['percentage'], 1) }}%)</li>
            @else
            <li>• {{ $subtest['name'] }}: <strong>{{ __('Perlu Perbaikan') }}</strong> ({{ number_format($subtest['percentage'], 1)
                }}%)</li>
            @endif
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-3">
        @if($package)
        <a href="{{ route('user.package.tryout.pembahasan', [$package->package_id, $tryout->tryout_id, $latestAttemptToken]) }}"
            class="flex-1 bg-green-600 text-white text-center py-3 px-4 rounded-lg hover:bg-green-700 transition-colors">
            <i class="ri-book-open-line mr-2"></i>{{ __('Lihat Pembahasan') }}
        </a>
        <a href="{{ route('user.package.tryout.ranking', [$package->package_id, $tryout->tryout_id]) }}"
            class="flex-1 bg-yellow-600 text-white text-center py-3 px-4 rounded-lg hover:bg-yellow-700 transition-colors">
            <i class="ri-trophy-line mr-2"></i>{{ __('Lihat Ranking') }}
        </a>
        <a href="{{ route('user.package.tryout', $package->package_id) }}"
            class="flex-1 bg-gray-600 text-white text-center py-3 px-4 rounded-lg hover:bg-gray-700 transition-colors">
            <i class="ri-arrow-left-line mr-2"></i>{{ __('Kembali ke Tryout') }}
        </a>
        @else
        <a href="{{ route('user.package.index') }}"
            class="flex-1 bg-gray-600 text-white text-center py-3 px-4 rounded-lg hover:bg-gray-700 transition-colors">
            <i class="ri-arrow-left-line mr-2"></i>{{ __('Kembali ke Tryout') }}
        </a>
        @endif
    </div>
</div>

@endsection

@section('styles')
<style>
    .progress-animation {
        animation: progressFill 1.5s ease-in-out;
    }

    @keyframes progressFill {
        from {
            width: 0%;
        }
    }

    .score-animation {
        animation: scoreCount 2s ease-in-out;
    }

    @keyframes scoreCount {
        from {
            transform: scale(0.8);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Add animation classes
    const progressBars = document.querySelectorAll('.h-3.rounded-full');
    const scoreElements = document.querySelectorAll('.text-5xl, .text-2xl');

    progressBars.forEach(bar => {
        bar.classList.add('progress-animation');
    });

    scoreElements.forEach(element => {
        element.classList.add('score-animation');
    });
});
</script>
@endsection
