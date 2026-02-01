@extends('user.layout.user')
@section('title', __('Paket Pembelian'))
@section('content')
@php
    $typePriceLabels = [
        'free_unconditional' => ['label' => 'Gratis', 'class' => 'bg-emerald-50 text-emerald-700 border border-emerald-100'],
        'free_conditional' => ['label' => 'Gratis Bersyarat', 'class' => 'bg-amber-50 text-amber-700 border border-amber-100'],
    ];
    $paymentMode = config('client.branding.payment_mode', 'gateway');
    $kelasPackages = $kelasPackages ?? collect();
    $tryouts = $tryouts ?? collect();
    $sertifikasiPackages = $sertifikasiPackages ?? collect();
    $practiceStats = $practiceStats ?? [];
    $premiumAccessIds = $premiumAccessIds ?? [];
    $unlockedTryoutIds = $practiceStats['unlocked_tryout_ids'] ?? [];
@endphp
<div class="dashboard">
    <x-page-desc title="{{ __('Paket') }}" description="{{ __('Pilihan paket gratis hingga berbayar') }}"></x-page-desc>
    <div class="flex justify-end mt-4">
        <a href="{{ route('user.package.riwayatPembelian') }}" class="text-blue-600 underline">{{ __('Riwayat Pembelian') }}</a>
    </div>

    <!-- Tryout List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mt-6 text-gray-600">
        @forelse($tryouts as $tryout)
        @php
            $tryoutDetail = $tryout->tryoutDetails->first();
            $questionCount = 0;
            if ($tryoutDetail) {
                $questionCount = \App\Models\Question::where('tryout_detail_id', $tryoutDetail->tryout_detail_id)->count();
            }
            $primaryPackage = $tryout->primaryPackage ?? null;
            $packageId = $primaryPackage?->package_id ?? 'free';
            $packageName = $primaryPackage?->name;
            $userAttempts = $tryout->userAnswers->count();
            $lastAttempt = $tryout->userAnswers->sortByDesc('created_at')->first();
            $isUnlocked = in_array($tryout->tryout_id, $unlockedTryoutIds, true);
            $hasPremiumAccess = in_array($tryout->tryout_id, $premiumAccessIds, true);
            $canAccess = $isUnlocked && (!$tryout->is_premium || $hasPremiumAccess);
        @endphp
        <div class="flex flex-col justify-between bg-white px-5 py-5 shadow rounded-lg">
            <div>
                <div class="flex items-center justify-between text-xs font-semibold mb-2">
                    <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-100 capitalize text-gray-600">
                        {{ __($tryout->type_tryout) }}
                    </span>
                    @if($tryout->is_premium)
                        <span class="px-2 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-100">
                            {{ __('Premium') }}
                        </span>
                    @endif
                </div>
                <p class="text-lg font-bold text-black">{{ $tryout->name }}</p>
                @if($packageName)
                    <p class="text-xs text-gray-500 mt-1">{{ __('Paket') }}: {{ $packageName }}</p>
                @endif
                <div class="flex flex-col mt-4 gap-2 font-light text-sm">
                    <span class="flex items-center justify-between">
                        <p class="font-medium">{{ __('Jumlah Soal') }}:</p>
                        <p class="font-light">{{ $questionCount }} {{ __('Soal') }}</p>
                    </span>
                    <span class="flex items-center justify-between">
                        <p class="font-medium">{{ __('Durasi') }}:</p>
                        <p class="font-light">{{ $tryoutDetail ? $tryoutDetail->duration : 0 }} {{ __('Menit') }}</p>
                    </span>
                    <span class="flex items-center justify-between">
                        <p class="font-medium">{{ __('Dikerjakan') }}:</p>
                        <p class="font-light">{{ $userAttempts }} {{ __('Kali') }}</p>
                    </span>
                    @if($lastAttempt)
                        <span class="flex items-center justify-between">
                            <p class="font-medium">{{ __('Skor Terakhir') }}:</p>
                            <p class="font-light {{ $lastAttempt->percentage >= 70 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($lastAttempt->percentage ?? 0, 1) }}%
                            </p>
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2 font-light mt-4">
                @if($tryout->is_premium && !$hasPremiumAccess)
                    <button type="button"
                        class="flex-1 min-w-0 flex justify-center bg-gray-200 text-gray-500 px-3 py-2 rounded-lg text-[13px] sm:text-sm leading-tight cursor-not-allowed truncate"
                        disabled>
                        {{ __('Premium - Hubungi Admin') }}
                    </button>
                @elseif(!$isUnlocked)
                    <button type="button"
                        class="flex-1 min-w-0 flex justify-center bg-gray-200 text-gray-500 px-3 py-2 rounded-lg text-[13px] sm:text-sm leading-tight cursor-not-allowed truncate"
                        disabled>
                        {{ __('Terkunci - Selesaikan Latihan') }}
                    </button>
                @else
                    @if($questionCount > 0)
                        <a href="{{ route('user.tryout.lobby', ['id_package' => $packageId, 'id_tryout' => $tryout->tryout_id]) }}"
                            class="flex-1 min-w-0 flex justify-center bg-primary text-white px-3 py-2 rounded-lg text-[13px] sm:text-sm leading-tight hover:bg-primary/90 transition-colors truncate">
                            {{ __('Kerjakan') }}
                        </a>
                    @else
                        <button type="button"
                            class="flex-1 min-w-0 flex justify-center bg-gray-400 text-white px-3 py-2 rounded-lg text-[13px] sm:text-sm leading-tight cursor-not-allowed truncate" disabled>
                            {{ __('Belum Ada Soal') }}
                        </button>
                    @endif

                    @if($userAttempts > 0)
                        <a href="{{ route('user.package.tryout.riwayat', ['id_package' => $packageId, 'id_tryout' => $tryout->tryout_id]) }}"
                            class="flex-1 min-w-0 flex justify-center border border-primary text-primary px-3 py-2 rounded-lg text-[13px] sm:text-sm leading-tight hover:bg-primary hover:text-white transition-colors truncate">
                            {{ __('Riwayat') }}
                        </a>
                    @endif

                    <a href="{{ route('user.package.tryout.ranking', ['id_package' => $packageId, 'id_tryout' => $tryout->tryout_id]) }}"
                        class="flex-none w-9 h-9 flex items-center justify-center border border-primary text-primary rounded-lg text-sm hover:bg-primary hover:text-white transition-colors">
                        <i class="ri-bar-chart-2-fill"></i>
                    </a>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-8">
            <p class="text-gray-500">{{ __('Belum ada tryout tersedia') }}</p>
        </div>
        @endforelse
    </div>

</div>

<!-- Loading Modal -->
<div id="loadingModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
    <div class="bg-white p-6 rounded-lg">
        <div class="flex items-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mr-3"></div>
            <p>{{ __('Memproses pembayaran...') }}</p>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function () {

        function toggleModal(id, show) {
            const modal = $(`[data-modal="${id}"]`);
            if (!modal.length) return;

            if (show) {
                modal.removeClass('hidden');
                $('html').addClass('overflow-hidden');
            } else {
                modal.addClass('hidden');
                if (!$('.payment-modal:not(.hidden)').length) {
                    $('html').removeClass('overflow-hidden');
                }
            }
        }

        $('[data-modal-open]').on('click', function () {
            toggleModal($(this).data('modal-open'), true);
        });

        $('[data-modal-close]').on('click', function () {
            toggleModal($(this).data('modal-close'), false);
        });

        $('[data-modal-overlay]').on('click', function (e) {
            if (e.target !== this) return;
            toggleModal($(this).data('modal-overlay'), false);
        });

        $('[data-modal-panel]').on('click', function (e) {
            e.stopPropagation();
        });

        // Handle buy package form submission
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
