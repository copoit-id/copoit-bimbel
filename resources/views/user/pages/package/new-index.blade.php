@php
    $useGeneralLayout = request('layout') === 'landing' || (!auth()->check() && request()->routeIs('user.package.index'));
@endphp
@extends($useGeneralLayout ? 'general.layout' : 'user.layout.new-user')

@section('title', 'Paket')

@section('content')
@if($useGeneralLayout)
<div class="mx-auto max-w-7xl px-4 pt-32 pb-16 sm:px-6 sm:pt-40 lg:px-8 bg-gray-50 min-h-screen">
@endif

@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$paymentMode = $clientBranding['payment_mode'] ?? 'gateway';
$bankName = $clientBranding['payment_bank_name'] ?? '';
$accountNumber = $clientBranding['payment_account_number'] ?? '';
$accountHolder = $clientBranding['payment_account_holder'] ?? '';
$paymentBankNote = $clientBranding['payment_bank_note'] ?? '';
$paymentUniqueCodeEnabled = (bool) ($clientBranding['payment_unique_code_enabled'] ?? true);
$publicDiscounts = $publicDiscounts ?? collect();
$packageAutomaticDiscounts = $packageAutomaticDiscounts ?? [];
$affiliateDiscountPreview = $affiliateDiscountPreview ?? null;
$hasCombinedPaymentFlash = is_array(session('combined_ai_payment'));
$combinedAiPayment = array_replace(
    is_array($combinedAiPayment ?? null) ? $combinedAiPayment : [],
    is_array(session('combined_ai_payment')) ? session('combined_ai_payment') : [],
);
$aiGatewayPlans = collect($aiGatewayPlans ?? [])->filter(fn ($plan) => (int) data_get($plan, 'token_limit', 0) > 0)->values();
$defaultAiGatewayPlanId = (int) data_get(
    $aiGatewayPlans->first(fn ($plan) => (int) data_get($plan, 'price', 0) > 0),
    'id',
    0,
);
$aiChatLabel = function ($plan): string {
    $chatLimit = (int) data_get($plan, 'chat_limit', 0);

    return $chatLimit > 0
        ? number_format($chatLimit, 0, ',', '.') . ' chat AI'
        : 'Chat AI unlimited';
};
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
$automaticDiscountsJson = collect($packageAutomaticDiscounts)->mapWithKeys(function($discount, $packageId) {
    return [(string) $packageId => $discount];
})->toArray();
$aiGatewayPlansJson = $aiGatewayPlans->map(fn ($plan) => [
    'id' => (int) data_get($plan, 'id'),
    'name' => (string) data_get($plan, 'name'),
    'price' => (int) data_get($plan, 'price', 0),
    'chat_limit' => (int) data_get($plan, 'chat_limit', 0),
    'duration_days' => (int) data_get($plan, 'duration_days', 0),
])->all();
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

<form method="GET" action="{{ route('user.package.index') }}" class="bg-white border border-gray-100 rounded-xl p-3 mb-6 flex flex-col md:flex-row gap-3">
    <div class="flex-1">
        <label for="package-search" class="sr-only">Cari paket</label>
        <div class="relative">
            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input id="package-search" type="search" name="search" value="{{ request('search', $search ?? '') }}"
                   placeholder="Cari berdasarkan nama"
                   class="w-full rounded-lg border border-gray-200 pl-10 pr-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                   style="--tw-ring-color: {{ $primaryColor }}">
        </div>
    </div>
    <div class="flex gap-2">
        <label for="package-sort" class="sr-only">Urutkan</label>
        <select id="package-sort" name="sort" class="min-w-[180px] rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
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

