@extends('user.layout.new-user')

@section('title', 'Paket')

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$paymentMode = $clientBranding['payment_mode'] ?? 'gateway';
$bankName = $clientBranding['payment_bank_name'] ?? '';
$accountNumber = $clientBranding['payment_account_number'] ?? '';
$accountHolder = $clientBranding['payment_account_holder'] ?? '';
$paymentUniqueCodeEnabled = (bool) ($clientBranding['payment_unique_code_enabled'] ?? true);
$publicDiscounts = $publicDiscounts ?? collect();
$discountsJson = $publicDiscounts->map(function($d) {
    return [
        'id' => $d->id,
        'code' => $d->code,
        'discount_type' => $d->discount_type,
        'discount_value' => (float)$d->discount_value,
        'max_discount_amount' => $d->max_discount_amount ? (float)$d->max_discount_amount : null,
        'min_purchase_amount' => (float)$d->min_purchase_amount,
        'formatted_value' => $d->formatted_value,
        'description' => $d->description,
        'ends_at' => $d->ends_at ? $d->ends_at->toIso8601String() : null,
    ];
})->values()->toArray();
@endphp

<!-- Header -->
<div x-data="packageManager()" id="package-container" class="w-full">
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

@if($tab === 'paid' && $publicDiscounts->isNotEmpty())
<div class="bg-white rounded-2xl p-6 mb-6 border border-gray-100 shadow-sm">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2.5">
            <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i class="ri-coupon-2-line text-xl"></i>
            </span>
            <div>
                <h4 class="font-bold text-gray-850 text-sm md:text-base leading-tight">Promo & Voucher Tersedia</h4>
                <p class="text-xs text-gray-400 mt-0.5">Pilih voucher untuk mendapatkan diskon langsung!</p>
            </div>
        </div>
        <!-- Active promo indicator -->
        <template x-if="activeDiscountCode">
            <button @click="selectDiscount(null)" class="text-xs font-semibold text-red-500 hover:text-red-650 transition-colors flex items-center gap-1">
                <i class="ri-close-circle-line"></i> Batalkan Promo
            </button>
        </template>
    </div>
    
    <!-- Voucher list grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($publicDiscounts as $discount)
        @php
            $isPercent = $discount->discount_type === 'percent';
            $valStr = $isPercent ? rtrim(rtrim(number_format((float) $discount->discount_value, 2, ',', '.'), '0'), ',') . '%' : 'Rp ' . number_format((float) $discount->discount_value, 0, ',', '.');
        @endphp
        <div 
            class="relative flex rounded-xl border overflow-hidden transition-all duration-300 group cursor-pointer"
            :class="isActive('{{ $discount->code }}') 
                ? 'border-emerald-500 bg-emerald-50/10 shadow-md shadow-emerald-50' 
                : 'border-gray-200 bg-white hover:border-gray-300 hover:shadow-sm'"
            @click="selectDiscount('{{ $discount->code }}')"
        >
            <!-- Left Ticket Part (Discount Value Indicator) -->
            <div class="w-1/3 p-4 flex flex-col justify-center items-center text-center relative text-white transition-colors duration-300 shrink-0"
                 :class="isActive('{{ $discount->code }}') ? 'bg-emerald-500' : 'bg-gray-800 group-hover:bg-gray-900'">
                
                <!-- Ticket Notch Left (Semi-circles on edges) -->
                <div class="absolute -left-1.5 top-1/2 -translate-y-1/2 w-3 h-3 bg-white border rounded-full transition-colors duration-300"
                     :class="isActive('{{ $discount->code }}') ? 'border-r-emerald-500' : 'border-r-gray-200'"></div>
                
                <!-- Inner dotted line separator between parts -->
                <div class="absolute right-0 top-0 bottom-0 border-r border-dashed border-white/30"></div>
                
                <span class="text-[9px] font-bold uppercase tracking-wider opacity-80">DISKON</span>
                <span class="text-base md:text-lg font-extrabold tracking-tight mt-0.5">{{ $valStr }}</span>
            </div>
            
            <!-- Right Ticket Part (Details & Action) -->
            <div class="flex-1 p-3.5 flex flex-col justify-between relative bg-white overflow-hidden">
                <!-- Ticket Notch Right -->
                <div class="absolute -right-1.5 top-1/2 -translate-y-1/2 w-3 h-3 bg-white border border-gray-250 rounded-full transition-colors duration-300"
                     :class="isActive('{{ $discount->code }}') ? 'border-l-emerald-500 bg-white' : 'bg-white'"></div>
                
                <div>
                    <!-- Promo Code Badge -->
                    <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                        <span class="px-2 py-0.5 bg-gray-55 rounded text-[10px] font-bold text-gray-700 font-mono tracking-wider uppercase border border-gray-200 transition-colors duration-300"
                              :class="isActive('{{ $discount->code }}') ? 'bg-emerald-100 border-emerald-200 text-emerald-800' : ''">
                            {{ $discount->code }}
                        </span>
                        @if($discount->min_purchase_amount > 0)
                        <span class="text-[9px] font-semibold text-gray-400">
                            Min. Rp {{ number_format($discount->min_purchase_amount, 0, ',', '.') }}
                        </span>
                        @endif
                    </div>
                    
                    <!-- Description -->
                    <p class="text-xs text-gray-600 font-medium line-clamp-1 mb-1 leading-snug">
                        {{ $discount->description ?: 'Potongan harga untuk pembelian paket.' }}
                    </p>
                </div>
                
                <!-- Bottom Info / Apply button -->
                <div class="flex items-center justify-between mt-2 pt-1.5 border-t border-gray-100">
                    <span class="text-[9px] flex items-center gap-1 transition-colors duration-300"
                          :class="getCountdownColor('{{ $discount->ends_at ? $discount->ends_at->toIso8601String() : '' }}')">
                        <i class="ri-time-line"></i>
                        <span x-text="getCountdown('{{ $discount->ends_at ? $discount->ends_at->toIso8601String() : '' }}') || 'Promo Terbatas'">
                            @if($discount->ends_at)
                                S/d {{ $discount->ends_at->format('d M Y') }}
                            @else
                                Promo Terbatas
                            @endif
                        </span>
                    </span>
                    
                    <!-- Gunakan Button -->
                    <button 
                        type="button"
                        class="text-[11px] font-bold px-2.5 py-0.5 rounded-lg transition-all duration-300 flex items-center gap-1"
                        :class="isActive('{{ $discount->code }}') 
                            ? 'bg-emerald-100 text-emerald-700' 
                            : 'bg-primary/10 text-primary hover:bg-primary/20'"
                        :style="!isActive('{{ $discount->code }}') ? 'color: ' + '{{ $primaryColor }}' + '; background-color: ' + '{{ $primaryColor }}' + '15' : ''"
                    >
                        <span x-text="isActive('{{ $discount->code }}') ? 'Aktif' : 'Gunakan'">Gunakan</span>
                        <template x-if="isActive('{{ $discount->code }}')">
                            <i class="ri-checkbox-circle-fill text-emerald-600 text-xs"></i>
                        </template>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

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
            <div class="absolute top-3 right-3 flex flex-col items-end gap-1">
                <!-- Crossed out original price -->
                <div x-show="isDiscountActiveFor({{ $package->package_id }}, {{ $package->price }})" 
                     class="bg-gray-900/60 backdrop-blur-sm text-gray-205 text-[10px] line-through px-2 py-0.5 rounded-full font-medium"
                     style="display: none;">
                    Rp {{ number_format($package->price, 0, ',', '.') }}
                </div>
                <!-- Active price -->
                <div class="px-3 py-1 rounded-full text-xs font-bold transition-all duration-300"
                     :class="isDiscountActiveFor({{ $package->package_id }}, {{ $package->price }}) ? 'bg-emerald-500 text-white' : ''"
                     :style="!isDiscountActiveFor({{ $package->package_id }}, {{ $package->price }}) ? 'background-color: {{ $primaryColor }}; color: white;' : ''">
                    <span x-text="getDisplayPrice({{ $package->package_id }}, {{ $package->price }})">Rp {{ number_format($package->price, 0, ',', '.') }}</span>
                </div>
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
            @php
                $features = json_decode($package->features ?? '[]', true);
                $features = is_array($features) ? array_filter($features) : [];
            @endphp
            @if(!empty($features))
            <div class="space-y-1.5 mb-4">
                @foreach ($features as $feature)
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
                        <button type="button" onclick="handleBuy({{ $package->package_id }}, {{ $package->price }}, @js($package->name))"
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

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-2">Kode Diskon</label>
                <div class="flex gap-2">
                    <input type="text" id="discountCodeInput" class="flex-1 border border-gray-300 rounded-lg px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Opsional">
                    <button type="button" onclick="applyDiscountCode()" class="px-4 py-2.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium">
                        Terapkan
                    </button>
                </div>
                <p id="discountInfo" class="hidden text-sm mt-2"></p>
            </div>

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
                        <span class="text-gray-500">Harga Paket</span>
                        <span class="font-semibold text-gray-800" id="baseAmountDisplay">Rp 0</span>
                    </div>
                    <div class="flex justify-between hidden" id="discountRow">
                        <span class="text-gray-500">Diskon</span>
                        <span class="font-semibold text-green-600" id="discountAmountDisplay">- Rp 0</span>
                    </div>
                    <div class="flex justify-between {{ $paymentUniqueCodeEnabled ? '' : 'hidden' }}" id="uniqueCodeRow">
                        <span class="text-gray-500">Kode Unik</span>
                        <span class="font-semibold text-gray-800" id="uniqueCodeDisplay">000</span>
                    </div>
                    <div class="flex justify-between border-t pt-2 mt-2">
                        <span class="text-gray-500">Total Bayar</span>
                        <span class="font-bold text-lg" style="color: {{ $primaryColor }}" id="paymentAmountDisplay">Rp 0</span>
                    </div>
                </div>
            </div>

            <form id="paymentForm" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="payment_unique_code" id="paymentUniqueCode" value="">
                <input type="hidden" name="discount_code" id="paymentDiscountCode" value="">
                <div class="space-y-4">
                    <div id="paymentProofSection">
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

