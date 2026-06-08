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
    <div class="bg-gray-50 rounded-xl p-4">
        <div class="flex items-center justify-between gap-4 mb-3">
            <div>
                <h4 class="font-medium text-gray-700">Grafik Per Kolom</h4>
                <p class="text-xs text-gray-500">Menampilkan jumlah jawaban benar di setiap kolom.</p>
            </div>
            @unless($hasColumnProgress)
            <span class="px-3 py-1 rounded-full bg-gray-200 text-gray-600 text-xs font-medium">
                Semua kolom 0
            </span>
            @endunless
        </div>

        <div class="flex items-end gap-1 h-40 border-b border-gray-200 pb-2">
            @foreach($columnScores as $index => $score)
            <div class="flex-1 flex flex-col items-center">
                <span class="text-[10px] text-gray-500 mb-1">{{ (int) $score }}</span>
                <div
                    class="w-full min-h-[6px] rounded-t"
                    style="height: {{ $score > 0 ? max(12, ($score / $maxColumnScore) * 100) : 6 }}%; background-color: {{ $score > 0 ? $primaryColor : '#d1d5db' }};">
                </div>
                <span class="text-[10px] text-gray-400 mt-1">{{ $index + 1 }}</span>
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
