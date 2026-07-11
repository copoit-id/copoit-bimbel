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
            <div class="mb-4"><h3 class="font-semibold text-gray-900">Ringkasan pemakaian</h3><p class="text-sm text-gray-500">Bandingkan konsumsi token berdasarkan rentang waktu.</p></div>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($periodUsage as $period)
                <div class="rounded-xl border border-gray-100 bg-gradient-to-br from-slate-50 to-white p-4">
                    <p class="text-sm font-semibold text-gray-700">{{ $period['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ $formatToken($period['total_tokens']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">token · {{ $formatToken($period['request_count']) }} chat</p>
                    <p class="mt-3 border-t border-gray-100 pt-2 text-xs text-gray-400">{{ $period['description'] }}</p>
                </div>
                @endforeach
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

    <div class="grid gap-6 xl:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <div class="mb-4"><h3 class="font-semibold text-gray-900">Soal dengan konsumsi tertinggi</h3><p class="text-sm text-gray-500">Akumulasi chat AI per soal.</p></div>
        <div class="overflow-x-auto rounded-xl border border-gray-100"><table class="ai-usage-table min-w-full text-sm"><thead><tr><th>Soal</th><th class="text-right">Chat</th><th class="text-right">Input</th><th class="text-right">Output</th><th class="text-right">Total</th></tr></thead><tbody class="divide-y divide-gray-100">
            @forelse($byQuestion as $row)<tr><td class="max-w-xl py-3 pr-4 text-gray-700">{{ \Illuminate\Support\Str::limit(trim(strip_tags($row->question?->question_text ?? 'Soal telah dihapus')), 120) }}</td><td class="py-3 pr-4 text-right">{{ $formatToken($row->request_count) }}</td><td class="py-3 pr-4 text-right">{{ $formatToken($row->input_tokens) }}</td><td class="py-3 pr-4 text-right">{{ $formatToken($row->output_tokens) }}</td><td class="py-3 text-right font-semibold">{{ $formatToken($row->total_tokens) }}</td></tr>@empty<tr><td colspan="5" class="py-8 text-center text-gray-500">Belum ada data.</td></tr>@endforelse
        </tbody></table></div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <div class="mb-4"><h3 class="font-semibold text-gray-900">Pengguna dengan konsumsi tertinggi</h3><p class="text-sm text-gray-500">Akumulasi token per siswa pada periode terpilih.</p></div>
        <div class="overflow-x-auto rounded-xl border border-gray-100"><table class="ai-usage-table min-w-full text-sm"><thead><tr><th>Pengguna</th><th class="text-right">Chat</th><th class="text-right">Input</th><th class="text-right">Output</th><th class="text-right">Total</th></tr></thead><tbody class="divide-y divide-gray-100">
            @forelse($byUser as $row)<tr><td class="py-3 pr-4"><p class="font-medium text-gray-800">{{ $row->user?->name ?? 'Pengguna dihapus' }}</p><p class="text-xs text-gray-500">{{ $row->user?->email ?? '-' }}</p></td><td class="py-3 pr-4 text-right">{{ $formatToken($row->request_count) }}</td><td class="py-3 pr-4 text-right">{{ $formatToken($row->input_tokens) }}</td><td class="py-3 pr-4 text-right">{{ $formatToken($row->output_tokens) }}</td><td class="py-3 text-right font-semibold">{{ $formatToken($row->total_tokens) }}</td></tr>@empty<tr><td colspan="5" class="py-8 text-center text-gray-500">Belum ada data.</td></tr>@endforelse
        </tbody></table></div>
    </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <div class="mb-4"><h3 class="font-semibold text-gray-900">Riwayat penggunaan</h3><p class="text-sm text-gray-500">Satu baris adalah satu tanya-jawab AI pada satu soal.</p></div>
        <div class="overflow-x-auto rounded-xl border border-gray-100"><table class="ai-usage-table min-w-full text-sm"><thead><tr><th>Waktu</th><th>Siswa & soal</th><th>Pertanyaan siswa</th><th>Model</th><th class="text-right">Token (I/O/Total)</th></tr></thead><tbody class="divide-y divide-gray-100">
            @forelse($logs as $log)<tr class="align-top"><td class="whitespace-nowrap py-3 pr-4 text-gray-500">{{ $log->created_at->format('d M Y H:i') }}</td><td class="max-w-xs py-3 pr-4"><p class="font-medium text-gray-800">{{ $log->user?->name ?? 'Pengguna dihapus' }}</p><p class="mt-1 text-xs text-gray-500">{{ \Illuminate\Support\Str::limit(trim(strip_tags($log->question?->question_text ?? 'Soal telah dihapus')), 70) }}</p></td><td class="max-w-sm py-3 pr-4 text-gray-600">{{ \Illuminate\Support\Str::limit($log->user_message, 120) }}</td><td class="py-3 pr-4"><span class="rounded bg-gray-100 px-2 py-1 text-xs">{{ strtoupper($log->provider) }}</span><p class="mt-1 text-xs text-gray-500">{{ $log->model }}</p></td><td class="whitespace-nowrap py-3 text-right font-medium">{{ $formatToken($log->input_tokens) }} / {{ $formatToken($log->output_tokens) }} / {{ $formatToken($log->total_tokens) }}<p class="mt-1 text-xs font-normal text-gray-500">{{ number_format(($log->response_time_ms ?? 0) / 1000, 2, ',', '.') }} dtk</p></td></tr>@empty<tr><td colspan="5" class="py-8 text-center text-gray-500">Belum ada riwayat penggunaan AI.</td></tr>@endforelse
        </tbody></table></div>
        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .ai-usage-table thead {
        background: linear-gradient(90deg, #f8fafc, #f1f5f9);
    }

    .ai-usage-table thead th {
        padding: 0.8rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        color: #475569;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .ai-usage-table tbody tr {
        transition: background-color 150ms ease;
    }

    .ai-usage-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .ai-usage-table tbody td:first-child,
    .ai-usage-table thead th:first-child { padding-left: 1rem; }

    .ai-usage-table tbody td:last-child,
    .ai-usage-table thead th:last-child { padding-right: 1rem; }
</style>
@endpush
