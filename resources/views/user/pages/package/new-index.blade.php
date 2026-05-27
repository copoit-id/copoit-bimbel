@extends('user.layout.new-user')

@section('title', 'Paket')

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$paymentMode = $clientBranding['payment_mode'] ?? 'gateway';
$bankName = $clientBranding['payment_bank_name'] ?? '';
$accountNumber = $clientBranding['payment_account_number'] ?? '';
$accountHolder = $clientBranding['payment_account_holder'] ?? '';
@endphp

<!-- Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('user.dashboard.index') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Paket</h1>
        <p class="text-gray-500 text-sm">Pilih paket yang sesuai dengan kebutuhanmu</p>
    </div>
</div>

<!-- Tabs -->
<div class="bg-white rounded-2xl p-2 mb-6 border border-gray-100 inline-flex">
    <a href="{{ route('user.package.index', ['tab' => 'paid']) }}" 
       class="px-6 py-2.5 rounded-xl font-medium transition-all {{ $tab === 'paid' ? 'text-white' : 'text-gray-600 hover:bg-gray-50' }}"
       style="{{ $tab === 'paid' ? 'background-color: ' . $primaryColor : '' }}">
        <i class="ri-vip-crown-line mr-2"></i>Berbayar
    </a>
    <a href="{{ route('user.package.index', ['tab' => 'free']) }}" 
       class="px-6 py-2.5 rounded-xl font-medium transition-all {{ $tab === 'free' ? 'text-white' : 'text-gray-600 hover:bg-gray-50' }}"
       style="{{ $tab === 'free' ? 'background-color: ' . $primaryColor : '' }}">
        <i class="ri-gift-line mr-2"></i>Gratis
    </a>
</div>

<!-- Packages Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($packages as $package)
    @php
    $isOwned = in_array((int) $package->package_id, $userOwnedPackageIds);
    @endphp
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all group flex flex-col">
        <!-- Package Image/Header -->
        <div class="h-32 relative overflow-hidden shrink-0" style="background: linear-gradient(135deg, {{ $primaryColor }}20 0%, {{ $primaryColor }}10 100%);">
            @if($package->image)
            @php
                $pkgImgExt = strtolower(pathinfo($package->image, PATHINFO_EXTENSION));
                $pkgImgIsVid = in_array($pkgImgExt, ['mp4','webm','mov','m4v'], true);
                $pkgImgUrl = Storage::url($package->image);
            @endphp
            @if($pkgImgIsVid)
            <video src="{{ $pkgImgUrl }}" class="w-full h-full object-cover" controls preload="metadata" playsinline></video>
            @else
            <img src="{{ $pkgImgUrl }}" alt="{{ $package->name }}" class="w-full h-full object-cover">
            @endif
            @else
            <div class="w-full h-full flex items-center justify-center">
                <i class="ri-book-3-line text-6xl" style="color: {{ $primaryColor }}40"></i>
            </div>
            @endif

            @if($tab === 'paid')
            <div class="absolute top-3 right-3 px-3 py-1 rounded-full text-xs font-semibold" style="background-color: {{ $primaryColor }}; color: white;">
                Rp {{ number_format($package->price, 0, ',', '.') }}
            </div>
            @else
            <div class="absolute top-3 right-3 px-3 py-1 rounded-full text-xs font-semibold bg-green-500 text-white">
                GRATIS
            </div>
            @endif
        </div>

        <!-- Content -->
        <div class="p-5 flex flex-col flex-1">
            <a href="{{ route('user.package.detail', $package->package_id) }}" class="block">
                <h3 class="font-bold text-lg text-gray-800 mb-2 hover:text-primary transition-colors">{{ $package->name }}</h3>
            </a>
            <div class="text-gray-500 text-sm mb-4 line-clamp-2">{!! $package->description ?? 'Paket belajar lengkap dengan materi dan tryout.' !!}</div>

            <!-- Features -->
            @if($package->features)
            <div class="space-y-1.5 mb-4">
                @foreach (json_decode($package->features) as $feature)
                <div class="flex items-center text-sm text-gray-600">
                    <i class="ri-checkbox-circle-fill mr-2 text-green"></i>
                    <span>{{ $feature }}</span>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="flex gap-2 mt-auto pt-2">
                <a href="{{ route('user.package.detail', $package->package_id) }}"
                   class="flex-1 py-2.5 rounded-xl text-center font-medium border transition-all hover:bg-gray-50"
                   style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                    <i class="ri-eye-line mr-1"></i>Detail
                </a>
                @auth
                    @if($isOwned)
                    <a href="{{ route('user.package.show', $package->package_id) }}"
                       class="flex-1 py-2.5 rounded-xl text-center font-medium text-white hover:opacity-90 transition-opacity"
                       style="background-color: {{ $primaryColor }}">
                        <i class="ri-play-circle-line mr-1"></i>Mulai
                    </a>
                    @elseif($tab === 'free')
                    <form action="{{ route('user.event.join', $package->package_id) }}" method="POST" class="claim-form flex-1">
                        @csrf
                        <button type="submit"
                                class="w-full py-2.5 rounded-xl text-center font-medium text-white hover:opacity-90 transition-opacity"
                                style="background-color: {{ $primaryColor }}">
                            <i class="ri-gift-line mr-1"></i>Klaim
                        </button>
                    </form>
                    @else
                    <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST" class="buy-form flex-1" data-package-id="{{ $package->package_id }}" data-price="{{ $package->price }}">
                        @csrf
                        <button type="button" onclick="handleBuy({{ $package->package_id }}, {{ $package->price }}, '{{ $package->name }}')"
                                class="w-full py-2.5 rounded-xl text-center font-medium text-white hover:opacity-90 transition-opacity"
                                style="background-color: {{ $primaryColor }}">
                            <i class="ri-shopping-cart-line mr-1"></i>Beli
                        </button>
                    </form>
                    @endif
                @else
                <a href="{{ route('login') }}"
                   class="flex-1 py-2.5 rounded-xl text-center font-medium text-white hover:opacity-90 transition-opacity"
                   style="background-color: {{ $primaryColor }}">
                    <i class="ri-login-box-line mr-1"></i>Masuk
                </a>
                @endauth
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-full text-center py-12">
        <div class="w-24 h-24 rounded-full mx-auto mb-4 flex items-center justify-center" style="background-color: {{ $primaryColor }}10">
            <i class="ri-inbox-line text-4xl" style="color: {{ $primaryColor }}"></i>
        </div>
        <h3 class="font-semibold text-gray-700 mb-1">Belum ada paket</h3>
        <p class="text-gray-400 text-sm">Paket {{ $tab === 'paid' ? 'berbayar' : 'gratis' }} akan segera tersedia.</p>
    </div>
    @endforelse
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center backdrop-blur-sm">
    <div class="bg-white px-6 py-5 rounded-2xl shadow-xl flex items-center gap-3">
        <div class="animate-spin rounded-full h-8 w-8 border-2 border-gray-300 border-t-transparent" style="border-top-color: {{ $primaryColor }}"></div>
        <p class="text-sm font-medium text-gray-700">Memproses...</p>
    </div>
