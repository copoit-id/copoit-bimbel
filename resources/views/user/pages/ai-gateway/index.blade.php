@extends('user.layout.new-user')

@section('title', 'Paket Diskusi AI')

@section('content')
<div class="space-y-6">
    @php
        $subscriptionPlan = data_get($subscription, 'plan', []);
        $subscriptionTokenLimit = (int) data_get($subscriptionPlan, 'token_limit', 0);
        $subscriptionTokensUsed = (int) data_get($subscription, 'tokens_used', 0);
        $subscriptionTokenPercentage = $subscriptionTokenLimit > 0
            ? min(100, ($subscriptionTokensUsed / $subscriptionTokenLimit) * 100)
            : null;
    @endphp
    <div><p class="text-sm text-gray-500">Diskusi AI</p><h1 class="text-2xl font-semibold text-gray-900">Paket & Penggunaan AI</h1><p class="mt-1 text-gray-500">Pilih paket untuk memakai chat AI pada pembahasan tryout dan pantau pemakaianmu.</p></div>

    @if(session('error'))<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif
    @if($gatewayError)<div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $gatewayError }}</div>@endif

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"><div><h2 class="font-semibold text-gray-900">Status paket saya</h2><p class="mt-1 text-sm text-gray-500">Kuota dihitung oleh gateway pusat.</p></div>@if(data_get($subscription, 'status') === 'active')<span class="w-fit rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-700">Aktif hingga {{ \Illuminate\Support\Carbon::parse(data_get($subscription, 'ends_at'))->translatedFormat('d M Y') }}</span>@else<span class="w-fit rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-600">Belum ada paket aktif</span>@endif</div>
        @if(data_get($subscription, 'status') === 'active')
            @php $plan = data_get($subscription, 'plan', []); @endphp
            <div class="mt-4 grid gap-3 sm:grid-cols-3"><div class="rounded-xl bg-gray-50 p-4"><p class="text-xs text-gray-500">Paket</p><p class="mt-1 font-semibold text-gray-900">{{ data_get($plan, 'name', '-') }}</p></div><div class="rounded-xl bg-gray-50 p-4"><p class="text-xs text-gray-500">Sisa chat</p><p class="mt-1 font-semibold text-gray-900">{{ data_get($plan, 'chat_limit') ? max(0, data_get($plan, 'chat_limit') - data_get($subscription, 'chats_used', 0)) : 'Tidak terbatas' }}</p></div><div class="rounded-xl bg-gray-50 p-4"><p class="text-xs text-gray-500">Sisa token</p><p class="mt-1 font-semibold text-gray-900">{{ data_get($plan, 'token_limit') ? number_format(max(0, data_get($plan, 'token_limit') - data_get($subscription, 'tokens_used', 0)), 0, ',', '.') : 'Tidak terbatas' }}</p></div></div>
        @endif
    </div>

    <div><h2 class="text-lg font-semibold text-gray-900">Pilih paket</h2><div class="mt-4 grid gap-4 md:grid-cols-3">@forelse($plans as $plan)<div class="flex flex-col rounded-xl border border-gray-200 bg-white p-5"><h3 class="font-semibold text-gray-900">{{ data_get($plan, 'name') }}</h3><p class="mt-2 text-2xl font-bold text-primary">Rp {{ number_format(data_get($plan, 'price'), 0, ',', '.') }}</p><p class="mt-1 text-sm text-gray-500">Aktif {{ data_get($plan, 'duration_days') }} hari</p><ul class="mt-4 space-y-2 text-sm text-gray-600"><li>{{ data_get($plan, 'chat_limit') ?: '∞' }} chat</li><li>{{ data_get($plan, 'token_limit') ? number_format(data_get($plan, 'token_limit'), 0, ',', '.') : '∞' }} token</li></ul><form method="POST" action="{{ route('user.ai-gateway.checkout') }}" class="mt-5">@csrf<input type="hidden" name="plan_id" value="{{ data_get($plan, 'id') }}"><button class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90">Beli paket</button></form><p class="mt-3 text-center text-xs text-gray-500">Pembayaran diproses oleh gateway pusat.</p></div>@empty<div class="rounded-xl border border-gray-200 bg-white p-5 text-sm text-gray-500 md:col-span-3">Belum ada paket AI yang tersedia.</div>@endforelse</div></div>

    <div class="rounded-xl border border-gray-200 bg-white p-5"><div><h2 class="font-semibold text-gray-900">Riwayat penggunaan saya</h2><p class="mt-1 text-sm text-gray-500">Riwayat chat AI dari akun ini di project saat ini.</p></div>@if($subscriptionTokenPercentage !== null)<div class="mt-4 rounded-xl bg-gray-50 p-4"><div class="flex items-center justify-between gap-3 text-sm"><span class="font-medium text-gray-700">Token paket digunakan</span><span class="font-semibold text-gray-900">{{ number_format($subscriptionTokensUsed, 0, ',', '.') }} / {{ number_format($subscriptionTokenLimit, 0, ',', '.') }} · {{ number_format($subscriptionTokenPercentage, 1, ',', '.') }}%</span></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200"><div class="h-full rounded-full bg-primary" style="width: {{ $subscriptionTokenPercentage }}%"></div></div></div>@elseif(data_get($subscription, 'status') === 'active')<p class="mt-4 rounded-xl bg-gray-50 p-4 text-sm text-gray-600">Paket Anda memiliki token tanpa batas.</p>@endif<div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-3 py-3">Waktu</th><th class="px-3 py-3 text-right">Token digunakan</th><th class="px-3 py-3 text-right">Respons</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($usageLogs as $log)<tr><td class="px-3 py-3 text-gray-500">{{ $log->created_at->format('d M Y H:i') }}</td><td class="px-3 py-3 text-right">{{ number_format($log->total_tokens, 0, ',', '.') }}</td><td class="px-3 py-3 text-right">{{ number_format(($log->response_time_ms ?? 0) / 1000, 2, ',', '.') }} dtk</td></tr>@empty<tr><td colspan="3" class="px-3 py-8 text-center text-gray-500">Belum ada riwayat penggunaan AI.</td></tr>@endforelse</tbody></table></div><div class="mt-4">{{ $usageLogs->links() }}</div></div>
</div>
@endsection
