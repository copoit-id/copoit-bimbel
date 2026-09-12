@php
    $activeSubscriptions = collect($subscriptions)->filter(fn ($item) => data_get($item, 'status') === 'active');
    $subscriptionTokenLimit = $activeSubscriptions->sum(function ($item) {
        $plan = data_get($item, 'plan', []);

        return (int) (data_get($item, 'token_limit') ?: data_get($plan, 'token_limit', 0));
    });
    $subscriptionTokensUsed = $activeSubscriptions->sum(fn ($item) => (int) data_get($item, 'tokens_used', 0));
    $subscriptionTokenPercentage = $subscriptionTokenLimit > 0
        ? min(100, ($subscriptionTokensUsed / $subscriptionTokenLimit) * 100)
        : null;
    $subscriptionChatLimit = $activeSubscriptions->sum(function ($item) {
        $plan = data_get($item, 'plan', []);

        return (int) (data_get($item, 'chat_limit') ?: data_get($plan, 'chat_limit', 0));
    });
    $subscriptionChatsUsed = $activeSubscriptions->sum(fn ($item) => (int) data_get($item, 'chats_used', 0));
    $subscriptionChatPercentage = $subscriptionChatLimit > 0
        ? min(100, ($subscriptionChatsUsed / $subscriptionChatLimit) * 100)
        : null;
    $subscriptionExhausted = $activeSubscriptions->isNotEmpty()
        && (($subscriptionTokenLimit > 0 && $subscriptionTokensUsed >= $subscriptionTokenLimit)
            || ($subscriptionChatLimit > 0 && $subscriptionChatsUsed >= $subscriptionChatLimit));
    $aiLearningReturnUrl = route('user.ai-learning.index', ['tool' => 'quota']);
    $aiLearningOnboardingReturnUrl = route('user.ai-learning.index', ['tool' => 'note', 'onboarding' => 1]);
@endphp