</div>

<!-- Payment Modal for Manual Transfer -->
<div id="paymentModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl">
            <h3 class="text-lg font-semibold text-gray-800">Pembayaran Manual</h3>
            <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-lg transition-colors">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="p-5">
            <div id="paymentError" class="hidden mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"></div>

            <!-- Bank Transfer Info -->
            <div class="bg-gray-50 rounded-xl p-4 mb-5">
                <p class="text-sm font-medium text-gray-600 mb-3">Silakan transfer ke rekening berikut:</p>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Bank</span>
                        <span class="font-semibold text-gray-800">{{ $bankName ?: 'Belum dikonfigurasi' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nomor Rekening</span>
                        <span class="font-semibold text-gray-800" id="accountNumberDisplay">{{ $accountNumber ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Atas Nama</span>
                        <span class="font-semibold text-gray-800">{{ $accountHolder ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between border-t pt-2 mt-2">
                        <span class="text-gray-500">Jumlah</span>
                        <span class="font-bold text-lg" style="color: {{ $primaryColor }}" id="paymentAmountDisplay">Rp 0</span>
                    </div>
                </div>
            </div>

            <form id="paymentForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Bukti Transfer <span class="text-red-500">*</span></label>
                        <input type="file" name="payment_proof" id="paymentProof" accept="image/*,.pdf" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                        <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, PDF. Maks: 20MB</p>
                        <div id="proofPreview" class="mt-3 hidden">
                            <p class="text-xs text-gray-500 mb-1">Preview:</p>
                            <img id="proofImage" src="" alt="Preview" class="max-h-40 rounded-lg border border-gray-200">
                        </div>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                        <p class="text-sm text-amber-800">
                            <i class="ri-information-line mr-1"></i>
                            Setelah upload, silakan tunggu verifikasi dari admin. Paket akan aktif setelah pembayaran dikonfirmasi.
                        </p>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closePaymentModal()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl hover:bg-gray-50 font-medium">
                        Batal
                    </button>
                    <button type="submit" id="submitPaymentBtn" class="flex-1 px-4 py-2.5 text-white rounded-xl font-medium" style="background-color: {{ $primaryColor }}">
                        Kirim Bukti Bayar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
<div id="successToast" class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-xl shadow-lg flex items-center gap-3 z-50">
    <i class="ri-check-line text-xl"></i>
    <span>{{ session('success') }}</span>
</div>
<script>
setTimeout(() => {
    document.getElementById('successToast')?.remove();
}, 3000);
</script>
@endif

@if(session('error'))
<div id="errorToast" class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-xl shadow-lg flex items-center gap-3 z-50">
    <i class="ri-close-circle-line text-xl"></i>
    <span>{{ session('error') }}</span>
</div>
<script>
setTimeout(() => {
    document.getElementById('errorToast')?.remove();
}, 3000);
</script>
@endif
@endsection

@section('scripts')
@php
$paymentModeConfig = $clientBranding['payment_mode'] ?? 'gateway';
@endphp
<script>
const PAYMENT_MODE = '{{ $paymentModeConfig }}';
let selectedPackageId = null;
let selectedPrice = 0;
let selectedPackageName = '';

function handleBuy(packageId, price, packageName) {
    selectedPackageId = packageId;
    selectedPrice = price;
    selectedPackageName = packageName;

    if (PAYMENT_MODE === 'manual') {
        document.getElementById('paymentAmountDisplay').textContent = 'Rp ' + formatNumber(price);
        document.getElementById('paymentError').classList.add('hidden');
        document.getElementById('paymentProof').value = '';
        document.getElementById('proofPreview').classList.add('hidden');
        document.getElementById('paymentModal').classList.remove('hidden');
        document.getElementById('paymentModal').classList.add('flex');
    } else {
        // Gateway mode - always use AJAX to handle redirect_url from gateway
        const form = document.querySelector(`form[data-package-id="${packageId}"]`);
        const submitBtn = form.querySelector('button');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-1"></i>Memproses...';

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form)
        })
        .then(r => r.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            if (data.success && data.redirect_url) {
                window.location.href = data.redirect_url;
            } else {
                alert(data.message || 'Gagal membuat pembayaran. Coba lagi nanti.');
            }
        })
        .catch(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            alert('Terjadi kesalahan. Silakan coba lagi.');
        });
    }
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
    document.getElementById('paymentModal').classList.remove('flex');
    selectedPackageId = null;
}

function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

// Preview uploaded file
document.getElementById('paymentProof')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const preview = document.getElementById('proofPreview');
        const img = document.getElementById('proofImage');
        img.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
    }
});

