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

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"><div><h3 class="font-semibold text-gray-900">Project terhubung ke AI Gateway</h3><p class="text-sm text-gray-500">Project lain memanggil <code>/api/ai-gateway/discussion</code> dengan header <code>X-AI-Gateway-Key</code>.</p></div><button type="button" onclick="document.getElementById('create-gateway-project-modal').classList.remove('hidden')" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90"><i class="ri-add-line"></i>Tambah project</button></div>
        <div class="mt-4 overflow-x-auto rounded-xl border border-gray-100"><table class="ai-usage-table min-w-full text-sm"><thead><tr><th>Project</th><th>Project key</th><th>Terakhir dipakai</th><th class="text-right">Token bulan ini</th><th class="text-right">Kuota</th><th class="text-right">Aksi</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($gatewayClients as $client)<tr><td class="py-3 pr-4 font-medium">{{ $client->name }}<p class="text-xs font-normal text-gray-500">{{ $client->slug }}</p></td><td class="py-3 pr-4">@if((int) session('gateway_key_client_id') === $client->id)<code class="select-all rounded bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-800">{{ session('gateway_key') }}</code><p class="mt-1 text-xs text-amber-700">Salin sekarang; key tidak ditampilkan lagi.</p>@else<span class="text-xs text-gray-400">Tersimpan aman</span>@endif</td><td class="py-3 pr-4 text-gray-500">{{ $client->last_used_at?->format('d M Y H:i') ?? 'Belum pernah' }}</td><td class="py-3 pr-4 text-right font-medium">{{ $formatToken($client->tokens_this_month) }}</td><td class="py-3 text-right">{{ $client->monthly_token_limit ? $formatToken($client->monthly_token_limit) : '∞' }}</td><td class="py-3 text-right whitespace-nowrap"><button type="button" onclick="document.getElementById('edit-gateway-{{ $client->id }}').classList.toggle('hidden')" class="text-xs font-medium text-primary hover:underline">Edit</button><form method="POST" action="{{ route('super-admin.ai-usage.projects.destroy', $client) }}" class="ml-3 inline" onsubmit="return confirm('Hapus project ini? Semua riwayat pemakaian project juga akan dihapus.')">@csrf @method('DELETE')<button class="text-xs font-medium text-red-600 hover:underline">Hapus</button></form><form id="edit-gateway-{{ $client->id }}" method="POST" action="{{ route('super-admin.ai-usage.projects.update', $client) }}" class="hidden mt-3 rounded-lg bg-gray-50 p-3 text-left">@csrf @method('PUT')<label class="block text-xs text-gray-600">Nama<input name="name" value="{{ $client->name }}" class="mt-1 w-full rounded border-gray-300 text-sm"></label><label class="mt-2 block text-xs text-gray-600">Kuota token<input name="monthly_token_limit" type="number" min="0" value="{{ $client->monthly_token_limit }}" class="mt-1 w-full rounded border-gray-300 text-sm"></label><button class="mt-2 rounded bg-primary px-3 py-1.5 text-xs text-white">Simpan</button></form></td></tr>@empty<tr><td colspan="6" class="py-6 text-center text-gray-500">Belum ada project eksternal.</td></tr>@endforelse</tbody></table></div>
    </div>

    <div id="create-gateway-project-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/50 p-4">
        <div class="flex min-h-full items-center justify-center">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between gap-4"><div><h3 class="text-lg font-semibold text-gray-900">Tambah project gateway</h3><p class="mt-1 text-sm text-gray-500">Satu key unik akan dibuat untuk server project ini.</p></div><button type="button" onclick="document.getElementById('create-gateway-project-modal').classList.add('hidden')" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700"><i class="ri-close-line text-xl"></i></button></div>
                <form method="POST" action="{{ route('super-admin.ai-usage.projects.store') }}" class="mt-6 space-y-4">@csrf
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Nama project</span>
                        <span class="mt-1 block text-xs text-gray-500">Gunakan nama yang mudah dikenali di dashboard pusat.</span>
                        <input name="name" required autofocus placeholder="Contoh: Bimbel Cabang A" class="mt-2 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10">
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Kuota token bulanan</span>
                        <div class="relative mt-2"><input name="monthly_token_limit" type="number" min="0" value="0" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 pr-20 text-sm text-gray-900 focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10"><span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-medium text-gray-400">token</span></div>
                        <span class="mt-1.5 block text-xs text-gray-500">Isi <b>0</b> jika project boleh memakai token tanpa batas.</span>
                    </label>
                    <div class="flex justify-end gap-2 pt-2"><button type="button" onclick="document.getElementById('create-gateway-project-modal').classList.add('hidden')" class="rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">Batal</button><button class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90">Buat project & key</button></div>
                </form>
            </div>
        </div>
    </div>

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
                        <div class="h-1 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-primary" style="width: {{ ($day->total_tokens / $maximum) * 100 }}%"></div></div>
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
