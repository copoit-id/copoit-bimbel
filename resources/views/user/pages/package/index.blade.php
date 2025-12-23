@extends('user.layout.user')
@section('title', 'Paket Pembelian')
@section('content')
@php
    $typePriceLabels = [
        'free_unconditional' => ['label' => 'Gratis', 'class' => 'bg-emerald-50 text-emerald-700 border border-emerald-100'],
        'free_conditional' => ['label' => 'Gratis Bersyarat', 'class' => 'bg-amber-50 text-amber-700 border border-amber-100'],
        'paid' => ['label' => 'Berbayar', 'class' => 'bg-blue-50 text-blue-700 border border-blue-100'],
    ];
    $typePackageLabels = [
        'bimbel' => 'Bimbel',
        'tryout' => 'Tryout',
        'sertifikasi' => 'Sertifikasi',
    ];
    $practiceData = $practiceStats ?? [];
    $practiceUnlockedIds = array_map('intval', $practiceData['unlocked_package_ids'] ?? []);
    $practiceAnswered = $practiceData['answered_count'] ?? 0;
    $practiceTotalQuestions = $practiceData['total_questions'] ?? 0;
    $practicePercent = $practiceData['progress_percent'] ?? 0;
    $practiceNextRemaining = $practiceData['next_unlock_remaining'] ?? 0;
    $practiceUnlockedCount = $practiceData['unlocked_count'] ?? 0;
    $practicePackageCount = $practiceData['package_count'] ?? 0;
    $practiceThresholdMap = $practiceData['unlock_thresholds'] ?? [];
    $practiceAveragePerPackage = ($practicePackageCount > 0 && $practiceTotalQuestions > 0)
        ? max(1, (int) ceil($practiceTotalQuestions / $practicePackageCount))
        : null;
