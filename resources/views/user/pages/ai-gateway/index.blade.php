@extends('user.layout.new-user')

@section('title', 'Paket Diskusi AI')

@section('content')
<div class="space-y-6">
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
        $subscriptionExhausted = $activeSubscriptions->isNotEmpty()
            && (($subscriptionTokenLimit > 0 && $subscriptionTokensUsed >= $subscriptionTokenLimit));
    @endphp
    <div><p class="text-sm text-gray-500">Diskusi AI</p><h1 class="text-2xl font-semibold text-gray-900">Paket & Penggunaan AI</h1><p class="mt-1 text-gray-500">Pilih paket untuk memakai chat AI pada pembahasan tryout dan pantau pemakaianmu.</p></div>

    @if(session('error'))<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif
    @if(request('payment') === 'success')<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">Pembayaran berhasil diterima. Status paket sedang disinkronkan dari gateway pusat.</div>@endif
    @if(request('payment') === 'failed')<div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Pembayaran belum berhasil. Anda dapat mencoba kembali kapan saja.</div>@endif
    @if($gatewayError)<div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $gatewayError }}</div>@endif
    @if(data_get($pendingPayment, 'invoice_url'))<div class="flex flex-col gap-3 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-semibold text-blue-900">Pembayaran paket {{ data_get($pendingPayment, 'plan_name', 'AI') }} masih menunggu.</p><p class="mt-1 text-sm text-blue-800">Lanjutkan pembayaran sebelum {{ \Illuminate\Support\Carbon::parse(data_get($pendingPayment, 'expires_at'))->translatedFormat('d M Y H:i') }}.</p></div><a href="{{ data_get($pendingPayment, 'invoice_url') }}" class="w-fit rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Lanjutkan pembayaran</a></div>@endif

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <div><h2 class="font-semibold text-gray-900">Status paket saya</h2><p class="mt-1 text-sm text-gray-500">Kuota setiap paket dihitung oleh gateway pusat.</p></div>
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse($subscriptions as $activeSubscription)
                @php
                    $activePlan = data_get($activeSubscription, 'plan', []);
                    $activeTokenLimit = (int) (data_get($activeSubscription, 'token_limit') ?: data_get($activePlan, 'token_limit', 0));
                    $activeTokensUsed = (int) data_get($activeSubscription, 'tokens_used', 0);
                @endphp
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4"><div class="flex items-start justify-between gap-3"><div><p class="font-semibold text-gray-900">{{ data_get($activePlan, 'name', '-') }}</p><p class="mt-1 text-xs text-gray-500">Berakhir {{ \Illuminate\Support\Carbon::parse(data_get($activeSubscription, 'ends_at'))->translatedFormat('d M Y') }}</p></div><span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Aktif</span></div><div class="mt-4"><div class="flex justify-between gap-3 text-xs text-gray-500"><span>Sisa token</span><span>{{ number_format(max(0, $activeTokenLimit - $activeTokensUsed), 0, ',', '.') }} / {{ number_format($activeTokenLimit, 0, ',', '.') }}</span></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200"><div class="h-full rounded-full bg-primary" style="width: {{ $activeTokenLimit > 0 ? min(100, ($activeTokensUsed / $activeTokenLimit) * 100) : 0 }}%"></div></div><p class="mt-3 text-xs text-gray-600">Estimasi {{ max(1, floor($activeTokenLimit / 1600)) }}–{{ max(1, floor($activeTokenLimit / 700)) }} tanya-jawab.</p></div></div>
            @empty
                <div class="rounded-xl bg-gray-50 p-4 text-sm text-gray-600 md:col-span-2 xl:col-span-3">Belum ada paket aktif.</div>
            @endforelse
        </div>
    </div>

    @if($subscriptionExhausted)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4"><p class="font-semibold text-amber-900">Kuota paket AI Anda sudah habis.</p><p class="mt-1 text-sm text-amber-800">Pilih paket baru di bawah untuk melanjutkan Diskusi AI Pembahasan.</p></div>
    @endif

    <div><h2 class="text-lg font-semibold text-gray-900">{{ $subscriptionExhausted ? 'Beli paket lagi' : 'Pilih paket' }}</h2><div class="mt-4 grid gap-4 md:grid-cols-3">@forelse($plans as $plan)<div class="flex flex-col rounded-xl border border-gray-200 bg-white p-5"><h3 class="font-semibold text-gray-900">{{ data_get($plan, 'name') }}</h3><p class="mt-2 text-2xl font-bold text-primary">Rp {{ number_format(data_get($plan, 'price'), 0, ',', '.') }}</p><p class="mt-1 text-sm text-gray-500">Aktif {{ data_get($plan, 'duration_days') }} hari</p><ul class="mt-4 space-y-2 text-sm text-gray-600"><li>{{ max(1, floor(data_get($plan, 'token_limit') / 1600)) }}–{{ max(1, floor(data_get($plan, 'token_limit') / 700)) }} tanya jawab</li><li>{{ number_format(data_get($plan, 'token_limit'), 0, ',', '.') }} token</li></ul><form method="POST" action="{{ route('user.ai-gateway.checkout') }}" class="mt-5">@csrf<input type="hidden" name="plan_id" value="{{ data_get($plan, 'id') }}"><button @disabled(data_get($pendingPayment, 'invoice_url')) class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50">{{ data_get($pendingPayment, 'invoice_url') ? 'Selesaikan pembayaran sebelumnya' : ($subscriptionExhausted ? 'Beli lagi' : 'Beli paket') }}</button></form><p class="mt-3 text-center text-xs text-gray-500">Pembayaran diproses oleh gateway pusat.</p></div>@empty<div class="rounded-xl border border-gray-200 bg-white p-5 text-sm text-gray-500 md:col-span-3">Belum ada paket AI yang tersedia.</div>@endforelse</div></div>

    <div class="rounded-xl border border-gray-200 bg-white p-5"><div><h2 class="font-semibold text-gray-900">Riwayat penggunaan saya</h2><p class="mt-1 text-sm text-gray-500">Riwayat chat AI dari akun ini di project saat ini.</p></div>@if($subscriptionTokenPercentage !== null)<div class="mt-4 rounded-xl bg-gray-50 p-4"><div class="flex items-center justify-between gap-3 text-sm"><span class="font-medium text-gray-700">Token paket digunakan dari semua paket aktif</span><span class="font-semibold text-gray-900">{{ number_format($subscriptionTokensUsed, 0, ',', '.') }} / {{ number_format($subscriptionTokenLimit, 0, ',', '.') }} · {{ number_format($subscriptionTokenPercentage, 1, ',', '.') }}%</span></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200"><div class="h-full rounded-full bg-primary" style="width: {{ $subscriptionTokenPercentage }}%"></div></div><p class="mt-2 text-xs text-gray-500">Total token riwayat chat: {{ number_format($usageTokenTotal ?? 0, 0, ',', '.') }}</p></div>@elseif($activeSubscriptions->isNotEmpty())<p class="mt-4 rounded-xl bg-gray-50 p-4 text-sm text-gray-600">Paket aktif belum memiliki batas token yang dapat dihitung.</p>@endif<div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-3 py-3">Waktu</th><th class="px-3 py-3 text-right">Token digunakan</th><th class="px-3 py-3 text-right">Respons</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($usageLogs as $log)<tr><td class="px-3 py-3 text-gray-500">{{ $log->created_at->format('d M Y H:i') }}</td><td class="px-3 py-3 text-right">{{ number_format($log->total_tokens, 0, ',', '.') }}</td><td class="px-3 py-3 text-right">{{ number_format(($log->response_time_ms ?? 0) / 1000, 2, ',', '.') }} dtk</td></tr>@empty<tr><td colspan="3" class="px-3 py-8 text-center text-gray-500">Belum ada riwayat penggunaan AI.</td></tr>@endforelse</tbody></table></div><div class="mt-4">{{ $usageLogs->links() }}</div></div>
</div>
@endsection

@if(data_get($pendingPayment, 'invoice_url'))
@push('scripts')
<script>
    document.querySelectorAll('button[disabled]').forEach((button) => {
        const form = button.closest('form');
        if (!form) return;
        const link = document.createElement('a');
        link.href = @json(data_get($pendingPayment, 'invoice_url'));
        link.className = 'block w-full rounded-lg bg-primary px-4 py-2 text-center text-sm font-medium text-white hover:bg-primary/90';
        link.textContent = 'Lanjutkan pembayaran';
        form.replaceWith(link);
    });
</script>
@endpush
@endif
