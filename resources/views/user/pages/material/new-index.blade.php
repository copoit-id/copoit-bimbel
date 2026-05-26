@extends('user.layout.new-user')

@section('title', 'Materi Pembelajaran')

@section('content')
@php
$user = auth()->user();
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
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
    <a href="{{ route('user.package.my') }}?tab=materials" class="px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition-opacity text-white" style="background-color: {{ $primaryColor }}">
        <i class="ri-book-marked-line mr-1"></i>Materi Saya
    </a>
    @endif
</div>

<!-- Filter Tabs -->
<div class="flex gap-2 mb-6 overflow-x-auto pb-2">
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

<!-- Categories -->
@if(isset($categories) && $categories->count() > 0)
<div class="flex gap-2 mb-6 overflow-x-auto pb-2">
    @foreach($categories as $category)
    <a href="{{ route('user.material.category', $category->category_id) }}" 
       class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-full hover:bg-gray-50 transition-colors">
        @if($category->icon)
        <i class="{{ $category->icon }}" style="color: {{ $primaryColor }}"></i>
        @endif
        <span class="text-sm text-gray-700">{{ $category->name }}</span>
    </a>
    @endforeach
</div>
@endif

<!-- Materials Grid -->
@if(isset($materials) && $materials->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($materials as $material)
    <div class="bg-white rounded-xl overflow-hidden border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all group">
        @if($material->thumbnail_url)
        <div class="h-36">
            <img src="{{ $material->thumbnail_url }}" alt="{{ $material->title }}" class="w-full h-full object-cover">
        </div>
        @endif

        @php
        $userAccess = $material->userAccess->first();
        @endphp

        @if(!$material->thumbnail_url)
        <div class="p-4 flex items-start gap-4">
            <div class="w-14 h-14 {{ $material->type === 'video' ? 'bg-red-100 text-red-500' : ($material->type === 'document' ? 'bg-blue-100 text-blue-500' : 'bg-purple-100 text-purple-500') }} rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="{{ $material->type_icon }} text-2xl"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">{{ $material->type_label }}</span>
                    @if($material->duration_minutes)
                    <span class="text-xs text-gray-400">{{ $material->formatted_duration }}</span>
                    @endif
                </div>
                <h3 class="font-medium text-gray-800 text-sm line-clamp-2">{{ $material->title }}</h3>
                <p class="text-xs text-gray-400 mt-1 line-clamp-1">{{ $material->description ?? 'Tidak ada deskripsi' }}</p>
            </div>
        </div>
        @else
        <div class="p-4">
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">{{ $material->type_label }}</span>
                @if($material->duration_minutes)
                <span class="text-xs text-gray-400">{{ $material->formatted_duration }}</span>
                @endif
            </div>
            <h3 class="font-medium text-gray-800 text-sm line-clamp-2">{{ $material->title }}</h3>
            <p class="text-xs text-gray-400 mt-1 line-clamp-1">{{ $material->description ?? 'Tidak ada deskripsi' }}</p>
        </div>
        @endif

        <div class="px-4 pb-4 pt-3 border-t mt-auto">
            @if(!$user)
                {{-- Guest - perlu login --}}
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs text-gray-400"><i class="ri-lock-line mr-1"></i>Login untuk akses</span>
                </div>
                <a href="{{ route('login') }}"
                   class="flex items-center justify-center w-full py-2 rounded-lg text-sm font-medium border-2 hover:bg-gray-50 transition-colors"
                   style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                    <i class="ri-login-box-line mr-1"></i>
                    Masuk untuk Akses
                </a>
            @elseif($material->has_access)
                {{-- User has access --}}
                @if($userAccess && $userAccess->is_completed)
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs flex items-center gap-1" style="color: {{ $primaryColor }}">
                        <i class="ri-check-line"></i>Selesai
                    </span>
                </div>
                @elseif($userAccess && $userAccess->is_in_progress)
                <div class="flex items-center gap-2 mb-3">
                    <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full" style="width: {{ $userAccess->progress_percentage }}%; background-color: {{ $primaryColor }}"></div>
                    </div>
                    <span class="text-xs text-gray-500">{{ $userAccess->progress_percentage }}%</span>
                </div>
                @else
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs text-gray-400">Belum dimulai</span>
                </div>
                @endif

                <a href="{{ route('user.material.show', $material->material_id) }}"
                   class="flex items-center justify-center w-full py-2 rounded-lg text-sm font-medium text-white hover:opacity-90 transition-opacity"
                   style="background-color: {{ $primaryColor }}">
                    <i class="ri-play-circle-line mr-1"></i>
                    {{ $userAccess && $userAccess->is_in_progress ? 'Lanjutkan Belajar' : 'Lihat Materi' }}
                </a>
            @else
                {{-- User doesn't have access --}}
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs text-gray-400"><i class="ri-lock-line mr-1"></i>Belum diakses</span>
                </div>

                @if($material->access_via_package)
                <a href="{{ route('user.package.my') }}?tab=packages"
                   class="flex items-center justify-center w-full py-2 rounded-lg text-sm font-medium border-2 hover:bg-gray-50 transition-colors"
                   style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                    <i class="ri-shopping-bag-line mr-1"></i>
                    Dapatkan Akses
                </a>
                @elseif($material->price > 0)
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs text-gray-400"><i class="ri-shopping-cart-line mr-1"></i>Harga:</span>
                    <span class="text-sm font-bold" style="color: {{ $primaryColor }}">Rp {{ number_format($material->price, 0, ',', '.') }}</span>
                </div>
                <button onclick="buyIndividual('material', {{ $material->material_id }}, {{ $material->price }}, '{{ addslashes($material->title) }}')"
                   class="flex items-center justify-center w-full py-2 rounded-lg text-sm font-medium text-white hover:opacity-90 transition-opacity"
                   style="background-color: {{ $primaryColor }}">
                    <i class="ri-shopping-cart-line mr-1"></i>
                    Beli Sekarang
                </button>
                @else
                <button disabled class="flex items-center justify-center w-full py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-400 cursor-not-allowed">
                    <i class="ri-lock-line mr-1"></i>
                    Tidak Tersedia
                </button>
                @endif
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
        <i class="ri-book-open-line text-3xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada materi</h3>
    <p class="text-gray-400 text-sm">Materi pembelajaran akan segera tersedia.</p>
</div>
@endif

@php
$paymentModeConfig = $clientBranding['payment_mode'] ?? 'gateway';
$bankName = $clientBranding['payment_bank_name'] ?? '';
$accountNumber = $clientBranding['payment_account_number'] ?? '';
$accountHolder = $clientBranding['payment_account_holder'] ?? '';
@endphp

<!-- Purchase Modal for Individual Items -->
<div id="purchaseModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl">
            <h3 class="text-lg font-semibold text-gray-800">Beli <span id="purchaseTypeDisplay"></span></h3>
            <button onclick="closePurchaseModal()" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-lg transition-colors">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="p-5">
            <div id="purchaseError" class="hidden mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"></div>

            <!-- Item Info -->
            <div class="mb-4 p-4 bg-gray-50 rounded-xl">
                <p class="text-sm font-medium text-gray-600 mb-1">Item:</p>
                <p class="font-semibold text-gray-800" id="purchaseNameDisplay"></p>
                <div class="mt-2 pt-2 border-t border-gray-200">
                    <span class="text-sm text-gray-500">Total:</span>
                    <span class="text-lg font-bold ml-2" style="color: {{ $primaryColor }}"><span id="purchasePriceDisplay"></span></span>
                </div>
            </div>

            <!-- Bank Transfer Info -->
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
                </div>
            </div>

            <form id="purchaseForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Bukti Transfer <span class="text-red-500">*</span></label>
                        <input type="file" name="payment_proof" id="paymentProof" accept="image/*,.pdf" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                        <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, PDF. Maks: 20MB</p>
                        <div id="proofPreviewContainer" class="mt-3 hidden">
                            <p class="text-xs text-gray-500 mb-1">Preview:</p>
                            <img id="proofImagePreview" src="" alt="Preview" class="max-h-40 rounded-lg border border-gray-200">
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
                    <button type="button" onclick="closePurchaseModal()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl hover:bg-gray-50 font-medium">
                        Batal
                    </button>
                    <button type="submit" id="submitPurchaseBtn" class="flex-1 px-4 py-2.5 text-white rounded-xl font-medium" style="background-color: {{ $primaryColor }}">
                        Kirim Bukti Bayar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Preview uploaded file
document.getElementById('paymentProof')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const preview = document.getElementById('proofPreviewContainer');
        const img = document.getElementById('proofImagePreview');
        img.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
    }
});

