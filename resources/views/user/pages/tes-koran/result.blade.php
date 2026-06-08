@extends('user.layout.new-user')

@section('title', 'Hasil Tes - ' . $tesKoran->name)

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$columnScores = $result->column_scores ?? [];
$maxColumnScore = max(array_merge($columnScores, [1]));
$hasColumnProgress = collect($columnScores)->contains(fn($score) => (int) $score > 0);
$resultIcon = match ($result->final_result) {
    'tinggi' => 'ri-emotion-happy-line',
    'sedang' => 'ri-emotion-normal-line',
    default => 'ri-emotion-unhappy-line',
};
$resultBadgeClass = match ($result->final_result) {
    'tinggi' => 'bg-green-500',
    'sedang' => 'bg-yellow-500',
    default => 'bg-red-500',
};
$stabilityIcon = match ($result->stability_status) {
    'meningkat' => 'ri-arrow-up-line',
    'menurun' => 'ri-arrow-down-line',
    default => 'ri-subtract-line',
};
$stabilityClass = match ($result->stability_status) {
    'meningkat' => 'text-green-600',
    'menurun' => 'text-red-600',
    default => 'text-yellow-600',
};
@endphp

<!-- Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('user.tes-koran.index') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Hasil Tes</h1>
        <p class="text-gray-500 text-sm">{{ $tesKoran->name }}</p>
    </div>
</div>

<!-- Result Summary -->
<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Ringkasan Hasil</h2>
            <p class="text-gray-500 text-sm">{{ $result->finished_at ? $result->finished_at->format('d M Y, H:i') : 'Baru saja' }}</p>
        </div>
        <div>
            <span class="px-4 py-2 {{ $resultBadgeClass }} text-white rounded-full font-bold">
                <i class="{{ $resultIcon }} mr-1"></i>{{ strtoupper($result->final_result) }}
            </span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="border rounded-xl p-4 text-center" style="background-color: {{ $primaryColor }}10; border-color: {{ $primaryColor }}30">
            <div class="text-3xl font-bold" style="color: {{ $primaryColor }}">{{ $result->total_correct }}</div>
            <div class="text-sm text-gray-600">Jawaban Benar</div>
        </div>
        <div class="border rounded-xl p-4 text-center" style="background-color: {{ $primaryColor }}10; border-color: {{ $primaryColor }}30">
            <div class="text-3xl font-bold" style="color: {{ $primaryColor }}">{{ $result->total_wrong }}</div>
            <div class="text-sm text-gray-600">Jawaban Salah</div>
        </div>
        <div class="border rounded-xl p-4 text-center" style="background-color: {{ $primaryColor }}10; border-color: {{ $primaryColor }}30">
            <div class="text-3xl font-bold" style="color: {{ $primaryColor }}">{{ round($result->accuracy_score, 1) }}%</div>
            <div class="text-sm text-gray-600">Akurasi</div>
        </div>
        <div class="border rounded-xl p-4 text-center" style="background-color: {{ $primaryColor }}10; border-color: {{ $primaryColor }}30">
            <div class="text-3xl font-bold" style="color: {{ $primaryColor }}">{{ round($result->stability_score) }}</div>
            <div class="text-sm text-gray-600">Skor Stabilitas</div>
        </div>
    </div>

    <!-- Stability Status -->
    <div class="rounded-xl p-4 mb-6" style="background-color: {{ $primaryColor }}08">
        <h4 class="font-medium text-gray-700 mb-3">Status Stabilitas</h4>
        <div class="flex items-center gap-4">
            @if($result->stability_status == 'meningkat')
            <span class="flex items-center gap-2 {{ $stabilityClass }}">
                <i class="{{ $stabilityIcon }} text-2xl"></i>
                <span class="font-bold">MENINGKAT</span>
            </span>
            <span class="text-sm text-gray-600">Grafik menunjukkan peningkatan - motivasi dan ketahanan tinggi</span>
            @elseif($result->stability_status == 'menurun')
            <span class="flex items-center gap-2 {{ $stabilityClass }}">
                <i class="{{ $stabilityIcon }} text-2xl"></i>
                <span class="font-bold">MENURUN</span>
            </span>
            <span class="text-sm text-gray-600">Grafik menurun - menunjukkan kelelahan atau penurunan motivasi</span>
            @else
            <span class="flex items-center gap-2 {{ $stabilityClass }}">
                <i class="{{ $stabilityIcon }} text-2xl"></i>
                <span class="font-bold">DATAR</span>
            </span>
            <span class="text-sm text-gray-600">Grafik stabil - menunjukkan konsistensi diri yang baik</span>
            @endif
        </div>
    </div>

    <!-- Column Chart -->
    @if(count($columnScores) > 0)
    <div class="bg-white border border-gray-100 rounded-2xl p-6 mb-6">
        <div class="flex items-center justify-between gap-4 mb-6">
            <div>
                <h4 class="text-lg font-bold text-gray-800">Jawaban Benar per Kolom (Maks: {{ $tesKoran->rows_count - 1 }})</h4>
                <p class="text-xs text-gray-500">Menampilkan jumlah jawaban benar di setiap kolom.</p>
            </div>
            @unless($hasColumnProgress)
            <span class="px-3 py-1 rounded-full bg-gray-200 text-gray-600 text-xs font-medium">
                Semua kolom 0
            </span>
            @endunless
        </div>

        <div class="relative w-full overflow-x-auto pb-2">
            <div class="h-64" style="min-width: {{ max(500, count($columnScores) * 25) }}px; width: 100%;">
                <canvas id="columnScoresChart"></canvas>
            </div>
        </div>

        @unless($hasColumnProgress)
        <p class="mt-3 text-xs text-gray-500">
            Grafik tidak rusak, tetapi hasil ini belum memiliki jawaban benar pada tiap kolom.
        </p>
        @endunless
    </div>
    @endif