@if($affiliateDiscountPreview || $publicDiscounts->isNotEmpty())
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
        @if($affiliateDiscountPreview)
        <div class="relative flex rounded-xl border border-emerald-500 overflow-hidden bg-emerald-50/10 shadow-md shadow-emerald-50">
            <div class="w-1/3 p-4 flex flex-col justify-center items-center text-center relative text-white bg-emerald-500 shrink-0">
                <div class="absolute -left-1.5 top-1/2 -translate-y-1/2 w-3 h-3 bg-white border border-r-emerald-500 rounded-full"></div>
                <div class="absolute right-0 top-0 bottom-0 border-r border-dashed border-white/30"></div>
                <span class="text-[9px] font-bold uppercase tracking-wider opacity-80">AFFILIATE</span>
                <span class="text-base md:text-lg font-extrabold tracking-tight mt-0.5">{{ $affiliateDiscountPreview['label'] }}</span>
            </div>
            <div class="flex-1 p-3.5 flex flex-col justify-between relative bg-white overflow-hidden">
                <div class="absolute -right-1.5 top-1/2 -translate-y-1/2 w-3 h-3 bg-white border border-l-emerald-500 rounded-full"></div>
                <div>
                    <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono tracking-wider uppercase border bg-emerald-100 border-emerald-200 text-emerald-800">
                            {{ $affiliateDiscountPreview['code'] }}
                        </span>
                        <span class="text-[9px] font-semibold text-emerald-600">Otomatis</span>
                    </div>
                    <p class="text-xs text-gray-600 font-medium line-clamp-2 mb-1 leading-snug">
                        Diskon khusus karena kamu daftar dari link affiliate. Otomatis dipakai saat checkout paket pertama jika tidak memakai voucher lain.
                    </p>
                </div>
                <div class="flex items-center justify-between mt-2 pt-1.5 border-t border-gray-100">
                    <span class="text-[9px] flex items-center gap-1 text-emerald-600">
                        <i class="ri-checkbox-circle-fill"></i>
                        Siap dipakai
                    </span>
                    <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-lg bg-emerald-100 text-emerald-700 flex items-center gap-1">
                        Aktif
                        <i class="ri-checkbox-circle-fill text-emerald-600 text-xs"></i>
                    </span>
                </div>
            </div>
        </div>
        @endif

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
    $isPendingConditional = in_array((int) $package->package_id, $pendingConditionalPackageIds ?? [], true);
    $pendingPackagePayment = ($pendingPackagePaymentsByPackage ?? collect())->get((int) $package->package_id);
    $isPendingManualPayment = $pendingPackagePayment && $pendingPackagePayment->payment_method === 'manual';
    $isPendingGatewayPayment = $pendingPackagePayment && $pendingPackagePayment->payment_method !== 'manual';
    $hasPendingAiPayment = is_array($combinedAiPayment)
        && data_get($combinedAiPayment, 'ai_payment_pending')
        && data_get($combinedAiPayment, 'product_type') === 'package'
        && (int) data_get($combinedAiPayment, 'product_item_id') === (int) $package->package_id;
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

            @if($package->type_price === 'paid')
            <div class="absolute top-3 right-3 flex flex-col items-end gap-1">
                <!-- Crossed out original price -->
                <div x-show="hasAnyDiscountFor({{ $package->package_id }}, {{ $package->price }})" 
                     class="bg-gray-900/60 backdrop-blur-sm text-gray-205 text-[10px] line-through px-2 py-0.5 rounded-full font-medium"
                     style="display: none;">
                    Rp {{ number_format($package->price, 0, ',', '.') }}
                </div>
                <!-- Active price -->
                <div class="px-3 py-1 rounded-full text-xs font-bold transition-all duration-300"
                     :class="hasAnyDiscountFor({{ $package->package_id }}, {{ $package->price }}) ? 'bg-emerald-500 text-white' : ''"
                     :style="!hasAnyDiscountFor({{ $package->package_id }}, {{ $package->price }}) ? 'background-color: {{ $primaryColor }}; color: white;' : ''">
                    <span x-text="getDisplayPrice({{ $package->package_id }}, {{ $package->price }})">Rp {{ number_format($package->price, 0, ',', '.') }}</span>
                </div>
                <template x-if="getAutomaticDiscount({{ $package->package_id }}) && !activeDiscountCode">
                    <div class="bg-emerald-50 text-emerald-700 text-[10px] px-2 py-0.5 rounded-full font-semibold border border-emerald-100 flex items-center gap-1">
                        <i class="ri-time-line"></i>
                        <span x-text="getCountdown(getAutomaticDiscount({{ $package->package_id }}).ends_at)"></span>
                    </div>
                </template>
            </div>
            @else
            <div class="absolute top-3 right-3 px-3 py-1 rounded-full text-xs font-semibold {{ $package->type_price === 'free_conditional' ? 'bg-amber-500' : 'bg-green-500' }} text-white">
                {{ $package->type_price === 'free_conditional' ? 'GRATIS BERSYARAT' : 'GRATIS' }}
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
                   class="flex-1 min-h-[44px] px-3 py-2.5 rounded-xl inline-flex items-center justify-center gap-1.5 text-center text-sm font-medium leading-tight border transition-all hover:bg-gray-50"
                   style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                    <i class="ri-eye-line"></i><span>Detail</span>
                </a>
                @auth
                    @if($hasPendingAiPayment)
                    <button type="button" onclick="openResumePaymentLinksModal()"
                            class="flex-1 min-h-[44px] px-3 py-2.5 rounded-xl inline-flex items-center justify-center gap-1.5 text-center text-sm font-medium leading-tight text-white hover:opacity-90 transition-opacity"
                            style="background-color: {{ $primaryColor }}">
                        <i class="ri-time-line"></i><span>Lanjutkan Pembayaran</span>
                    </button>
                    @elseif($isOwned)
                    <a href="{{ route('user.package.show', $package->package_id) }}"
                       class="flex-1 min-h-[44px] px-3 py-2.5 rounded-xl inline-flex items-center justify-center gap-1.5 text-center text-sm font-medium leading-tight text-white hover:opacity-90 transition-opacity"
                       style="background-color: {{ $primaryColor }}">
                        <i class="ri-play-circle-line"></i><span>Mulai</span>
                    </a>
                    @elseif($package->type_price === 'free_conditional' && $package->free_claim_requirement_type !== 'completed_tryout' && $isPendingConditional)
                    <button type="button" disabled
                            class="flex-1 min-h-[44px] px-3 py-2.5 rounded-xl inline-flex items-center justify-center gap-1.5 text-center text-sm font-medium leading-tight bg-amber-100 text-amber-700 cursor-not-allowed">
                        <i class="ri-time-line"></i><span>Menunggu Verifikasi</span>
                    </button>
                    @elseif($isPendingManualPayment)
                    <button type="button" disabled
                            class="flex-1 min-h-[44px] px-3 py-2.5 rounded-xl inline-flex items-center justify-center gap-1.5 text-center text-sm font-medium leading-tight bg-amber-100 text-amber-700 cursor-not-allowed">
                        <i class="ri-time-line"></i><span>On Review</span>
                    </button>
                    @elseif($isPendingGatewayPayment)
                    <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST" class="buy-form flex-1 flex flex-col gap-2" data-package-id="{{ $package->package_id }}" data-price="{{ $package->price }}">
                        @csrf
                        <button type="button" onclick="handleBuy({{ $package->package_id }}, {{ $package->price }}, @js($package->name))"
                                class="w-full min-h-[44px] px-3 py-2.5 rounded-xl inline-flex items-center justify-center gap-1.5 text-center text-sm font-medium leading-tight text-white hover:opacity-90 transition-opacity"
                                style="background-color: {{ $primaryColor }}">
                            <i class="ri-time-line"></i><span>Lanjutkan Pembayaran</span>
                        </button>
                    </form>
                    @elseif($package->type_price === 'free_conditional' && $package->free_claim_requirement_type === 'completed_tryout' && $package->freeClaimTryout)
                    <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST" class="claim-form flex-1 flex">
                        @csrf
                        <button type="submit" class="w-full min-h-[44px] px-3 py-2.5 rounded-xl inline-flex items-center justify-center gap-1.5 text-center text-sm font-medium leading-tight text-white hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
                            <i class="ri-file-list-3-line"></i><span>Klaim via Tryout</span>
                        </button>
                    </form>
                    @elseif($package->type_price === 'free_conditional')
                    <button type="button"
                            onclick="openConditionalModal({{ $package->package_id }}, @js($package->name), @js($package->conditional_requirement ?: 'Kirim bukti pemenuhan syarat untuk diverifikasi admin.'))"
                            class="flex-1 min-h-[44px] px-3 py-2.5 rounded-xl inline-flex items-center justify-center gap-1.5 text-center text-sm font-medium leading-tight text-white hover:opacity-90 transition-opacity"
                            style="background-color: {{ $primaryColor }}">
                        <i class="ri-file-upload-line"></i><span>Kirim Syarat</span>
                    </button>
                    @elseif($package->type_price === 'free_unconditional')
                    <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST" class="claim-form flex-1 flex">
                        @csrf
                        <button type="submit"
                                class="w-full min-h-[44px] px-3 py-2.5 rounded-xl inline-flex items-center justify-center gap-1.5 text-center text-sm font-medium leading-tight text-white hover:opacity-90 transition-opacity"
                                style="background-color: {{ $primaryColor }}">
                            <i class="ri-gift-line"></i><span>Klaim</span>
                        </button>
                    </form>
                    @else
                    <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST" class="buy-form flex-1 flex flex-col gap-2" data-package-id="{{ $package->package_id }}" data-price="{{ $package->price }}">
                        @csrf
                        <button type="button" onclick="handleBuy({{ $package->package_id }}, {{ $package->price }}, @js($package->name))"
                                class="w-full min-h-[44px] px-3 py-2.5 rounded-xl inline-flex items-center justify-center gap-1.5 text-center text-sm font-medium leading-tight text-white hover:opacity-90 transition-opacity"
                                style="background-color: {{ $primaryColor }}">
                            <i class="ri-shopping-cart-line"></i><span>Beli</span>
                        </button>
                    </form>
                    @endif
                @else
                <a href="{{ route('login') }}"
                   class="flex-1 min-h-[44px] px-3 py-2.5 rounded-xl inline-flex items-center justify-center gap-1.5 text-center text-sm font-medium leading-tight text-white hover:opacity-90 transition-opacity"
                   style="background-color: {{ $primaryColor }}">
                    <i class="ri-login-box-line"></i><span>Masuk</span>
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
        <p class="text-gray-400 text-sm">Paket akan segera tersedia.</p>
    </div>
    @endforelse
