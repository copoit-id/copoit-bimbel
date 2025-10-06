@extends('user.layout.user')
@section('title', 'Statistik Tryout')
@section('content')
<div class="package-bimbel bg-white p-4 rounded-lg border border-border">
    <x-page-desc title="Statistik - {{ $tryout->name }}" description="Analisis detail hasil tryout Anda"
        name_link="Kembali ke Tryout" url_link="{{ route('user.package.tryout', $package->package_id) }}">
    </x-page-desc>

    <!-- Overall Summary Card -->
    <div class="bg-white p-6 rounded-lg border border-border mt-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Score Summary -->
            <div class="text-center">
                <div class="mb-4">
                    <div class="text-4xl font-bold text-gray-800">{{ number_format($overallStats['percentage'], 1) }}%
                    </div>
                    <div class="text-sm text-gray-600">Total Skor</div>
                </div>
                <div class="flex justify-center">
                    <span
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium {{ $overallStats['is_passed'] ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-700' }}">
                        <i class="ri-{{ $overallStats['is_passed'] ? 'check' : 'close' }}-line"></i>
                        {{ $overallStats['is_passed'] ? 'LULUS' : 'TIDAK LULUS' }}
                    </span>
                </div>
                @if(isset($tryoutDetails) && $tryoutDetails->count() > 1)
                <div class="mt-2">
                    <span class="inline-flex px-3 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-full">
                        SKD Full - {{ $tryoutDetails->count() }} Subtest
                    </span>
                </div>
                @endif
            </div>

            <!-- Performance Metrics -->
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-800">{{ number_format($overallStats['accuracy'], 1) }}%
                    </div>
                    <div class="text-xs text-gray-600">Akurasi</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-800">{{ number_format($overallStats['completion'], 1) }}%
                    </div>
                    <div class="text-xs text-gray-600">Pengerjaan</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-800">{{ $overallStats['time_spent'] }}</div>
                    <div class="text-xs text-gray-600">Menit</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-800">{{
                        number_format($overallStats['avg_time_per_question'], 1) }}</div>
                    <div class="text-xs text-gray-600">Menit/Soal</div>
                </div>
            </div>

            <!-- Question Breakdown -->
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total Soal</span>
                    <span class="font-semibold">{{ $overallStats['total_questions'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-primary">Benar</span>
                    <span class="font-semibold text-primary">{{ $overallStats['correct'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-700">Salah</span>
                    <span class="font-semibold text-gray-700">{{ $overallStats['wrong'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">Kosong</span>
                    <span class="font-semibold text-gray-500">{{ $overallStats['unanswered'] }}</span>
                </div>
                <div class="border-t pt-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Skor</span>
                        <span class="font-bold text-gray-800">{{ number_format($overallStats['total_score'], 0) }}/{{
                            number_format($overallStats['max_score'], 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Bars -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <!-- Accuracy Progress -->
        <div class="bg-white p-6 rounded-lg border border-border">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Tingkat Akurasi</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Jawaban Benar</span>
                        <span>{{ $overallStats['correct'] }}/{{ $overallStats['answered'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-primary h-3 rounded-full transition-all duration-500"
                            style="width: {{ $overallStats['accuracy'] }}%"></div>
                    </div>
                    <div class="text-right text-sm text-primary font-medium mt-1">
                        {{ number_format($overallStats['accuracy'], 1) }}%
                    </div>
                </div>
            </div>
        </div>

        <!-- Completion Progress -->
        <div class="bg-white p-6 rounded-lg border border-border">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Tingkat Pengerjaan</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Soal Dikerjakan</span>
                        <span>{{ $overallStats['answered'] }}/{{ $overallStats['total_questions'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-primary h-3 rounded-full transition-all duration-500"
                            style="width: {{ $overallStats['completion'] }}%"></div>
                    </div>
                    <div class="text-right text-sm text-primary font-medium mt-1">
                        {{ number_format($overallStats['completion'], 1) }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Subtest Statistics (for SKD Full) -->
    @if(count($categoryStats) > 1)
    <div class="bg-white p-6 rounded-lg border border-border mt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Statistik Per Subtest</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($categoryStats as $category)
            <div class="border border-border rounded-lg p-4">
                <div class="text-center mb-4">
                    <h4 class="font-semibold text-gray-800">{{ strtoupper($category['type']) }}</h4>
                    <p class="text-sm text-gray-600">{{ $category['name'] }}</p>
                </div>

                <div class="space-y-3">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-800">{{ number_format($category['percentage'], 1) }}%
                        </div>
                        <div class="text-xs text-gray-600">Skor: {{ $category['score'] }}/{{ $category['max_score'] }}
                        </div>
                    </div>

                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-primary h-2 rounded-full transition-all duration-500"
                            style="width: {{ $category['percentage'] }}%"></div>
                    </div>

                    <div class="grid grid-cols-3 gap-1 text-center text-xs">
                        <div>
                            <div class="font-semibold text-primary">{{ $category['correct'] }}</div>
                            <div class="text-gray-600">Benar</div>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-700">{{ $category['wrong'] }}</div>
                            <div class="text-gray-600">Salah</div>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-500">{{ $category['unanswered'] }}</div>
                            <div class="text-gray-600">Kosong</div>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-gray-200">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">Akurasi:</span>
                            <span class="font-medium">{{ number_format($category['accuracy'], 1) }}%</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">Pengerjaan:</span>
                            <span class="font-medium">{{ number_format($category['completion'], 1) }}%</span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Performance Analysis -->
    <div class="bg-white p-6 rounded-lg border border-border mt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Analisis Performa</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Strengths & Weaknesses -->
            <div class="space-y-4">
                <h4 class="font-medium text-gray-700">Analisis Kekuatan & Kelemahan</h4>
                @if(count($categoryStats) > 1)
                @php
                $bestCategory = collect($categoryStats)->sortByDesc('percentage')->first();
                $worstCategory = collect($categoryStats)->sortBy('percentage')->first();
                @endphp
                <div class="space-y-3">
                    <div class="p-3 bg-primary/10 rounded-lg border border-primary/20">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="ri-trophy-line text-primary"></i>
                            <span class="font-medium text-primary">Kategori Terbaik</span>
                        </div>
                        <div class="text-sm text-gray-700">
                            {{ $bestCategory['name'] }} ({{ strtoupper($bestCategory['type']) }}) - {{
                            number_format($bestCategory['percentage'], 1) }}%
                        </div>
                    </div>

                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="ri-focus-line text-gray-600"></i>
                            <span class="font-medium text-gray-700">Perlu Ditingkatkan</span>
                        </div>
                        <div class="text-sm text-gray-700">
                            {{ $worstCategory['name'] }} ({{ strtoupper($worstCategory['type']) }}) - {{
                            number_format($worstCategory['percentage'], 1) }}%
                        </div>
                    </div>
                </div>
                @endif

                <!-- Performance Recommendations -->
                <div class="space-y-2">
                    <h5 class="font-medium text-gray-700">Rekomendasi</h5>
                    <div class="space-y-2 text-sm text-gray-600">
                        @if($overallStats['accuracy'] < 70) <div class="flex items-start gap-2">
                            <i class="ri-lightbulb-line text-primary mt-0.5"></i>
                            <span>Tingkatkan pemahaman materi dasar untuk meningkatkan akurasi jawaban</span>
                    </div>
                    @endif

                    @if($overallStats['completion'] < 90) <div class="flex items-start gap-2">
                        <i class="ri-timer-line text-primary mt-0.5"></i>
                        <span>Latih manajemen waktu untuk menyelesaikan lebih banyak soal</span>
                </div>
                @endif

                @if($overallStats['avg_time_per_question'] > 2)
                <div class="flex items-start gap-2">
                    <i class="ri-speed-line text-primary mt-0.5"></i>
                    <span>Tingkatkan kecepatan membaca dan menganalisis soal</span>
                </div>
                @endif

                @if($overallStats['is_passed'])
                <div class="flex items-start gap-2">
                    <i class="ri-star-line text-primary mt-0.5"></i>
                    <span>Pertahankan performa yang baik dan terus berlatih secara konsisten</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Time Analysis -->
    <div class="space-y-4">
        <h4 class="font-medium text-gray-700">Analisis Waktu</h4>
        <div class="space-y-3">
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <span class="text-sm text-gray-600">Total Waktu Pengerjaan</span>
                <span class="font-semibold">{{ $overallStats['time_spent'] }} menit</span>
            </div>

            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <span class="text-sm text-gray-600">Rata-rata per Soal</span>
                <span class="font-semibold">{{ number_format($overallStats['avg_time_per_question'], 1) }} menit</span>
            </div>

            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <span class="text-sm text-gray-600">Tanggal Pengerjaan</span>
                <span class="font-semibold">{{ \Carbon\Carbon::parse($overallStats['attempt_date'])->format('d M Y,
                    H:i') }}</span>
            </div>
        </div>

        <!-- Time Efficiency -->
        <div
            class="p-3 rounded-lg {{ $overallStats['avg_time_per_question'] <= 1.5 ? 'bg-primary/10 border border-primary/20' : 'bg-gray-50 border border-gray-200' }}">
            <div class="flex items-center gap-2 mb-1">
                <i
                    class="ri-dashboard-line {{ $overallStats['avg_time_per_question'] <= 1.5 ? 'text-primary' : 'text-gray-600' }}"></i>
                <span
                    class="font-medium {{ $overallStats['avg_time_per_question'] <= 1.5 ? 'text-primary' : 'text-gray-700' }}">
                    Efisiensi Waktu
                </span>
            </div>
            <div class="text-sm {{ $overallStats['avg_time_per_question'] <= 1.5 ? 'text-primary' : 'text-gray-600' }}">
                @if($overallStats['avg_time_per_question'] <= 1.5) Sangat Efisien - Waktu pengerjaan optimal
                    @elseif($overallStats['avg_time_per_question'] <=2) Cukup Efisien - Masih dalam batas wajar @else
                    Perlu Ditingkatkan - Terlalu lama per soal @endif </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="flex flex-col sm:flex-row gap-4 justify-center mt-6">
    <a href="{{ route('user.package.tryout', $package->package_id) }}"
        class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-center">
        <i class="ri-arrow-left-line mr-2"></i>Kembali ke Tryout
    </a>

    <a href="{{ route('user.package.tryout.pembahasan', [$package->package_id, $tryout->tryout_id]) }}"
        class="px-6 py-3 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors text-center">
        <i class="ri-book-open-line mr-2"></i>Lihat Pembahasan
    </a>

    <a href="{{ route('user.package.tryout.ranking', [$package->package_id, $tryout->tryout_id]) }}"
        class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-center">
        <i class="ri-trophy-line mr-2"></i>Lihat Ranking
    </a>

    <a href="{{ route('user.tryout.lobby', [$package->package_id, $tryout->tryout_id]) }}"
        class="px-6 py-3 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition-colors text-center">
        <i class="ri-refresh-line mr-2"></i>Coba Lagi
    </a>
</div>
</div>
@endsection

@section('styles')
<style>
    /* Progress bar animation */
    .progress-bar {
        transition: width 1.5s ease-out;
    }

    /* Hover effects */
    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
</style>
@endsection