// Handle purchase form submission
document.getElementById('purchaseForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('type', selectedPurchaseType);
    formData.append('id', selectedPurchaseId);

    const submitBtn = document.getElementById('submitPurchaseBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-2"></i>Mengirim...';

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
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            const errorEl = document.getElementById('purchaseError');
            errorEl.textContent = data.message || 'Terjadi kesalahan.';
            errorEl.classList.remove('hidden');
        }
    })
    .catch(err => {
        console.error(err);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        const errorEl = document.getElementById('purchaseError');
        errorEl.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
        errorEl.classList.remove('hidden');
    });
});

// Close modal on backdrop click
document.getElementById('purchaseModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePurchaseModal();
    }
});
</script>

@section('scripts')
<script>
const PAYMENT_MODE = '{{ $paymentModeConfig }}';
let selectedPurchaseType = null;
let selectedPurchaseId = null;
let selectedPurchasePrice = 0;
let selectedPurchaseName = '';

function buyIndividual(type, id, price, name) {
    selectedPurchaseType = type;
    selectedPurchaseId = id;
    selectedPurchasePrice = price;
    selectedPurchaseName = name;

    if (PAYMENT_MODE === 'manual') {
        // Show modal for manual payment
        document.getElementById('purchaseTypeDisplay').textContent = type === 'material' ? 'Materi' : 'Tryout';
        document.getElementById('purchaseNameDisplay').textContent = name;
        document.getElementById('purchasePriceDisplay').textContent = 'Rp ' + formatRupiah(price);
        document.getElementById('purchaseModal').classList.remove('hidden');
        document.getElementById('purchaseModal').classList.add('flex');
    } else {
        // Direct buy via API
        fetch('/user/pembelian/buy', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ type: type, id: id })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Berhasil!');
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                }
            } else {
                alert(data.message || 'Terjadi kesalahan');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan. Silakan coba lagi.');
        });
    }
}

function formatRupiah(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

function closePurchaseModal() {
    document.getElementById('purchaseModal').classList.add('hidden');
    document.getElementById('purchaseModal').classList.remove('flex');
    selectedPurchaseType = null;
    selectedPurchaseId = null;
}
</script>
@endsection
