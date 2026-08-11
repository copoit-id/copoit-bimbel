@extends('user.layout.tryout')
@section('title', 'Hasil Tryout')
@section('content')
    <div class="min-h-screen bg-gray-50 py-8 pt-18 flex items-center">
        <div class="max-w-4xl mx-auto">
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
                <div class="flex gap-3 w-full mt-6">
                    <div class="flex-1 text-center p-4 bg-gray-50 rounded-lg min-w-0">
                        <i class="ri-check-line text-xl text-gray-700 mb-1"></i>
                        <p class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $correctAnswers }}</p>
                        <p class="text-xs md:text-sm text-gray-500">Benar</p>
                    </div>
                    <div class="flex-1 text-center p-4 bg-gray-50 rounded-lg min-w-0">
                        <i class="ri-close-line text-xl text-gray-700 mb-1"></i>
                        <p class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $wrongAnswers }}</p>
                        <p class="text-xs md:text-sm text-gray-500">Salah</p>
                    </div>
                    <div class="flex-1 text-center p-4 bg-gray-50 rounded-lg min-w-0">
                        <i class="ri-checkbox-blank-circle-line text-xl text-gray-700 mb-1"></i>
                        <p class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $unansweredCount ?? 0 }}</p>
                        <p class="text-xs md:text-sm text-gray-500">Kosong</p>
                    </div>
                    <div class="flex-1 text-center p-4 bg-gray-50 rounded-lg min-w-0">
                        <i class="ri-time-line text-xl text-gray-700 mb-1"></i>
                        <p class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $formattedTime }}</p>
                        <p class="text-xs md:text-sm text-gray-500">Waktu</p>
                    </div>
                    <div class="flex-1 text-center p-4 bg-gray-50 rounded-lg min-w-0">
                        <i class="ri-timer-line text-xl text-gray-700 mb-1"></i>
                        <p class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $avgTimeText }}</p>
                        <p class="text-xs md:text-sm text-gray-500">Rata-rata</p>
                    </div>
                    @if (($pendingReviewCount ?? 0) > 0)
                        <div class="flex-1 text-center p-4 bg-gray-100 rounded-lg border border-gray-300 min-w-0">
                            <i class="ri-time-line text-xl text-gray-700 mb-1"></i>
                            <p class="text-xl md:text-2xl font-semibold text-gray-900 truncate">{{ $pendingReviewCount }}</p>
                            <p class="text-xs md:text-sm text-gray-600">Menunggu</p>
                        </div>
                    @endif
                </div>

                <!-- Time Per Question Line Chart -->
                @php
                    // Build time per question data with sequential timestamps
                    $questionTimeData = [];
                    $qIndex = 1;
                    $prevTime = null;
                    
                    foreach ($latestUserAnswers as $userAnswer) {
                        $details = $userAnswer->userAnswerDetails->sortBy('user_answer_detail_id');
                        foreach ($details as $detail) {
                            // Calculate time spent on this question (difference from previous answer)
                            if ($prevTime === null) {
                                $qTime = $avgSecondsPerQuestion > 0 ? $avgSecondsPerQuestion : 60;
                            } else {
                                $currentTime = $detail->created_at ? $detail->created_at->timestamp : $prevTime + 60;
                                $qTime = max(5, $currentTime - $prevTime); // Minimum 5 seconds
                            }
                            $prevTime = $detail->created_at ? $detail->created_at->timestamp : ($prevTime + $qTime);
                            
                            $questionTimeData[] = [
                                'number' => $qIndex,
                                'time' => $qTime,
                                'isCorrect' => $detail->is_correct,
                                'pending' => $detail->answer_json['pending_review'] ?? false
                            ];
                            $qIndex++;
                        }
                    }
                    
                    $totalQuestionsChart = count($questionTimeData);
                @endphp

                @if($totalQuestionsChart > 0)
                @php
                    $maxTime = max(30, collect($questionTimeData)->max('time'));
                    $chartHeight = 200;
                    $chartWidth = max(600, $totalQuestionsChart * 25);
                    $paddingX = 40;
                    $paddingY = 20;
                    $plotWidth = $chartWidth - ($paddingX * 2);
                    $plotHeight = $chartHeight - ($paddingY * 2);
                    $stepX = $totalQuestionsChart > 1 ? $plotWidth / ($totalQuestionsChart - 1) : 0;
                @endphp
                <div class="mt-6 bg-white rounded-lg border border-gray-200 p-6">
                    <h4 class="text-sm font-semibold text-gray-900 mb-4">Waktu per Soal (detik)</h4>
                    <div class="overflow-x-auto">
                        <svg width="{{ $chartWidth }}" height="{{ $chartHeight + 30 }}" class="block" style="min-width: 100%;">
                            <!-- Grid lines horizontal -->
                            @for($i = 0; $i <= 5; $i++)
                                @php
                                    $y = $paddingY + ($plotHeight / 5) * $i;
                                    $value = round($maxTime - ($maxTime / 5) * $i);
                                @endphp
                                <line x1="{{ $paddingX }}" y1="{{ $y }}" x2="{{ $chartWidth - $paddingX }}" y2="{{ $y }}" stroke="#e5e7eb" stroke-width="1"/>
                                <text x="{{ $paddingX - 5 }}" y="{{ $y + 4 }}" text-anchor="end" font-size="10" fill="#6b7280">{{ $value }}</text>
                            @endfor
                            
                            <!-- Line path -->
                            @php
                                $points = [];
                                foreach($questionTimeData as $i => $qData) {
                                    $x = $paddingX + ($i * $stepX);
                                    $y = $paddingY + $plotHeight - (($qData['time'] / $maxTime) * $plotHeight);
                                    $points[] = "{$x},{$y}";
                                }
                                $pathData = implode(' L ', $points);
                            @endphp
                            <path d="M {{ $pathData }}" fill="none" stroke="#3b82f6" stroke-width="2"/>
                            
                            <!-- Data points -->
                            @foreach($questionTimeData as $i => $qData)
                                @php
                                    $x = $paddingX + ($i * $stepX);
                                    $y = $paddingY + $plotHeight - (($qData['time'] / $maxTime) * $plotHeight);
                                    $color = $qData['pending'] ? '#9ca3af' : ($qData['isCorrect'] ? '#22c55e' : '#ef4444');
                                @endphp
                                <circle cx="{{ $x }}" cy="{{ $y }}" r="4" fill="{{ $color }}" stroke="white" stroke-width="2"/>
                                
                                <!-- X axis labels -->
                                @if($totalQuestionsChart <= 15 || $i % 2 == 0 || $i == $totalQuestionsChart - 1)
                                    <text x="{{ $x }}" y="{{ $chartHeight - 5 }}" text-anchor="middle" font-size="10" fill="#6b7280">{{ $qData['number'] }}</text>
                                @endif
                            @endforeach
                            
                            <!-- Axes -->
                            <line x1="{{ $paddingX }}" y1="{{ $paddingY + $plotHeight }}" x2="{{ $chartWidth - $paddingX }}" y2="{{ $paddingY + $plotHeight }}" stroke="#9ca3af" stroke-width="1"/>
                            <line x1="{{ $paddingX }}" y1="{{ $paddingY }}" x2="{{ $paddingX }}" y2="{{ $paddingY + $plotHeight }}" stroke="#9ca3af" stroke-width="1"/>
                        </svg>
                    </div>
                    <div class="mt-4 flex items-center gap-4 text-xs text-gray-500">
                        <span><span class="inline-block w-3 h-3 bg-green-500 rounded-full mr-1"></span>Benar</span>
                        <span><span class="inline-block w-3 h-3 bg-red-500 rounded-full mr-1"></span>Salah</span>
                        @if(($pendingReviewCount ?? 0) > 0)
                            <span><span class="inline-block w-3 h-3 bg-gray-400 rounded-full mr-1"></span>Menunggu</span>
                        @endif
                    </div>
                </div>
                @endif
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
