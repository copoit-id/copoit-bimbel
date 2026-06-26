@extends('user.layout.new-user')

@section('title', 'Tryout')

@section('content')
@php
$user = auth()->user();
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$paymentMode = strtolower((string) ($clientBranding['payment_mode'] ?? config('client.branding.payment_mode', 'gateway')));
@endphp

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Tryout</h1>
        <p class="text-gray-500 mt-1">Jelajahi semua tryout yang tersedia</p>
    </div>
    @if($user)
    <a href="{{ route('user.package.my') }}?tab=tryouts" class="px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition-opacity text-white" style="background-color: {{ $primaryColor }}">
        <i class="ri-file-list-3-line mr-1"></i>Tryout Saya
    </a>
    @endif
</div>

@if($user)
<!-- Stats Card (hanya untuk user login) -->
<div class="rounded-2xl p-6 text-white mb-6" style="background: linear-gradient(135deg, {{ $primaryColor }}, {{ $primaryColor }}dd);">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-white/80 text-sm mb-1">Total Tryout Dikerjakan</p>
            <h2 class="text-3xl font-bold">{{ $tryouts->filter(function($t) { return $t->userAnswers->count() > 0; })->count() }}</h2>
        </div>
        <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
            <i class="ri-file-list-3-line text-3xl"></i>
        </div>
    </div>
</div>
@else
<!-- Guest Info Card -->
<div class="rounded-2xl p-6 text-white mb-6" style="background: linear-gradient(135deg, {{ $primaryColor }}, {{ $primaryColor }}dd);">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-white/80 text-sm mb-1">Ingin mengerjakan tryout?</p>
            <h2 class="text-xl font-bold">Login untuk akses penuh</h2>
        </div>
        <a href="{{ route('login') }}" class="px-4 py-2 bg-white rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors" style="color: {{ $primaryColor }}">
            Masuk Sekarang
        </a>
    </div>
</div>
@endif

