@extends('admin.layout.admin')

@section('title', 'Kuota AI Generator Soal')

@section('content')
@php
    $subscriptions = collect(data_get($status, 'subscriptions', []));
    $tokenLimit = $subscriptions->sum(fn ($subscription) => (int) data_get($subscription, 'token_limit', data_get($subscription, 'plan.token_limit', 0)));
    $tokensUsed = $subscriptions->sum(fn ($subscription) => (int) data_get($subscription, 'tokens_used', 0));
    $remainingTokens = max(0, $tokenLimit - $tokensUsed);
    $questionEstimate = data_get($status, 'question_estimate', ['label' => '0']);
@endphp

<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-primary">AI Question Generator</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">Kuota Generator Soal</h1>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Pilih paket untuk membuat draft soal pilihan ganda, pembahasan, dan variasi berbasis bank soal atau tryout.</p>
        </div>
        <a href="{{ route('admin.question-bank.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"><i class="ri-arrow-left-line"></i> Bank Soal</a>
    </div>

    @if(session('success'))<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif
    @if($error)<div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $error }}</div>@endif

    <section class="rounded-2xl border border-border bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div><h2 class="text-lg font-semibold text-gray-900">Kapasitas Aktif</h2><p class="mt-1 text-sm text-gray-500">Kapasitas berkurang saat preview dibuat; menyimpan atau membuka ulang preview tidak mengurangi kapasitas lagi.</p></div>
            <div class="rounded-xl bg-primary/10 px-5 py-3 text-right"><p class="text-xs font-semibold uppercase tracking-wide text-primary">Perkiraan soal tersisa</p><p class="mt-1 text-2xl font-bold text-primary">{{ data_get($questionEstimate, 'label') }} soal</p></div>
        </div>
        <div class="mt-5 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-primary" style="width: {{ $tokenLimit > 0 ? min(100, ($remainingTokens / $tokenLimit) * 100) : 0 }}%"></div></div>
        <p class="mt-2 text-xs text-gray-500">Estimasi dapat berubah sesuai panjang pembahasan dan penggunaan referensi soal.</p>
    </section>

    <section>
        <div class="mb-4"><h2 class="text-lg font-semibold text-gray-900">Paket AI Generator Soal</h2><p class="mt-1 text-sm text-gray-500">Khusus untuk admin. Paket ini tidak dapat digunakan untuk Pembahasan AI siswa.</p></div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($plans as $plan)
                <article class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">{{ data_get($plan, 'name') }}</h3>
                    <p class="mt-2 text-2xl font-bold text-primary">{{ data_get($plan, 'is_free') ? 'Gratis' : 'Rp '.number_format(data_get($plan, 'price'), 0, ',', '.') }}</p>
                    <dl class="mt-5 space-y-2 text-sm text-gray-600"><div class="flex justify-between gap-3"><dt>Perkiraan soal</dt><dd class="font-semibold text-gray-900">{{ data_get($plan, 'question_estimate.label') }} soal</dd></div><div class="flex justify-between gap-3"><dt>Fitur</dt><dd class="font-semibold text-gray-900">Generate & referensi soal</dd></div></dl>
                    <form method="POST" action="{{ route('admin.question-generator.quota.checkout') }}" class="mt-6">@csrf<input type="hidden" name="plan_id" value="{{ data_get($plan, 'id') }}"><button class="w-full rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary/90">{{ data_get($plan, 'is_free') ? 'Klaim Paket' : 'Beli Kuota' }}</button></form>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center text-sm text-gray-500 md:col-span-2 xl:col-span-3">Belum ada paket Generator Soal yang aktif. Super Admin dapat menambahkannya dari menu Paket AI.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
