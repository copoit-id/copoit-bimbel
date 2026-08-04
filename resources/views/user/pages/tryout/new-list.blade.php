@php
    $useGeneralLayout = request('layout') === 'landing' || (!auth()->check() && request()->routeIs('user.package.tryout.list'));
@endphp
@extends($useGeneralLayout ? 'general.layout' : 'user.layout.new-user')

@section('title', 'Tryout')

@section('content')
@if($useGeneralLayout)
<div class="mx-auto max-w-7xl px-4 pt-32 pb-16 sm:px-6 sm:pt-40 lg:px-8 bg-gray-50 min-h-screen">
@endif

@php
$user = auth()->user();
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$paymentMode = strtolower((string) ($clientBranding['payment_mode'] ?? config('client.branding.payment_mode', 'gateway')));
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
$aiGatewayPlansJson = $aiGatewayPlans->map(fn ($plan) => [
    'id' => (int) data_get($plan, 'id'),
    'price' => (int) data_get($plan, 'price', 0),
    'chat_limit' => (int) data_get($plan, 'chat_limit', 0),
])->all();
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
            <h2 class="text-3xl font-bold">{{ $tryouts->filter(function($t) use ($user) { return $user && $t->completedAttemptCountForUser($user->id) > 0; })->count() }}</h2>
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
    $totalQuestions = $tryout->getTotalQuestionsAttribute();
    $totalDuration = $tryout->getTotalDurationAttribute();
    $completedAttempts = $user ? $tryout->completedAttemptCountForUser($user->id) : 0;
    $remainingAttempts = $user ? $tryout->remainingAttemptsForUser($user->id) : null;
    $isInProgress = $user ? $tryout->hasInProgressAttemptForUser($user->id) : false;
    $hasHistory = $completedAttempts > 0;
    $isAttemptLimitReached = $user ? ($tryout->hasReachedAttemptLimitForUser($user->id) && ! $isInProgress) : false;
    $isForSale = $tryout->isIndividuallyAvailable();
    $isPaid = $tryout->isPaidIndividualAccess();
    $isFreeUnconditional = $tryout->isFreeUnconditionalIndividualAccess();
    $isFreeConditional = $tryout->isFreeConditionalIndividualAccess();
    $isPendingIndividual = (bool) ($tryout->is_pending_individual ?? false);
    $hasPendingAiPayment = is_array($combinedAiPayment)
        && data_get($combinedAiPayment, 'ai_payment_pending')
        && data_get($combinedAiPayment, 'product_type') === 'individual'
        && (int) data_get($combinedAiPayment, 'product_item_id') === (int) $tryout->tryout_id;
    $routePackageId = $tryout->route_package_id ?? ($tryout->packages->first()?->package_id ?? 'free');
    $showHistoryAction = $user && $hasHistory && ($tryout->has_access ?? false);
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
            @if($user)
            <div class="flex items-center text-sm {{ $isAttemptLimitReached ? 'text-red-600' : 'text-gray-500' }}">
                <i class="{{ $isAttemptLimitReached ? 'ri-checkbox-circle-line' : 'ri-repeat-line' }} mr-2 text-gray-400"></i>
                <span>
                    @if(is_null($remainingAttempts))
                        {{ $completedAttempts }}x dikerjakan, tanpa batas
                    @else
                        {{ $completedAttempts }}/{{ $tryout->max_attempts ?? 0 }}x dikerjakan
                    @endif
                </span>
            </div>
            @endif
        </div>

        <div class="mt-auto w-full {{ $showHistoryAction ? 'flex gap-2' : '' }}">
            @if(!$user)
                @if($tryout->access_via_package)
                <a href="{{ route('user.package.detail', $tryout->access_via_package->package_id) }}"
	                   class="flex items-center justify-center w-full py-2.5 rounded-xl text-sm font-medium border-2 hover:bg-gray-50 transition-colors"
	                   style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
	                    <i class="ri-shopping-bag-line mr-1"></i>Dapatkan Paket
	                </a>
                @else
                <a href="{{ route('login') }}"
                   class="flex items-center justify-center w-full py-2.5 rounded-xl text-sm font-medium border-2 hover:bg-gray-50 transition-colors"
                   style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                    <i class="ri-login-box-line mr-1"></i>
                    Masuk untuk Akses
                </a>
                @endif
            @elseif($hasPendingAiPayment)
                <button type="button" onclick="openResumeTryoutPaymentLinksModal()"
                        class="flex items-center justify-center w-full py-2.5 text-white text-center rounded-xl text-sm font-medium hover:opacity-90 transition-opacity"
                        style="background-color: {{ $primaryColor }}">
                    <i class="ri-time-line mr-1"></i>Lanjutkan Pembayaran
                </button>
            @elseif($isForSale)
                {{-- Tryout for individual sale --}}
                @if($isAttemptLimitReached)
                <a href="{{ route('user.tryout.result', ['id_package' => $routePackageId, 'id_tryout' => $tryout->tryout_id]) }}"
                   class="flex {{ $showHistoryAction ? 'flex-1' : 'w-full' }} items-center justify-center gap-1 py-2.5 text-center rounded-xl text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">
                    <i class="ri-checkbox-circle-line mr-1"></i>Sudah Dikerjakan
                </a>
                @elseif($tryout->has_access)
                <a href="{{ route('user.tryout.lobby', ['id_package' => $routePackageId, 'id_tryout' => $tryout->tryout_id]) }}"
                   class="flex {{ $showHistoryAction ? 'flex-1' : 'w-full' }} items-center justify-center py-2.5 text-white text-center rounded-xl text-sm font-medium hover:opacity-90 transition-opacity"
                   style="background-color: {{ $primaryColor }}">
                    <i class="ri-play-circle-line mr-1"></i>{{ $isInProgress ? 'Lanjutkan' : 'Kerjakan' }}
                </a>
	                @elseif($isPendingIndividual)
	                <button disabled class="w-full py-2.5 rounded-xl text-sm font-medium bg-amber-100 text-amber-700 cursor-not-allowed">
	                    <i class="ri-time-line mr-1"></i>Menunggu Verifikasi
	                </button>
	                @elseif($isFreeUnconditional)
	                <form action="{{ route('user.individual-purchase.buy') }}" method="POST" class="w-full">
	                    @csrf
	                    <input type="hidden" name="type" value="tryout">
	                    <input type="hidden" name="id" value="{{ $tryout->tryout_id }}">
	                    <button type="submit"
	                            class="w-full py-2.5 rounded-xl text-sm font-medium text-white hover:opacity-90 transition-opacity"
	                            style="background-color: {{ $primaryColor }}">
	                        <i class="ri-gift-line mr-1"></i>Klaim Gratis
	                    </button>
	                </form>
	                @else
	                <button type="button"
	                        data-buy-tryout
                        data-id="{{ $tryout->tryout_id }}"
                        data-name="{{ e($tryout->name) }}"
                        data-price="{{ (int) $tryout->price }}"
                        data-price-type="{{ $tryout->priceType() }}"
                        data-requirement="{{ e($tryout->conditional_requirement ?? '') }}"
                        class="w-full py-2.5 rounded-xl text-sm font-medium text-white hover:opacity-90 transition-opacity"
                        style="background-color: {{ $primaryColor }}">
                    <i class="{{ $isPaid ? 'ri-shopping-cart-line' : ($isFreeConditional ? 'ri-time-line' : 'ri-gift-line') }} mr-1"></i>
                    {{ $isPaid ? 'Beli Sekarang' : ($isFreeConditional ? 'Ajukan Akses' : 'Akses Gratis') }}
                </button>
                @endif
	            @elseif($tryout->has_access)
	                {{-- User has access via package/direct access - bisa langsung kerjakan --}}
                    @if($isAttemptLimitReached)
                    <a href="{{ route('user.tryout.result', ['id_package' => $routePackageId, 'id_tryout' => $tryout->tryout_id]) }}"
                       class="flex {{ $showHistoryAction ? 'flex-1' : 'w-full' }} items-center justify-center gap-1 py-2.5 text-center rounded-xl text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">
                        <i class="ri-checkbox-circle-line mr-1"></i>Sudah Dikerjakan
                    </a>
                    @else
                    <a href="{{ route('user.tryout.lobby', ['id_package' => $routePackageId, 'id_tryout' => $tryout->tryout_id]) }}"
                       class="flex {{ $showHistoryAction ? 'flex-1' : 'w-full' }} items-center justify-center py-2.5 text-white text-center rounded-xl text-sm font-medium hover:opacity-90 transition-opacity"
                       style="background-color: {{ $primaryColor }}">
                        <i class="ri-play-circle-line mr-1"></i>{{ $isInProgress ? 'Lanjutkan' : 'Kerjakan' }}
                    </a>
                    @endif
            @else
                {{-- User doesn't have access --}}
	                @if($tryout->access_via_package)
	                <a href="{{ route('user.package.detail', $tryout->access_via_package->package_id) }}"
	                   class="flex items-center justify-center w-full py-2.5 rounded-xl text-sm font-medium border-2 hover:bg-gray-50 transition-colors"
	                   style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
	                    <i class="ri-shopping-bag-line mr-1"></i>
	                    Dapatkan Paket
	                </a>
                @else
                <button disabled class="flex items-center justify-center w-full py-2.5 rounded-xl text-sm font-medium bg-gray-100 text-gray-400 cursor-not-allowed">
                    <i class="ri-lock-line mr-1"></i>
                    Tidak Tersedia
                </button>
                @endif
            @endif

            @if($showHistoryAction)
                <a href="{{ route('user.package.tryout.riwayat', ['id_package' => $routePackageId, 'id_tryout' => $tryout->tryout_id]) }}"
                   class="flex flex-1 items-center justify-center py-2.5 text-center rounded-xl text-sm font-medium border-2 hover:opacity-90 transition-colors"
                   style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                    <i class="ri-history-line mr-1"></i>Riwayat
                </a>
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
            <div id="tryoutConditionalRequirementWrapper" class="hidden mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-sm font-semibold text-amber-900 mb-1">Persyaratan</p>
                <p id="tryoutConditionalRequirementText" class="text-sm text-amber-800 whitespace-pre-line"></p>
            </div>
            <div id="tryoutVoucherWrapper" class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Kode Voucher (opsional)</label>
                <input type="text" name="discount_code"
                       class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm uppercase focus:outline-none focus:ring-2"
                       style="--tw-ring-color: {{ $primaryColor }}"
                       placeholder="CONTOH: HEMAT50">
            </div>
            @if($aiGatewayPlans->isNotEmpty())
            <div id="tryoutAiAddOn" class="mb-4 hidden rounded-xl border border-gray-200 p-4">
                <div class="flex items-start gap-3">
                    <input id="tryoutAiEnabled" type="checkbox" class="mt-1 h-4 w-4 rounded border-gray-300" onchange="document.getElementById('tryoutAiPlanWrap').classList.toggle('hidden', !this.checked)">
                    <button type="button" onclick="event.preventDefault(); event.stopPropagation(); openAiPreviewModal()" class="group relative h-20 w-24 shrink-0 overflow-hidden rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" aria-label="Lihat ilustrasi Pembahasan AI ukuran besar">
                        <img src="{{ asset('img/new-fitures/pembahasan-ai.webp') }}" alt="Ilustrasi Pembahasan AI" loading="lazy" decoding="async" class="h-full w-full object-cover transition duration-200 group-hover:scale-105">
                        <span class="absolute inset-0 flex items-center justify-center bg-slate-900/0 text-white transition group-hover:bg-slate-900/45"><i class="ri-zoom-in-line text-xl opacity-0 transition group-hover:opacity-100"></i></span>
                    </button>
                    <label for="tryoutAiEnabled" class="min-w-0 flex-1 cursor-pointer">
                        <p class="font-semibold text-gray-800"><i class="ri-robot-2-line mr-1 text-primary"></i>Tambahkan Pembahasan AI</p>
                        <p class="mt-1 text-xs leading-5 text-gray-500">Paket berbayar ditagih terpisah; paket gratis langsung aktif setelah diklaim.</p>
                    </label>
                </div>
                <div id="tryoutAiPlanWrap" class="mt-3 hidden">
                    <select id="tryoutAiPlan" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700">
                        @if($defaultAiGatewayPlanId === 0)
                        <option value="" selected disabled>Pilih paket AI</option>
                        @endif
                        @foreach($aiGatewayPlans as $plan)
                        <option value="{{ data_get($plan, 'id') }}" @selected((int) data_get($plan, 'id') === $defaultAiGatewayPlanId)>{{ data_get($plan, 'name') }} — {{ (int) data_get($plan, 'price', 0) === 0 ? 'Gratis' : 'Rp ' . number_format(data_get($plan, 'price'), 0, ',', '.') }} · {{ $aiChatLabel($plan) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif
            @if($paymentMode === 'manual')
            <div id="tryoutPaymentProofWrapper" class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Bukti Pembayaran</label>
                <input type="file" name="payment_proof" accept="image/*,.pdf" required
                       class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
            </div>
            @endif
            <div id="tryoutConditionalProofWrapper" class="hidden mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Bukti Persyaratan</label>
                <input type="file" name="requirement_proofs[]" accept="image/*,.pdf,.mp4,.webm" multiple
                       class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                <p class="text-xs text-gray-500 mt-1">Format JPG, PNG, PDF, MP4, atau WEBM. Maksimal 2MB per file.</p>
            </div>
            <div id="tryoutConditionalNotesWrapper" class="hidden mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Catatan (opsional)</label>
                <textarea name="requirement_user_notes" rows="3"
                          class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2"
                          style="--tw-ring-color: {{ $primaryColor }}"
                          placeholder="Tambahkan keterangan untuk admin"></textarea>
            </div>
            <x-legal-links class="mb-4" />
            <div class="flex gap-3">
                <button type="button" id="cancelTryoutPurchase" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl hover:bg-gray-50 font-medium">Batal</button>
                <button id="tryoutSubmitPurchaseBtn" type="submit" class="flex-1 px-4 py-2.5 text-white rounded-xl font-medium" style="background-color: {{ $primaryColor }}">Beli</button>
            </div>
        </form>
    </div>
</div>

<div id="tryoutPaymentLinksModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="tryoutPaymentLinksModalTitle">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h3 id="tryoutPaymentLinksModalTitle" class="text-center text-lg font-semibold text-gray-800">Dua tagihan sudah dibuat</h3>
        <p class="mt-1 text-center text-sm text-gray-500">Bayar sesuai urutan yang kamu inginkan.</p>
        <div class="mt-5 space-y-3">
            <a id="tryoutPayProductLink" href="#" class="flex items-center justify-between rounded-xl border border-gray-200 p-4 hover:border-primary/50"><span><span class="block font-semibold text-gray-800">Bayar Tryout</span><span class="text-xs text-gray-500">Aktifkan akses tryout</span></span><i class="ri-arrow-right-line text-xl text-primary"></i></a>
            <a id="tryoutPayAiLink" href="#" class="flex items-center justify-between rounded-xl border border-gray-200 p-4 hover:border-primary/50"><span><span class="block font-semibold text-gray-800">Bayar Pembahasan AI</span><span class="text-xs text-gray-500">Aktifkan kuota Diskusi AI</span></span><i class="ri-arrow-right-line text-xl text-primary"></i></a>
        </div>
        <button type="button" onclick="closeTryoutPaymentLinksModal()" class="mt-5 w-full rounded-xl border border-gray-300 px-4 py-2.5 font-medium text-gray-600 hover:bg-gray-50">Nanti saja</button>
    </div>
</div>

@if(is_array($combinedAiPayment) && filled(data_get($combinedAiPayment, 'invoice_url')))
<div id="resumeTryoutPaymentLinksModal" class="fixed inset-0 z-[60] {{ $hasCombinedPaymentFlash ? 'flex' : 'hidden' }} items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="resumeTryoutPaymentLinksModalTitle">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h3 id="resumeTryoutPaymentLinksModalTitle" class="text-center text-lg font-semibold text-gray-800">Dua tagihan sudah dibuat</h3>
        <p class="mt-1 text-center text-sm text-gray-500">Bayar sesuai urutan yang kamu inginkan.</p>
        <div class="mt-5 space-y-3">
            @if(data_get($combinedAiPayment, 'product_paid'))
            <div class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-4"><span><span class="block font-semibold text-emerald-800">Tryout sudah dibayar</span><span class="text-xs text-emerald-700">Akses tryout sudah aktif</span></span><i class="ri-checkbox-circle-fill text-2xl text-emerald-600"></i></div>
            @else
            <a href="{{ data_get($combinedAiPayment, 'product_payment_url') }}" class="flex items-center justify-between rounded-xl border border-gray-200 p-4 hover:border-primary/50"><span><span class="block font-semibold text-gray-800">Bayar Tryout</span><span class="text-xs text-gray-500">Aktifkan akses tryout</span></span><i class="ri-arrow-right-line text-xl text-primary"></i></a>
            @endif
            <a href="{{ data_get($combinedAiPayment, 'invoice_url') }}" class="flex items-center justify-between rounded-xl border border-gray-200 p-4 hover:border-primary/50"><span><span class="block font-semibold text-gray-800">Bayar Pembahasan AI</span><span class="text-xs text-gray-500">Aktifkan kuota Diskusi AI</span></span><i class="ri-arrow-right-line text-xl text-primary"></i></a>
        </div>
        <button type="button" onclick="closeResumeTryoutPaymentLinksModal()" class="mt-5 w-full rounded-xl border border-gray-300 px-4 py-2.5 font-medium text-gray-600 hover:bg-gray-50">Nanti saja</button>
    </div>
</div>
@endif

<div id="aiPreviewModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/65 p-4 backdrop-blur-sm" onclick="if (event.target === this) closeAiPreviewModal()" role="dialog" aria-modal="true" aria-label="Pratinjau ilustrasi Pembahasan AI">
    <div class="relative max-h-[90vh] w-full max-w-4xl">
        <button type="button" onclick="closeAiPreviewModal()" class="absolute -right-1 -top-11 rounded-lg p-2 text-white hover:bg-white/15" aria-label="Tutup pratinjau"><i class="ri-close-line text-2xl"></i></button>
        <img src="{{ asset('img/new-fitures/pembahasan-ai.webp') }}" alt="Ilustrasi Pembahasan AI ukuran besar" class="max-h-[90vh] w-full rounded-2xl object-contain shadow-2xl">
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const aiGatewayPlans = @json($aiGatewayPlansJson);
    const defaultAiGatewayPlanId = @json($defaultAiGatewayPlanId);
    const aiGatewayCheckoutUrl = @json(route('user.ai-gateway.checkout'));
    const activeCombinedPayment = @json($combinedAiPayment);
    const modal = document.getElementById('tryoutPurchaseModal');
    const form = document.getElementById('tryoutPurchaseForm');
    const itemId = document.getElementById('tryoutPurchaseItemId');
    const itemName = document.getElementById('tryoutPurchaseItemName');
    const itemPrice = document.getElementById('tryoutPurchaseItemPrice');
    const modalTitle = document.getElementById('tryoutPurchaseModalTitle');
    const voucherWrapper = document.getElementById('tryoutVoucherWrapper');
    const proofWrapper = document.getElementById('tryoutPaymentProofWrapper');
    const proofInput = proofWrapper?.querySelector('input[type="file"]');
    const conditionalRequirementWrapper = document.getElementById('tryoutConditionalRequirementWrapper');
    const conditionalRequirementText = document.getElementById('tryoutConditionalRequirementText');
    const conditionalProofWrapper = document.getElementById('tryoutConditionalProofWrapper');
    const conditionalProofInput = conditionalProofWrapper?.querySelector('input[type="file"]');
    const conditionalNotesWrapper = document.getElementById('tryoutConditionalNotesWrapper');
    const submitBtn = document.getElementById('tryoutSubmitPurchaseBtn');
    const aiAddOn = document.getElementById('tryoutAiAddOn');
    const aiEnabled = document.getElementById('tryoutAiEnabled');
    const aiPlan = document.getElementById('tryoutAiPlan');

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        form.reset();
    }

    document.querySelectorAll('[data-buy-tryout]').forEach(button => {
        button.addEventListener('click', function () {
            if (activeCombinedPayment
                && activeCombinedPayment.product_type === 'individual'
                && Number(activeCombinedPayment.product_item_id) === Number(this.dataset.id)) {
                openResumeTryoutPaymentLinksModal();
                return;
            }

            itemId.value = this.dataset.id;
            itemName.textContent = this.dataset.name;
            const priceType = this.dataset.priceType || 'paid';
            const isPaid = priceType === 'paid';
            const isConditional = priceType === 'free_conditional';
            modalTitle.textContent = isPaid ? 'Beli Tryout' : (priceType === 'free_conditional' ? 'Ajukan Akses Tryout' : 'Akses Gratis Tryout');
            itemPrice.textContent = isPaid ? 'Rp ' + Number(this.dataset.price).toLocaleString('id-ID') : (priceType === 'free_conditional' ? 'Gratis Bersyarat' : 'Gratis');
            voucherWrapper?.classList.toggle('hidden', !isPaid);
            aiAddOn?.classList.toggle('hidden', !isPaid);
            if (aiEnabled) aiEnabled.checked = false;
            if (aiPlan) aiPlan.value = defaultAiGatewayPlanId ? String(defaultAiGatewayPlanId) : '';
            document.getElementById('tryoutAiPlanWrap')?.classList.add('hidden');
            proofWrapper?.classList.toggle('hidden', !isPaid);
            if (proofInput) proofInput.required = isPaid;
            conditionalRequirementWrapper?.classList.toggle('hidden', !isConditional);
            conditionalProofWrapper?.classList.toggle('hidden', !isConditional);
            conditionalNotesWrapper?.classList.toggle('hidden', !isConditional);
            if (conditionalRequirementText) {
                conditionalRequirementText.textContent = this.dataset.requirement || 'Kirim bukti pemenuhan syarat untuk diverifikasi admin.';
            }
            if (conditionalProofInput) conditionalProofInput.required = isConditional;
            submitBtn.textContent = isPaid ? 'Beli' : (priceType === 'free_conditional' ? 'Ajukan' : 'Aktifkan');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    document.getElementById('closeTryoutPurchaseModal').addEventListener('click', closeModal);
    document.getElementById('cancelTryoutPurchase').addEventListener('click', closeModal);

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        let aiInvoiceUrl = null;
        if (aiEnabled?.checked) {
            const selectedPlanId = Number(aiPlan?.value || 0);
            if (!aiGatewayPlans.some((plan) => Number(plan.id) === selectedPlanId)) {
                alert('Paket Pembahasan AI tidak valid.');
                return;
            }

            const aiFormData = new FormData();
            aiFormData.append('plan_id', selectedPlanId);
            aiFormData.append('return_url', window.location.href);
            aiFormData.append('combined_checkout', '1');
            const aiResponse = await fetch(aiGatewayCheckoutUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: aiFormData,
            });
            const aiData = await aiResponse.json();
            if (!aiResponse.ok || !aiData.success) {
                alert(aiData.message || 'Gagal membuat tagihan Pembahasan AI.');
                return;
            }
            if (!aiData.activated && !aiData.already_claimed && !aiData.invoice_url) {
                alert(aiData.message || 'Gagal membuat tagihan Pembahasan AI.');
                return;
            }
            aiInvoiceUrl = aiData.invoice_url || null;
        }

        const purchaseFormData = new FormData(form);
        if (aiInvoiceUrl) {
            purchaseFormData.set('combined_ai_checkout', '1');
        }

        const response = await fetch('{{ route('user.individual-purchase.buy') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: purchaseFormData,
        });

        const data = await response.json();
        if (data.redirect_url) {
            if (aiInvoiceUrl) {
                window.showTryoutPaymentLinks(data.redirect_url, aiInvoiceUrl);
                closeModal();
                return;
            }
            window.location.href = data.redirect_url;
            return;
        }

        alert(data.message || (data.success ? 'Pembelian berhasil diproses.' : 'Pembelian gagal diproses.'));
        if (data.success) {
            if (aiInvoiceUrl) {
                window.showTryoutPaymentLinks(null, aiInvoiceUrl);
                closeModal();
                return;
            }
            window.location.reload();
        }
    });

    window.showTryoutPaymentLinks = function (productUrl, aiUrl) {
        const productLink = document.getElementById('tryoutPayProductLink');
        productLink.classList.toggle('hidden', !productUrl);
        productLink.href = productUrl || '#';
        document.getElementById('tryoutPayAiLink').href = aiUrl;
        document.getElementById('tryoutPaymentLinksModal').classList.remove('hidden');
        document.getElementById('tryoutPaymentLinksModal').classList.add('flex');
    };
});

function closeTryoutPaymentLinksModal() {
    document.getElementById('tryoutPaymentLinksModal').classList.add('hidden');
    document.getElementById('tryoutPaymentLinksModal').classList.remove('flex');
}

function openResumeTryoutPaymentLinksModal() {
    const modal = document.getElementById('resumeTryoutPaymentLinksModal');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeResumeTryoutPaymentLinksModal() {
    const modal = document.getElementById('resumeTryoutPaymentLinksModal');
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

@if($useGeneralLayout)
</div>
@endif
@endsection