<form method="GET" action="{{ route('user.package.tryout.list') }}" class="bg-white border border-gray-100 rounded-xl p-3 mb-6 flex flex-col md:flex-row gap-3">
    <div class="flex-1">
        <label for="tryout-search" class="sr-only">Cari tryout</label>
        <div class="relative">
            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input id="tryout-search" type="search" name="search" value="{{ request('search', $search ?? '') }}"
                   placeholder="Cari berdasarkan nama"
                   class="w-full rounded-lg border border-gray-200 pl-10 pr-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                   style="--tw-ring-color: {{ $primaryColor }}">
        </div>
    </div>
    <div class="flex gap-2">
        <label for="tryout-sort" class="sr-only">Urutkan</label>
        <select id="tryout-sort" name="sort" class="min-w-[180px] rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                style="--tw-ring-color: {{ $primaryColor }}">
            <option value="latest" {{ request('sort', $sort ?? 'latest') === 'latest' ? 'selected' : '' }}>Terbaru</option>
            <option value="oldest" {{ request('sort', $sort ?? 'latest') === 'oldest' ? 'selected' : '' }}>Terlama</option>
            <option value="name_asc" {{ request('sort', $sort ?? 'latest') === 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
            <option value="name_desc" {{ request('sort', $sort ?? 'latest') === 'name_desc' ? 'selected' : '' }}>Nama Z-A</option>
        </select>
        <button type="submit" class="px-4 py-2.5 text-white rounded-lg text-sm font-medium hover:opacity-90" style="background-color: {{ $primaryColor }}">
            Terapkan
        </button>
    </div>
</form>

<!-- Tryouts Grid -->
@if($tryouts->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($tryouts as $tryout)
    @php
    $userAnswer = $tryout->userAnswers->first();
    $totalQuestions = $tryout->getTotalQuestionsAttribute();
    $totalDuration = $tryout->getTotalDurationAttribute();
    $isCompleted = $userAnswer && $userAnswer->status === 'completed';
    $isInProgress = $userAnswer && $userAnswer->status === 'in_progress';
    $isForSale = $tryout->isIndividuallyAvailable();
    $isPaid = $tryout->isPaidIndividualAccess();
    $isFreeConditional = $tryout->isFreeConditionalIndividualAccess();
    $tryoutIcon = $tryout->icon_class ?: 'ri-file-list-3-line';
    $showThumbnail = ($tryout->user_card_display ?? 'icon') === 'thumbnail' && filled($tryout->thumbnail_url);
    @endphp
    <div class="bg-white rounded-2xl p-5 border border-gray-100 hover:shadow-md hover:-translate-y-0.5 transition-all flex flex-col h-full">
        @if($showThumbnail)
        <div class="relative mb-4 h-40 w-full overflow-hidden rounded-xl bg-gray-100">
            <img src="{{ $tryout->thumbnail_url }}" alt="{{ $tryout->name }}" class="h-full w-full object-cover">
            <div class="absolute right-3 top-3">
                @if($isForSale)
                <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium shadow-sm">
                    <i class="{{ $isPaid ? 'ri-shopping-cart-line' : 'ri-gift-line' }} mr-0.5"></i>{{ $tryout->price_type_label }}
                </span>
                @else
                <span class="px-2.5 py-1 bg-blue-100 text-blue-700 text-xs rounded-full font-medium shadow-sm">
                    <i class="ri-folder-fill mr-0.5"></i>Paket
                </span>
                @endif
            </div>
        </div>
        @else
        <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center overflow-hidden" style="background-color: {{ $primaryColor }}20">
                    <i class="{{ $tryoutIcon }} text-xl" style="color: {{ $primaryColor }}"></i>
            </div>
            <div class="flex items-center gap-1">
                @if($isForSale)
                <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">
                    <i class="{{ $isPaid ? 'ri-shopping-cart-line' : 'ri-gift-line' }} mr-0.5"></i>{{ $tryout->price_type_label }}
                </span>
                @else
                <span class="px-2.5 py-1 bg-blue-100 text-blue-700 text-xs rounded-full font-medium">
                    <i class="ri-folder-fill mr-0.5"></i>Paket
                </span>
                @endif
            </div>
        </div>
        @endif

        <h3 class="font-bold text-gray-800 mb-2 line-clamp-2">{{ $tryout->name }}</h3>

        <div class="space-y-2 mb-4">
            <div class="flex items-center text-sm text-gray-500">
                <i class="ri-question-line mr-2 text-gray-400"></i>
                <span>{{ $totalQuestions }} Soal</span>
            </div>
            <div class="flex items-center text-sm text-gray-500">
                <i class="ri-time-line mr-2 text-gray-400"></i>
                <span>{{ $totalDuration }} Menit</span>
            </div>
            @if($isForSale)
            <div class="flex items-center text-sm text-gray-500">
                <i class="{{ $isPaid ? 'ri-money-dollar-circle-line' : 'ri-gift-line' }} mr-2 text-gray-400"></i>
                <span class="font-semibold" style="color: {{ $primaryColor }}">
                    {{ $isPaid ? 'Rp ' . number_format($tryout->price, 0, ',', '.') : $tryout->price_type_label }}
                </span>
            </div>
            @endif
        </div>

        <div class="mt-auto w-full">
            @if(!$user)
                {{-- Guest - perlu login --}}
                <a href="{{ route('login') }}"
                   class="flex items-center justify-center w-full py-2.5 rounded-xl text-sm font-medium border-2 hover:bg-gray-50 transition-colors"
                   style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                    <i class="ri-login-box-line mr-1"></i>
                    Masuk untuk Akses
                </a>
            @elseif($isForSale)
                {{-- Tryout for individual sale --}}
                @if($tryout->has_access)
                <a href="{{ route('user.tryout.lobby', ['id_package' => $tryout->packages->first()?->package_id ?? 'free', 'id_tryout' => $tryout->tryout_id]) }}"
                   class="block w-full py-2.5 text-white text-center rounded-xl text-sm font-medium hover:opacity-90 transition-opacity"
                   style="background-color: {{ $primaryColor }}">
                    <i class="ri-play-circle-line mr-1"></i>Kerjakan
                </a>
                @else
                <button type="button"
                        data-buy-tryout
                        data-id="{{ $tryout->tryout_id }}"
                        data-name="{{ e($tryout->name) }}"
                        data-price="{{ (int) $tryout->price }}"
                        data-price-type="{{ $tryout->priceType() }}"
                        class="w-full py-2.5 rounded-xl text-sm font-medium text-white hover:opacity-90 transition-opacity"
                        style="background-color: {{ $primaryColor }}">
                    <i class="{{ $isPaid ? 'ri-shopping-cart-line' : ($isFreeConditional ? 'ri-time-line' : 'ri-gift-line') }} mr-1"></i>
                    {{ $isPaid ? 'Beli Sekarang' : ($isFreeConditional ? 'Ajukan Akses' : 'Akses Gratis') }}
                </button>
                @endif
            @elseif($tryout->has_access)
                {{-- User has access via package - arahkan ke paket saya --}}
                <a href="{{ route('user.package.my') }}?tab=tryouts"
                   class="flex items-center justify-center w-full py-2.5 rounded-xl text-sm font-medium border-2 hover:bg-gray-50 transition-colors"
                   style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                    <i class="ri-folder-open-line mr-1"></i>Lihat di Paket Saya
                </a>
            @else
                {{-- User doesn't have access --}}
                @if($tryout->access_via_package)
                <a href="{{ route('user.package.my') }}?tab=packages"
                   class="flex items-center justify-center w-full py-2.5 rounded-xl text-sm font-medium border-2 hover:bg-gray-50 transition-colors"
                   style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                    <i class="ri-shopping-bag-line mr-1"></i>
                    Dapatkan Akses
                </a>
                @else
                <button disabled class="flex items-center justify-center w-full py-2.5 rounded-xl text-sm font-medium bg-gray-100 text-gray-400 cursor-not-allowed">
                    <i class="ri-lock-line mr-1"></i>
                    Tidak Tersedia
                </button>
                @endif
            @endif
        </div>
    </div>
    @endforeach
</div>

<!-- Purchase Modal -->
<div id="tryoutPurchaseModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 id="tryoutPurchaseModalTitle" class="text-lg font-bold text-gray-800">Beli Tryout</h3>
            <button type="button" id="closeTryoutPurchaseModal" class="p-1 hover:bg-gray-100 rounded-lg">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <form id="tryoutPurchaseForm" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="type" value="tryout">
            <input type="hidden" name="id" id="tryoutPurchaseItemId">
            <div class="mb-4">
                <p class="text-sm text-gray-500">Tryout</p>
                <p id="tryoutPurchaseItemName" class="font-semibold text-gray-800"></p>
            </div>
            <div class="mb-4">
                <p class="text-sm text-gray-500">Harga</p>
                <p id="tryoutPurchaseItemPrice" class="font-bold text-lg" style="color: {{ $primaryColor }}"></p>
            </div>
            <div id="tryoutVoucherWrapper" class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Kode Voucher (opsional)</label>
                <input type="text" name="discount_code"
                       class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm uppercase focus:outline-none focus:ring-2"
                       style="--tw-ring-color: {{ $primaryColor }}"
                       placeholder="CONTOH: HEMAT50">
            </div>
            @if($paymentMode === 'manual')
            <div id="tryoutPaymentProofWrapper" class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Bukti Pembayaran</label>
                <input type="file" name="payment_proof" accept="image/*,.pdf" required
                       class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
            </div>
            @endif
            <x-legal-links class="mb-4" />
            <div class="flex gap-3">
                <button type="button" id="cancelTryoutPurchase" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl hover:bg-gray-50 font-medium">Batal</button>
                <button id="tryoutSubmitPurchaseBtn" type="submit" class="flex-1 px-4 py-2.5 text-white rounded-xl font-medium" style="background-color: {{ $primaryColor }}">Beli</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('tryoutPurchaseModal');
    const form = document.getElementById('tryoutPurchaseForm');
    const itemId = document.getElementById('tryoutPurchaseItemId');
    const itemName = document.getElementById('tryoutPurchaseItemName');
    const itemPrice = document.getElementById('tryoutPurchaseItemPrice');
    const modalTitle = document.getElementById('tryoutPurchaseModalTitle');
    const voucherWrapper = document.getElementById('tryoutVoucherWrapper');
    const proofWrapper = document.getElementById('tryoutPaymentProofWrapper');
    const proofInput = proofWrapper?.querySelector('input[type="file"]');
    const submitBtn = document.getElementById('tryoutSubmitPurchaseBtn');

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        form.reset();
    }

    document.querySelectorAll('[data-buy-tryout]').forEach(button => {
        button.addEventListener('click', function () {
            itemId.value = this.dataset.id;
            itemName.textContent = this.dataset.name;
            const priceType = this.dataset.priceType || 'paid';
            const isPaid = priceType === 'paid';
            modalTitle.textContent = isPaid ? 'Beli Tryout' : (priceType === 'free_conditional' ? 'Ajukan Akses Tryout' : 'Akses Gratis Tryout');
            itemPrice.textContent = isPaid ? 'Rp ' + Number(this.dataset.price).toLocaleString('id-ID') : (priceType === 'free_conditional' ? 'Gratis Bersyarat' : 'Gratis');
            voucherWrapper?.classList.toggle('hidden', !isPaid);
            proofWrapper?.classList.toggle('hidden', !isPaid);
            if (proofInput) proofInput.required = isPaid;
            submitBtn.textContent = isPaid ? 'Beli' : (priceType === 'free_conditional' ? 'Ajukan' : 'Aktifkan');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    document.getElementById('closeTryoutPurchaseModal').addEventListener('click', closeModal);
    document.getElementById('cancelTryoutPurchase').addEventListener('click', closeModal);

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const response = await fetch('{{ route('user.individual-purchase.buy') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: new FormData(form),
        });

        const data = await response.json();
        if (data.redirect_url) {
            window.location.href = data.redirect_url;
            return;
        }

        alert(data.message || (data.success ? 'Pembelian berhasil diproses.' : 'Pembelian gagal diproses.'));
        if (data.success) {
            window.location.reload();
        }
    });
});
</script>
@endpush

@else
<div class="text-center py-16">
    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="ri-file-list-3-line text-4xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada tryout</h3>
    <p class="text-gray-400 text-sm mb-6">Tryout akan segera tersedia.</p>
</div>
@endif
@endsection
