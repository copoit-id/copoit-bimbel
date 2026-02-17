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
    $isDevisadia = Auth::user()->is_devisadia_student ?? false;
    $freeTryouts = $tryouts->where('is_premium', false)->values();
    $premiumTryouts = $tryouts->where('is_premium', true)->values();
    $hasFreeTryouts = $freeTryouts->isNotEmpty();
    $hasPremiumTryouts = $premiumTryouts->isNotEmpty();
@endphp
<div class="dashboard">
    <x-page-desc title="{{ __('Paket') }}" description="{{ __('Pilihan paket gratis hingga berbayar') }}"></x-page-desc>
    <div class="flex justify-end mt-4">
        <a href="{{ route('user.package.riwayatPembelian') }}" class="text-blue-600 underline">{{ __('Riwayat Pembelian') }}</a>
    </div>

    <!-- Tryout List -->
    @if(!$hasFreeTryouts && !$hasPremiumTryouts)
        <div class="text-center py-8 mt-6">
            <p class="text-gray-500">{{ __('Belum ada tryout tersedia') }}</p>
        </div>
    @endif

    @if($hasFreeTryouts && $hasPremiumTryouts)
        <div class="mt-6 flex gap-2">
            <button type="button" class="tryout-tab-btn px-4 py-2 rounded-lg text-sm font-medium bg-primary text-white"
                data-target="free">
                {{ __('Tryout Free') }} ({{ $freeTryouts->count() }})
            </button>
            <button type="button" class="tryout-tab-btn px-4 py-2 rounded-lg text-sm font-medium border border-primary text-primary"
                data-target="premium">
                {{ __('Tryout Premium') }} ({{ $premiumTryouts->count() }})
            </button>
        </div>
    @endif

    @if($hasFreeTryouts)
        <div id="tryout-tab-free" class="tryout-tab-section mt-6">
            <h3 class="text-base font-semibold text-gray-800">{{ __('Tryout Free') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mt-3 text-gray-600">
                @foreach($freeTryouts as $tryout)
                    @include('user.pages.package.partials.tryout-card', ['tryout' => $tryout])
                @endforeach
            </div>
        </div>
    @endif

    @if($hasPremiumTryouts)
        <div id="tryout-tab-premium" class="tryout-tab-section mt-8 {{ $hasFreeTryouts && $hasPremiumTryouts ? 'hidden' : '' }}">
            <h3 class="text-base font-semibold text-gray-800">{{ __('Tryout Premium') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mt-3 text-gray-600">
                @foreach($premiumTryouts as $tryout)
                    @include('user.pages.package.partials.tryout-card', ['tryout' => $tryout])
                @endforeach
            </div>
        </div>
    @endif

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
        const tabButtons = $('.tryout-tab-btn');
        const tabSections = $('.tryout-tab-section');

        function activateTryoutTab(target) {
            tabSections.addClass('hidden');
            $(`#tryout-tab-${target}`).removeClass('hidden');

            tabButtons
                .removeClass('bg-primary text-white')
                .addClass('border border-primary text-primary');

            tabButtons.filter(`[data-target="${target}"]`)
                .removeClass('border border-primary text-primary')
                .addClass('bg-primary text-white');
        }

        tabButtons.on('click', function () {
            activateTryoutTab($(this).data('target'));
        });

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