</div>
</div>

<!-- Custom Prompt Modal -->
<div id="customPromptModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all duration-200 scale-95 opacity-0" id="customPromptContainer">
        <div class="p-5 border-b border-gray-150 flex items-center justify-between">
            <h3 class="font-bold text-gray-800 text-base">Kode Diskon</h3>
            <button onclick="closeCustomPrompt()" class="text-gray-400 hover:text-gray-650 p-1 rounded-lg hover:bg-gray-100 transition-all">
                <i class="ri-close-line text-lg"></i>
            </button>
        </div>
        <div class="p-5">
            <p class="text-xs text-gray-500 mb-3.5 leading-relaxed">Masukkan kode diskon jika ada untuk mendapatkan potongan harga. Kosongkan jika tidak ada.</p>
            <input type="text" id="customPromptInput" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-sm" placeholder="Contoh: PROMO10">
            <div class="flex gap-2.5 mt-5">
                <button type="button" onclick="submitCustomPrompt(true)" class="flex-1 py-2.5 rounded-xl border border-gray-300 text-gray-605 hover:bg-gray-50 font-bold text-xs transition-colors uppercase tracking-wider">
                    Lewati
                </button>
                <button type="button" onclick="submitCustomPrompt(false)" class="flex-1 py-2.5 rounded-xl text-white font-bold text-xs transition-colors uppercase tracking-wider" style="background-color: {{ $primaryColor }}">
                    Gunakan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Conditional Free Package Modal -->
<div id="conditionalModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl">
            <h3 class="text-lg font-semibold text-gray-800">Kirim Syarat Paket</h3>
            <button type="button" onclick="closeConditionalModal()" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-lg transition-colors">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="p-5">
            <div id="conditionalError" class="hidden mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"></div>

            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5">
                <p class="text-xs text-amber-700 mb-1">Paket</p>
                <p id="conditionalPackageName" class="font-semibold text-gray-800 mb-3"></p>
                <p class="text-xs text-amber-700 mb-1">Syarat</p>
                <p id="conditionalRequirementText" class="text-sm text-amber-900 leading-relaxed"></p>
            </div>

            <form id="conditionalForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Bukti Syarat <span class="text-red-500">*</span></label>
                        <input type="file" name="requirement_proofs[]" id="requirementProof" accept="image/*,.pdf,.mp4,.webm" required multiple
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                        <p class="text-xs text-gray-500 mt-1">Bisa pilih lebih dari satu file. Format: JPG, PNG, PDF, MP4, atau WEBM. Maks: 2MB per file.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan untuk Admin <span class="text-gray-400 font-normal">(opsional)</span></label>
                        <textarea name="requirement_user_notes" id="requirementUserNotes" rows="3" maxlength="1000"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary resize-none"
                                  placeholder="Contoh: Bukti ini dari akun Instagram saya, nama akun @..."></textarea>
                        <p class="text-xs text-gray-500 mt-1">Catatan ini akan terlihat oleh admin saat review pengajuan.</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <p class="text-sm text-gray-600">
                            <i class="ri-information-line mr-1"></i>
                            Akses belum aktif sampai admin menyetujui bukti yang kamu kirim.
                        </p>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closeConditionalModal()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl hover:bg-gray-50 font-medium">
                        Batal
                    </button>
                    <button type="submit" id="submitConditionalBtn" class="flex-1 px-4 py-2.5 text-white rounded-xl font-medium" style="background-color: {{ $primaryColor }}">
                        Kirim Bukti
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center backdrop-blur-sm">
    <div class="bg-white px-6 py-5 rounded-2xl shadow-xl flex items-center gap-3">
        <div class="animate-spin rounded-full h-8 w-8 border-2 border-gray-300 border-t-transparent" style="border-top-color: {{ $primaryColor }}"></div>
        <p class="text-sm font-medium text-gray-700">Memproses...</p>
    </div>
