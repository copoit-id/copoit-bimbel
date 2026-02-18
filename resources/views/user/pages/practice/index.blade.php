@extends('user.layout.user')

@section('title', __('Latihan Soal'))

@section('content')
@php
    $totalQuestions = $stats['total_questions'] ?? 0;
    $answeredCount = $stats['answered_count'] ?? 0;
    $progressPercent = $stats['progress_percent'] ?? 0;
    $nextUnlockRemaining = $stats['next_unlock_remaining'] ?? 0;
    $tryouts = $stats['tryouts'] ?? collect();
    $unlockedIds = $stats['unlocked_tryout_ids'] ?? [];
    $thresholds = $stats['unlock_thresholds'] ?? [];
@endphp

<x-page-desc title="{{ __('Latihan Soal') }}" description="{{ __('Selesaikan latihan untuk membuka seluruh paket secara bertahap. Jawaban tersimpan otomatis sehingga bisa dilanjutkan kapan pun.') }}">
</x-page-desc>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
    <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm xl:col-span-2">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500">{{ __('Progress latihan') }}</p>
                <p class="text-4xl font-semibold text-gray-900" id="practice-progress-label">{{ $answeredCount }} / {{ $totalQuestions }}</p>
            </div>
            <div class="text-sm text-gray-500">
                @if(($stats['tryout_count'] ?? 0) > 0)
                    <span class="font-semibold text-primary">{{ $stats['unlocked_count'] ?? 0 }}</span> {{ __('tryout terbuka dari :total', ['total' => $stats['tryout_count']]) }}
                @else
                    {{ __('Tryout belum tersedia') }}
                @endif
            </div>
        </div>

        <div class="mt-4 h-3 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full bg-primary transition-all duration-500" style="width: {{ min(100, $progressPercent) }}%"></div>
        </div>
        <p class="mt-3 text-sm text-gray-500" id="practice-next-unlock">
            @if(!$hasQuestions)
                {{ __('Belum ada soal latihan. Silakan hubungi admin untuk menambahkan bank soal.') }}
            @elseif(($stats['tryout_count'] ?? 0) === 0)
                {{ __('Tryout akan muncul setelah admin menambahkannya.') }}
            @elseif($nextUnlockRemaining === 0 && ($stats['unlocked_count'] ?? 0) >= ($stats['tryout_count'] ?? 0))
                {{ __('Semua tryout sudah terbuka. Tetap lanjutkan latihan untuk mempertahankan progresmu.') }}
            @else
                {!! __('Selesaikan <span class="font-semibold text-gray-800">:count</span> soal lagi untuk membuka tryout berikutnya.', ['count' => $nextUnlockRemaining]) !!}
            @endif
        </p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-8">
            <div class="border border-gray-100 rounded-2xl p-6 flex flex-col justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase tracking-wide">{{ __('Latihan Utama') }}</p>
                    <h3 class="text-xl font-semibold text-gray-900 mt-1">{{ __('Semua Soal Bank') }}</h3>
                    <p class="text-sm text-gray-500 mt-2">{{ __('Total :total soal dari seluruh bank yang disiapkan admin.', ['total' => $totalQuestions]) }}</p>
                </div>
                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <a href="{{ $hasQuestions ? route('user.practice.play', ['number' => $nextQuestionNumber]) : '#' }}"
                        class="flex-1 inline-flex items-center justify-center px-4 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-primary/90 transition-colors {{ $hasQuestions ? '' : 'opacity-60 cursor-not-allowed' }}">
                        {{ $answeredCount > 0 ? __('Lanjutkan Latihan') : __('Mulai Latihan') }}
                    </a>
                    <a href="{{ route('user.package.index') }}"
                        class="inline-flex items-center justify-center px-4 py-3 rounded-xl border border-gray-200 text-gray-600 font-semibold hover:bg-gray-50">
                        {{ __('Kembali ke Tryout') }}
                    </a>
                </div>
            </div>

            <div class="border border-gray-100 rounded-2xl p-6 bg-gradient-to-br from-primary/5 to-white">
                <p class="text-sm text-gray-500 uppercase tracking-wide">{{ __('Tips') }}</p>
                <h3 class="text-xl font-semibold text-gray-900 mt-1">{{ __('Jawaban Auto Save') }}</h3>
                <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                    {{ __('Setiap jawaban tersimpan otomatis. Kamu bisa menutup halaman kapan saja lalu melanjutkan dari nomor terakhir.') }}
                </p>
                <ul class="mt-4 space-y-2 text-sm text-gray-600">
                    <li class="flex items-center gap-2">
                        <i class="ri-checkbox-circle-line text-primary"></i> {{ __('Tidak ada batas waktu') }}
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="ri-checkbox-circle-line text-primary"></i> {{ __('History pengerjaan disimpan') }}
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="ri-checkbox-circle-line text-primary"></i> {{ __('Buka tryout premium secara berurutan') }}
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900">{{ __('Status Tryout') }}</h3>
        <p class="text-sm text-gray-500 mt-1">{{ __('Latihan membuka tryout premium dari kiri ke kanan.') }}</p>
        <div class="mt-4 space-y-3">
            @forelse($tryouts as $tryout)
                @php
                    $threshold = $thresholds[$tryout->tryout_id] ?? PHP_INT_MAX;
                    $isUnlocked = in_array($tryout->tryout_id, $unlockedIds);
                    $remaining = $threshold === PHP_INT_MAX ? null : max(0, $threshold - $answeredCount);
                @endphp
                <div class="border border-gray-100 rounded-xl p-4 flex items-start gap-3 {{ $isUnlocked ? 'bg-emerald-50/60 border-emerald-100' : 'bg-gray-50' }}">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $isUnlocked ? 'bg-white text-emerald-600 border border-emerald-100' : 'bg-white text-gray-500 border border-gray-200' }}">
                        <i class="{{ $isUnlocked ? 'ri-checkbox-circle-line' : 'ri-lock-line' }} text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900">{{ $tryout->name }}</p>
                        <p class="text-xs text-gray-500 capitalize">{{ $tryout->type_tryout }}</p>
                        @if($threshold === PHP_INT_MAX)
                            <p class="text-xs text-gray-500 mt-1">{{ __('Menunggu soal latihan.') }}</p>
                        @elseif($isUnlocked)
                            <p class="text-xs text-emerald-600 mt-1">{{ __('Sudah terbuka & bisa dibeli.') }}</p>
                        @else
                            <p class="text-xs text-gray-500 mt-1">{{ __('Butuh :count soal lagi untuk membuka.', ['count' => $remaining]) }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-sm text-gray-500 border border-dashed border-gray-200 rounded-xl p-4 text-center">
                    {{ __('Belum ada tryout aktif.') }}
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
