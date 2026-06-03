@extends('user.layout.new-user')

@section('title', 'Riwayat Tes Koran')

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
@endphp

<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('user.tes-koran.index') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Riwayat Tes Koran</h1>
        <p class="text-gray-500 text-sm">Hasil pengerjaan Pauli dan Kraepelin</p>
    </div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3">Tes</th>
                    <th class="px-6 py-3 text-center">Benar</th>
                    <th class="px-6 py-3 text-center">Salah</th>
                    <th class="px-6 py-3 text-center">Akurasi</th>
                    <th class="px-6 py-3 text-center">Hasil</th>
                    <th class="px-6 py-3 text-center">Tanggal</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($results as $result)
                <tr class="border-b border-dashed border-gray-200">
                    <td class="px-6 py-4">
                        <p class="font-semibold text-gray-800">{{ $result->tesKoran?->name ?? 'Tes Koran' }}</p>
                        <p class="text-xs text-gray-500">{{ ucfirst($result->tesKoran?->test_type ?? 'tes') }}</p>
                    </td>
                    <td class="px-6 py-4 text-center">{{ $result->total_correct }}</td>
                    <td class="px-6 py-4 text-center">{{ $result->total_wrong }}</td>
                    <td class="px-6 py-4 text-center">{{ round($result->accuracy_score, 1) }}%</td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ ucfirst($result->final_result) }}</span>
                    </td>
                    <td class="px-6 py-4 text-center text-gray-500">{{ $result->created_at->format('d M Y, H:i') }}</td>
                    <td class="px-6 py-4 text-center">
                        @if($result->tesKoran)
                        <a href="{{ route('user.tes-koran.result', [$result->tesKoran, $result]) }}"
                           class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-white"
                           style="background-color: {{ $primaryColor }}">
                            Detail
                        </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                        <i class="ri-history-line text-4xl block mb-2"></i>
                        Belum ada riwayat tes koran
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($results->hasPages())
<div class="mt-4">
    {{ $results->links() }}
</div>
@endif
@endsection
