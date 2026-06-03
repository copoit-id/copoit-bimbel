@extends('user.layout.new-user')

@section('title', 'Tes Koran')

@section('content')
@php
$user = auth()->user();
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$paymentMode = strtolower((string) ($clientBranding['payment_mode'] ?? config('client.branding.payment_mode', 'gateway')));
@endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Tes Koran</h1>
        <p class="text-gray-500 mt-1">Tes Pauli dan Kraepelin yang tersedia</p>
    </div>
    @if($user)
    <div class="flex items-center gap-2">
        <a href="{{ route('user.tes-koran.history') }}" class="px-4 py-2 rounded-lg text-sm font-medium border hover:bg-gray-50 transition-colors" style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
            <i class="ri-history-line mr-1"></i>Riwayat
        </a>
        <a href="{{ route('user.package.my') }}?tab=tes-koran" class="px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition-opacity text-white" style="background-color: {{ $primaryColor }}">
            <i class="ri-archive-line mr-1"></i>Tes Saya
        </a>
    </div>
    @endif
</div>

@if($tesKorans->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($tesKorans as $tesKoran)
    @php
    $isForSale = $tesKoran->is_for_sale && $tesKoran->price > 0;
    @endphp
    <div class="bg-white rounded-2xl p-5 border border-gray-100 hover:shadow-md hover:-translate-y-0.5 transition-all">
        <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: {{ $primaryColor }}20">
                <i class="ri-file-edit-line text-xl" style="color: {{ $primaryColor }}"></i>
            </div>
            <span class="px-2.5 py-1 {{ $isForSale ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }} text-xs rounded-full font-medium">
                <i class="{{ $isForSale ? 'ri-shopping-cart-line' : 'ri-folder-fill' }} mr-0.5"></i>{{ $isForSale ? 'Dijual' : 'Paket' }}
            </span>
        </div>

        <h3 class="font-bold text-gray-800 mb-2 line-clamp-2">{{ $tesKoran->name }}</h3>

        <div class="space-y-2 mb-4">
            <div class="flex items-center text-sm text-gray-500">
                <i class="ri-file-list-line mr-2 text-gray-400"></i>
                <span>{{ ucfirst($tesKoran->test_type) }}</span>
            </div>
            <div class="flex items-center text-sm text-gray-500">
                <i class="ri-time-line mr-2 text-gray-400"></i>
                <span>{{ $tesKoran->duration_minutes }} Menit</span>
            </div>
            <div class="flex items-center text-sm text-gray-500">
                <i class="ri-layout-column-line mr-2 text-gray-400"></i>
                <span>{{ $tesKoran->columns_count }} Kolom, {{ $tesKoran->rows_count }} Baris</span>
            </div>
            @if($isForSale)
            <div class="flex items-center text-sm text-gray-500">
                <i class="ri-money-dollar-circle-line mr-2 text-gray-400"></i>
                <span class="font-semibold" style="color: {{ $primaryColor }}">Rp {{ number_format($tesKoran->price, 0, ',', '.') }}</span>
            </div>
            @endif
        </div>

        @if(!$user)
        <a href="{{ route('login') }}"
           class="flex items-center justify-center w-full py-2.5 rounded-xl text-sm font-medium border-2 hover:bg-gray-50 transition-colors"
           style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
            <i class="ri-login-box-line mr-1"></i>Masuk untuk Akses
        </a>
        @elseif($tesKoran->has_access)
        <a href="{{ route('user.tes-koran.show', $tesKoran) }}"
           class="block w-full py-2.5 text-white text-center rounded-xl text-sm font-medium hover:opacity-90 transition-opacity"
           style="background-color: {{ $primaryColor }}">
            <i class="ri-play-circle-line mr-1"></i>Mulai Tes
        </a>
        @elseif($tesKoran->has_pending_purchase)
        <button disabled class="flex items-center justify-center w-full py-2.5 rounded-xl text-sm font-medium bg-yellow-100 text-yellow-700 cursor-not-allowed">
            <i class="ri-time-line mr-1"></i>Menunggu Verifikasi
        </button>
        @elseif($isForSale)
        <button type="button"
                data-buy-tes-koran
                data-id="{{ $tesKoran->id }}"
                data-name="{{ e($tesKoran->name) }}"
                data-price="{{ (int) $tesKoran->price }}"
                class="w-full py-2.5 rounded-xl text-sm font-medium text-white hover:opacity-90 transition-opacity"
                style="background-color: {{ $primaryColor }}">
            <i class="ri-shopping-cart-line mr-1"></i>Beli Sekarang
        </button>
        @elseif($tesKoran->access_via_package)
        <a href="{{ route('user.package.detail', $tesKoran->access_via_package->package_id) }}"
           class="flex items-center justify-center w-full py-2.5 rounded-xl text-sm font-medium border-2 hover:bg-gray-50 transition-colors"
           style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
            <i class="ri-shopping-bag-line mr-1"></i>Dapatkan Paket
        </a>
        @else
        <button disabled class="flex items-center justify-center w-full py-2.5 rounded-xl text-sm font-medium bg-gray-100 text-gray-400 cursor-not-allowed">
            <i class="ri-lock-line mr-1"></i>Tidak Tersedia
        </button>
        @endif
    </div>
    @endforeach
</div>
@else
<div class="text-center py-16">
    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="ri-file-edit-line text-4xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada Tes Koran</h3>
    <p class="text-gray-400 text-sm">Tes akan segera tersedia</p>
</div>
@endif

<div id="purchaseModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-800">Beli Tes Koran</h3>
            <button type="button" id="closePurchaseModal" class="p-1 hover:bg-gray-100 rounded-lg">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <form id="purchaseForm" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="type" value="tes_koran">
            <input type="hidden" name="id" id="purchaseItemId">
            <div class="mb-4">
                <p class="text-sm text-gray-500">Tes</p>
                <p id="purchaseItemName" class="font-semibold text-gray-800"></p>
            </div>
            <div class="mb-4">
                <p class="text-sm text-gray-500">Harga</p>
                <p id="purchaseItemPrice" class="font-bold text-lg" style="color: {{ $primaryColor }}"></p>
            </div>
            @if($paymentMode === 'manual')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Bukti Pembayaran</label>
                <input type="file" name="payment_proof" accept="image/*,.pdf" required
                       class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
            </div>
            @endif
            <div class="flex gap-3">
                <button type="button" id="cancelPurchase" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl hover:bg-gray-50 font-medium">Batal</button>
                <button type="submit" class="flex-1 px-4 py-2.5 text-white rounded-xl font-medium" style="background-color: {{ $primaryColor }}">Beli</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('purchaseModal');
    const form = document.getElementById('purchaseForm');
    const itemId = document.getElementById('purchaseItemId');
    const itemName = document.getElementById('purchaseItemName');
    const itemPrice = document.getElementById('purchaseItemPrice');

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        form.reset();
    }

    document.querySelectorAll('[data-buy-tes-koran]').forEach(button => {
        button.addEventListener('click', function () {
            itemId.value = this.dataset.id;
            itemName.textContent = this.dataset.name;
            itemPrice.textContent = 'Rp ' + Number(this.dataset.price).toLocaleString('id-ID');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    document.getElementById('closePurchaseModal').addEventListener('click', closeModal);
    document.getElementById('cancelPurchase').addEventListener('click', closeModal);

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
@endsection
