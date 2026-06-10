@extends('user.layout.user')
@section('title', 'Event Gratis')
@section('content')
@php
    $tabConfig = [
        'kelas' => ['label' => 'Kelas', 'packages' => $kelasPackages],
        'tryout' => ['label' => 'Tryout', 'packages' => $tryoutPackages],
        'sertifikasi' => ['label' => 'Sertifikasi', 'packages' => $sertifikasiPackages],
    ];
    $typePriceLabels = [
        'free_unconditional' => ['label' => 'Gratis', 'class' => 'bg-emerald-50 text-emerald-700 border border-emerald-100'],
        'free_conditional' => ['label' => 'Gratis Bersyarat', 'class' => 'bg-amber-50 text-amber-700 border border-amber-100'],
    ];
    $paymentMode = config('client.branding.payment_mode', 'gateway');
@endphp
<div class="dashboard space-y-6">
    <x-page-desc title="Event Gratis" description="Paket gratis dengan syarat khusus untuk komunitasmu"></x-page-desc>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex gap-2">
            @foreach($tabConfig as $key => $config)
            <button id="btn-{{ $key }}" type="button"
                class="tab-btn px-6 py-1.5 rounded-xl cursor-pointer {{ $loop->first ? 'bg-primary text-white' : 'border border-primary text-primary' }}">
                {{ $config['label'] }}
            </button>
            @endforeach
        </div>
        <a href="{{ route('user.package.riwayatPembelian') }}"
            class="text-primary text-sm font-semibold hover:underline">Riwayat Paket</a>
    </div>

    @foreach($tabConfig as $key => $config)
    <div id="{{ $key }}-package"
        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 {{ $loop->first ? '' : 'hidden' }}">
        @forelse($config['packages'] as $package)
        @php
            $thumbExt = $package->image ? strtolower(pathinfo($package->image, PATHINFO_EXTENSION)) : null;
            $thumbIsVideo = $package->image ? in_array($thumbExt, ['mp4','webm','mov','m4v'], true) : false;
            $thumbUrl = $package->image ? Storage::url($package->image) : null;
            $tagStyle = $typePriceLabels[$package->type_price] ?? ['label' => 'Gratis', 'class' => 'bg-gray-100 text-gray-700 border border-gray-200'];
            $featureList = $package->features ? json_decode($package->features, true) : [];
            $currentAccess = ($package->userAccess ?? collect())->first();
            $isActive = $currentAccess && $currentAccess->status === 'active';
            $isPending = $currentAccess && $currentAccess->requirement_status === 'pending';
            $isRejected = $currentAccess && $currentAccess->requirement_status === 'rejected';
            $modalId = "conditional-modal-{$key}-{$package->package_id}";
            $targetRoute = match($package->type_package) {
                'tryout' => route('user.package.tryout', $package->package_id),
                'sertifikasi' => route('user.package.sertifikasi', $package->package_id),
                default => route('user.package.bimbel', $package->package_id),
            };
        @endphp
        <div class="bg-white px-5 py-5 shadow rounded-lg flex flex-col gap-4">
                <div class="w-full h-36 rounded-xl bg-gray-100 overflow-hidden">
                @if($package->image)
                    @if($thumbIsVideo)
                    <video src="{{ $thumbUrl }}" class="w-full h-full object-cover" controls preload="metadata" playsinline></video>
                    @else
                    <img src="{{ $thumbUrl }}" alt="{{ $package->name }}" class="w-full h-full object-cover">
                    @endif
                @else
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                    <i class="ri-image-line text-4xl"></i>
                </div>
                @endif
            </div>

            <div class="flex items-center justify-between text-xs font-semibold">
                <span class="px-3 py-1 rounded-full {{ $tagStyle['class'] }}">
                    {{ $tagStyle['label'] }}
                </span>
                <span class="px-3 py-1 rounded-full bg-gray-50 text-gray-600 border border-gray-100 capitalize">
                    {{ $package->type_package }}
                </span>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $package->name }}</h3>
                <div class="text-sm text-gray-600 mt-1">{!! $package->description ?? 'Belum ada deskripsi.' !!}</div>
            </div>

            <div class="space-y-2">
                @if(!empty($featureList))
                    @foreach($featureList as $feature)
                    <p class="text-sm text-gray-700 flex items-center gap-2">
                        <i class="ri-checkbox-circle-fill text-green-500"></i>
                        {{ $feature }}
                    </p>
                    @endforeach
                @else
                    <p class="text-sm text-gray-500">Belum ada fitur terdaftar.</p>
                @endif
            </div>

            <div class="pt-2 border-t border-gray-100 mt-auto">
                @if($isActive)
                <a href="{{ $targetRoute }}"
                    class="inline-flex w-full items-center justify-center rounded-xl bg-green-600 px-4 py-3 text-sm font-semibold text-white hover:bg-green-700 transition">
                    Buka Paket
                </a>
                @elseif($package->type_price === 'free_conditional')
                    @if($isPending)
                    <div
                        class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700 text-center">
                        Menunggu verifikasi admin
                    </div>
                    @else
                    <button type="button" data-modal-open="{{ $modalId }}"
                        class="inline-flex w-full items-center justify-center rounded-lg border border-primary bg-white px-4 py-3 text-sm font-semibold text-primary hover:bg-primary/5 transition">
                        {{ $isRejected ? 'Ajukan Ulang' : 'Ajukan Akses' }}
                    </button>
                    @if($isRejected && $currentAccess?->requirement_review_notes)
                    <p class="mt-2 text-xs text-red-500">
                        Catatan admin: {{ $currentAccess->requirement_review_notes }}
                    </p>
                    @endif
                    @endif
                @else
                    @if($paymentMode === 'manual' && $package->type_price === 'paid')
                    <button type="button" data-modal-open="manual-payment-{{ $package->package_id }}"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-primary px-4 py-3 text-sm font-semibold text-white hover:bg-primary/90 transition">
                        Beli Sekarang
                    </button>
                    @else
                    <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST"
                        class="buy-package-form mt-1">
                        @csrf
                        <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-primary px-4 py-3 text-sm font-semibold text-white hover:bg-primary/90 transition">
                            Ambil Gratis
                        </button>
                    </form>
                    @endif
                @endif
            </div>
        </div>

        @if($package->type_price === 'free_conditional')
        <div class="event-modal hidden" data-modal="{{ $modalId }}">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-blue-900/20 backdrop-blur-[2px] px-4 py-8"
                data-modal-overlay="{{ $modalId }}">
                <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl overflow-hidden"
                    data-modal-panel>
                    <button type="button" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600"
                        data-modal-close="{{ $modalId }}">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                    <div class="p-6 space-y-4">
                        <div>
                            <p class="text-xs font-semibold text-primary uppercase tracking-wide">Syarat Event</p>
                            <h3 class="text-xl font-semibold text-gray-900 mt-1">{{ $package->name }}</h3>
                            <p class="text-sm text-gray-500">Lengkapi bukti syarat untuk mendapatkan paket ini secara
                                gratis.</p>
                        </div>
                        <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-4 text-sm text-gray-700">
                            <p class="font-semibold text-blue-900 mb-1">Detail Syarat</p>
                            <p class="whitespace-pre-line">{{ $package->conditional_requirement }}</p>
                        </div>
                        @if($isRejected && $currentAccess?->requirement_review_notes)
                        <div class="rounded-xl border border-red-100 bg-red-50 p-3 text-xs text-red-700">
                            <p class="font-semibold mb-1">Catatan admin</p>
                            <p>{{ $currentAccess->requirement_review_notes }}</p>
                        </div>
                        @endif
                        <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST"
                            enctype="multipart/form-data" class="buy-package-form space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Bukti</label>
                                <input type="file" name="requirement_proof" required
                                    accept=".jpg,.jpeg,.png,.pdf,.mp4,.webm"
                                    class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                                <p class="mt-2 text-xs text-gray-500">Format: JPG, PNG, PDF, MP4, WEBM (maks 20MB)</p>
                            </div>
                            <div class="flex items-center justify-end gap-3">
                                <button type="button"
                                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                                    data-modal-close="{{ $modalId }}">Batal</button>
                                <button type="submit"
                                    class="rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-white hover:bg-primary/90">Kirim
                                    Bukti</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if($package->type_price === 'paid' && $paymentMode === 'manual')
        <div class="event-modal hidden" data-modal="manual-payment-{{ $package->package_id }}">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-blue-900/20 backdrop-blur-[2px] px-4 py-8"
                data-modal-overlay="manual-payment-{{ $package->package_id }}">
                <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl overflow-hidden"
                    data-modal-panel>
                    <button type="button" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600"
                        data-modal-close="manual-payment-{{ $package->package_id }}">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                    <div class="p-6 space-y-4">
                        <div>
                            <p class="text-xs font-semibold text-primary uppercase tracking-wide">Pembayaran Manual</p>
                            <h3 class="text-xl font-semibold text-gray-900 mt-1">{{ $package->name }}</h3>
                            <p class="text-sm text-gray-500">Upload bukti pembayaran untuk diproses admin.</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 space-y-2">
                            <div>
                                <p class="font-semibold text-gray-900 mb-1">Total Tagihan</p>
                                <p>Rp {{ number_format($package->price, 0, ',', '.') }}</p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 mb-1">Transfer ke</p>
                                @if(!empty($clientBranding['payment_bank_name']) && !empty($clientBranding['payment_account_number']) && !empty($clientBranding['payment_account_holder']))
                                    <p>{{ $clientBranding['payment_bank_name'] }} {{ $clientBranding['payment_account_number'] }}</p>
                                    <p class="text-gray-500">a.n {{ $clientBranding['payment_account_holder'] }}</p>
                                @else
                                    <p class="text-gray-500">Info rekening belum diatur.</p>
                                @endif
                                @if(!empty($clientBranding['payment_bank_note']))
                                    <div class="prose prose-sm max-w-none text-gray-500 mt-2">
                                        {!! $clientBranding['payment_bank_note'] !!}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST"
                            enctype="multipart/form-data" class="buy-package-form space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Bukti</label>
                                <input type="file" name="payment_proof" required accept=".jpg,.jpeg,.png,.pdf"
                                    class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                                <p class="mt-2 text-xs text-gray-500">Format: JPG, PNG, PDF (maks 20MB)</p>
                            </div>
                            <div class="flex items-center justify-end gap-3">
                                <button type="button"
                                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                                    data-modal-close="manual-payment-{{ $package->package_id }}">Batal</button>
                                <button type="submit"
                                    class="rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                                    Kirim Bukti
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @empty
        <div class="col-span-full rounded-lg bg-white py-12 text-center text-gray-500 shadow">
            Belum ada paket tersedia di kategori ini.
        </div>
        @endforelse
    </div>
    @endforeach
