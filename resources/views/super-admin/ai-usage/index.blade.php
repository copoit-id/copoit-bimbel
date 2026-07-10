@extends('super-admin.layouts.app')

@section('title', 'Super Admin - AI Usage')

@section('content')
@php
    $formatToken = fn ($value) => number_format((int) $value, 0, ',', '.');
    $quotaUnlimited = $monthlyLimit === 0;
    $remainingTokens = $quotaUnlimited ? null : max(0, $monthlyLimit - $usedTokens);
    $usagePercentage = $quotaUnlimited ? 0 : min(100, $monthlyLimit > 0 ? ($usedTokens / $monthlyLimit) * 100 : 0);
@endphp
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">AI Usage</h2>
            <p class="text-gray-500">Pantau konsumsi token Diskusi AI hingga detail pertanyaan pada setiap soal.</p>
        </div>
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="month" name="month" value="{{ $month }}" class="rounded-lg border-gray-300 text-sm">
            <select name="provider" class="rounded-lg border-gray-300 text-sm">
                <option value="">Semua provider</option>
                @foreach($providers as $provider)<option value="{{ $provider }}" @selected(request('provider') === $provider)>{{ strtoupper($provider) }}</option>@endforeach
            </select>
            <select name="model" class="rounded-lg border-gray-300 text-sm max-w-52">
                <option value="">Semua model</option>
                @foreach($models as $model)<option value="{{ $model }}" @selected(request('model') === $model)>{{ $model }}</option>@endforeach
            </select>
            <button class="rounded-lg bg-primary px-4 py-2 text-sm text-white hover:bg-primary/90">Terapkan</button>
        </form>
    </div>

    @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>@endif

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-500">Token terpakai</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $formatToken($usedTokens) }}</p>
            <p class="mt-1 text-xs text-gray-500">Input {{ $formatToken($summary->input_tokens) }} · Output {{ $formatToken($summary->output_tokens) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-500">Sisa kuota bulan ini</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $quotaUnlimited ? 'Tidak terbatas' : $formatToken($remainingTokens) }}</p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full {{ $usagePercentage >= 90 ? 'bg-red-500' : 'bg-primary' }}" style="width: {{ $usagePercentage }}%"></div></div>
            <p class="mt-1 text-xs text-gray-500">{{ $quotaUnlimited ? 'Belum ada batas kuota aplikasi.' : number_format($usagePercentage, 1, ',', '.') . '% dari ' . $formatToken($monthlyLimit) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-500">Permintaan AI</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $formatToken($summary->request_count) }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $formatToken($summary->user_count) }} siswa · {{ $formatToken($summary->question_count) }} soal</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-500">Rata-rata per chat</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $formatToken($summary->avg_tokens) }} token</p>
            <p class="mt-1 text-xs text-gray-500">Respons rata-rata {{ number_format($summary->avg_response_time / 1000, 2, ',', '.') }} detik</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 xl:col-span-2">
            <div class="mb-4"><h3 class="font-semibold text-gray-900">Pemakaian harian</h3><p class="text-sm text-gray-500">Total token untuk periode terpilih.</p></div>
            <div class="space-y-3">
                @forelse($dailyUsage as $day)
                    @php $maximum = max(1, $dailyUsage->max('total_tokens')); @endphp
                    <div class="grid grid-cols-[90px_1fr_100px] items-center gap-3 text-sm">
                        <span class="text-gray-500">{{ \Carbon\Carbon::parse($day->usage_date)->format('d M') }}</span>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-primary" style="width: {{ ($day->total_tokens / $maximum) * 100 }}%"></div></div>
                        <span class="text-right font-medium text-gray-700">{{ $formatToken($day->total_tokens) }}</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-gray-500">Belum ada penggunaan AI pada periode ini.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <h3 class="font-semibold text-gray-900">Atur kuota aplikasi</h3>
            <p class="mt-1 text-sm text-gray-500">0 berarti tanpa batas. Kuota ini untuk pemantauan internal, bukan saldo billing provider.</p>
            <form method="POST" action="{{ route('super-admin.ai-usage.quota.update') }}" class="mt-4 space-y-3">
                @csrf @method('PUT')
                <label class="block text-sm font-medium text-gray-700">Token per bulan</label>
                <input name="monthly_token_limit" type="number" min="0" value="{{ $monthlyLimit }}" class="w-full rounded-lg border-gray-300" required>
                @error('monthly_token_limit')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <button class="w-full rounded-lg border border-primary px-4 py-2 text-sm font-medium text-primary hover:bg-primary hover:text-white">Simpan kuota</button>
            </form>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <div class="mb-4"><h3 class="font-semibold text-gray-900">Soal dengan konsumsi tertinggi</h3><p class="text-sm text-gray-500">Akumulasi chat AI per soal.</p></div>
        <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="border-b text-left text-xs uppercase text-gray-500"><tr><th class="pb-3 pr-4">Soal</th><th class="pb-3 pr-4 text-right">Chat</th><th class="pb-3 pr-4 text-right">Input</th><th class="pb-3 pr-4 text-right">Output</th><th class="pb-3 text-right">Total</th></tr></thead><tbody class="divide-y divide-gray-100">
            @forelse($byQuestion as $row)<tr><td class="max-w-xl py-3 pr-4 text-gray-700">{{ \Illuminate\Support\Str::limit(trim(strip_tags($row->question?->question_text ?? 'Soal telah dihapus')), 120) }}</td><td class="py-3 pr-4 text-right">{{ $formatToken($row->request_count) }}</td><td class="py-3 pr-4 text-right">{{ $formatToken($row->input_tokens) }}</td><td class="py-3 pr-4 text-right">{{ $formatToken($row->output_tokens) }}</td><td class="py-3 text-right font-semibold">{{ $formatToken($row->total_tokens) }}</td></tr>@empty<tr><td colspan="5" class="py-8 text-center text-gray-500">Belum ada data.</td></tr>@endforelse
        </tbody></table></div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <div class="mb-4"><h3 class="font-semibold text-gray-900">Riwayat penggunaan</h3><p class="text-sm text-gray-500">Satu baris adalah satu tanya-jawab AI pada satu soal.</p></div>
        <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="border-b text-left text-xs uppercase text-gray-500"><tr><th class="pb-3 pr-4">Waktu</th><th class="pb-3 pr-4">Siswa & soal</th><th class="pb-3 pr-4">Pertanyaan siswa</th><th class="pb-3 pr-4">Model</th><th class="pb-3 text-right">Token (I/O/Total)</th></tr></thead><tbody class="divide-y divide-gray-100">
            @forelse($logs as $log)<tr class="align-top"><td class="whitespace-nowrap py-3 pr-4 text-gray-500">{{ $log->created_at->format('d M Y H:i') }}</td><td class="max-w-xs py-3 pr-4"><p class="font-medium text-gray-800">{{ $log->user?->name ?? 'Pengguna dihapus' }}</p><p class="mt-1 text-xs text-gray-500">{{ \Illuminate\Support\Str::limit(trim(strip_tags($log->question?->question_text ?? 'Soal telah dihapus')), 70) }}</p></td><td class="max-w-sm py-3 pr-4 text-gray-600">{{ \Illuminate\Support\Str::limit($log->user_message, 120) }}</td><td class="py-3 pr-4"><span class="rounded bg-gray-100 px-2 py-1 text-xs">{{ strtoupper($log->provider) }}</span><p class="mt-1 text-xs text-gray-500">{{ $log->model }}</p></td><td class="whitespace-nowrap py-3 text-right font-medium">{{ $formatToken($log->input_tokens) }} / {{ $formatToken($log->output_tokens) }} / {{ $formatToken($log->total_tokens) }}<p class="mt-1 text-xs font-normal text-gray-500">{{ number_format(($log->response_time_ms ?? 0) / 1000, 2, ',', '.') }} dtk</p></td></tr>@empty<tr><td colspan="5" class="py-8 text-center text-gray-500">Belum ada riwayat penggunaan AI.</td></tr>@endforelse
        </tbody></table></div>
        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
