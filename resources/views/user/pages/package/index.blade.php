@extends('user.layout.user')
@section('title', 'Paket Pembelian')
@section('content')
@php
    $typePriceLabels = [
        'free_unconditional' => ['label' => 'Gratis', 'class' => 'bg-emerald-50 text-emerald-700 border border-emerald-100'],
        'free_conditional' => ['label' => 'Gratis Bersyarat', 'class' => 'bg-amber-50 text-amber-700 border border-amber-100'],
    ];
    $paymentMode = config('client.branding.payment_mode', 'gateway');
    $kelasPackages = $kelasPackages ?? collect();
    $tryoutPackages = $tryoutPackages ?? collect();
    $sertifikasiPackages = $sertifikasiPackages ?? collect();
@endphp
<div class="dashboard">
    <x-page-desc title="Paket " description="Pilihan paket gratis hingga berbayar"></x-page-desc>
    <div class="flex flex-col md:flex-row md:items-center justify-between">
        <div class="flex justify-start gap-2 mt-4">
            <div id="btn-kelas" class="tab-btn px-6 py-1.5 bg-primary text-white rounded-xl cursor-pointer">
                Kelas
            </div>
            <div id="btn-tryout"
                class="tab-btn px-6 py-1.5 border border-primary text-primary rounded-xl cursor-pointer">
                Tryout
            </div>
            <div id="btn-sertifikasi"
                class="tab-btn px-6 py-1.5 border border-primary text-primary rounded-xl cursor-pointer">
                Sertifikasi
            </div>
        </div>

        <a href="{{ route('user.package.riwayatPembelian') }}" class="text-blue-600 underline mt-3 md:mt-0">Riwayat
            Pembelian</a>
    </div>

    <!-- Kelas Package -->
    <div id="kelas-package" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6 text-gray-600">
        @forelse($kelasPackages as $package)
        <div class="bg-white px-5 py-5 shadow rounded-lg">
            @php
                $thumbExt = $package->image ? strtolower(pathinfo($package->image, PATHINFO_EXTENSION)) : null;
                $thumbIsVideo = $package->image ? in_array($thumbExt, ['mp4','webm','mov','m4v'], true) : false;
                $thumbUrl = $package->image ? Storage::url($package->image) : null;
                $typePriceStyle = $typePriceLabels[$package->type_price] ?? null;
            @endphp
            <div class="w-full h-32 bg-gray-300 rounded-xl mb-4 overflow-hidden">
                @if($package->image)
                    @if($thumbIsVideo)
                    <video src="{{ $thumbUrl }}" class="w-full h-full object-cover" controls preload="metadata" playsinline></video>
                    @else
                        <img src="{{ $thumbUrl }}" alt="{{ $package->name }}"
                            class="w-full h-full object-cover">
                    @endif
                @else
                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                        <i class="ri-image-line text-3xl text-gray-400"></i>
                    </div>
                @endif
            </div>
            <div class="flex items-center justify-between text-xs font-semibold mb-2">
                @if($typePriceStyle)
                <span class="px-3 py-1 rounded-full {{ $typePriceStyle['class'] }}">
                    {{ $typePriceStyle['label'] }}
                </span>
                @endif
                <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-100 capitalize text-gray-600">
                    {{ $package->type_package }}
                </span>
            </div>
            <p class="text-lg font-bold text-black">{{ $package->name }}</p>
            <p class="font-light">{{ $package->description }}</p>
            <span class="font-bold text-black">Rp {{ number_format($package->price, 0, ',', '.') }}</span>

            <div class="flex flex-col mt-4 gap-3 font-light">
                @if($package->features)
                @foreach (json_decode($package->features) as $feature)
                <span>
                    <i class="ri-checkbox-circle-fill text-green-500"></i>
                    {{ $feature }}
                </span>
                @endforeach
                @endif
            </div>

            <div class="mt-4">
                @if($package->user_access_count > 0)
                <a href="{{ route('user.package.bimbel', $package->package_id) }}"
                    class="w-full bg-green-600 text-white px-4 py-3 rounded-lg font-bold text-center block">
                    SUDAH DIBELI
                </a>
                @else
                @if($paymentMode === 'manual')
                    <button type="button" data-modal-open="manual-payment-{{ $package->package_id }}"
                        class="w-full bg-primary text-white px-4 py-3 rounded-lg font-bold">
                        BELI SEKARANG
                    </button>
                @else
                    <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST"
                        class="buy-package-form">
                        @csrf
                        <button type="submit" class="w-full bg-primary text-white px-4 py-3 rounded-lg font-bold">
                            BELI SEKARANG
                        </button>
                    </form>
                @endif
                @endif
            </div>
        </div>
        @if($package->user_access_count === 0 && $paymentMode === 'manual')
        <div class="payment-modal hidden" data-modal="manual-payment-{{ $package->package_id }}">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 py-8"
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
                                    <p class="text-xs text-gray-500 mt-1">{{ $clientBranding['payment_bank_note'] }}</p>
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
        <div class="col-span-full text-center py-8">
            <p class="text-gray-500">Belum ada paket kelas tersedia</p>
        </div>
        @endforelse
    </div>

    <!-- Tryout Package -->
    <div id="tryout-package" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6 text-gray-600 hidden">
        @forelse($tryoutPackages as $package)
        <div class="flex flex-col justify-between bg-white px-5 py-5 shadow rounded-lg">
            <div>
                @php
                    $thumbExt = $package->image ? strtolower(pathinfo($package->image, PATHINFO_EXTENSION)) : null;
                    $thumbIsVideo = $package->image ? in_array($thumbExt, ['mp4','webm','mov','m4v'], true) : false;
                    $thumbUrl = $package->image ? Storage::url($package->image) : null;
                    $typePriceStyle = $typePriceLabels[$package->type_price] ?? null;
                @endphp
                <div class="w-full h-32 bg-gray-300 rounded-xl mb-4 overflow-hidden">
                    @if($package->image)
                        @if($thumbIsVideo)
                            <video src="{{ $thumbUrl }}" class="w-full h-full object-cover" controls preload="metadata" playsinline></video>
                        @else
                            <img src="{{ $thumbUrl }}" alt="{{ $package->name }}"
                                class="w-full h-full object-cover">
                        @endif
                    @else
                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                        <i class="ri-image-line text-3xl text-gray-400"></i>
                    </div>
                    @endif
                </div>
                <div class="flex items-center justify-between text-xs font-semibold mb-2">
                    @if($typePriceStyle)
                    <span class="px-3 py-1 rounded-full {{ $typePriceStyle['class'] }}">
                        {{ $typePriceStyle['label'] }}
                    </span>
                    @endif
                    <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-100 capitalize text-gray-600">
                        {{ $package->type_package }}
                    </span>
                </div>
                <p class="text-lg font-bold text-black">{{ $package->name }}</p>
                <p class="font-light">{{ $package->description }}</p>
                <span class="font-bold text-black">Rp {{ number_format($package->price, 0, ',', '.') }}</span>

                <div class="flex flex-col mt-4 gap-3 font-light">
                    @if($package->features)
                    @foreach (json_decode($package->features) as $feature)
                    <span>
                        <i class="ri-checkbox-circle-fill text-green-500"></i>
                        {{ $feature }}
                    </span>
                    @endforeach
                    @endif
                </div>
            </div>

            <div class="mt-4">
                @if($package->user_access_count > 0)
                <a href="{{ route('user.package.tryout', $package->package_id) }}"
                    class="w-full bg-green-600 text-white px-4 py-3 rounded-lg font-bold text-center block">
                    SUDAH DIBELI
                </a>
                @else
                @if($paymentMode === 'manual')
                    <button type="button" data-modal-open="manual-payment-{{ $package->package_id }}"
                        class="w-full bg-primary text-white px-4 py-3 rounded-lg font-bold">
                        BELI SEKARANG
                    </button>
                @else
                    <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST"
                        class="buy-package-form">
                        @csrf
                        <button type="submit" class="w-full bg-primary text-white px-4 py-3 rounded-lg font-bold">
                            BELI SEKARANG
                        </button>
                    </form>
                @endif
                @endif
            </div>
        </div>
        @if($package->user_access_count === 0 && $paymentMode === 'manual')
        <div class="payment-modal hidden" data-modal="manual-payment-{{ $package->package_id }}">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 py-8"
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
                                    <p class="text-xs text-gray-500 mt-1">{{ $clientBranding['payment_bank_note'] }}</p>
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
        <div class="col-span-full text-center py-8">
            <p class="text-gray-500">Belum ada paket tryout tersedia</p>
        </div>
        @endforelse
    </div>

    <!-- Sertifikasi Package -->
    <div id="sertifikasi-package"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6 text-gray-600 hidden">
        @forelse($sertifikasiPackages as $package)
        <div class="bg-white px-5 py-5 shadow rounded-lg">
            @php
                $thumbExt = $package->image ? strtolower(pathinfo($package->image, PATHINFO_EXTENSION)) : null;
                $thumbIsVideo = $package->image ? in_array($thumbExt, ['mp4','webm','mov','m4v'], true) : false;
                $thumbUrl = $package->image ? Storage::url($package->image) : null;
                $typePriceStyle = $typePriceLabels[$package->type_price] ?? null;
            @endphp
            <div class="w-full h-32 bg-gray-300 rounded-xl mb-4 overflow-hidden">
                @if($package->image)
                    @if($thumbIsVideo)
                        <video src="{{ $thumbUrl }}" class="w-full h-full object-cover" controls preload="metadata" playsinline></video>
                    @else
                        <img src="{{ $thumbUrl }}" alt="{{ $package->name }}"
                            class="w-full h-full object-cover">
                    @endif
                @else
                <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                    <i class="ri-image-line text-3xl text-gray-400"></i>
                </div>
                @endif
            </div>
            <div class="flex items-center justify-between text-xs font-semibold mb-2">
                @if($typePriceStyle)
                <span class="px-3 py-1 rounded-full {{ $typePriceStyle['class'] }}">
                    {{ $typePriceStyle['label'] }}
                </span>
                @endif
                <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-100 capitalize text-gray-600">
                    {{ $package->type_package }}
                </span>
            </div>
            <p class="text-lg font-bold text-black">{{ $package->name }}</p>
            <p class="font-light">{{ $package->description }}</p>
            <span class="font-bold text-black">Rp {{ number_format($package->price, 0, ',', '.') }}</span>

            <div class="flex flex-col mt-4 gap-3 font-light">
                @if($package->features)
                @foreach (json_decode($package->features) as $feature)
                <span>
                    <i class="ri-checkbox-circle-fill text-green-500"></i>
                    {{ $feature }}
                </span>
                @endforeach
                @endif
            </div>

            <div class="mt-4">
                @if($package->user_access_count > 0)
                <a href="{{ route('user.package.bimbel', $package->package_id) }}"
                    class="w-full bg-green-600 text-white px-4 py-3 rounded-lg font-bold text-center block">
                    SUDAH DIBELI
                </a>
                @else
                @if($paymentMode === 'manual')
                    <button type="button" data-modal-open="manual-payment-{{ $package->package_id }}"
                        class="w-full bg-primary text-white px-4 py-3 rounded-lg font-bold">
                        BELI SEKARANG
                    </button>
                @else
                    <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST"
                        class="buy-package-form">
                        @csrf
                        <button type="submit" class="w-full bg-primary text-white px-4 py-3 rounded-lg font-bold">
                            BELI SEKARANG
                        </button>
                    </form>
                @endif
                @endif
            </div>
        </div>
        @if($package->user_access_count === 0 && $paymentMode === 'manual')
        <div class="payment-modal hidden" data-modal="manual-payment-{{ $package->package_id }}">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 py-8"
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
                                    <p class="text-xs text-gray-500 mt-1">{{ $clientBranding['payment_bank_note'] }}</p>
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
        <div class="col-span-full text-center py-8">
            <p class="text-gray-500">Belum ada paket sertifikasi tersedia</p>
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

        // Tab click events
        tabs.forEach(tab => {
            $(`#btn-${tab}`).click(function() {
                activateTab(tab);
            });
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

        // Initialize first tab
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