@endphp
<div class="dashboard">
    <x-page-desc title="Paket" description="Semua paket berada dalam satu daftar. Buka kuncinya melalui latihan soal, lalu pilih paket yang kamu butuhkan."></x-page-desc>

    <div class="mt-6 bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500">Progress Latihan Soal</p>
                <p class="text-3xl font-semibold text-gray-900">{{ $practiceAnswered }} / {{ $practiceTotalQuestions }}</p>
            </div>
            <div class="text-sm text-gray-500">
                @if($practicePackageCount > 0)
                    <span class="font-semibold text-primary">{{ $practiceUnlockedCount }}</span> paket terbuka dari {{ $practicePackageCount }}
                @else
                    Belum ada paket aktif
                @endif
            </div>
            <a href="{{ route('user.practice.index') }}"
                class="inline-flex justify-center items-center px-5 py-2 rounded-xl border border-primary text-primary font-semibold hover:bg-primary hover:text-white transition-colors">
                Kerjakan Latihan
            </a>
        </div>
        <div class="mt-4 h-3 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full bg-primary transition-all duration-500" style="width: {{ min(100, $practicePercent) }}%"></div>
        </div>
        <p class="mt-3 text-sm text-gray-500" id="package-practice-hints">
            @if($practicePackageCount === 0)
                Paket akan muncul setelah admin menambahkannya.
            @elseif($practiceTotalQuestions === 0)
                Latihan belum memiliki soal. Hubungi admin untuk mengaktifkan latihan.
            @elseif($practiceNextRemaining === 0 && $practiceUnlockedCount >= $practicePackageCount)
                Semua paket sudah terbuka. Tetap lanjutkan latihan untuk mempertahankan progresmu.
            @else
                Selesaikan <span class="font-semibold text-gray-800">{{ $practiceNextRemaining }}</span> soal lagi untuk membuka paket berikutnya
                @if($practiceAveragePerPackage)
                    (rata-rata {{ $practiceAveragePerPackage }} soal/paket).
                @endif
            @endif
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mt-6 text-gray-600">
        @forelse($packages as $package)
        @php
            $thumbExt = $package->image ? strtolower(pathinfo($package->image, PATHINFO_EXTENSION)) : null;
            $thumbIsVideo = $package->image ? in_array($thumbExt, ['mp4','webm','mov','m4v'], true) : false;
            $thumbUrl = $package->image ? Storage::url($package->image) : null;
            $typePriceStyle = $typePriceLabels[$package->type_price] ?? null;
            $packageThreshold = $practiceThresholdMap[$package->package_id] ?? PHP_INT_MAX;
            $isUnlocked = in_array($package->package_id, $practiceUnlockedIds, true);
            $remainingToUnlock = $packageThreshold === PHP_INT_MAX ? null : max(0, $packageThreshold - $practiceAnswered);
            $typeLabel = $typePackageLabels[$package->type_package] ?? ucfirst($package->type_package);
            $detailRoute = match ($package->type_package) {
                'bimbel' => route('user.package.bimbel', $package->package_id),
                'tryout' => route('user.package.tryout', $package->package_id),
                'sertifikasi' => route('user.package.tryout', $package->package_id),
                default => '#',
            };
        @endphp
        <div class="flex flex-col justify-between bg-white px-5 py-5 shadow rounded-xl border border-gray-100">
            <div>
                <div class="w-full h-36 bg-gray-200 rounded-xl mb-4 overflow-hidden">
                    @if($package->image)
                        @if($thumbIsVideo)
                            <video src="{{ $thumbUrl }}" class="w-full h-full object-cover" controls preload="metadata" playsinline></video>
                        @else
                            <img src="{{ $thumbUrl }}" alt="{{ $package->name }}" class="w-full h-full object-cover">
                        @endif
                    @else
                        <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                            <i class="ri-image-line text-3xl text-gray-400"></i>
                        </div>
                    @endif
                </div>
                <div class="flex flex-wrap items-center justify-between text-xs font-semibold mb-2 gap-2">
                    @if($typePriceStyle)
                    <span class="px-3 py-1 rounded-full {{ $typePriceStyle['class'] }}">
                        {{ $typePriceStyle['label'] }}
                    </span>
                    @endif
                    <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-100 capitalize text-gray-600">
                        {{ $typeLabel }}
                    </span>
                    @if(!$isUnlocked)
                        <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-600 border border-gray-200 flex items-center gap-1">
                            <i class="ri-lock-2-line text-xs"></i> Terkunci
                        </span>
                    @endif
                </div>
                <p class="text-lg font-bold text-black">{{ $package->name }}</p>
                <p class="font-light">{{ $package->description }}</p>
                @if($package->type_price === 'paid')
                <span class="font-bold text-black mt-1 block">Rp {{ number_format($package->price, 0, ',', '.') }}</span>
                @else
                <span class="font-bold text-black mt-1 block">{{ $typePriceLabels[$package->type_price]['label'] ?? 'Gratis' }}</span>
                @endif

                <div class="flex flex-col mt-4 gap-3 font-light min-h-[110px]">
                    @if($package->features)
                    @foreach (json_decode($package->features) as $feature)
                    <span class="flex items-center gap-2">
                        <i class="ri-checkbox-circle-fill text-green-500"></i>
                        {{ $feature }}
                    </span>
                    @endforeach
                    @endif
                </div>
            </div>

            <div class="mt-4 space-y-2">
                @if(!$isUnlocked)
                <div class="px-4 py-3 rounded-lg bg-gray-100 border border-gray-200 text-sm text-gray-600 flex items-center gap-2">
                    <i class="ri-lock-line text-lg"></i>
                    @if(is_null($remainingToUnlock))
                        Paket terkunci. Selesaikan latihan saat tersedia.
                    @else
                        Paket terkunci. Butuh <strong>{{ $remainingToUnlock }} soal</strong> lagi untuk membuka.
                    @endif
                </div>
                <button type="button"
                    class="w-full border border-dashed border-gray-300 text-gray-500 px-4 py-3 rounded-lg font-semibold text-center flex items-center justify-center gap-2 cursor-not-allowed">
                    <i class="ri-lock-2-line text-lg"></i> Tombol Terkunci
                </button>
                <a href="{{ route('user.practice.index') }}"
                    class="w-full border border-primary text-primary px-4 py-3 rounded-lg font-semibold text-center block hover:bg-primary hover:text-white transition-colors">
                    Buka Lewat Latihan Soal
                </a>
                @elseif($package->user_access_count > 0)
                <a href="{{ $detailRoute }}"
                    class="w-full bg-green-600 text-white px-4 py-3 rounded-lg font-bold text-center block">
                    SUDAH DIBELI
                </a>
                @else
                <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST"
                    class="buy-package-form">
                    @csrf
                    <button type="submit" class="w-full bg-primary text-white px-4 py-3 rounded-lg font-bold flex items-center justify-center gap-2">
                        <i class="ri-shopping-bag-3-line text-lg"></i> BELI SEKARANG
                    </button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-8">
            <p class="text-gray-500">Belum ada paket tersedia saat ini.</p>
        </div>
        @endforelse
    </div>
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
    <div class="bg-white p-6 rounded-lg">
        <div class="flex items-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mr-3"></div>
            <p>Memproses pembayaran...</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $('.buy-package-form').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const button = form.find('button[type="submit"]');
            const originalText = button.text();

            button.prop('disabled', true).text('Memproses...');
            $('#loadingModal').removeClass('hidden').addClass('flex');

            const formData = new FormData(this);

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#loadingModal').addClass('hidden').removeClass('flex');

                    if (response.redirect_url) {
                        window.location.href = response.redirect_url;
                        return;
                    }

                    if (response.success) {
                        if (response.message) {
                            alert(response.message);
                        }
                        location.reload();
                        return;
                    }

                    button.prop('disabled', false).text(originalText);
                },
                error: function(xhr) {
                    $('#loadingModal').addClass('hidden').removeClass('flex');
                    button.prop('disabled', false).text(originalText);

                    let errorMessage = 'Terjadi kesalahan. Silakan coba lagi.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    alert(errorMessage);
                }
            });
        });
    });
</script>
@endsection

@section('styles')
<style>
    .buy-package-form button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>
@endsection