@php
$paymentModeConfig = $clientBranding['payment_mode'] ?? 'gateway';
@endphp
@push('scripts')
<script>
const PAYMENT_MODE = '{{ $paymentModeConfig }}';
const PAYMENT_UNIQUE_CODE_ENABLED = @json($paymentUniqueCodeEnabled);
const MANUAL_PAYMENT_UNIQUE_CODES = @json($manualPaymentUniqueCodes ?? []);
let selectedPackageId = null;
let selectedPrice = 0;
let selectedPackageName = '';
let selectedDiscountCode = '';
let selectedDiscountAmount = 0;
let selectedPayableAmount = 0;

// Initialize Alpine.js packageManager component
document.addEventListener('alpine:init', () => {
    Alpine.data('packageManager', () => ({
        discounts: @js($discountsJson ?? []),
        activeDiscountCode: null,
        now: Date.now(),
        
        init() {
            const stored = sessionStorage.getItem('activeDiscountCode');
            if (stored) {
                const found = this.discounts.find(d => d.code === stored);
                if (found) {
                    this.activeDiscountCode = stored;
                }
            }
            
            // Start real-time countdown timer tick
            setInterval(() => {
                this.now = Date.now();
                
                // Auto-cancel active discount if it expires
                if (this.activeDiscountCode) {
                    const discount = this.discounts.find(d => d.code === this.activeDiscountCode);
                    if (discount) {
                        let endTime;
                        if (discount.ends_at) {
                            endTime = new Date(discount.ends_at).getTime();
                        } else {
                            const now = new Date();
                            const endOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);
                            endTime = endOfDay.getTime();
                        }
                        if (endTime - this.now <= 0) {
                            this.activeDiscountCode = null;
                            sessionStorage.removeItem('activeDiscountCode');
                        }
                    }
                }
            }, 1000);
        },

        selectDiscount(code) {
            if (this.activeDiscountCode === code) {
                this.activeDiscountCode = null;
                sessionStorage.removeItem('activeDiscountCode');
            } else {
                this.activeDiscountCode = code;
                sessionStorage.setItem('activeDiscountCode', code);
            }
        },

        isActive(code) {
            return this.activeDiscountCode === code;
        },

        isDiscountActiveFor(packageId, price) {
            if (!this.activeDiscountCode) return false;
            const discount = this.discounts.find(d => d.code === this.activeDiscountCode);
            if (!discount) return false;
            return price >= discount.min_purchase_amount;
        },

        getDisplayPrice(packageId, price) {
            if (!this.activeDiscountCode) {
                return 'Rp ' + this.formatNumber(price);
            }
            const discount = this.discounts.find(d => d.code === this.activeDiscountCode);
            if (!discount || price < discount.min_purchase_amount) {
                return 'Rp ' + this.formatNumber(price);
            }
            const discountAmount = this.calculateDiscount(price, discount);
            const finalPrice = price - discountAmount;
            return 'Rp ' + this.formatNumber(finalPrice);
        },

        calculateDiscount(price, discount) {
            if (price <= 0) return 0;
            if (price < discount.min_purchase_amount) return 0;
            
            let amount = 0;
            if (discount.discount_type === 'fixed') {
                amount = Math.min(price, discount.discount_value);
            } else {
                amount = Math.floor(price * (discount.discount_value / 100));
                if (discount.max_discount_amount !== null && discount.max_discount_amount !== undefined) {
                    amount = Math.min(amount, discount.max_discount_amount);
                }
            }
            return Math.min(price, Math.max(0, amount));
        },

        getCountdown(endsAtStr) {
            let endTime;
            if (endsAtStr) {
                endTime = new Date(endsAtStr).getTime();
            } else {
                // Countdown to end of today (23:59:59 today)
                const now = new Date();
                const endOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);
                endTime = endOfDay.getTime();
            }
            
            const diff = endTime - this.now;
            if (diff <= 0) return 'Expired';
            
            const secs = Math.floor(diff / 1000) % 60;
            const mins = Math.floor(diff / (1000 * 60)) % 60;
            const hours = Math.floor(diff / (1000 * 60 * 60)) % 24;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            
            let parts = [];
            if (days > 0) parts.push(days + 'd');
            parts.push(String(hours).padStart(2, '0') + 'j');
            parts.push(String(mins).padStart(2, '0') + 'm');
            parts.push(String(secs).padStart(2, '0') + 's');
            
            return 'Sisa ' + parts.join(' ');
        },

        getCountdownColor(endsAtStr) {
            let endTime;
            if (endsAtStr) {
                endTime = new Date(endsAtStr).getTime();
            } else {
                // Countdown to end of today
                const now = new Date();
                const endOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);
                endTime = endOfDay.getTime();
            }
            const diff = endTime - this.now;
            if (diff <= 0) return 'text-red-500 font-semibold';
            if (diff < 1000 * 60 * 60 * 24) return 'text-amber-600 font-semibold animate-pulse';
            return 'text-gray-400';
        },

        formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }
    }));
});

