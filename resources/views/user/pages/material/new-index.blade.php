@extends('user.layout.new-user')

@section('title', 'Materi Pembelajaran')

@section('content')
@php
$user = auth()->user();
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$paymentMode = $clientBranding['payment_mode'] ?? 'gateway';
$bankName = $clientBranding['payment_bank_name'] ?? '';
$accountNumber = $clientBranding['payment_account_number'] ?? '';
$accountHolder = $clientBranding['payment_account_holder'] ?? '';
$paymentBankNote = $clientBranding['payment_bank_note'] ?? '';
@endphp

<style>
.tab-active {
    background-color: {{ $primaryColor }} !important;
    color: white !important;
}
</style>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Materi Pembelajaran</h1>
        <p class="text-gray-500 mt-1">Jelajahi semua materi yang tersedia</p>
    </div>
    @if($user)
    <a href="{{ route('user.package.my', ['tab' => 'videos']) }}" class="px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition-opacity text-white" style="background-color: {{ $primaryColor }}">
        <i class="ri-book-marked-line mr-1"></i>Materi Saya
    </a>
    @endif
</div>

<!-- Filter Tabs -->
<div class="flex gap-2 mb-6 overflow-x-auto pb-1">
    <a href="{{ route('user.material.index') }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap tab-active">
        Semua
    </a>
    <a href="{{ route('user.material.videos') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        <i class="ri-video-line mr-1"></i>Video
    </a>
    <a href="{{ route('user.material.documents') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        <i class="ri-file-text-line mr-1"></i>Dokumen
    </a>
    <a href="{{ route('user.material.live-sessions') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        <i class="ri-live-line mr-1"></i>Live
    </a>
</div>

<!-- Categories Filter -->
@if(isset($categories) && $categories->count() > 0)
<div class="flex gap-2 mb-6 overflow-x-auto pb-2">
    @foreach($categories as $category)
    <a href="{{ route('user.material.index', ['category' => $category->category_id]) }}"
       class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-full hover:bg-gray-50 transition-colors flex-shrink-0 text-sm text-gray-700
          {{ request('category') == $category->category_id ? 'border-primary' : '' }}">
        @if($category->icon)
        <i class="{{ $category->icon }}" style="color: {{ $primaryColor }}"></i>
        @endif
        <span class="text-sm text-gray-700">{{ $category->name }}</span>
        @if($category->materials_count > 0)
        <span class="ml-1 px-1.5 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full">{{ $category->materials_count }}</span>
        @endif
    </a>
    @endforeach
</div>
@endif

@include('user.pages.material.partials.filter-sort', ['action' => route('user.material.index')])