// Handle payment form submission
document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = document.getElementById('submitPaymentBtn');
    const originalText = submitBtn.innerHTML;

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-2"></i>Mengirim...';

    fetch('/user/paket-pembelian/' + selectedPackageId + '/buy', {
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
            closePaymentModal();
            alert(data.message || 'Bukti pembayaran berhasil dikirim!');
            window.location.href = '{{ route("user.package.riwayatPembelian") }}';
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            const errorEl = document.getElementById('paymentError');
            errorEl.textContent = data.message || 'Terjadi kesalahan. Silakan coba lagi.';
            errorEl.classList.remove('hidden');
        }
    })
    .catch(err => {
        console.error(err);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        const errorEl = document.getElementById('paymentError');
        errorEl.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
        errorEl.classList.remove('hidden');
    });
});

// Close modal on backdrop click
document.getElementById('paymentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});

$(document).ready(function() {
    // Handle claim form (free packages)
    $('.claim-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const btn = form.find('button');
        const originalText = btn.html();

        btn.prop('disabled', true).html('<i class="ri-loader-4-line animate-spin mr-2"></i>Memproses...');
        $('#loadingModal').removeClass('hidden').addClass('flex');

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                $('#loadingModal').addClass('hidden').removeClass('flex');
                if (response.success) {
                    window.location.href = response.redirect_url || '{{ route("user.package.my") }}';
                } else {
                    btn.prop('disabled', false).html(originalText);
                    alert(response.message || 'Terjadi kesalahan');
                }
            },
            error: function(xhr) {
                $('#loadingModal').addClass('hidden').removeClass('flex');
                btn.prop('disabled', false).html(originalText);
                const msg = xhr.responseJSON?.message || 'Terjadi kesalahan. Silakan coba lagi.';
                alert(msg);
            }
        });
    });
});
</script>
@endsection