function getActivePromoCode() {
    const el = document.getElementById('package-container');
    if (el && window.Alpine) {
        try {
            return Alpine.$data(el).activeDiscountCode || '';
        } catch(e) {
            console.error(e);
        }
    }
    return '';
}

function handleBuy(packageId, price, packageName) {
    selectedPackageId = packageId;
    selectedPrice = price;
    selectedPackageName = packageName;
    
    // Auto-inject selected promo code from Alpine component if any
    const activeCode = getActivePromoCode();
    selectedDiscountCode = activeCode || '';
    selectedDiscountAmount = 0;
    selectedPayableAmount = Number(price);

    if (PAYMENT_MODE === 'manual') {
        const uniqueCode = PAYMENT_UNIQUE_CODE_ENABLED ? Number(MANUAL_PAYMENT_UNIQUE_CODES[packageId] || 0) : 0;
        const totalAmount = selectedPayableAmount + uniqueCode;

        document.getElementById('baseAmountDisplay').textContent = 'Rp ' + formatNumber(price);
        document.getElementById('discountRow').classList.add('hidden');
        document.getElementById('discountAmountDisplay').textContent = '- Rp 0';
        document.getElementById('uniqueCodeRow').classList.toggle('hidden', !PAYMENT_UNIQUE_CODE_ENABLED);
        document.getElementById('uniqueCodeDisplay').textContent = String(uniqueCode).padStart(3, '0');
        document.getElementById('paymentUniqueCode').value = PAYMENT_UNIQUE_CODE_ENABLED ? uniqueCode : '';
        document.getElementById('paymentDiscountCode').value = selectedDiscountCode;
        document.getElementById('paymentAmountDisplay').textContent = 'Rp ' + formatNumber(totalAmount);
        document.getElementById('paymentError').classList.add('hidden');
        document.getElementById('discountCodeInput').value = selectedDiscountCode;
        document.getElementById('discountInfo').classList.add('hidden');
        document.getElementById('paymentProof').value = '';
        document.getElementById('paymentProof').required = true;
        document.getElementById('paymentProofSection').classList.remove('hidden');
        document.getElementById('submitPaymentBtn').textContent = 'Kirim Bukti Bayar';
        document.getElementById('proofPreview').classList.add('hidden');
        document.getElementById('paymentModal').classList.remove('hidden');
        document.getElementById('paymentModal').classList.add('flex');
        
        if (selectedDiscountCode) {
            applyDiscountCode();
        } else {
            checkAutomaticReferralDiscount();
        }
    } else {
        // Gateway mode - always use AJAX to handle redirect_url from gateway
        const form = document.querySelector(`form[data-package-id="${packageId}"]`);
        
        // If a promo code is already selected on the page, use it directly. Otherwise, ask user.
        let discountCode = selectedDiscountCode;
        if (!discountCode) {
            discountCode = window.prompt('Masukkan kode diskon jika ada. Kosongkan jika tidak ada.', '') || '';
        }
        
        const submitBtn = form.querySelector('button');
        const originalText = submitBtn.innerHTML;
        const formData = new FormData(form);

        if (discountCode.trim() !== '') {
            formData.set('discount_code', discountCode.trim().toUpperCase());
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-1"></i>Memproses...';

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData
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

function updateDiscountDisplay(code, discountAmount, payableAmount) {
    const uniqueCode = PAYMENT_UNIQUE_CODE_ENABLED ? Number(MANUAL_PAYMENT_UNIQUE_CODES[selectedPackageId] || 0) : 0;

    selectedDiscountCode = code || '';
    selectedDiscountAmount = Number(discountAmount || 0);
    selectedPayableAmount = Number(payableAmount || selectedPrice);
    document.getElementById('paymentDiscountCode').value = selectedDiscountCode === 'REFERRAL' ? '' : selectedDiscountCode;
    document.getElementById('discountAmountDisplay').textContent = '- Rp ' + formatNumber(selectedDiscountAmount);
    document.getElementById('discountRow').classList.toggle('hidden', selectedDiscountAmount <= 0);

    const effectiveUniqueCode = selectedPayableAmount > 0 ? uniqueCode : 0;
    document.getElementById('uniqueCodeRow').classList.toggle('hidden', !PAYMENT_UNIQUE_CODE_ENABLED || selectedPayableAmount <= 0);
    document.getElementById('paymentUniqueCode').value = PAYMENT_UNIQUE_CODE_ENABLED && selectedPayableAmount > 0 ? uniqueCode : '';
    document.getElementById('paymentProof').required = selectedPayableAmount > 0;
    document.getElementById('paymentProofSection').classList.toggle('hidden', selectedPayableAmount <= 0);
    document.getElementById('submitPaymentBtn').textContent = selectedPayableAmount <= 0 ? 'Aktifkan Paket' : 'Kirim Bukti Bayar';
    document.getElementById('paymentAmountDisplay').textContent = 'Rp ' + formatNumber(selectedPayableAmount + effectiveUniqueCode);
}

function checkAutomaticReferralDiscount() {
    const formData = new FormData();

    fetch('/user/paket-pembelian/' + selectedPackageId + '/discount/preview', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: formData,
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.code || Number(data.discount_amount || 0) <= 0) {
            return;
        }

        updateDiscountDisplay(data.code, data.discount_amount, data.payable_amount);
        const info = document.getElementById('discountInfo');
        info.textContent = 'Diskon referral otomatis diterapkan.';
        info.className = 'text-sm mt-2 text-green-600';
    })
    .catch(() => {});
}

function applyDiscountCode() {
    const input = document.getElementById('discountCodeInput');
    const info = document.getElementById('discountInfo');
    const code = (input.value || '').trim().toUpperCase();
    const uniqueCode = PAYMENT_UNIQUE_CODE_ENABLED ? Number(MANUAL_PAYMENT_UNIQUE_CODES[selectedPackageId] || 0) : 0;

    if (!code) {
        selectedDiscountCode = '';
        selectedDiscountAmount = 0;
        selectedPayableAmount = Number(selectedPrice);
        document.getElementById('paymentDiscountCode').value = '';
        document.getElementById('discountRow').classList.add('hidden');
        document.getElementById('uniqueCodeRow').classList.toggle('hidden', !PAYMENT_UNIQUE_CODE_ENABLED);
        document.getElementById('paymentUniqueCode').value = PAYMENT_UNIQUE_CODE_ENABLED ? uniqueCode : '';
        document.getElementById('paymentProof').required = true;
        document.getElementById('paymentProofSection').classList.remove('hidden');
        document.getElementById('submitPaymentBtn').textContent = 'Kirim Bukti Bayar';
        document.getElementById('paymentAmountDisplay').textContent = 'Rp ' + formatNumber(selectedPayableAmount + uniqueCode);
        info.className = 'hidden text-sm mt-2';
        return;
    }

    info.textContent = 'Mengecek kode diskon...';
    info.className = 'text-sm mt-2 text-gray-500';

    const formData = new FormData();
    formData.append('discount_code', code);

    fetch('/user/paket-pembelian/' + selectedPackageId + '/discount/preview', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: formData,
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (!ok || !data.success) {
            selectedDiscountCode = '';
            selectedDiscountAmount = 0;
            selectedPayableAmount = Number(selectedPrice);
            document.getElementById('paymentDiscountCode').value = '';
            document.getElementById('discountRow').classList.add('hidden');
            document.getElementById('paymentAmountDisplay').textContent = 'Rp ' + formatNumber(selectedPayableAmount + uniqueCode);
            info.textContent = data.message || 'Kode diskon tidak valid.';
            info.className = 'text-sm mt-2 text-red-600';
            return;
        }

        updateDiscountDisplay(data.code || code, data.discount_amount, data.payable_amount);
        info.textContent = 'Kode diskon berhasil diterapkan.';
        info.className = 'text-sm mt-2 text-green-600';
    })
    .catch(() => {
        info.textContent = 'Gagal mengecek kode diskon. Silakan coba lagi.';
        info.className = 'text-sm mt-2 text-red-600';
    });
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
    const typedDiscount = (document.getElementById('discountCodeInput')?.value || '').trim();
    const appliedDiscount = (document.getElementById('paymentDiscountCode')?.value || '').trim();
    if (typedDiscount && !appliedDiscount) {
        const errorEl = document.getElementById('paymentError');
        errorEl.textContent = 'Klik Terapkan dulu untuk memakai kode diskon.';
        errorEl.classList.remove('hidden');
        return;
    }

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
@endpush