<!-- Materials Grid -->
@if(isset($materials) && $materials->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($materials as $material)
    <div class="bg-white rounded-xl overflow-hidden border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all flex flex-col">
        {{-- Thumbnail --}}
        <div class="aspect-video bg-gray-100 overflow-hidden flex items-center justify-center">
            @if($material->thumbnail_url)
            <img src="{{ $material->thumbnail_url }}" alt="{{ $material->title }}" loading="lazy" decoding="async" width="480" height="270" class="w-full h-full object-cover">
            @else
            <div class="w-full h-full flex items-center justify-center {{ $material->type === 'video' ? 'bg-red-50 text-red-300' : ($material->type === 'document' ? 'bg-blue-50 text-blue-300' : 'bg-purple-50 text-purple-300') }}">
                <i class="{{ $material->type_icon }} text-5xl"></i>
            </div>
            @endif
        </div>

        {{-- Card Body --}}
        <div class="p-4 flex flex-col flex-1">
            {{-- Type Badge & Duration --}}
            <div class="flex items-center gap-2 mb-2">
                <span class="px-2 py-0.5 rounded text-xs font-medium {{ $material->type === 'video' ? 'bg-red-100 text-red-600' : ($material->type === 'document' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600') }}">
                    <i class="{{ $material->type_icon }} mr-0.5"></i>{{ $material->type_label }}
                </span>
                @if($material->duration_minutes)
                <span class="text-xs text-gray-400">
                    <i class="ri-time-line mr-0.5"></i>{{ $material->formatted_duration }}
                </span>
                @endif
            </div>

            {{-- Title & Description --}}
            <h3 class="font-semibold text-gray-800 text-sm mb-1 line-clamp-2">{{ $material->title }}</h3>
            <p class="text-xs text-gray-400 line-clamp-2 flex-1">{{ $material->description ?? 'Tidak ada deskripsi' }}</p>

            {{-- Price (show if material has price and user doesn't have access) --}}
            @if(!$material->has_access && $material->price > 0)
            <div class="mt-3 pt-3 border-t border-gray-100">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">Harga:</span>
                    <span class="font-bold text-sm" style="color: {{ $primaryColor }}">Rp {{ number_format($material->price, 0, ',', '.') }}</span>
                </div>
            </div>
            @endif
        </div>

        {{-- Card Footer --}}
        <div class="px-4 pb-4 pt-0">
            @if(!$user)
                {{-- Guest: Show login button --}}
                <a href="{{ route('login') }}"
                   class="flex items-center justify-center w-full py-2.5 rounded-lg text-sm font-medium border-2 hover:bg-gray-50 transition-colors"
                   style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                    <i class="ri-login-box-line mr-1"></i>Masuk untuk Akses
                </a>
            @elseif($material->has_access)
                <a href="{{ route('user.material.show', $material->material_id) }}"
                   class="flex items-center justify-center w-full py-2.5 rounded-lg text-sm font-medium text-white hover:opacity-90 transition-opacity"
                   style="background-color: {{ $primaryColor }}">
                    <i class="ri-play-circle-line mr-1"></i>Lihat Materi
                </a>
            @elseif($material->is_for_sale)
                {{-- Material for individual sale --}}
                <button onclick="buyIndividual('material', {{ $material->material_id }}, {{ $material->price ?? 0 }}, '{{ addslashes($material->title) }}')"
                        class="w-full py-2.5 rounded-lg text-sm font-medium text-white hover:opacity-90 transition-opacity"
                        style="background-color: {{ $primaryColor }}">
                    <i class="ri-shopping-cart-line mr-1"></i>Beli Sekarang
                </button>
            @else
                {{-- Material not for sale, only available via package --}}
                <div class="flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-medium bg-gray-100 text-gray-500">
                    <i class="ri-package-line mr-1"></i>Tersedia dalam Paket
                </div>
            @endif
        </div>
    </div>
    @endforeach
</div>

@if($materials->hasPages())
<div class="mt-8">
    {{ $materials->appends(request()->query())->links() }}
</div>
@endif

@else
<div class="text-center py-16">
    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="ri-book-open-line text-3xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada materi</h3>
    <p class="text-gray-400 text-sm">Materi pembelajaran akan segera tersedia.</p>
</div>
@endif

<!-- Purchase Modal -->
<div id="purchaseModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Beli <span id="purchaseTypeDisplay"></span></h3>
            <button onclick="closePurchaseModal()" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-lg">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="p-5">
            <div id="purchaseError" class="hidden mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"></div>

            <div class="bg-gray-50 rounded-xl p-4 mb-5">
                <p class="text-sm font-medium text-gray-600 mb-1">Item:</p>
                <p class="font-semibold text-gray-800" id="purchaseNameDisplay"></p>
                <div class="mt-2 pt-2 border-t border-gray-200">
                    <span class="text-sm text-gray-500">Total:</span>
                    <span class="text-lg font-bold ml-2" id="purchasePriceDisplay" style="color: {{ $primaryColor }}"></span>
                </div>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 mb-5">
                <p class="text-sm font-medium text-gray-600 mb-3">Transfer ke rekening berikut:</p>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Bank</span>
                        <span class="font-semibold text-gray-800">{{ $bankName ?: 'Belum dikonfigurasi' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nomor Rekening</span>
                        <span class="font-semibold text-gray-800">{{ $accountNumber ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Atas Nama</span>
                        <span class="font-semibold text-gray-800">{{ $accountHolder ?: '-' }}</span>
                    </div>
                    @if(!empty($paymentBankNote))
                    <div class="border-t pt-3 mt-3">
                        <div class="prose prose-sm max-w-none text-gray-600">
                            {!! $paymentBankNote !!}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <form id="purchaseForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kode Voucher (opsional)</label>
                        <input type="text" name="discount_code"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 uppercase focus:outline-none focus:ring-2"
                               style="--tw-ring-color: {{ $primaryColor }}"
                               placeholder="CONTOH: HEMAT50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Bukti Transfer <span class="text-red-500">*</span></label>
                        <input type="file" name="payment_proof" id="paymentProofInput" accept="image/*,.pdf" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2"
                               style="--tw-ring-color: {{ $primaryColor }}">
                        <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, PDF. Maks: 20MB</p>
                        <div id="proofPreview" class="mt-3 hidden">
                            <img id="proofImage" src="" alt="Preview" class="max-h-40 rounded-lg border border-gray-200">
                        </div>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                        <p class="text-sm text-amber-800">
                            <i class="ri-information-line mr-1"></i>
                            Setelah upload, tunggu verifikasi admin. Item akan aktif setelah pembayaran dikonfirmasi.
                        </p>
                    </div>
                    <x-legal-links />
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closePurchaseModal()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl hover:bg-gray-50 font-medium">Batal</button>
                    <button type="submit" id="submitPurchaseBtn" class="flex-1 px-4 py-2.5 text-white rounded-xl font-medium" style="background-color: {{ $primaryColor }}">Kirim Bukti Bayar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let selectedType = null;
let selectedId = null;
let selectedPrice = 0;

function buyIndividual(type, id, price, name) {
    selectedType = type;
    selectedId = id;
    selectedPrice = price;
    document.getElementById('purchaseTypeDisplay').textContent = type === 'material' ? 'Materi' : 'Tryout';
    document.getElementById('purchaseNameDisplay').textContent = name;
    document.getElementById('purchasePriceDisplay').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(price);
    document.getElementById('purchaseError').classList.add('hidden');
    document.getElementById('purchaseModal').classList.remove('hidden');
    document.getElementById('purchaseModal').classList.add('flex');
}

function closePurchaseModal() {
    document.getElementById('purchaseModal').classList.add('hidden');
    document.getElementById('purchaseModal').classList.remove('flex');
    document.getElementById('purchaseForm').reset();
    document.getElementById('proofPreview').classList.add('hidden');
    selectedType = null;
    selectedId = null;
}

document.getElementById('paymentProofInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const preview = document.getElementById('proofPreview');
        const img = document.getElementById('proofImage');
        img.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
    }
});

document.getElementById('purchaseForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('type', selectedType);
    formData.append('id', selectedId);
    const btn = document.getElementById('submitPurchaseBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-2"></i>Mengirim...';
    fetch('/user/pembelian/buy', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closePurchaseModal();
            alert(data.message || 'Bukti pembayaran berhasil dikirim!');
            window.location.reload();
        } else {
            btn.disabled = false;
            btn.innerHTML = 'Kirim Bukti Bayar';
            document.getElementById('purchaseError').textContent = data.message || 'Terjadi kesalahan.';
            document.getElementById('purchaseError').classList.remove('hidden');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = 'Kirim Bukti Bayar';
        document.getElementById('purchaseError').textContent = 'Terjadi kesalahan.';
        document.getElementById('purchaseError').classList.remove('hidden');
    });
});

document.getElementById('purchaseModal')?.addEventListener('click', function(e) {
    if (e.target === this) closePurchaseModal();
});
</script>
@endpush