</div>

<!-- Checkout summary: product and AI are intentionally billed separately. -->
<div id="checkoutModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm">
    <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-100 bg-white p-5">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Ringkasan Checkout</h3>
                <p class="mt-0.5 text-xs text-gray-500">Paket dan AI dapat dibayar terpisah.</p>
            </div>
            <button type="button" onclick="closeCheckoutModal()" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Tutup">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="space-y-5 p-5">
            <div class="rounded-xl border border-gray-200 p-4">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div><p class="text-sm font-semibold text-gray-800" id="checkoutPackageName">Paket</p><p class="text-xs text-gray-500">Tagihan paket/tryout</p></div>
                    <i class="ri-book-3-line text-xl text-primary"></i>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Harga</span><span id="checkoutPackagePrice" class="font-medium text-gray-800">Rp 0</span></div>
                    <div id="checkoutDiscountRow" class="hidden justify-between"><span class="text-gray-500">Diskon</span><span id="checkoutDiscountAmount" class="font-medium text-emerald-600">- Rp 0</span></div>
                    <div class="flex justify-between border-t border-dashed border-gray-200 pt-2"><span class="font-medium text-gray-700">Tagihan paket</span><span id="checkoutPackageTotal" class="font-semibold text-gray-900">Rp 0</span></div>
                </div>
                <div class="mt-4 flex gap-2">
                    <input id="checkoutDiscountInput" type="text" maxlength="50" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase" placeholder="Kode diskon (opsional)">
                    <button type="button" onclick="applyCheckoutDiscount()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Terapkan</button>
                </div>
                <p id="checkoutDiscountInfo" class="mt-2 hidden text-xs"></p>
            </div>

            @if($aiGatewayPlans->isNotEmpty())
                <div class="rounded-xl border border-gray-200 p-4 transition hover:border-primary/50">
                    <div class="flex items-start gap-3">
                        <input id="checkoutAiEnabled" type="checkbox" class="mt-1 h-4 w-4 rounded border-gray-300" onchange="toggleAiCheckoutPlan()">
                        <button type="button" onclick="event.preventDefault(); event.stopPropagation(); openAiPreviewModal()" class="group relative h-20 w-24 shrink-0 overflow-hidden rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" aria-label="Lihat ilustrasi Pembahasan AI ukuran besar">
                            <img src="{{ asset('img/new-fitures/pembahasan-ai.webp') }}" alt="Ilustrasi Pembahasan AI" loading="lazy" decoding="async" class="h-full w-full object-cover transition duration-200 group-hover:scale-105">
                            <span class="absolute inset-0 flex items-center justify-center bg-slate-900/0 text-white transition group-hover:bg-slate-900/45"><i class="ri-zoom-in-line text-xl opacity-0 transition group-hover:opacity-100"></i></span>
                        </button>
                        <label for="checkoutAiEnabled" class="min-w-0 flex-1 cursor-pointer">
                            <div class="flex items-center justify-between gap-3"><span class="font-semibold text-gray-800"><i class="ri-robot-2-line mr-1 text-primary"></i>Tambahkan Pembahasan AI</span><span class="text-xs font-medium text-gray-500">Opsional</span></div>
                            <p class="mt-1 text-xs leading-5 text-gray-500">Gunakan untuk tanya konsep dan langkah penyelesaian pada pembahasan tryout.</p>
                        </label>
                    </div>
                    <div id="checkoutAiPlanWrap" class="mt-3 hidden">
                        <select id="checkoutAiPlan" onchange="renderCheckoutSummary()" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700">
                            @if($defaultAiGatewayPlanId === 0)
                                <option value="" selected disabled>Pilih paket AI</option>
                            @endif
                            @foreach($aiGatewayPlans as $plan)
                                <option value="{{ data_get($plan, 'id') }}" @selected((int) data_get($plan, 'id') === $defaultAiGatewayPlanId)>{{ data_get($plan, 'name') }} — {{ (int) data_get($plan, 'price', 0) === 0 ? 'Gratis' : 'Rp ' . number_format(data_get($plan, 'price'), 0, ',', '.') }} · {{ $aiChatLabel($plan) }}</option>
                            @endforeach
                        </select>
                        <div class="mt-3 flex justify-between border-t border-dashed border-gray-200 pt-3 text-sm"><span id="checkoutAiBillingLabel" class="font-medium text-gray-700">Tagihan AI terpisah</span><span id="checkoutAiTotal" class="font-semibold text-gray-900">Rp 0</span></div>
                    </div>
                </div>
            @endif

            <div class="rounded-lg bg-blue-50 p-3 text-xs leading-5 text-blue-800"><i class="ri-information-line mr-1"></i>Paket AI berbayar dibuat sebagai tagihan terpisah. Paket AI gratis langsung aktif setelah diklaim.</div>
            <div id="checkoutError" class="hidden rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"></div>
            <div class="flex gap-3">
                <button type="button" onclick="closeCheckoutModal()" class="flex-1 rounded-xl border border-gray-300 px-4 py-2.5 font-medium text-gray-600 hover:bg-gray-50">Batal</button>
                <button type="button" id="checkoutSubmitBtn" onclick="continueCheckout()" class="flex-1 rounded-xl px-4 py-2.5 font-medium text-white" style="background-color: {{ $primaryColor }}">Buat Tagihan</button>
            </div>
        </div>
    </div>
