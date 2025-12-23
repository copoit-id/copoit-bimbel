@extends('user.layout.user')

@section('title', 'Latihan Soal')

@section('content')
@php
    $totalQuestions = $stats['total_questions'] ?? 0;
    $answeredCount = $stats['answered_count'] ?? 0;
    $progressPercent = $stats['progress_percent'] ?? 0;
    $nextUnlockRemaining = $stats['next_unlock_remaining'] ?? 0;
    $packages = $stats['packages'] ?? collect();
    $unlockedIds = $stats['unlocked_package_ids'] ?? [];
    $thresholds = $stats['unlock_thresholds'] ?? [];
@endphp

<x-page-desc title="Latihan Soal" description="Selesaikan latihan untuk membuka seluruh paket secara bertahap. Jawaban tersimpan otomatis sehingga bisa dilanjutkan kapan pun.">
</x-page-desc>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
    <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm xl:col-span-2">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500">Progress latihan</p>
                <p class="text-4xl font-semibold text-gray-900" id="practice-progress-label">{{ $answeredCount }} / {{ $totalQuestions }}</p>
            </div>
            <div class="text-sm text-gray-500">
                @if(($stats['package_count'] ?? 0) > 0)
                    <span class="font-semibold text-primary">{{ $stats['unlocked_count'] ?? 0 }}</span> paket terbuka dari {{ $stats['package_count'] }}
                @else
                    Paket belum tersedia
                @endif
            </div>
        </div>

        <div class="mt-4 h-3 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full bg-primary transition-all duration-500" style="width: {{ min(100, $progressPercent) }}%"></div>
        </div>
        <p class="mt-3 text-sm text-gray-500" id="practice-next-unlock">
            @if(!$hasQuestions)
                Belum ada soal latihan. Silakan hubungi admin untuk menambahkan bank soal.
            @elseif(($stats['package_count'] ?? 0) === 0)
                Paket akan muncul setelah admin menambahkannya.
            @elseif($nextUnlockRemaining === 0 && ($stats['unlocked_count'] ?? 0) >= ($stats['package_count'] ?? 0))
                Semua paket sudah terbuka. Tetap lanjutkan latihan untuk mempertahankan progresmu.
            @else
                Selesaikan <span class="font-semibold text-gray-800">{{ $nextUnlockRemaining }}</span> soal lagi untuk membuka paket berikutnya.
            @endif
        </p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-8">
            <div class="border border-gray-100 rounded-2xl p-6 flex flex-col justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase tracking-wide">Latihan Utama</p>
                    <h3 class="text-xl font-semibold text-gray-900 mt-1">Semua Soal Bank</h3>
                    <p class="text-sm text-gray-500 mt-2">Total {{ $totalQuestions }} soal dari seluruh bank yang disiapkan admin.</p>
                </div>
                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <a href="{{ $hasQuestions ? route('user.practice.play', ['number' => $nextQuestionNumber]) : '#' }}"
                        class="flex-1 inline-flex items-center justify-center px-4 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-primary/90 transition-colors {{ $hasQuestions ? '' : 'opacity-60 cursor-not-allowed' }}">
                        {{ $answeredCount > 0 ? 'Lanjutkan Latihan' : 'Mulai Latihan' }}
                    </a>
                    <a href="{{ route('user.package.index') }}"
                        class="inline-flex items-center justify-center px-4 py-3 rounded-xl border border-gray-200 text-gray-600 font-semibold hover:bg-gray-50">
                        Kembali ke Paket
                    </a>
                </div>
            </div>

            <div class="border border-gray-100 rounded-2xl p-6 bg-gradient-to-br from-primary/5 to-white">
                <p class="text-sm text-gray-500 uppercase tracking-wide">Tips</p>
                <h3 class="text-xl font-semibold text-gray-900 mt-1">Jawaban Auto Save</h3>
                <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                    Setiap jawaban tersimpan otomatis. Kamu bisa menutup halaman kapan saja lalu melanjutkan dari nomor terakhir.
                </p>
                <ul class="mt-4 space-y-2 text-sm text-gray-600">
                    <li class="flex items-center gap-2">
                        <i class="ri-checkbox-circle-line text-primary"></i> Tidak ada batas waktu
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="ri-checkbox-circle-line text-primary"></i> History pengerjaan disimpan
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="ri-checkbox-circle-line text-primary"></i> Buka paket secara berurutan
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900">Status Paket</h3>
        <p class="text-sm text-gray-500 mt-1">Latihan membuka paket dari kiri ke kanan.</p>
        <div class="mt-4 space-y-3">
            @forelse($packages as $package)
                @php
                    $threshold = $thresholds[$package->package_id] ?? PHP_INT_MAX;
                    $isUnlocked = in_array($package->package_id, $unlockedIds);
                    $remaining = $threshold === PHP_INT_MAX ? null : max(0, $threshold - $answeredCount);
                @endphp
                <div class="border border-gray-100 rounded-xl p-4 flex items-start gap-3 {{ $isUnlocked ? 'bg-emerald-50/60 border-emerald-100' : 'bg-gray-50' }}">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $isUnlocked ? 'bg-white text-emerald-600 border border-emerald-100' : 'bg-white text-gray-500 border border-gray-200' }}">
                        <i class="{{ $isUnlocked ? 'ri-checkbox-circle-line' : 'ri-lock-line' }} text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900">{{ $package->name }}</p>
                        <p class="text-xs text-gray-500 capitalize">{{ $package->type_package }}</p>
                        @if($threshold === PHP_INT_MAX)
                            <p class="text-xs text-gray-500 mt-1">Menunggu soal latihan.</p>
                        @elseif($isUnlocked)
                            <p class="text-xs text-emerald-600 mt-1">Sudah terbuka & bisa dibeli.</p>
                        @else
                            <p class="text-xs text-gray-500 mt-1">Butuh {{ $remaining }} soal lagi untuk membuka.</p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-sm text-gray-500 border border-dashed border-gray-200 rounded-xl p-4 text-center">
                    Belum ada paket tryout aktif.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
