@extends('user.layout.new-user')

@section('title', 'Kecermatan')

@section('content')
@php
    $primaryColor = $clientBranding['primary_color'] ?? '#10b981';
    $paymentMode = strtolower((string) ($clientBranding['payment_mode'] ?? config('client.branding.payment_mode', 'gateway')));
@endphp
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Kecermatan</h1>
        <p class="mt-1 text-gray-500">Latihan Kecermatan TNI/POLRI berbasis kolom.</p>
    </div>
</div>

@if(session('error'))
<div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
    @forelse($kecermatans as $kecermatan)
    <div class="flex h-full flex-col rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-start justify-between">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl" style="background-color: {{ $primaryColor }}20">
                <i class="ri-focus-3-line text-xl" style="color: {{ $primaryColor }}"></i>
            </div>
            <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700">{{ $kecermatan->typeLabel() }}</span>
        </div>
        <h3 class="mb-2 font-bold text-gray-800">{{ $kecermatan->name }}</h3>
        <p class="mb-4 line-clamp-3 text-sm text-gray-500">{{ $kecermatan->description ?: 'Tes cepat untuk melatih kecepatan dan ketelitian.' }}</p>
        <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
            <i class="ri-price-tag-3-line text-gray-400"></i>
            <span>{{ $kecermatan->is_for_sale ? 'Rp ' . number_format((int) $kecermatan->price, 0, ',', '.') : 'Akses via paket' }}</span>
        </div>
        <div class="mt-auto">
            @if($kecermatan->has_access)
            <a href="{{ route('user.kecermatan.start', $kecermatan) }}" class="inline-flex w-full items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90" style="background-color: {{ $primaryColor }}">
                Mulai
            </a>
            @elseif($kecermatan->has_pending_purchase)
            <button type="button" disabled class="w-full rounded-lg bg-yellow-100 px-4 py-2.5 text-sm font-semibold text-yellow-700">Menunggu Verifikasi</button>
            @elseif($kecermatan->is_for_sale)
            <button type="button"
                data-buy-kecermatan
                data-id="{{ $kecermatan->id }}"
                data-name="{{ $kecermatan->name }}"
                data-price="{{ (int) $kecermatan->price }}"
                class="inline-flex w-full items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90"
                style="background-color: {{ $primaryColor }}">
                Beli
            </button>
            @else
            <button type="button" disabled class="w-full rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-400">Belum Ada Akses</button>
            @endif
        </div>
    </div>
    @empty
    <div class="col-span-full rounded-2xl border border-gray-100 bg-white p-10 text-center text-gray-500">Belum ada kecermatan tersedia.</div>
    @endforelse
</div>

<div class="mt-6">{{ $kecermatans->links() }}</div>

<div id="purchaseModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Beli Kecermatan</h3>
            <button type="button" id="closePurchaseModal" class="rounded-lg p-1 hover:bg-gray-100">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <form id="purchaseForm" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="type" value="kecermatan">
            <input type="hidden" name="id" id="purchaseItemId">
            <div class="mb-4">
                <p class="text-sm text-gray-500">Kecermatan</p>
                <p id="purchaseItemName" class="font-semibold text-gray-800"></p>
            </div>
            <div class="mb-4">
                <p class="text-sm text-gray-500">Harga</p>
                <p id="purchaseItemPrice" class="text-lg font-bold" style="color: {{ $primaryColor }}"></p>
            </div>
            <div class="mb-4">
                <label class="mb-2 block text-sm font-medium text-gray-700">Kode Voucher (opsional)</label>
                <input type="text" name="discount_code" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2" style="--tw-ring-color: {{ $primaryColor }}" placeholder="CONTOH: HEMAT50">
            </div>
            @if($paymentMode === 'manual')
            <div class="mb-4">
                <label class="mb-2 block text-sm font-medium text-gray-700">Bukti Pembayaran</label>
                <input type="file" name="payment_proof" accept="image/*,.pdf" required class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
            </div>
            @endif
            <x-legal-links class="mb-4" />
            <div class="flex gap-3">
                <button type="button" id="cancelPurchase" class="flex-1 rounded-xl border border-gray-300 px-4 py-2.5 font-medium text-gray-600 hover:bg-gray-50">Batal</button>
                <button type="submit" class="flex-1 rounded-xl px-4 py-2.5 font-medium text-white" style="background-color: {{ $primaryColor }}">Beli</button>
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

    document.querySelectorAll('[data-buy-kecermatan]').forEach((button) => {
        button.addEventListener('click', function () {
            itemId.value = this.dataset.id;
            itemName.textContent = this.dataset.name;
            itemPrice.textContent = 'Rp ' + Number(this.dataset.price).toLocaleString('id-ID');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    document.getElementById('closePurchaseModal')?.addEventListener('click', closeModal);
    document.getElementById('cancelPurchase')?.addEventListener('click', closeModal);

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
