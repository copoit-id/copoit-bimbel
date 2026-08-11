@extends('user.layout.tryout')
@section('title', 'Hasil Tryout')
@section('content')
    <div class="min-h-screen bg-gray-50 py-8 pt-18 flex items-center">
        <div class="w-full max-w-5xl mx-auto px-4 sm:px-6">
            <!-- Main Result -->
            <div class="bg-white rounded-lg border border-border p-4 md:p-8 text-center mb-6">
                <div>
                    @php
                        // Calculate overall pass status based on subtest results
                        $isOverallPassed =
                            isset($subtestResults) && count($subtestResults) > 0
                                ? collect($subtestResults)->every('is_passed')
                                : (isset($singleIsPassed)
                                    ? (bool) $singleIsPassed
                                    : (bool) optional($latestUserAnswers->first())->is_passed);
                        $firstUserAnswer = $latestUserAnswers->first();
                        $showResultScores = $tryout->shouldShowResultScores();
                        $showTotalResultScore = $tryout->shouldShowTotalResultScore();
                    @endphp
                    <div class="flex flex-col justify-center items-center">
                        <p class="text-xl font-semibold text-gray-900 mb-2">{{ $tryout->name }}</p>
                        @if ($showTotalResultScore && isset($rawScore) && isset($maxScore))
                            <div class="flex justify-center items-end text-center gap-2 my-6">
                                <p class="text-5xl font-semibold text-gray-900">{{ $rawScore }}</p>
                                <p class="text-xl text-gray-500">/ {{ $maxScore }}</p>
                            </div>
                        @elseif (! $showResultScores)
                            <p class="my-5 text-sm text-gray-500">Nilai tryout ini tidak ditampilkan.</p>
                        @endif
                        @if (isset($rawScore) && isset($maxScore) && ($showTotalResultScore || ! $showResultScores))
                            @if (($pendingReviewCount ?? 0) > 0)
                                <p class="mb-4 text-sm text-gray-600">
                                    {{ $pendingReviewCount }} jawaban masih menunggu koreksi AI
                                </p>
                            @endif
                            <span class="inline-flex px-4 py-1.5 text-sm font-medium rounded {{ $isOverallPassed ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                                {{ $isOverallPassed ? 'Lulus' : 'Belum Lulus' }}
                            </span>
                        @endif
                    </div>
                </div>

                @php
                    // Calculate total time spent
                    $firstAnswer = $latestUserAnswers->sortBy('started_at')->first();
                    $lastAnswer = $latestUserAnswers->sortByDesc('finished_at')->first();
                    $startTime = $firstAnswer?->started_at;
                    $endTime = $lastAnswer?->finished_at;
                    $totalSeconds = 0;
                    $formattedTime = '-';
                    if ($startTime && $endTime) {
                        $totalSeconds = $startTime->diffInSeconds($endTime);
                        $hours = floor($totalSeconds / 3600);
                        $minutes = floor(($totalSeconds % 3600) / 60);
                        $seconds = $totalSeconds % 60;
                        if ($hours > 0) {
                            $formattedTime = sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
                        } else {
                            $formattedTime = sprintf('%02d:%02d', $minutes, $seconds);
                        }
                    }
                    // Average time per question
                    $answeredQuestions = $correctAnswers + $wrongAnswers + ($pendingReviewCount ?? 0);
                    $avgSecondsPerQuestion = $answeredQuestions > 0 ? round($totalSeconds / $answeredQuestions) : 0;
                    $avgTimeText = $avgSecondsPerQuestion > 0 ? sprintf('%02d:%02d', floor($avgSecondsPerQuestion / 60), $avgSecondsPerQuestion % 60) : '-';
                @endphp

                <!-- Quick Stats -->
                <div class="grid grid-cols-2 gap-3 mt-6 sm:grid-cols-3 {{ ($pendingReviewCount ?? 0) > 0 ? 'lg:grid-cols-6' : 'lg:grid-cols-5' }}">
                    <div class="text-center p-4 bg-gray-50 rounded-lg min-w-0">
                        <i class="ri-check-line text-xl text-gray-700 mb-1"></i>
                        <p class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $correctAnswers }}</p>
                        <p class="text-xs md:text-sm text-gray-500">Benar</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg min-w-0">
                        <i class="ri-close-line text-xl text-gray-700 mb-1"></i>
                        <p class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $wrongAnswers }}</p>
                        <p class="text-xs md:text-sm text-gray-500">Salah</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg min-w-0">
                        <i class="ri-checkbox-blank-circle-line text-xl text-gray-700 mb-1"></i>
                        <p class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $unansweredCount ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-gray-500">Kosong</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg min-w-0">
                        <i class="ri-time-line text-xl text-gray-700 mb-1"></i>
                        <p class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $formattedTime }}</p>
                        <p class="text-xs md:text-sm text-gray-500">Waktu</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg min-w-0">
                        <i class="ri-timer-line text-xl text-gray-700 mb-1"></i>
                        <p class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $avgTimeText }}</p>
                        <p class="text-xs md:text-sm text-gray-500">Rata-rata</p>
                    </div>
                    @if (($pendingReviewCount ?? 0) > 0)
                        <div class="text-center p-4 bg-gray-100 rounded-lg border border-gray-300 min-w-0">
                            <i class="ri-time-line text-xl text-gray-700 mb-1"></i>
                            <p class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $pendingReviewCount }}</p>
                            <p class="text-xs md:text-sm text-gray-600">Menunggu</p>
                        </div>
                    @endif
                </div>

                <!-- Answer Chart -->
                @php
                    $answerChartItems = collect($subtestResults ?? [])
                        ->map(fn (array $subtest): array => [
                            'name' => (string) ($subtest['name'] ?? 'Subtest'),
                            'correct' => (int) ($subtest['correct_answers'] ?? 0),
                            'wrong' => (int) ($subtest['wrong_answers'] ?? 0),
                            'unanswered' => (int) ($subtest['unanswered'] ?? 0),
                        ])
                        ->values();

                    if ($answerChartItems->isEmpty()) {
                        $answerChartItems = collect([[
                            'name' => 'Tryout',
                            'correct' => (int) $correctAnswers,
                            'wrong' => (int) $wrongAnswers,
                            'unanswered' => (int) ($unansweredCount ?? 0),
                        ]]);
                    }

                    $answerChartHighestValue = max(1, $answerChartItems->flatMap(
                        fn (array $item): array => [$item['correct'], $item['wrong'], $item['unanswered']]
                    )->max());
                    $answerChartMaximum = $answerChartHighestValue + max(1, (int) ceil($answerChartHighestValue * 0.15));
                    $answerChartItemCount = $answerChartItems->count();
                    $answerChartWidth = max(660, $answerChartItemCount * 210);
                @endphp

                <section class="mt-8 border-t border-gray-100 pt-6">
                    <div class="text-center">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Grafik Jawaban</p>
                            <p class="mt-1 text-xs text-gray-500">Jumlah jawaban benar, salah, dan tidak terjawab pada setiap subtest.</p>
                        </div>
                        <div class="mt-3 flex items-center justify-center gap-4 text-xs text-gray-500">
                            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-green-500"></span>Benar</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-red-500"></span>Salah</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-gray-300"></span>Tidak Terjawab</span>
                        </div>
                    </div>

                    <div class="mt-7 overflow-x-auto pb-2">
                        <div class="mx-auto" style="width: {{ $answerChartWidth }}px">
                            <div class="relative ml-7 h-60 border-b border-l border-gray-200">
                                <div class="pointer-events-none absolute inset-0 flex flex-col justify-between">
                                    <span class="border-t border-dashed border-gray-100"></span>
                                    <span class="border-t border-dashed border-gray-100"></span>
                                    <span class="border-t border-dashed border-gray-100"></span>
                                    <span></span>
                                </div>

                                <div class="absolute inset-0 grid" style="grid-template-columns: repeat({{ $answerChartItemCount }}, minmax(0, 1fr));">
                                    @foreach($answerChartItems as $item)
                                        @php
                                            $correctHeight = ($item['correct'] / $answerChartMaximum) * 100;
                                            $wrongHeight = ($item['wrong'] / $answerChartMaximum) * 100;
                                            $unansweredHeight = ($item['unanswered'] / $answerChartMaximum) * 100;
                                        @endphp
                                        <div class="flex h-full items-end justify-center gap-3 px-5">
                                            <div class="relative w-10 rounded-t-sm bg-green-500" style="height: {{ $correctHeight }}%">
                                                <span class="absolute inset-x-0 -top-6 text-center text-sm font-medium text-gray-700">{{ $item['correct'] }}</span>
                                            </div>
                                            <div class="relative w-10 rounded-t-sm bg-red-500" style="height: {{ $wrongHeight }}%">
                                                <span class="absolute inset-x-0 -top-6 text-center text-sm font-medium text-gray-700">{{ $item['wrong'] }}</span>
                                            </div>
                                            <div class="relative w-10 rounded-t-sm bg-gray-300" style="height: {{ $unansweredHeight }}%">
                                                <span class="absolute inset-x-0 -top-6 text-center text-sm font-medium text-gray-700">{{ $item['unanswered'] }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="ml-7 mt-3 grid" style="grid-template-columns: repeat({{ $answerChartItemCount }}, minmax(0, 1fr));">
                                @foreach($answerChartItems as $item)
                                    <p class="px-3 text-center text-xs font-medium leading-5 text-gray-500">{{ $item['name'] }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            @php
                $totalPending = collect($subtestResults ?? [])->sum('pending_count');
            @endphp

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

            <!-- SKD Subtest Results -->
            @if (isset($subtestResults) && count($subtestResults) >= 1)
                <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6 {{ $totalPending > 0 ? 'opacity-60' : '' }}">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Hasil Per Subtest</h2>
                        @if ($totalPending > 0)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm">
                                <i class="ri-time-line animate-pulse"></i>
                                Menunggu Koreksi
                            </span>
                        @endif
                    </div>
                    
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
                    <div class="space-y-3">
                        @foreach ($subtestResults as $result)
                            @php
                                $typeKey = strtolower($result['type'] ?? '');
                                $shortLabel = $shortLabels[$typeKey] ?? strtoupper(substr($result['alias'] ?? $result['type'] ?? 'S', 0, 2));
                            @endphp
                            <div class="flex items-center justify-between px-4 py-4 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded bg-gray-800 flex items-center justify-center text-xs font-bold text-white">
                                            {{ $shortLabel }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $result['name'] }}</p>
                                            <div class="text-sm text-gray-500">
                                                @if ($showResultScores)
                                                    <span class="font-medium {{ ($result['pending_count'] ?? 0) > 0 ? 'text-gray-900' : '' }}">
                                                        {{ $result['raw_score'] }}/{{ $result['max_score'] }}
                                                    </span>
                                                @endif
                                                @if (($result['pending_count'] ?? 0) > 0)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-gray-200 text-gray-700 rounded ml-2">
                                                        <i class="ri-time-line animate-pulse"></i>
                                                        {{ $result['pending_count'] }} menunggu
                                                    </span>
                                                @endif
                                                @if ($showResultScores)
                                                    <span class="mx-1">-</span>
                                                    Passing: {{ ($result['passing_type'] ?? 'score') === 'percentage' ? number_format($result['passing_score'] ?? 0, 1).'%' : ($result['passing_score'] ?? '-') }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    @if (($result['pending_count'] ?? 0) > 0)
                                        <span class="inline-flex px-3 py-1 text-sm font-medium rounded bg-gray-300 text-gray-700">
                                            Menunggu
                                        </span>
                                    @else
                                        <span class="inline-flex px-3 py-1 text-sm font-medium rounded {{ $result['is_passed'] ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                                            {{ $result['is_passed'] ? 'Lulus' : 'Belum Lulus' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Overall SKD Status -->
                    @php
                        $passedSubtests = collect($subtestResults)->where('is_passed', true)->count();
                        $totalSubtests = count($subtestResults);
                    @endphp
                    <div class="mt-4 p-4 {{ $totalPending > 0 ? 'bg-gray-100 border border-gray-300' : (collect($subtestResults)->every('is_passed') ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200') }} rounded-lg">
                        <p class="text-sm {{ $totalPending > 0 ? 'text-gray-700' : (collect($subtestResults)->every('is_passed') ? 'text-green-800' : 'text-red-800') }}">
                            @if ($totalPending > 0)
                                <strong>Status Sementara:</strong> {{ $passedSubtests }}/{{ $totalSubtests }} subtest lulus.
                                Hasil final akan muncul setelah koreksi selesai.
                            @else
                                <strong>Status:</strong> {{ $passedSubtests }}/{{ $totalSubtests }} subtest lulus
                                {{ collect($subtestResults)->every('is_passed') ? '- Anda lulus semua subtest' : '' }}
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3 justify-center">
                @if ($package)
                    <a href="{{ route('user.package.tryout', $package->package_id) }}"
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="ri-arrow-left-line mr-2"></i>Kembali
                    </a>
                    <a href="{{ route('user.package.tryout.riwayat', [$package->package_id, $tryout->tryout_id]) }}"
                        class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                        <i class="ri-history-line mr-2"></i>Riwayat
                    </a>
                    @if($tryout->show_leaderboard)
                    <a href="{{ route('user.package.tryout.ranking', [$package->package_id, $tryout->tryout_id]) }}"
                        class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                        <i class="ri-trophy-line mr-2"></i>Ranking
                    </a>
                    @endif
                    @if($tryout->show_discussion)
                    <a href="{{ route('user.package.tryout.pembahasan', [$package->package_id, $tryout->tryout_id, 'token' => $latestAttemptToken]) }}"
                        class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                        <i class="ri-book-open-line mr-2"></i>Pembahasan
                    </a>
                    @endif

                    {{-- Certificate Download Button for Certification Full --}}
                    @if (
                        ($clientBranding['certificate_management_enabled'] ?? true) &&
                            $tryout->is_certification &&
                            ($tryout->type_tryout === 'certification' || $tryout->type_tryout === 'computer'))
                        <a href="{{ route('user.certificate.preview', [$package->package_id, $tryout->tryout_id, 'token' => $latestAttemptToken]) }}"
                            class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                            <i class="ri-award-line mr-2"></i>Unduh Sertifikat
                        </a>
                    @endif
                @else
                    <a href="{{ route('user.event.index') }}"
                        class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                        <i class="ri-arrow-left-line mr-2"></i>Kembali ke Event
                    </a>
                    @if($tryout->show_leaderboard)
                    <a href="{{ route('user.package.tryout.ranking', ['id_package' => 'free', 'id_tryout' => $tryout->tryout_id]) }}"
                        class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                        <i class="ri-trophy-line mr-2"></i>Ranking
                    </a>
                    @endif
                    @if($tryout->show_discussion)
                    <a href="{{ route('user.package.tryout.pembahasan', ['free', $tryout->tryout_id, 'token' => $latestAttemptToken]) }}"
                        class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                        <i class="ri-book-open-line mr-2"></i>Pembahasan
                    </a>
                    @endif
                @endif
                <a href="{{ route('user.tryout.lobby', [$package ? $package->package_id : 'free', $tryout->tryout_id]) }}"
                    class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                    <i class="ri-refresh-line mr-2"></i>Coba Lagi
                </a>
            </div>
        </div>
    </div>

    @include('user.components.feedback-modal', ['package' => $package ?? null, 'tryout' => $tryout])
@endsection

@section('scripts')
<script>
    // Auto refresh when AI correction completes
    const pendingCount = {{ $pendingReviewCount ?? 0 }};
    
    if (pendingCount > 0) {
        const pollInterval = setInterval(() => {
            fetch(`{{ route('user.tryout.check-essay-status') }}?attempt_token={{ $latestAttemptToken }}&count_only=1`)
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
</script>
@endsection