</div>

<!-- Loading Modal -->
<div id="loadingModal"
    class="fixed inset-0 bg-black bg-opacity-40 z-50 hidden items-center justify-center backdrop-blur-[1px]">
    <div class="bg-white px-6 py-5 rounded-2xl shadow-xl flex items-center gap-3">
        <div class="animate-spin rounded-full h-8 w-8 border-2 border-primary border-t-transparent"></div>
        <p class="text-sm font-medium text-gray-700">Memproses permintaan Anda...</p>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        const tabs = ['kelas', 'tryout', 'sertifikasi'];

        function activateTab(active) {
            tabs.forEach(tab => {
                const container = $(`#${tab}-package`);
                const button = $(`#btn-${tab}`);

                if (tab === active) {
                    container.removeClass('hidden').addClass('grid');
                    button.addClass('bg-primary text-white').removeClass('border border-primary text-primary');
                } else {
                    container.addClass('hidden').removeClass('grid');
                    button.removeClass('bg-primary text-white').addClass('border border-primary text-primary');
                }
            });
        }

        function toggleModal(id, show) {
            const modal = $(`[data-modal="${id}"]`);
            if (!modal.length) return;

            if (show) {
                modal.removeClass('hidden');
                $('html').addClass('overflow-hidden');
            } else {
                modal.addClass('hidden');
                if (!$('.event-modal:not(.hidden)').length) {
                    $('html').removeClass('overflow-hidden');
                }
            }
        }

        function closeAllModals() {
            $('.event-modal').addClass('hidden');
            $('html').removeClass('overflow-hidden');
        }

        tabs.forEach(tab => {
            $(`#btn-${tab}`).on('click', function () {
                activateTab(tab);
            });
        });

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

        $('.buy-package-form').on('submit', function (e) {
            e.preventDefault();

            const form = $(this);
            const button = form.find('button[type="submit"]');
            const originalHtml = button.html();

            button.prop('disabled', true).html('Memproses...');
            $('#loadingModal').removeClass('hidden').addClass('flex');

            const formData = new FormData(this);

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $('#loadingModal').addClass('hidden').removeClass('flex');

                    if (response.redirect_url) {
                        window.location.href = response.redirect_url;
                        return;
                    }

                    if (response.success) {
                        if (response.message) {
                            alert(response.message);
                        }
                        closeAllModals();
                        window.location.reload();
                        return;
                    }

                    button.prop('disabled', false).html(originalHtml);
                    alert(response.message || 'Terjadi kesalahan. Silakan coba lagi.');
                },
                error: function (xhr) {
                    $('#loadingModal').addClass('hidden').removeClass('flex');
                    button.prop('disabled', false).html(originalHtml);

                    let errorMessage = 'Terjadi kesalahan. Silakan coba lagi.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    alert(errorMessage);
                }
            });
        });

        activateTab('kelas');
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
