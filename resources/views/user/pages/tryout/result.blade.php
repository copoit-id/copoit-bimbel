@extends('user.layout.tryout')
@section('title', 'Hasil Tryout')
@section('content')
<div class="min-h-screen bg-gray-50 py-8 pt-18">
    <div class="max-w-4xl mx-auto px-4">
        <!-- Main Result -->
        <div class="bg-white rounded-lg border border-border p-4 md:p-8 text-center mb-6">
            <div>
                @php
                // Calculate overall pass status
                $isOverallPassed = $overallPercentage >= 70;
                $firstUserAnswer = $latestUserAnswers->first();
                @endphp
                <div class="flex flex-col justify-center items-center">
                    <p class="text-xl font-bold text-dark mb-2">Tryout - {{ $tryout->name }}</p>
                    @if(isset($rawScore) && isset($maxScore))
                    <div class="flex justify-center items-end text-center gap-2 my-8">
                        <p class="text-5xl font-bold text-dark">{{ $rawScore }}</p>
                        <p class="text-lg text-gray-600">/ {{ $maxScore }}</p>
                    </div>
                    <p class="text-md px-4 py-1 {{ $isOverallPassed ? 'bg-green' : 'bg-red' }} text-white rounded-md">
                        {{ $isOverallPassed ? 'Selamat! Anda Lulus' : 'Belum Lulus' }}
                    </p>
                    @endif

                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-3 gap-4 w-full mx-auto">
                <div class="flex items-center gap-3 bg-white p-4 rounded-lg border border-border mt-6">
                    <i
                        class="ri-book-line text-[20px] flex items-center justify-center text-white font-medium bg-primary w-10 h-10 rounded-lg"></i>
                    <div class="flex flex-col items-start text-start">
                        <p class="text-[24px] font-bold">{{ $correctAnswers }}</p>
                        <p class="text-[12px] mt-[-6px] font-light">Jawaban Benar</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-white p-4 rounded-lg border border-border mt-6">
                    <i
                        class="ri-book-line text-[20px] flex items-center justify-center text-white font-medium bg-primary w-10 h-10 rounded-lg"></i>
                    <div class="flex flex-col items-start text-start">
                        <p class="text-[24px] font-bold">{{ $wrongAnswers }}</p>
                        <p class="text-[12px] mt-[-6px] font-light">Jawaban Salah</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-white p-4 rounded-lg border border-border mt-6">
                    <i
                        class="ri-book-line text-[20px] flex items-center justify-center text-white font-medium bg-primary w-10 h-10 rounded-lg"></i>
                    <div class="flex flex-col items-start text-start">
                        <p class="text-[24px] font-bold">{{ $latestUserAnswers->sum('unanswered') ?? 0 }}</p>
                        <p class="text-[12px] mt-[-6px] font-light">Jawaban Kosong</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SKD Subtest Results -->
        @if(isset($subtestResults) && count($subtestResults) > 1)
        <div class="bg-white rounded-lg border border-border p-6 mb-6">
            <h2 class="text-lg font-bold text-dark mb-4">Hasil Per Subtest</h2>
            <div class="space-y-4">
                @foreach($subtestResults as $result)
                <div class="flex items-center justify-between px-2 py-4 md:p-4 bg-gray-50 rounded-lg">
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 rounded-lg text-primary bg-primary/10 flex items-center justify-center text-sm font-bold">
                                {{ strtoupper($result['type']) }}
                            </div>
                            <div>
                                <p class="font-medium text-dark">{{ $result['name'] }}</p>
                                {{-- <p class="text-sm text-gray-600">{{ $result['correct_answers'] }}/{{
                                    $result['total_questions'] }} benar</p> --}}
                                <div class="font-bold text-dark">
                                    {{ $result['raw_score'] }}/{{ $result['max_score'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div
                            class="text-sm text-white px-4 py-1 rounded-md {{ $result['is_passed'] ? 'bg-green' : 'bg-red' }}">
                            {{ $result['is_passed'] ? 'Lulus' : 'Belum Lulus' }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Overall SKD Status -->
            <div
                class="mt-6 p-4 {{ collect($subtestResults)->every('is_passed') ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }} rounded-lg">
                @php
                $passedSubtests = collect($subtestResults)->where('is_passed', true)->count();
                $totalSubtests = count($subtestResults);
                @endphp
                <p class="text-sm {{ collect($subtestResults)->every('is_passed') ? 'text-green-700' : 'text-red' }}">
                    <strong>Status SKD:</strong> {{ $passedSubtests }}/{{ $totalSubtests }} subtest lulus
                    @if(collect($subtestResults)->every('is_passed'))
                    - Selamat! Anda lulus semua subtest SKD
                    @else
                    - Perlu meningkatkan hasil di {{ $totalSubtests - $passedSubtests }} subtest
                    @endif
                </p>
            </div>
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-3 justify-center">
            @if($package)
            <a href="{{ route('user.package.tryout', $package->package_id) }}"
                class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="ri-arrow-left-line mr-2"></i>Kembali
            </a>
            <a href="{{ route('user.package.tryout.riwayat', [$package->package_id, $tryout->tryout_id]) }}"
                class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                <i class="ri-history-line mr-2"></i>Riwayat
            </a>
            <a href="{{ route('user.package.tryout.ranking', [$package->package_id, $tryout->tryout_id]) }}"
                class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                <i class="ri-trophy-line mr-2"></i>Ranking
            </a>
            <a href="{{ route('user.package.tryout.pembahasan', [$package->package_id, $tryout->tryout_id, 'token' => $latestAttemptToken]) }}"
                class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                <i class="ri-book-open-line mr-2"></i>Pembahasan
            </a>

            {{-- Certificate Download Button for Certification Full --}}
            @if($tryout->is_certification && ($tryout->type_tryout === 'certification' || $tryout->type_tryout ===
            'computer'))
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
            @endif
            <a href="{{ route('user.tryout.lobby', [$package ? $package->package_id : 'free', $tryout->tryout_id]) }}"
                class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                <i class="ri-refresh-line mr-2"></i>Coba Lagi
            </a>
        </div>
    </div>
</div>
@endsection