@extends('user.layout.new-user')

@section('title', 'Hasil Tes - ' . $tesKoran->name)

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$columnScores = $result->column_scores ?? [];
$hasColumnProgress = collect($columnScores)->contains(fn($score) => (int) $score > 0);
$scoreOffset = 0;
$sheetChartData = $tesKoran->sheetConfigs()
    ->map(function (array $sheet, int $sheetIndex) use (&$scoreOffset, $columnScores) {
        $columnsCount = max(0, (int) ($sheet['columns_count'] ?? 0));
        $scores = array_slice($columnScores, $scoreOffset, $columnsCount);
        $scoreOffset += $columnsCount;

        return [
            'name' => $sheet['name'] ?? 'Lembar ' . ($sheetIndex + 1),
            'scores' => array_values(array_map(fn($score) => (int) $score, $scores)),
            'max_score' => max(1, ((int) ($sheet['rows_count'] ?? 10)) - 1),
            'columns_count' => $columnsCount,
            'has_progress' => collect($scores)->contains(fn($score) => (int) $score > 0),
        ];
    })
    ->filter(fn(array $sheet) => count($sheet['scores']) > 0)
    ->values();

if ($sheetChartData->isEmpty() && count($columnScores) > 0) {
    $sheetChartData = collect([[
        'name' => 'Lembar 1',
        'scores' => array_values(array_map(fn($score) => (int) $score, $columnScores)),
        'max_score' => max(1, (int) ($tesKoran->rows_count ?? 10) - 1),
        'columns_count' => count($columnScores),
        'has_progress' => $hasColumnProgress,
    ]]);
} elseif ($scoreOffset < count($columnScores)) {
    $remainingScores = array_slice($columnScores, $scoreOffset);
    $sheetChartData->push([
        'name' => 'Lembar Tambahan',
        'scores' => array_values(array_map(fn($score) => (int) $score, $remainingScores)),
        'max_score' => max(1, (int) ($tesKoran->rows_count ?? 10) - 1),
        'columns_count' => count($remainingScores),
        'has_progress' => collect($remainingScores)->contains(fn($score) => (int) $score > 0),
    ]);
}
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

    <!-- Sheet Charts -->
    @if($sheetChartData->isNotEmpty())
    <div class="bg-white border border-gray-100 rounded-2xl p-6 mb-6">
        <div class="flex items-center justify-between gap-4 mb-6">
            <div>
                <h4 class="text-lg font-bold text-gray-800">Jawaban Benar per Lembar</h4>
                <p class="text-xs text-gray-500">Menampilkan grafik jawaban benar di setiap kolom untuk masing-masing lembar.</p>
            </div>
            @unless($hasColumnProgress)
            <span class="px-3 py-1 rounded-full bg-gray-200 text-gray-600 text-xs font-medium">
                Semua kolom 0
            </span>
            @endunless
        </div>

        @if($sheetChartData->count() > 1)
        <div class="flex gap-2 overflow-x-auto pb-2 mb-5" role="tablist" aria-label="Pilih grafik lembar">
            @foreach($sheetChartData as $sheetIndex => $sheetChart)
            <button type="button"
                    class="sheet-chart-tab shrink-0 px-4 py-2 rounded-lg border text-sm font-semibold transition-colors {{ $sheetIndex === 0 ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 hover:bg-gray-50' }}"
                    data-sheet-tab="{{ $sheetIndex }}"
                    role="tab"
                    aria-selected="{{ $sheetIndex === 0 ? 'true' : 'false' }}"
                    aria-controls="sheetChartPanel{{ $sheetIndex }}"
                    style="{{ $sheetIndex === 0 ? 'background-color: ' . $primaryColor : '' }}">
                {{ $sheetChart['name'] }}
            </button>
            @endforeach
        </div>
        @endif

        <div>
            @foreach($sheetChartData as $sheetIndex => $sheetChart)
            <div id="sheetChartPanel{{ $sheetIndex }}"
                 class="sheet-chart-panel rounded-xl border border-gray-100 p-4 {{ $sheetIndex === 0 ? '' : 'hidden' }}"
                 data-sheet-panel="{{ $sheetIndex }}"
                 role="tabpanel">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
                    <div>
                        <h5 class="font-semibold text-gray-800">{{ $sheetChart['name'] }}</h5>
                        <p class="text-xs text-gray-500">
                            {{ count($sheetChart['scores']) }} kolom
                            <span class="mx-1">•</span>
                            Maks {{ $sheetChart['max_score'] }} benar per kolom
                        </p>
                    </div>
                    @unless($sheetChart['has_progress'])
                    <span class="self-start sm:self-center px-3 py-1 rounded-full bg-gray-100 text-gray-500 text-xs font-medium">
                        Lembar ini 0
                    </span>
                    @endunless
                </div>

                <div class="relative w-full overflow-x-auto pb-2">
                    <div class="h-64" style="min-width: {{ max(420, count($sheetChart['scores']) * 28) }}px; width: 100%;">
                        <canvas id="columnScoresChart{{ $sheetIndex }}"></canvas>
                    </div>
                </div>
            </div>
            @endforeach
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
        const sheetCharts = @json($sheetChartData);
        const primaryColor = '{{ $primaryColor }}';
        const renderedCharts = {};

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

        sheetCharts.forEach(function(sheet, sheetIndex) {
            const canvas = document.getElementById('columnScoresChart' + sheetIndex);
            if (!canvas) return;

            const scores = sheet.scores || [];
            const gradient = canvas.getContext('2d').createLinearGradient(0, 0, 0, 240);
            gradient.addColorStop(0, hexToRgba(primaryColor, 0.25));
            gradient.addColorStop(1, hexToRgba(primaryColor, 0.0));

            renderedCharts[sheetIndex] = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: scores.map(function(_, index) {
                        return index + 1;
                    }),
                    datasets: [{
                        label: 'Jawaban Benar',
                        data: scores,
                        borderColor: '#1f2937',
                        borderWidth: 2,
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#d1fae5',
                        pointBorderColor: '#1f2937',
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
                                    return sheet.name + ' - Kolom ' + context[0].label;
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
                            suggestedMax: Math.max(sheet.max_score || 1, Math.max(...scores, 0) + 1),
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

        const tabs = document.querySelectorAll('[data-sheet-tab]');
        const panels = document.querySelectorAll('[data-sheet-panel]');

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                const activeIndex = tab.dataset.sheetTab;

                tabs.forEach(function(item) {
                    const isActive = item.dataset.sheetTab === activeIndex;
                    item.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    item.classList.toggle('text-white', isActive);
                    item.classList.toggle('border-transparent', isActive);
                    item.classList.toggle('text-gray-600', !isActive);
                    item.classList.toggle('border-gray-200', !isActive);
                    item.classList.toggle('hover:bg-gray-50', !isActive);
                    item.style.backgroundColor = isActive ? primaryColor : '';
                });

                panels.forEach(function(panel) {
                    panel.classList.toggle('hidden', panel.dataset.sheetPanel !== activeIndex);
                });

                if (renderedCharts[activeIndex]) {
                    renderedCharts[activeIndex].resize();
                }
            });
        });
    });
</script>
@endpush