</div>

<div id="paymentLinksModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="paymentLinksModalTitle">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div class="text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary"><i class="ri-bill-line text-2xl"></i></span><h3 id="paymentLinksModalTitle" class="mt-3 text-lg font-semibold text-gray-800">Dua tagihan sudah dibuat</h3><p class="mt-1 text-sm text-gray-500">Bayar sesuai urutan yang kamu inginkan.</p></div>
        <div class="mt-5 space-y-3">
            <a id="payPackageLink" href="#" class="flex items-center justify-between rounded-xl border border-gray-200 p-4 hover:border-primary/50"><span><span class="block font-semibold text-gray-800">Bayar Paket / Tryout</span><span class="text-xs text-gray-500">Aktifkan akses belajar</span></span><i class="ri-arrow-right-line text-xl text-primary"></i></a>
            <a id="payAiLink" href="#" class="flex items-center justify-between rounded-xl border border-gray-200 p-4 hover:border-primary/50"><span><span class="block font-semibold text-gray-800">Bayar Pembahasan AI</span><span class="text-xs text-gray-500">Aktifkan kuota Diskusi AI</span></span><i class="ri-arrow-right-line text-xl text-primary"></i></a>
        </div>
        <button type="button" onclick="closePaymentLinksModal()" class="mt-5 w-full rounded-xl border border-gray-300 px-4 py-2.5 font-medium text-gray-600 hover:bg-gray-50">Nanti saja</button>
    </div>
</div>

@if(is_array($combinedAiPayment) && filled(data_get($combinedAiPayment, 'invoice_url')))
<div id="resumePaymentLinksModal" class="fixed inset-0 z-[60] {{ $hasCombinedPaymentFlash ? 'flex' : 'hidden' }} items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="resumePaymentLinksModalTitle">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div class="text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary"><i class="ri-bill-line text-2xl"></i></span><h3 id="resumePaymentLinksModalTitle" class="mt-3 text-lg font-semibold text-gray-800">Dua tagihan sudah dibuat</h3><p class="mt-1 text-sm text-gray-500">Bayar sesuai urutan yang kamu inginkan.</p></div>
        <div class="mt-5 space-y-3">
            @if(data_get($combinedAiPayment, 'product_paid'))
            <div class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-4"><span><span class="block font-semibold text-emerald-800">Paket / Tryout sudah dibayar</span><span class="text-xs text-emerald-700">Akses belajar sudah aktif</span></span><i class="ri-checkbox-circle-fill text-2xl text-emerald-600"></i></div>
            @else
            <a href="{{ data_get($combinedAiPayment, 'product_payment_url') }}" class="flex items-center justify-between rounded-xl border border-gray-200 p-4 hover:border-primary/50"><span><span class="block font-semibold text-gray-800">Bayar Paket / Tryout</span><span class="text-xs text-gray-500">Aktifkan akses belajar</span></span><i class="ri-arrow-right-line text-xl text-primary"></i></a>
            @endif
            <a href="{{ data_get($combinedAiPayment, 'invoice_url') }}" class="flex items-center justify-between rounded-xl border border-gray-200 p-4 hover:border-primary/50"><span><span class="block font-semibold text-gray-800">Bayar Pembahasan AI</span><span class="text-xs text-gray-500">Aktifkan kuota Diskusi AI</span></span><i class="ri-arrow-right-line text-xl text-primary"></i></a>
        </div>
        <button type="button" onclick="closeResumePaymentLinksModal()" class="mt-5 w-full rounded-xl border border-gray-300 px-4 py-2.5 font-medium text-gray-600 hover:bg-gray-50">Nanti saja</button>
    </div>
</div>
@endif

<div id="aiPreviewModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/65 p-4 backdrop-blur-sm" onclick="if (event.target === this) closeAiPreviewModal()" role="dialog" aria-modal="true" aria-label="Pratinjau ilustrasi Pembahasan AI">
    <div class="relative max-h-[90vh] w-full max-w-4xl">
        <button type="button" onclick="closeAiPreviewModal()" class="absolute -right-1 -top-11 rounded-lg p-2 text-white hover:bg-white/15" aria-label="Tutup pratinjau"><i class="ri-close-line text-2xl"></i></button>
        <img src="{{ asset('img/new-fitures/pembahasan-ai.webp') }}" alt="Ilustrasi Pembahasan AI ukuran besar" class="max-h-[90vh] w-full rounded-2xl object-contain shadow-2xl">
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
                    <div class="flex justify-between border-t border-dashed border-gray-200/80 pt-2 mt-2">
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
                    <div class="flex justify-between border-t border-dashed border-gray-200/80 pt-2 mt-2">
                        <span class="text-gray-500">Total Bayar</span>
                        <span class="font-bold text-lg" style="color: {{ $primaryColor }}" id="paymentAmountDisplay">Rp 0</span>
                    </div>
                    <div id="manualAiInvoiceNotice" class="hidden border-t border-dashed border-gray-200/80 pt-3 mt-3">
                        <p class="text-sm font-medium text-gray-700">Tagihan Pembahasan AI sudah dibuat.</p>
                        <a id="manualAiPaymentLink" href="#" target="_blank" rel="noopener" class="mt-2 inline-flex rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white hover:opacity-90">Bayar Pembahasan AI</a>
                    </div>
                    @if(!empty($paymentBankNote))
                    <div class="border-t border-dashed border-gray-200/80 pt-3 mt-3">
                        <div class="prose prose-sm max-w-none text-gray-600">
                            {!! $paymentBankNote !!}
                        </div>
                    </div>
                    @endif
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
                    <x-legal-links />
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
@if($useGeneralLayout)
</div>
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
const CONDITIONAL_PACKAGE_BUY_URL_TEMPLATE = @json(route('user.package.buy', ['package_id' => '__PACKAGE_ID__']));
const AI_GATEWAY_PLANS = @json($aiGatewayPlansJson);
const DEFAULT_AI_GATEWAY_PLAN_ID = @json($defaultAiGatewayPlanId);
const AI_GATEWAY_CHECKOUT_URL = @json(route('user.ai-gateway.checkout'));
const ACTIVE_COMBINED_PAYMENT = @json($combinedAiPayment);
let selectedPackageId = null;
let selectedPrice = 0;
let selectedPackageName = '';
let selectedDiscountCode = '';
let selectedDiscountAmount = 0;
let selectedPayableAmount = 0;
let selectedConditionalPackageId = null;
let selectedAiInvoiceUrl = null;