</div>

<!-- Interpretation -->
<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <h3 class="font-bold text-gray-800 mb-4">Interpretasi Hasil</h3>
    <div class="space-y-3 text-sm text-gray-600">
        <div class="flex items-start gap-3">
            <i class="ri-speed-line text-primary mt-0.5"></i>
            <div>
                <strong>Kecepatan:</strong>
                @if($result->speed_score >= 70)
                <span class="text-green-600">Tinggi - Anda mampu bekerja dengan cepat</span>
                @elseif($result->speed_score >= 40)
                <span class="text-yellow-600">Sedang - Kecepatan Anda cukup baik</span>
                @else
                <span class="text-red-600">Rendah - Perlu meningkatkan kecepatan kerja</span>
                @endif
            </div>
        </div>
        <div class="flex items-start gap-3">
            <i class="ri-target-line text-primary mt-0.5"></i>
            <div>
                <strong>Akurasi:</strong>
                @if($result->accuracy_score >= 85)
                <span class="text-green-600">Sangat Baik - Ketelitian sangat tinggi</span>
                @elseif($result->accuracy_score >= 70)
                <span class="text-green-600">Baik - Ketelitian cukup baik</span>
                @else
                <span class="text-yellow-600">Perlu peningkatan - Perbanyak latihan ketelitian</span>
                @endif
            </div>
        </div>
        <div class="flex items-start gap-3">
            <i class="ri-line-chart-line text-primary mt-0.5"></i>
            <div>
                <strong>Stabilitas:</strong>
                @if($result->stability_status == 'meningkat')
                <span class="text-green-600">Baik - Anda mampu mempertahankan performa hingga akhir</span>
                @elseif($result->stability_status == 'datar')
                <span class="text-green-600">Baik - Konsistensi Anda sangat baik</span>
                @else
                <span class="text-yellow-600">Perlu perhatian - Coba bangun stamina konsentrasi</span>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Actions -->
<div class="flex gap-4">
    <a href="{{ route('user.tes-koran.index') }}"
       class="flex-1 py-3 text-center border border-gray-300 rounded-xl font-medium hover:bg-gray-50 transition-colors">
        <i class="ri-arrow-left-line mr-2"></i>Kembali ke Daftar
    </a>
    <a href="{{ route('user.tes-koran.show', $tesKoran) }}"
       class="flex-1 py-3 text-center text-white rounded-xl font-medium hover:opacity-90 transition-opacity"
       style="background-color: {{ $primaryColor }}">
        <i class="ri-refresh-line mr-2"></i>Coba Lagi
    </a>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('columnScoresChart');
        if (!ctx) return;

        const columnScores = @json($columnScores);
        const maxScore = {{ $tesKoran->rows_count - 1 }};
        const primaryColor = '{{ $primaryColor }}';

        // Helper function to convert Hex to RGBA
        function hexToRgba(hex, alpha) {
            let c;
            if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)){
                c = hex.substring(1).split('');
                if(c.length === 3){
                    c = [c[0], c[0], c[1], c[1], c[2], c[2]];
                }
                c = '0x' + c.join('');
                return 'rgba(' + [(c>>16)&255, (c>>8)&255, c&255].join(',') + ',' + alpha + ')';
            }
            return hex;
        }

        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 240);
        gradient.addColorStop(0, hexToRgba(primaryColor, 0.25));
        gradient.addColorStop(1, hexToRgba(primaryColor, 0.0));

        const labels = Array.from({length: columnScores.length}, (_, i) => i + 1);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jawaban Benar',
                    data: columnScores,
                    borderColor: '#1f2937', // dark color for line as in screenshot
                    borderWidth: 2,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.35, // smooth curved line
                    pointBackgroundColor: '#d1fae5', // light green point fill
                    pointBorderColor: '#1f2937', // dark border for points
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: primaryColor,
                    pointHoverBorderColor: '#1f2937',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(31, 41, 55, 0.9)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            title: function(context) {
                                return 'Kolom ' + context[0].label;
                            },
                            label: function(context) {
                                return 'Jawaban Benar: ' + context.raw;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: true,
                            color: '#e5e7eb',
                            drawTicks: false
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 10,
                                weight: '500'
                            },
                            padding: 8
                        },
                        border: {
                            color: '#e5e7eb'
                        }
                    },
                    y: {
                        min: 0,
                        suggestedMax: Math.max(5, Math.max(...columnScores, 0) + 1),
                        grid: {
                            display: true,
                            color: '#e5e7eb',
                            drawTicks: false
                        },
                        ticks: {
                            stepSize: 1,
                            color: '#6b7280',
                            font: {
                                size: 10,
                                weight: '500'
                            },
                            padding: 8
                        },
                        border: {
                            color: '#e5e7eb'
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
