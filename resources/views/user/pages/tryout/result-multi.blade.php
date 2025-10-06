@extends('user.layout.user')
@section('title', 'Hasil ' . $tryout->name)
@section('content')

<div class="package-bimbel bg-white p-4 rounded-lg border border-border">
    <div class="text-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Hasil {{ $tryout->name }}</h1>
        <p class="text-gray-600">{{ $tryout->description }}</p>
    </div>

    <!-- Overall Score Card -->
    <div class="bg-gradient-to-r from-primary to-blue-600 text-white rounded-lg p-6 mb-6">
        <div class="text-center">
            <h2 class="text-2xl font-bold mb-2">Skor Total</h2>
            <div class="text-5xl font-bold mb-2">{{ number_format($overallPercentage, 1) }}%</div>
            <p class="text-lg">{{ $rawScore }} dari {{ $maxScore }} poin</p>

            @if($tryout->type_tryout === 'computer')
            @if($overallPercentage >= 70)
            <div class="mt-4 inline-block bg-green-500 text-white px-4 py-2 rounded-full">
                <i class="ri-check-double-line mr-2"></i>Kompeten
            </div>
            @else
            <div class="mt-4 inline-block bg-red-500 text-white px-4 py-2 rounded-full">
                <i class="ri-close-line mr-2"></i>Belum Kompeten
            </div>
            @endif
            @elseif($tryout->type_tryout === 'pppk_full')
            @if($overallPercentage >= 65)
            <div class="mt-4 inline-block bg-green-500 text-white px-4 py-2 rounded-full">
                <i class="ri-check-double-line mr-2"></i>Lulus
            </div>
            @else
            <div class="mt-4 inline-block bg-red-500 text-white px-4 py-2 rounded-full">
                <i class="ri-close-line mr-2"></i>Tidak Lulus
            </div>
            @endif
            @endif
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $totalQuestions }}</div>
            <div class="text-sm text-blue-600">Total Soal</div>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $correctAnswers }}</div>
            <div class="text-sm text-green-600">Benar</div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-red-600">{{ $wrongAnswers }}</div>
            <div class="text-sm text-red-600">Salah</div>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-600">{{ $totalQuestions - $correctAnswers - $wrongAnswers }}</div>
            <div class="text-sm text-gray-600">Tidak Dijawab</div>
        </div>
    </div>

    <!-- Subtest Results -->
    <div class="mb-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Hasil Per Subtest</h3>
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
                            <i class="ri-check-line mr-1"></i>Lulus
                        </span>
                        @else
                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">
                            <i class="ri-close-line mr-1"></i>Tidak Lulus
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
                    Passing Grade: {{ $subtest['passing_score'] }}
                    @if(!is_null($subtest['passing_percentage']))
                        ({{ number_format($subtest['passing_percentage'], 1) }}%)
                    @endif
                </div>
                @endif

                <!-- Subtest Statistics -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
                    <div class="text-center">
                        <div class="font-medium text-gray-600">{{ $subtest['total_questions'] }}</div>
                        <div class="text-gray-500">Total Soal</div>
                    </div>
                    <div class="text-center">
                        <div class="font-medium text-green-600">{{ $subtest['correct_answers'] }}</div>
                        <div class="text-gray-500">Benar</div>
                    </div>
                    <div class="text-center">
                        <div class="font-medium text-red-600">{{ $subtest['wrong_answers'] }}</div>
                        <div class="text-gray-500">Salah</div>
                    </div>
                    <div class="text-center">
                        <div class="font-medium text-gray-600">{{ $subtest['unanswered'] }}</div>
                        <div class="text-gray-500">Kosong</div>
                    </div>
                    <div class="text-center">
                        <div class="font-medium text-blue-600">{{ $subtest['raw_score'] }}/{{ $subtest['max_score'] }}
                        </div>
                        <div class="text-gray-500">Skor</div>
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
            <i class="ri-lightbulb-line mr-2"></i>Rekomendasi Peningkatan
        </h4>
        <ul class="text-sm text-blue-700 space-y-1">
            @foreach($subtestResults as $subtest)
            @if($subtest['percentage'] < 70) <li>• Tingkatkan kemampuan {{ $subtest['name'] }} - skor saat ini {{
                number_format($subtest['percentage'], 1) }}%</li>
            @endif
            @endforeach
            @if($overallPercentage >= 70)
                <li>• Pertahankan kemampuan yang sudah baik dan terus berlatih</li>
                @endif
        </ul>
    </div>
    @elseif($tryout->type_tryout === 'pppk_full')
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <h4 class="font-medium text-yellow-800 mb-2">
            <i class="ri-information-line mr-2"></i>Analisis Hasil PPPK
        </h4>
        <ul class="text-sm text-yellow-700 space-y-1">
            @foreach($subtestResults as $subtest)
            @if($subtest['is_passed'])
            <li>• {{ $subtest['name'] }}: <strong>Lulus</strong> ({{ number_format($subtest['percentage'], 1) }}%)</li>
            @else
            <li>• {{ $subtest['name'] }}: <strong>Perlu Perbaikan</strong> ({{ number_format($subtest['percentage'], 1)
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
            <i class="ri-book-open-line mr-2"></i>Lihat Pembahasan
        </a>
        <a href="{{ route('user.package.tryout.ranking', [$package->package_id, $tryout->tryout_id]) }}"
            class="flex-1 bg-yellow-600 text-white text-center py-3 px-4 rounded-lg hover:bg-yellow-700 transition-colors">
            <i class="ri-trophy-line mr-2"></i>Lihat Ranking
        </a>
        <a href="{{ route('user.package.tryout', $package->package_id) }}"
            class="flex-1 bg-gray-600 text-white text-center py-3 px-4 rounded-lg hover:bg-gray-700 transition-colors">
            <i class="ri-arrow-left-line mr-2"></i>Kembali ke Tryout
        </a>
        @else
        <a href="{{ route('user.event.index') }}"
            class="flex-1 bg-gray-600 text-white text-center py-3 px-4 rounded-lg hover:bg-gray-700 transition-colors">
            <i class="ri-arrow-left-line mr-2"></i>Kembali ke Event
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