<div class="space-y-6">
    <div><p class="text-sm font-semibold text-primary"><i class="ri-cpu-line mr-1"></i>Paket & Kuota</p><h2 class="mt-1 text-2xl font-bold text-gray-900">Paket & Penggunaan AI</h2><p class="mt-1 text-sm text-gray-500">Kelola paket pembahasan AI dan pantau pemakaian tokenmu di satu tempat.</p></div>

    @if(session('error'))<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif
    @if(session('success'))<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>@endif
    @if(request('payment') === 'success')<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">Pembayaran diterima. Status paket sedang disinkronkan dari gateway pusat.</div>@endif
    @if(request('payment') === 'failed')<div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Pembayaran belum berhasil. Kamu dapat mencoba kembali kapan saja.</div>@endif
    @if($gatewayError)<div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $gatewayError }}</div>@endif

    @if(data_get($pendingPayment, 'invoice_url'))
        <div class="flex flex-col gap-3 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div><p class="font-semibold text-blue-900">Pembayaran paket {{ data_get($pendingPayment, 'plan_name', 'AI') }} masih menunggu.</p><p class="mt-1 text-sm text-blue-800">Lanjutkan pembayaran sebelum {{ \Illuminate\Support\Carbon::parse(data_get($pendingPayment, 'expires_at'))->translatedFormat('d M Y H:i') }}.</p></div>
            <a href="{{ data_get($pendingPayment, 'invoice_url') }}" class="w-fit rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Lanjutkan pembayaran</a>
        </div>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <div><h3 class="text-lg font-bold text-gray-900">Status paket saya</h3><p class="mt-1 text-sm text-gray-500">Kuota setiap paket dihitung oleh gateway pusat.</p></div>
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse($subscriptions as $activeSubscription)
                @php
                    $activePlan = data_get($activeSubscription, 'plan', []);
                    $activeTokenLimit = (int) (data_get($activeSubscription, 'token_limit') ?: data_get($activePlan, 'token_limit', 0));
                    $activeTokensUsed = (int) data_get($activeSubscription, 'tokens_used', 0);
                    $activeChatLimit = (int) (data_get($activeSubscription, 'chat_limit') ?: data_get($activePlan, 'chat_limit', 0));
                    $activeChatsUsed = (int) data_get($activeSubscription, 'chats_used', 0);
                @endphp
                <article class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <div class="flex items-start justify-between gap-3"><div><p class="font-semibold text-gray-900">{{ data_get($activePlan, 'name', '-') }}</p><p class="mt-1 text-xs text-gray-500">{{ data_get($activeSubscription, 'ends_at') ? 'Berakhir ' . \Illuminate\Support\Carbon::parse(data_get($activeSubscription, 'ends_at'))->translatedFormat('d M Y') : 'Tanpa masa aktif' }}</p></div><span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Aktif</span></div>
                    <div class="mt-4"><div class="flex justify-between gap-3 text-xs text-gray-500"><span>Sisa token</span><span>{{ number_format(max(0, $activeTokenLimit - $activeTokensUsed), 0, ',', '.') }} / {{ number_format($activeTokenLimit, 0, ',', '.') }}</span></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200"><div class="h-full rounded-full bg-primary" style="width: {{ $activeTokenLimit > 0 ? min(100, ($activeTokensUsed / $activeTokenLimit) * 100) : 0 }}%"></div></div>@if($activeChatLimit > 0)<p class="mt-3 text-xs text-gray-600">Sisa {{ number_format(max(0, $activeChatLimit - $activeChatsUsed), 0, ',', '.') }} dari {{ number_format($activeChatLimit, 0, ',', '.') }} chat AI.</p>@else<p class="mt-3 text-xs text-gray-600">Chat AI unlimited sampai token habis.</p>@endif</div>
                </article>
            @empty
                <div class="rounded-xl bg-gray-50 p-4 text-sm text-gray-600 md:col-span-2 xl:col-span-3">Belum ada paket aktif.</div>
            @endforelse
        </div>
    </section>

    @if($subscriptionExhausted)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4"><p class="font-semibold text-amber-900">Kuota paket AI sudah habis.</p><p class="mt-1 text-sm text-amber-800">Pilih paket baru di bawah untuk melanjutkan fitur AI.</p></div>
    @endif

    <section>
        <h3 class="text-lg font-bold text-gray-900">{{ $subscriptionExhausted ? 'Beli paket lagi' : 'Pilih paket' }}</h3>
        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($plans as $plan)
                @php
                    $isFreePlan = (bool) data_get($plan, 'is_free', (int) data_get($plan, 'price', 0) === 0);
                    $freePlanClaimed = $isFreePlan && in_array((int) data_get($plan, 'id'), $claimedFreePlanIds, true);
                    $paidCheckoutBlocked = ! $isFreePlan && data_get($pendingPayment, 'invoice_url');
                @endphp
                <article class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5">
                    <div class="flex items-start justify-between gap-3"><h4 class="font-semibold text-gray-900">{{ data_get($plan, 'name') }}</h4>@if($isFreePlan)<span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">Gratis</span>@endif</div>
                    <p class="mt-2 text-2xl font-bold text-primary">{{ $isFreePlan ? 'Gratis' : 'Rp ' . number_format(data_get($plan, 'price'), 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ data_get($plan, 'duration_days') > 0 ? 'Aktif ' . data_get($plan, 'duration_days') . ' hari' : 'Tanpa masa aktif' }}</p>
                    <p class="mt-4 text-sm text-gray-600">{{ data_get($plan, 'chat_limit') ? number_format(data_get($plan, 'chat_limit'), 0, ',', '.') . ' chat AI' : 'Chat AI unlimited sampai token habis' }}</p>
                    <form method="POST" action="{{ route('user.ai-gateway.checkout') }}" class="mt-5">@csrf<input type="hidden" name="plan_id" value="{{ data_get($plan, 'id') }}"><input type="hidden" name="return_url" value="{{ $isFreePlan ? $aiLearningOnboardingReturnUrl : $aiLearningReturnUrl }}"><button @disabled($freePlanClaimed || $paidCheckoutBlocked) class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50">
                        @if($freePlanClaimed)
                            Sudah diklaim
                        @elseif($isFreePlan)
                            Klaim gratis
                        @elseif($paidCheckoutBlocked)
                            Selesaikan pembayaran sebelumnya
                        @elseif($subscriptionExhausted)
                            Beli lagi
                        @else
                            Beli paket
                        @endif
                    </button></form>
                    <p class="mt-3 text-center text-xs text-gray-500">{{ $isFreePlan ? 'Langsung aktif tanpa pembayaran. Satu kali klaim per akun.' : 'Pembayaran diproses oleh gateway pusat.' }}</p>
                </article>
            @empty
                <div class="rounded-xl border border-gray-200 bg-white p-5 text-sm text-gray-500 md:col-span-2 xl:col-span-3">Belum ada paket AI yang tersedia.</div>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between"><div><h3 class="text-lg font-bold text-gray-900">Penggunaan kuota saya</h3><p class="mt-1 text-sm text-gray-500">Pantau pemakaian token dan chat AI dari semua paket yang masih aktif.</p></div><span class="inline-flex w-fit items-center gap-1 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary"><i class="ri-pulse-line"></i>Live quota</span></div>

        @if($activeSubscriptions->isEmpty())
            <div class="mt-5 rounded-xl border border-dashed border-gray-200 bg-gray-50 px-5 py-8 text-center text-sm text-gray-500"><i class="ri-pie-chart-2-line mb-2 block text-2xl text-gray-300"></i>Aktifkan paket AI untuk melihat penggunaan kuota.</div>
        @else
            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <article class="rounded-2xl border border-gray-100 bg-gray-50 p-4 sm:p-5">
                    <div class="flex items-start justify-between gap-3"><div><p class="text-sm font-semibold text-gray-900"><i class="ri-coin-line mr-1 text-primary"></i>Token AI</p><p class="mt-1 text-xs text-gray-500">{{ $subscriptionTokenLimit > 0 ? 'Akumulasi semua paket aktif' : 'Token tidak dibatasi paket' }}</p></div><span class="text-right text-sm font-bold text-gray-900">{{ $subscriptionTokenLimit > 0 ? number_format(max(0, $subscriptionTokenLimit - $subscriptionTokensUsed), 0, ',', '.') : '∞' }}<span class="ml-1 text-xs font-medium text-gray-500">tersisa</span></span></div>
                    @if($subscriptionTokenPercentage !== null)
                        <div class="mt-5 flex items-end justify-between gap-3"><p class="text-2xl font-bold text-gray-900">{{ number_format($subscriptionTokenPercentage, 1, ',', '.') }}<span class="text-sm font-semibold text-gray-500">%</span></p><p class="text-right text-xs text-gray-500">{{ number_format($subscriptionTokensUsed, 0, ',', '.') }} terpakai dari {{ number_format($subscriptionTokenLimit, 0, ',', '.') }}</p></div><div class="mt-2 h-3 overflow-hidden rounded-full bg-gray-200"><div class="h-full rounded-full bg-primary transition-all" style="width: {{ $subscriptionTokenPercentage }}%"></div></div>
                    @else
                        <p class="mt-5 text-sm font-medium text-gray-700">Paket ini tidak membatasi token.</p>
                    @endif
                </article>

                <article class="rounded-2xl border border-gray-100 bg-gray-50 p-4 sm:p-5">
                    <div class="flex items-start justify-between gap-3"><div><p class="text-sm font-semibold text-gray-900"><i class="ri-message-3-line mr-1 text-primary"></i>Chat AI</p><p class="mt-1 text-xs text-gray-500">Jumlah percakapan yang digunakan</p></div><span class="text-right text-sm font-bold text-gray-900">{{ $subscriptionChatLimit > 0 ? number_format(max(0, $subscriptionChatLimit - $subscriptionChatsUsed), 0, ',', '.') : '∞' }}<span class="ml-1 text-xs font-medium text-gray-500">tersisa</span></span></div>
                    @if($subscriptionChatPercentage !== null)
                        <div class="mt-5 flex items-end justify-between gap-3"><p class="text-2xl font-bold text-gray-900">{{ number_format($subscriptionChatPercentage, 1, ',', '.') }}<span class="text-sm font-semibold text-gray-500">%</span></p><p class="text-right text-xs text-gray-500">{{ number_format($subscriptionChatsUsed, 0, ',', '.') }} terpakai dari {{ number_format($subscriptionChatLimit, 0, ',', '.') }}</p></div><div class="mt-2 h-3 overflow-hidden rounded-full bg-gray-200"><div class="h-full rounded-full bg-primary/80 transition-all" style="width: {{ $subscriptionChatPercentage }}%"></div></div>
                    @else
                        <p class="mt-5 text-sm font-medium text-gray-700">Chat AI tidak dibatasi selama token masih tersedia.</p>
                    @endif
                </article>
            </div>
        @endif
    </section>
</div>
