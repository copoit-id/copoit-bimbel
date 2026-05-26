@extends('user.layout.new-user')

@section('title', 'Live Session')

@section('content')
@php
$user = auth()->user();
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
@endphp

<style>
.tab-active {
    background-color: #a855f7 !important;
    color: white !important;
}
</style>

<!-- Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('user.material.index') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Live Session</h1>
        <p class="text-gray-500 text-sm">Ikuti kelas online dan webinar interaktif</p>
    </div>
</div>

<!-- Filter Tabs -->
<div class="flex gap-2 mb-6 overflow-x-auto pb-2">
    <a href="{{ route('user.material.index') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        Semua
    </a>
    <a href="{{ route('user.material.videos') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        <i class="ri-video-line mr-1"></i>Video
    </a>
    <a href="{{ route('user.material.documents') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        <i class="ri-file-text-line mr-1"></i>Dokumen
    </a>
    <a href="{{ route('user.material.live-sessions') }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap tab-active">
        <i class="ri-live-line mr-1"></i>Live
    </a>
</div>

<!-- Live Sessions Grid -->
@if($materials->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @foreach($materials as $material)
    @php
    $isAccessible = $user && $material->has_access;
    $userAccess = $user ? $material->userAccess->first() : null;
    $displayPrice = $material->price ?? 0;
    @endphp
    <div class="bg-white rounded-xl overflow-hidden border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all flex flex-col">
        {{-- Thumbnail --}}
        @if($material->thumbnail_url)
        <a href="{{ $isAccessible ? route('user.material.show', $material->material_id) : 'javascript:void(0)' }}" class="block aspect-video bg-gray-100 overflow-hidden">
            <img src="{{ $material->thumbnail_url }}" alt="{{ $material->title }}" class="w-full h-full object-cover">
        </a>
        @endif

        <div class="p-5">
        @if(!$material->thumbnail_url)
        <div class="flex items-start gap-4 mb-4">
            <div class="w-16 h-16 bg-gradient-to-br from-purple-100 to-pink-50 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="ri-live-line text-3xl text-purple-500"></i>
            </div>
            <div class="flex-1 min-w-0">
        @else
        <div>
        @endif
                <a href="{{ $isAccessible ? route('user.material.show', $material->material_id) : 'javascript:void(0)' }}" class="block">
                    <h3 class="font-medium text-gray-800 mb-1 line-clamp-2 hover:text-purple-500 transition-colors">{{ $material->title }}</h3>
                </a>
                <p class="text-sm text-gray-400 line-clamp-2 mb-3">{{ $material->description ?? 'Live session interaktif' }}</p>

                <div class="flex items-center gap-3 flex-wrap">
                    @if(!$user)
                    <span class="px-2.5 py-1 bg-gray-100 text-gray-600 text-xs rounded-full font-medium">
                        <i class="ri-lock-line mr-1"></i>Login untuk akses
                    </span>
                    <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-lg text-xs font-medium border border-purple-300 text-purple-500 hover:bg-purple-50 transition-colors">
                        Masuk
                    </a>
                    @elseif($userAccess && $userAccess->is_completed)
                    <span class="px-2.5 py-1 text-xs rounded-full font-medium" style="background-color: {{ $primaryColor }}20; color: {{ $primaryColor }}">
                        <i class="ri-check-line mr-1"></i>Sudah diikuti
                    </span>
                    <a href="{{ route('user.material.show', $material->material_id) }}" class="text-purple-500 text-sm font-medium hover:translate-x-1 transition-transform flex items-center">
                        Detail <i class="ri-arrow-right-line ml-1"></i>
                    </a>
                    @else
                    {{-- User logged in but no access: Beli button --}}
                    <span class="px-2.5 py-1 bg-purple-100 text-purple-700 text-xs rounded-full font-medium">
                        <i class="ri-live-line mr-1"></i>Live Session
                    </span>
                    <button onclick="buyIndividual('material', {{ $material->material_id }}, {{ $displayPrice }}, '{{ addslashes($material->title) }}')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-white hover:opacity-90 transition-opacity"
                            style="background-color: {{ $primaryColor }}">
                        <i class="ri-shopping-cart-line mr-1"></i>Beli Sekarang
                    </button>
                    @endif
                </div>
            </div>
            @if(!$material->thumbnail_url)
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>

@if($materials->hasPages())
<div class="mt-8">
    {{ $materials->links() }}
</div>
@endif

@else
<div class="text-center py-16">
    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="ri-live-line text-3xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada live session</h3>
    <p class="text-gray-400 text-sm">Live session akan muncul di sini.</p>
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
            <form id="purchaseForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
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