function openConditionalModal(packageId, packageName, requirementText) {
    selectedConditionalPackageId = packageId;
    document.getElementById('conditionalPackageName').textContent = packageName;
    document.getElementById('conditionalRequirementText').textContent = requirementText;
    document.getElementById('conditionalError').classList.add('hidden');
    document.getElementById('conditionalForm').action = CONDITIONAL_PACKAGE_BUY_URL_TEMPLATE.replace('__PACKAGE_ID__', packageId);
    document.getElementById('requirementProof').value = '';
    document.getElementById('requirementUserNotes').value = '';
    document.getElementById('conditionalModal').classList.remove('hidden');
    document.getElementById('conditionalModal').classList.add('flex');
}

function closeConditionalModal() {
    document.getElementById('conditionalModal').classList.add('hidden');
    document.getElementById('conditionalModal').classList.remove('flex');
    document.getElementById('conditionalForm').reset();
    selectedConditionalPackageId = null;
}

// Reusable Toast Notification System
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed bottom-4 right-4 z-[9999] flex flex-col gap-3 max-w-sm w-full';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = 'transform translate-y-4 opacity-0 transition-all duration-300 ease-out flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-xl border text-sm font-medium text-white';
    
    let icon = 'ri-information-line';
    if (type === 'success') {
        toast.className += ' bg-emerald-600 border-emerald-500';
        icon = 'ri-checkbox-circle-line';
    } else if (type === 'error') {
        toast.className += ' bg-red-600 border-red-500';
        icon = 'ri-close-circle-line';
    } else if (type === 'warning') {
        toast.className += ' bg-amber-500 border-amber-400';
        icon = 'ri-alert-line';
    } else {
        toast.className += ' bg-gray-800 border-gray-700';
    }
    
    toast.innerHTML = `
        <i class="${icon} text-lg shrink-0"></i>
        <span class="flex-1">${message}</span>
        <button type="button" onclick="this.parentElement.remove()" class="text-white/70 hover:text-white transition-colors">
            <i class="ri-close-line text-base"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('translate-y-4', 'opacity-0');
    }, 10);
    
    setTimeout(() => {
        toast.classList.add('translate-y-4', 'opacity-0');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}

// Reusable Custom Prompt Modal System
let customPromptResolve = null;

function showCustomPrompt() {
    return new Promise((resolve) => {
        customPromptResolve = resolve;
        const modal = document.getElementById('customPromptModal');
        const container = document.getElementById('customPromptContainer');
        const input = document.getElementById('customPromptInput');
        
        input.value = '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        setTimeout(() => {
            container.classList.remove('scale-95', 'opacity-0');
            input.focus();
        }, 10);
    });
}

function closeCustomPrompt() {
    const modal = document.getElementById('customPromptModal');
    const container = document.getElementById('customPromptContainer');
    
    container.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 200);
    
    if (customPromptResolve) {
        customPromptResolve(null); // Resolve with null to signal cancellation
        customPromptResolve = null;
    }
}

function submitCustomPrompt(skip = false) {
    const modal = document.getElementById('customPromptModal');
    const container = document.getElementById('customPromptContainer');
    const input = document.getElementById('customPromptInput');
    
    container.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 200);
    
    if (customPromptResolve) {
        const value = skip ? '' : input.value.trim();
        customPromptResolve(value);
        customPromptResolve = null;
    }
}

// Close prompt modal on backdrop click
document.getElementById('customPromptModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeCustomPrompt();
    }
});

// Initialize Alpine.js packageManager component
document.addEventListener('alpine:init', () => {
    Alpine.data('packageManager', () => ({
        discounts: @js($discountsJson ?? []),
        automaticDiscounts: @js($automaticDiscountsJson ?? []),
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

        getAutomaticDiscount(packageId) {
            return this.automaticDiscounts[String(packageId)] || null;
        },

        isAutomaticDiscountActiveFor(packageId) {
            const discount = this.getAutomaticDiscount(packageId);
            return Boolean(discount && Number(discount.discount_amount || 0) > 0);
        },

        hasAnyDiscountFor(packageId, price) {
            return this.isDiscountActiveFor(packageId, price) || (!this.activeDiscountCode && this.isAutomaticDiscountActiveFor(packageId));
        },

        getDisplayPrice(packageId, price) {
            if (!this.activeDiscountCode) {
                const automaticDiscount = this.getAutomaticDiscount(packageId);
                if (automaticDiscount && Number(automaticDiscount.final_price) < Number(price)) {
                    return 'Rp ' + this.formatNumber(Number(automaticDiscount.final_price));
                }
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

function hasAutomaticDiscountFor(packageId) {
    const el = document.getElementById('package-container');
    if (el && window.Alpine) {
        try {
            return Boolean(Alpine.$data(el).getAutomaticDiscount(packageId));
        } catch(e) {
            console.error(e);
        }
    }
    return false;
}

async function handleBuy(packageId, price, packageName) {
    if (ACTIVE_COMBINED_PAYMENT
        && ACTIVE_COMBINED_PAYMENT.product_type === 'package'
        && Number(ACTIVE_COMBINED_PAYMENT.product_item_id) === Number(packageId)) {
        openResumePaymentLinksModal();
        return;
    }

    selectedPackageId = packageId;
    selectedPrice = price;
    selectedPackageName = packageName;
    
    // Auto-inject selected promo code from Alpine component if any
    const activeCode = getActivePromoCode();
    selectedDiscountCode = activeCode || '';
    selectedDiscountAmount = 0;
    selectedPayableAmount = Number(price);
    selectedAiInvoiceUrl = null;

    document.getElementById('checkoutDiscountInput').value = selectedDiscountCode;
    const aiCheckoutCheckbox = document.getElementById('checkoutAiEnabled');
    if (aiCheckoutCheckbox) {
        aiCheckoutCheckbox.checked = false;
    }
    const aiPlanSelect = document.getElementById('checkoutAiPlan');
    if (aiPlanSelect) {
        aiPlanSelect.value = DEFAULT_AI_GATEWAY_PLAN_ID ? String(DEFAULT_AI_GATEWAY_PLAN_ID) : '';
    }
    document.getElementById('checkoutAiPlanWrap')?.classList.add('hidden');
    document.getElementById('checkoutError').classList.add('hidden');
    await refreshCheckoutDiscount();
    renderCheckoutSummary();
    document.getElementById('checkoutModal').classList.remove('hidden');
    document.getElementById('checkoutModal').classList.add('flex');
}

function closeCheckoutModal() {
    document.getElementById('checkoutModal').classList.add('hidden');
    document.getElementById('checkoutModal').classList.remove('flex');
}

function toggleAiCheckoutPlan() {
    document.getElementById('checkoutAiPlanWrap')?.classList.toggle('hidden', !document.getElementById('checkoutAiEnabled')?.checked);
    renderCheckoutSummary();
}

function selectedAiPlan() {
    const planId = Number(document.getElementById('checkoutAiPlan')?.value || 0);
    return AI_GATEWAY_PLANS.find((plan) => Number(plan.id) === planId) || null;
}

function renderCheckoutSummary() {
    document.getElementById('checkoutPackageName').textContent = selectedPackageName || 'Paket';
    document.getElementById('checkoutPackagePrice').textContent = 'Rp ' + formatNumber(selectedPrice);
    document.getElementById('checkoutDiscountAmount').textContent = '- Rp ' + formatNumber(selectedDiscountAmount);
    document.getElementById('checkoutDiscountRow').classList.toggle('hidden', selectedDiscountAmount <= 0);
    document.getElementById('checkoutDiscountRow').classList.toggle('flex', selectedDiscountAmount > 0);
    document.getElementById('checkoutPackageTotal').textContent = 'Rp ' + formatNumber(selectedPayableAmount);

    const plan = selectedAiPlan();
    const aiTotal = document.getElementById('checkoutAiTotal');
    const aiBillingLabel = document.getElementById('checkoutAiBillingLabel');
    if (aiTotal) {
        aiTotal.textContent = Number(plan?.price || 0) === 0 ? 'Gratis' : 'Rp ' + formatNumber(plan.price);
    }
    if (aiBillingLabel) {
        aiBillingLabel.textContent = Number(plan?.price || 0) === 0 ? 'Klaim paket AI' : 'Tagihan AI terpisah';
    }
}

async function applyCheckoutDiscount() {
    selectedDiscountCode = (document.getElementById('checkoutDiscountInput').value || '').trim().toUpperCase();
    await refreshCheckoutDiscount();
}

async function refreshCheckoutDiscount() {
    const info = document.getElementById('checkoutDiscountInfo');
    const formData = new FormData();
    if (selectedDiscountCode) {
        formData.append('discount_code', selectedDiscountCode);
    }

    try {
        const response = await fetch('/user/paket-pembelian/' + selectedPackageId + '/discount/preview', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: formData,
        });
        const data = await response.json();

        if (!response.ok || !data.success) {
            selectedDiscountAmount = 0;
            selectedPayableAmount = Number(selectedPrice);
            info.textContent = selectedDiscountCode ? (data.message || 'Kode diskon tidak valid.') : '';
            info.className = selectedDiscountCode ? 'mt-2 text-xs text-red-600' : 'hidden text-xs';
        } else {
            selectedDiscountCode = data.code || selectedDiscountCode;
            selectedDiscountAmount = Number(data.discount_amount || 0);
            selectedPayableAmount = Number(data.payable_amount || selectedPrice);
            info.textContent = selectedDiscountAmount > 0 ? 'Diskon berhasil diterapkan pada tagihan paket.' : '';
            info.className = selectedDiscountAmount > 0 ? 'mt-2 text-xs text-emerald-600' : 'hidden text-xs';
        }
    } catch {
        selectedDiscountAmount = 0;
        selectedPayableAmount = Number(selectedPrice);
        info.textContent = 'Gagal mengecek diskon. Harga paket tetap digunakan.';
        info.className = 'mt-2 text-xs text-red-600';
    }

    renderCheckoutSummary();
}

async function createAiInvoice() {
    if (!document.getElementById('checkoutAiEnabled')?.checked) {
        return null;
    }

    const plan = selectedAiPlan();
    if (!plan) {
        throw new Error('Paket Pembahasan AI belum dipilih.');
    }

    const formData = new FormData();
    formData.append('plan_id', plan.id);
    formData.append('return_url', window.location.href);
    formData.append('combined_checkout', '1');
    const response = await fetch(AI_GATEWAY_CHECKOUT_URL, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
    });
    const data = await response.json();

    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Gagal membuat tagihan Pembahasan AI.');
    }

    if (data.activated || data.already_claimed) {
        return null;
    }

    if (!data.invoice_url) {
        throw new Error(data.message || 'Gagal membuat tagihan Pembahasan AI.');
    }

    return data.invoice_url;
}

async function continueCheckout() {
    const submitButton = document.getElementById('checkoutSubmitBtn');
    const originalText = submitButton.innerHTML;
    const errorElement = document.getElementById('checkoutError');
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="ri-loader-4-line animate-spin mr-1"></i>Menyiapkan...';
    errorElement.classList.add('hidden');

    try {
        selectedAiInvoiceUrl = await createAiInvoice();
        closeCheckoutModal();
        await startPackageCheckout();
    } catch (error) {
        errorElement.textContent = error.message || 'Gagal menyiapkan tagihan.';
        errorElement.classList.remove('hidden');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    }
}

async function startPackageCheckout() {

    if (PAYMENT_MODE === 'manual') {
        const uniqueCode = PAYMENT_UNIQUE_CODE_ENABLED ? Number(MANUAL_PAYMENT_UNIQUE_CODES[selectedPackageId] || 0) : 0;
        const totalAmount = selectedPayableAmount + uniqueCode;

        document.getElementById('baseAmountDisplay').textContent = 'Rp ' + formatNumber(selectedPrice);
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
        document.getElementById('manualAiInvoiceNotice').classList.toggle('hidden', !selectedAiInvoiceUrl);
        document.getElementById('manualAiPaymentLink').href = selectedAiInvoiceUrl || '#';
        document.getElementById('paymentModal').classList.remove('hidden');
        document.getElementById('paymentModal').classList.add('flex');
        
        if (selectedDiscountCode) {
            applyDiscountCode();
        } else {
            checkAutomaticReferralDiscount();
        }
    } else {
        // Gateway mode - always use AJAX to handle redirect_url from gateway
        const form = document.querySelector(`form[data-package-id="${selectedPackageId}"]`);
        
        const discountCode = selectedDiscountCode;
        
        const submitBtn = form.querySelector('button');
        const originalText = submitBtn.innerHTML;
        const formData = new FormData(form);

        if (selectedAiInvoiceUrl) {
            formData.set('combined_ai_checkout', '1');
        }

        if (discountCode.trim() !== '') {
            formData.set('discount_code', discountCode.trim().toUpperCase());
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-1"></i>Memproses...';

        return fetch(form.action, {
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
                if (selectedAiInvoiceUrl) {
                    openPaymentLinksModal(data.redirect_url, selectedAiInvoiceUrl);
                } else {
                    window.location.href = data.redirect_url;
                }
            } else {
                showToast(data.message || 'Gagal membuat pembayaran. Coba lagi nanti.', 'error');
            }
        })
        .catch(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
        });
    }
}

function openPaymentLinksModal(packageUrl, aiUrl) {
    document.getElementById('payPackageLink').href = packageUrl;
    document.getElementById('payAiLink').href = aiUrl;
    document.getElementById('paymentLinksModal').classList.remove('hidden');
    document.getElementById('paymentLinksModal').classList.add('flex');
}

function closePaymentLinksModal() {
    document.getElementById('paymentLinksModal').classList.add('hidden');
    document.getElementById('paymentLinksModal').classList.remove('flex');
}

function openResumePaymentLinksModal() {
    const modal = document.getElementById('resumePaymentLinksModal');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeResumePaymentLinksModal() {
    const modal = document.getElementById('resumePaymentLinksModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function openAiPreviewModal() {
    document.getElementById('aiPreviewModal').classList.remove('hidden');
    document.getElementById('aiPreviewModal').classList.add('flex');
}

function closeAiPreviewModal() {
    document.getElementById('aiPreviewModal').classList.add('hidden');
    document.getElementById('aiPreviewModal').classList.remove('flex');
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
        if (!data.success || Number(data.discount_amount || 0) <= 0) {
            return;
        }

        updateDiscountDisplay(data.code, data.discount_amount, data.payable_amount);
        const info = document.getElementById('discountInfo');
        info.textContent = data.source === 'automatic' ? 'Diskon otomatis diterapkan.' : 'Diskon referral otomatis diterapkan.';
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
        checkAutomaticReferralDiscount();
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
            showToast(data.message || 'Bukti pembayaran berhasil dikirim!', 'success');
            setTimeout(() => {
                window.location.href = '{{ route("user.package.riwayatPembelian") }}';
            }, 1500);
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

document.getElementById('conditionalModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeConditionalModal();
    }
});

document.getElementById('conditionalForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const form = this;
    const submitBtn = document.getElementById('submitConditionalBtn');
    const originalText = submitBtn.innerHTML;
    const errorEl = document.getElementById('conditionalError');

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-2"></i>Mengirim...';
    errorEl.classList.add('hidden');

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: new FormData(form),
    })
    .then(async response => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Bukti syarat gagal dikirim.');
        }

        closeConditionalModal();
        showToast(data.message || 'Bukti berhasil dikirim. Mohon tunggu verifikasi admin.', 'success');
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    })
    .catch(error => {
        errorEl.textContent = error.message || 'Terjadi kesalahan. Silakan coba lagi.';
        errorEl.classList.remove('hidden');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
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
                    showToast(response.message || 'Paket berhasil diklaim!', 'success');
                    setTimeout(() => {
                        window.location.href = response.redirect_url || '{{ route("user.package.my") }}';
                    }, 1500);
                } else {
                    btn.prop('disabled', false).html(originalText);
                    showToast(response.message || 'Terjadi kesalahan', 'error');
                }
            },
            error: function(xhr) {
                $('#loadingModal').addClass('hidden').removeClass('flex');
                btn.prop('disabled', false).html(originalText);
                const msg = xhr.responseJSON?.message || 'Terjadi kesalahan. Silakan coba lagi.';
                showToast(msg, 'error');
            }
        });
    });
});
</script>
@endpush
