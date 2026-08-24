@extends('admin.layout.admin')

@section('title', 'Hasil Tes Koran')

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
@endphp

<div class="container mx-auto px-4">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.tes-koran.index') }}" title="Tes Koran" />
            <x-breadcrumb-item href="" title="Hasil" />
        </x-slot>
    </x-breadcrumb>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Hasil Tes: {{ $tesKoran->name }}</h2>
                <p class="text-gray-500">{{ $tesKoran->test_type == 'pauli' ? 'Pauli' : 'Kraepelin' }} - {{ $tesKoran->operationLabel() }} - {{ $tesKoran->columns_count }} kolom × {{ $tesKoran->rows_count }} baris</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.tes-koran.results.export', $tesKoran) }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    <i class="ri-download-line mr-1"></i>Download Excel
                </a>
                <a href="{{ route('admin.tes-koran.edit', $tesKoran) }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    <i class="ri-arrow-left-line mr-1"></i>Kembali
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="p-4 rounded-lg border" style="background-color: {{ $primaryColor }}08; border-color: {{ $primaryColor }}30">
                <p class="text-sm text-gray-500">Total Peserta</p>
                <p class="text-2xl font-bold" style="color: {{ $primaryColor }}">{{ $statistics['total_participants'] }}</p>
            </div>
            <div class="p-4 rounded-lg border" style="background-color: {{ $primaryColor }}08; border-color: {{ $primaryColor }}30">
                <p class="text-sm text-gray-500">Rata-rata Benar</p>
                <p class="text-2xl font-bold text-primary">{{ round($statistics['avg_correct']) }}</p>
            </div>
            <div class="p-4 rounded-lg border" style="background-color: {{ $primaryColor }}08; border-color: {{ $primaryColor }}30">
                <p class="text-sm text-gray-500">Rata-rata Akurasi</p>
                <p class="text-2xl font-bold text-primary">{{ round($statistics['avg_accuracy'], 1) }}%</p>
            </div>
            <div class="p-4 rounded-lg border" style="background-color: {{ $primaryColor }}08; border-color: {{ $primaryColor }}30">
                <p class="text-sm text-gray-500">Stabilitas Tinggi</p>
                <p class="text-2xl font-bold text-green-600">{{ $statistics['high_count'] }}</p>
            </div>
            <div class="p-4 rounded-lg border" style="background-color: {{ $primaryColor }}08; border-color: {{ $primaryColor }}30">
                <p class="text-sm text-gray-500">Stabilitas Rendah</p>
                <p class="text-2xl font-bold text-red-600">{{ $statistics['low_count'] }}</p>
            </div>
        </div>

        <!-- Distribution Chart -->
        <div class="rounded-lg p-4 mb-6" style="background-color: {{ $primaryColor }}08">
            <h4 class="font-medium text-gray-700 mb-3">Distribusi Hasil</h4>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 bg-green-500 rounded"></span>
                    <span class="text-sm">Tinggi ({{ $statistics['high_count'] }})</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 bg-yellow-500 rounded"></span>
                    <span class="text-sm">Sedang ({{ $statistics['medium_count'] }})</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 bg-red-500 rounded"></span>
                    <span class="text-sm">Rendah ({{ $statistics['low_count'] }})</span>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">Rank</th>
                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">Peserta</th>
                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase text-center">Benar</th>
                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase text-center">Salah</th>
                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase text-center">Kecepatan</th>
                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase text-center">Akurasi</th>
                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase text-center">Stabilitas</th>
                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase text-center">Hasil</th>
                        <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase text-center">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($results as $index => $result)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            @if($index == 0)
                            <span class="flex items-center justify-center w-8 h-8 text-white rounded-full font-bold" style="background-color: {{ $primaryColor }}">1</span>
                            @elseif($index == 1)
                            <span class="flex items-center justify-center w-8 h-8 text-white rounded-full font-bold" style="background-color: {{ $primaryColor }}">2</span>
                            @elseif($index == 2)
                            <span class="flex items-center justify-center w-8 h-8 text-white rounded-full font-bold" style="background-color: {{ $primaryColor }}">3</span>
                            @else
                            <span class="flex items-center justify-center w-8 h-8 bg-gray-100 text-gray-600 rounded-full">{{ $index + 1 }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($result->user->name ?? 'Unknown') }}&background=444444&color=fff"
                                     class="w-8 h-8 rounded-full">
                                <div>
                                    <p class="font-medium text-gray-800">{{ $result->user->name ?? 'Unknown' }}</p>
                                    <p class="text-xs text-gray-500">{{ $result->user->email ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center font-bold text-lg text-primary">{{ $result->total_correct }}</td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $result->total_wrong }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded text-xs" style="background-color: {{ $primaryColor }}15; color: {{ $primaryColor }}">
                                {{ round($result->speed_score) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded text-xs" style="background-color: {{ $primaryColor }}15; color: {{ $primaryColor }}">
                                {{ round($result->accuracy_score, 1) }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($result->stability_status == 'meningkat')
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">
                                <i class="ri-arrow-up-line mr-1"></i>Meningkat
                            </span>
                            @elseif($result->stability_status == 'menurun')
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">
                                <i class="ri-arrow-down-line mr-1"></i>Menurun
                            </span>
                            @else
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">
                                <i class="ri-subtract-line mr-1"></i>Datar
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($result->final_result == 'tinggi')
                            <span class="px-3 py-1 bg-green-500 text-white rounded-full text-xs font-medium">Tinggi</span>
                            @elseif($result->final_result == 'sedang')
                            <span class="px-3 py-1 bg-yellow-500 text-white rounded-full text-xs font-medium">Sedang</span>
                            @else
                            <span class="px-3 py-1 bg-red-500 text-white rounded-full text-xs font-medium">Rendah</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-500">
                            @if($result->finished_at)
                            {{ $result->finished_at->format('d M H:i') }}
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                            <i class="ri-file-list-3-line text-4xl text-gray-300 mb-2 block"></i>
                            <p>Belum ada peserta yang mengerjakan tes ini</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($results->hasPages())
        <div class="mt-4">
            {{ $results->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
