@extends('user.layout.user')
@section('title', 'Hasil ' . $tryout->name)
@section('content')

<div class="package-bimbel bg-white p-4 rounded-lg border border-border">
    @php
        $showResultScores = $tryout->shouldShowResultScores();
        $showScoreMaximum = $tryout->shouldShowScoreMaximum();
        $showPassingGrade = $tryout->shouldShowPassingGrade();
        $showTotalResultScore = $tryout->shouldShowTotalResultScore();
    @endphp
    <div class="text-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Hasil {{ $tryout->name }}</h1>
        @if(filled($tryout->description))
            <p class="text-gray-600">{{ $tryout->description }}</p>
        @endif
    </div>

    <!-- Overall Score Card -->
    @if($showTotalResultScore)
    <div class="bg-gradient-to-r from-primary to-blue-600 text-white rounded-lg p-6 mb-6">
        <div class="text-center">
            <h2 class="text-2xl font-bold mb-2">Skor Total</h2>
            <div class="text-5xl font-bold mb-2">{{ number_format($overallPercentage, 1) }}%</div>
            <p class="text-lg">{{ $rawScore }}@if($showScoreMaximum) dari {{ $maxScore }} poin@endif</p>

            @if($showPassingGrade && $tryout->type_tryout === 'computer')
            @if($overallPercentage >= 70)
            <div class="mt-4 inline-block bg-green-500 text-white px-4 py-2 rounded-full">
                <i class="ri-check-double-line mr-2"></i>Kompeten
            </div>
            @else
            <div class="mt-4 inline-block bg-red-500 text-white px-4 py-2 rounded-full">
                <i class="ri-close-line mr-2"></i>Belum Kompeten
            </div>
            @endif
            @elseif($showPassingGrade && $tryout->type_tryout === 'pppk_full')
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
    @elseif(! $showResultScores)
    <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-5 text-center text-sm text-gray-500">
        Nilai tryout ini tidak ditampilkan.
    </div>
    @endif

    @php
        $totalPending = collect($subtestResults ?? [])->sum('pending_count');
        
        // Calculate total time spent
        $firstStartTime = null;
        $lastEndTime = null;
        foreach ($subtestResults ?? [] as $subtest) {
            if (isset($subtest['started_at']) && (!$firstStartTime || $subtest['started_at'] < $firstStartTime)) {
                $firstStartTime = $subtest['started_at'];
            }
            if (isset($subtest['finished_at']) && (!$lastEndTime || $subtest['finished_at'] > $lastEndTime)) {
                $lastEndTime = $subtest['finished_at'];
            }
        }
        
        $totalSeconds = 0;
        $formattedTime = '-';
        if ($firstStartTime && $lastEndTime) {
            $start = \Carbon\Carbon::parse($firstStartTime);
            $end = \Carbon\Carbon::parse($lastEndTime);
            $totalSeconds = $start->diffInSeconds($end);
            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);
            $seconds = $totalSeconds % 60;
            if ($hours > 0) {
                $formattedTime = sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
            } else {
                $formattedTime = sprintf('%02d:%02d', $minutes, $seconds);
            }
        }
        
        $answeredCount = $correctAnswers + $wrongAnswers + ($pendingReviewCount ?? 0);
        $avgSecondsPerQuestion = $answeredCount > 0 ? round($totalSeconds / $answeredCount) : 0;
        $avgTimeText = $avgSecondsPerQuestion > 0 ? sprintf('%02d:%02d', floor($avgSecondsPerQuestion / 60), $avgSecondsPerQuestion % 60) : '-';
    @endphp

    <!-- Statistics Grid -->
    <div class="flex gap-3 w-full mb-6">
        <div class="flex-1 text-center p-4 bg-gray-50 rounded-lg min-w-0">
            <i class="ri-file-list-line text-xl text-gray-700 mb-1"></i>
            <div class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $totalQuestions }}</div>
            <div class="text-xs md:text-sm text-gray-500">Total</div>
        </div>
        <div class="flex-1 text-center p-4 bg-gray-50 rounded-lg min-w-0">
            <i class="ri-check-line text-xl text-gray-700 mb-1"></i>
            <div class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $correctAnswers }}</div>
            <div class="text-xs md:text-sm text-gray-500">Benar</div>
        </div>
        <div class="flex-1 text-center p-4 bg-gray-50 rounded-lg min-w-0">
            <i class="ri-close-line text-xl text-gray-700 mb-1"></i>
            <div class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $wrongAnswers }}</div>
            <div class="text-xs md:text-sm text-gray-500">Salah</div>
        </div>
        <div class="flex-1 text-center p-4 bg-gray-50 rounded-lg min-w-0">
            <i class="ri-time-line text-xl text-gray-700 mb-1"></i>
            <div class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $formattedTime }}</div>
            <div class="text-xs md:text-sm text-gray-500">Waktu</div>
        </div>
        <div class="flex-1 text-center p-4 bg-gray-50 rounded-lg min-w-0">
            <i class="ri-timer-line text-xl text-gray-700 mb-1"></i>
            <div class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $avgTimeText }}</div>
            <div class="text-xs md:text-sm text-gray-500">Rata-rata</div>
        </div>
        @if (($pendingReviewCount ?? 0) > 0)
            <div class="flex-1 text-center p-4 bg-gray-100 rounded-lg border border-gray-300 min-w-0">
                <i class="ri-time-line text-xl text-gray-700 mb-1"></i>
                <div class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $pendingReviewCount }}</div>
                <div class="text-xs md:text-sm text-gray-600">Menunggu</div>
            </div>
        @endif
    </div>

    <!-- AI Processing Section - Show when there are pending essays -->
    @if ($totalPending > 0)
        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="ri-robot-line text-2xl text-gray-700"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-gray-900">Sedang Diproses oleh AI</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ $totalPending }} jawaban essay sedang dalam proses koreksi. 
                        Halaman akan otomatis memperbarui saat selesai.
                    </p>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-gray-700 rounded-full text-sm">
                        <span class="w-1.5 h-1.5 bg-gray-500 rounded-full animate-pulse"></span>
                        Memproses
                    </span>
                </div>
            </div>
        </div>
    @endif

    @php
        $shortLabels = [
            'general' => 'G', 'tpa' => 'TPA', 'tbi' => 'TBI', 'listening' => 'L', 'reading' => 'R', 'writing' => 'W',
            'twk' => 'TWK', 'tiu' => 'TIU', 'tkp' => 'TKP',
            'penalaran_umum' => 'PU', 'pengetahuan_umum' => 'PPU', 
            'pengetahuan_kuantitatif' => 'PK', 'pemahaman_bacaan_menulis' => 'PBM',
            'literasi_bahasa_indonesia' => 'LBI', 'literasi_bahasa_inggris' => 'LBE',
            'penalaran_matematika' => 'PM', 'word' => 'W', 'excel' => 'E', 'ppt' => 'P',
            'teknis' => 'T', 'social culture' => 'SC', 'interview' => 'I'
        ];
    @endphp
    <!-- Subtest Results -->
    <div class="mb-6 {{ $totalPending > 0 ? 'opacity-60' : '' }}">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Hasil Per Subtest</h3>
            @if ($totalPending > 0)
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm">
                    <i class="ri-time-line animate-pulse"></i>
                    Menunggu Koreksi
                </span>
            @endif
        </div>
        
        <div class="space-y-3">
            @foreach($subtestResults as $subtest)
            @php
                $typeKey = strtolower($subtest['type'] ?? '');
                $shortLabel = $shortLabels[$typeKey] ?? strtoupper(substr($subtest['alias'] ?? $subtest['type'] ?? 'S', 0, 2));
            @endphp
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded bg-gray-800 flex items-center justify-center text-xs font-bold text-white">
                            {{ $shortLabel }}
                        </div>
                        <h4 class="font-medium text-gray-900">{{ $subtest['name'] }}</h4>
                    </div>
                    @if($showResultScores && ($subtest['pending_count'] ?? 0) > 0)
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-semibold text-gray-900">{{ number_format($subtest['percentage'], 1) }}%</span>
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs">
                                <i class="ri-time-line animate-pulse"></i>
                                {{ $subtest['pending_count'] }} menunggu
                            </span>
                        </div>
                    @elseif($showResultScores)
                        <span class="text-xl font-semibold text-gray-900">{{ number_format($subtest['percentage'], 1) }}%</span>
                    @elseif(($subtest['pending_count'] ?? 0) > 0)
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs">
                            <i class="ri-time-line animate-pulse"></i>
                            {{ $subtest['pending_count'] }} menunggu
                        </span>
                    @endif
                </div>
                
                <div class="flex items-center justify-between">
                    @if($showResultScores || $showPassingGrade)
                        <p class="text-sm text-gray-500">
                            @if($showResultScores)
                                {{ $subtest['display_score']['formatted'] ?? $subtest['raw_score'] }}@if($showScoreMaximum)/{{ $subtest['display_score']['formatted_maximum'] ?? $subtest['max_score'] }}@endif
                            @endif
                            @if($showResultScores && $showPassingGrade)
                                <span class="mx-1">-</span>
                            @endif
                            @if($showPassingGrade)
                                Passing: {{ ($subtest['passing_type'] ?? 'score') === 'percentage' ? number_format($subtest['passing_score'], 1).'%' : $subtest['passing_score'] }}
                            @endif
                        </p>
                    @else
                        <span></span>
                    @endif
                    @if(($subtest['pending_count'] ?? 0) > 0)
                        <span class="inline-flex px-3 py-1 text-xs font-medium rounded bg-gray-300 text-gray-700">
                            Menunggu
                        </span>
                    @elseif($showPassingGrade)
                        <span class="inline-flex px-3 py-1 text-xs font-medium rounded {{ $subtest['is_passed'] ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                            {{ $subtest['is_passed'] ? 'Lulus' : 'Tidak Lulus' }}
                        </span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Recommendations -->
    @if($showResultScores && $tryout->type_tryout === 'computer')
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
    @elseif($showResultScores && $showPassingGrade && $tryout->type_tryout === 'pppk_full')
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
        @if($tryout->show_discussion)
        <a href="{{ route('user.package.tryout.pembahasan', [$package->package_id, $tryout->tryout_id, $latestAttemptToken]) }}"
            class="flex-1 bg-green-600 text-white text-center py-3 px-4 rounded-lg hover:bg-green-700 transition-colors">
            <i class="ri-book-open-line mr-2"></i>Lihat Pembahasan
        </a>
        @endif
        @if($tryout->show_leaderboard)
        <a href="{{ route('user.package.tryout.ranking', [$package->package_id, $tryout->tryout_id]) }}"
            class="flex-1 bg-yellow-600 text-white text-center py-3 px-4 rounded-lg hover:bg-yellow-700 transition-colors">
            <i class="ri-trophy-line mr-2"></i>Lihat Ranking
        </a>
        @endif
        <a href="{{ route('user.package.tryout', $package->package_id) }}"
            class="flex-1 bg-gray-600 text-white text-center py-3 px-4 rounded-lg hover:bg-gray-700 transition-colors">
            <i class="ri-arrow-left-line mr-2"></i>Kembali ke Tryout
        </a>
        @else
        @if($tryout->show_discussion)
        <a href="{{ route('user.package.tryout.pembahasan', ['free', $tryout->tryout_id, $latestAttemptToken]) }}"
            class="flex-1 bg-green-600 text-white text-center py-3 px-4 rounded-lg hover:bg-green-700 transition-colors">
            <i class="ri-book-open-line mr-2"></i>Lihat Pembahasan
        </a>
        @endif
        @if($tryout->show_leaderboard)
        <a href="{{ route('user.package.tryout.ranking', ['id_package' => 'free', 'id_tryout' => $tryout->tryout_id]) }}"
            class="flex-1 bg-yellow-600 text-white text-center py-3 px-4 rounded-lg hover:bg-yellow-700 transition-colors">
            <i class="ri-trophy-line mr-2"></i>Lihat Ranking
        </a>
        @endif
        <a href="{{ route('user.event.index') }}"
            class="flex-1 bg-gray-600 text-white text-center py-3 px-4 rounded-lg hover:bg-gray-700 transition-colors">
            <i class="ri-arrow-left-line mr-2"></i>Kembali ke Event
        </a>
        @endif
    </div>
</div>

@include('user.components.feedback-modal', ['package' => $package ?? null, 'tryout' => $tryout])
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
    
    // Polling for AI essay correction status
    const pendingCount = {{ $pendingReviewCount ?? 0 }};
    
    if (pendingCount > 0) {
        const pollInterval = setInterval(() => {
            fetch(`{{ route('user.tryout.check-essay-status') }}?attempt_token={{ $latestAttemptToken ?? '' }}&count_only=1`)
                .then(response => response.json())
                .then(data => {
                    if (data.pending_count === 0) {
                        clearInterval(pollInterval);
                        location.reload();
                    }
                })
                .catch(error => console.error('Error checking status:', error));
        }, 5000);
    }
});
</script>
@endsection
