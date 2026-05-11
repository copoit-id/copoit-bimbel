@extends('user.layout.new-user')

@section('title', 'Hasil Tes - ' . $tesKoran->name)

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$columnScores = $result->column_scores ?? [];
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
            @if($result->final_result == 'tinggi')
            <span class="px-4 py-2 bg-green-500 text-white rounded-full font-bold">
                <i class="ri-emotion-happy-line mr-1"></i>TINGGI
            </span>
            @elseif($result->final_result == 'sedang')
            <span class="px-4 py-2 bg-yellow-500 text-white rounded-full font-bold">
                <i class="ri-emotion-normal-line mr-1"></i>SEDANG
            </span>
            @else
            <span class="px-4 py-2 bg-red-500 text-white rounded-full font-bold">
                <i class="ri-emotion-unhappy-line mr-1"></i>RENDAH
            </span>
            @endif
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-green-600">{{ $result->total_correct }}</div>
            <div class="text-sm text-green-700">Jawaban Benar</div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-red-600">{{ $result->total_wrong }}</div>
            <div class="text-sm text-red-700">Jawaban Salah</div>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-blue-600">{{ round($result->accuracy_score, 1) }}%</div>
            <div class="text-sm text-blue-700">Akurasi</div>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-purple-600">{{ round($result->stability_score) }}</div>
            <div class="text-sm text-purple-700">Skor Stabilitas</div>
        </div>
    </div>

    <!-- Stability Status -->
    <div class="bg-gray-50 rounded-xl p-4 mb-6">
        <h4 class="font-medium text-gray-700 mb-3">Status Stabilitas</h4>
        <div class="flex items-center gap-4">
            @if($result->stability_status == 'meningkat')
            <span class="flex items-center gap-2 text-green-600">
                <i class="ri-arrow-up-line text-2xl"></i>
                <span class="font-bold">MENINGKAT</span>
            </span>
            <span class="text-sm text-gray-600">Grafik menunjukkan peningkatan - motivasi dan ketahanan tinggi</span>
            @elseif($result->stability_status == 'menurun')
            <span class="flex items-center gap-2 text-red-600">
                <i class="ri-arrow-down-line text-2xl"></i>
                <span class="font-bold">MENURUN</span>
            </span>
            <span class="text-sm text-gray-600">Grafik menurun - menunjukkan kelelahan atau penurunan motivasi</span>
            @else
            <span class="flex items-center gap-2 text-yellow-600">
                <i class="ri-subtract-line text-2xl"></i>
                <span class="font-bold">DATAR</span>
            </span>
            <span class="text-sm text-gray-600">Grafik stabil - menunjukkan konsistensi diri yang baik</span>
            @endif
        </div>
    </div>

    <!-- Column Chart -->
    @if(count($columnScores) > 0)
    <div class="bg-gray-50 rounded-xl p-4">
        <h4 class="font-medium text-gray-700 mb-3">Grafik Per Kolom</h4>
        <div class="flex items-end gap-1 h-32">
            @foreach($columnScores as $index => $score)
            <div class="flex-1 flex flex-col items-center">
                <div class="w-full bg-primary rounded-t" style="height: {{ max(10, ($score / max(array_merge($columnScores, [1])) * 100) }}%"></div>
                <span class="text-xs text-gray-400 mt-1">{{ $index + 1 }}</span>
            </div>
            @endforeach
        </div>
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