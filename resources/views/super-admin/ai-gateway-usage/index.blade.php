@extends('super-admin.layouts.app')

@section('title', 'Super Admin - Gateway Monitoring')

@section('content')
@php($featureLabels = [
    'discussion' => 'Diskusi soal',
    'learning_note' => 'Catatan materi',
    'learning_recommendation' => 'Rekomendasi belajar',
    'learning_question' => 'Generate soal',
    'learning_flashcard' => 'Flashcard',
])
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gateway Monitoring</h1>
            <p class="mt-1 text-gray-500">Pantau project yang memakai AI Gateway pusat tanpa mencampurkannya dengan pemakaian lokal.</p>
        </div>
        <a href="{{ route('super-admin.ai-usage.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            <i class="ri-arrow-left-line"></i> AI Usage Lokal
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Project terdaftar</p><p class="mt-1 text-2xl font-bold text-gray-900">{{ $clients->count() }}</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Total request gateway</p><p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($summary->request_count ?? 0, 0, ',', '.') }}</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Token gateway</p><p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($summary->total_tokens ?? 0, 0, ',', '.') }}</p><p class="mt-1 text-xs text-gray-500">{{ number_format($summary->user_count ?? 0, 0, ',', '.') }} akun pengguna asal</p></div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <div>
            <h2 class="font-semibold text-gray-900">Fitur yang paling sering digunakan</h2>
            <p class="mt-1 text-sm text-gray-500">Persentase dihitung dari jumlah request gateway sesuai filter aktif, bukan dari jumlah token.</p>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach($featureUsage as $usage)
                @php($label = $featureLabels[$usage['feature']] ?? ucfirst(str_replace('_', ' ', $usage['feature'])))
                <div class="rounded-xl border border-gray-100 bg-slate-50 p-4">
                    <p class="text-sm font-medium text-gray-800">{{ $label }}</p>
                    <div class="mt-3 flex items-end justify-between gap-2"><p class="text-2xl font-bold text-gray-900">{{ number_format($usage['percentage'], 1, ',', '.') }}%</p><p class="text-xs text-gray-500">{{ number_format($usage['request_count'], 0, ',', '.') }} request</p></div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-200"><div class="h-full rounded-full bg-primary" style="width: {{ min(100, $usage['percentage']) }}%"></div></div>
                    <p class="mt-2 text-xs text-gray-500">{{ number_format($usage['user_count'], 0, ',', '.') }} peserta · {{ number_format($usage['total_tokens'], 0, ',', '.') }} token</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <div><h2 class="font-semibold text-gray-900">Pemakaian per peserta dan fitur</h2><p class="mt-1 text-sm text-gray-500">Satu baris menunjukkan jumlah penggunaan satu fitur oleh satu peserta pada project tertentu.</p></div>
        <div class="mt-4 overflow-x-auto rounded-xl border border-gray-100">
            <table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Peserta / project</th><th class="px-4 py-3">Fitur terpakai</th><th class="px-4 py-3 text-right">Request</th><th class="px-4 py-3 text-right">Proporsi</th><th class="px-4 py-3 text-right">Token</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($userFeatureUsage as $usage)<tr><td class="px-4 py-3"><p class="font-medium text-gray-900">{{ $usage->external_user_name ?: 'Pengguna tidak tersedia' }}</p><p class="mt-1 text-xs text-gray-500">{{ $usage->external_user_email ?: 'ID: '.($usage->external_user_id ?: '-') }}</p><p class="mt-1 text-xs text-gray-500">{{ $usage->client?->name ?? 'Project dihapus' }}</p></td><td class="px-4 py-3"><span class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">{{ $featureLabels[$usage->feature] ?? ucfirst(str_replace('_', ' ', $usage->feature)) }}</span></td><td class="px-4 py-3 text-right font-medium">{{ number_format($usage->request_count, 0, ',', '.') }}</td><td class="px-4 py-3 text-right text-gray-600">{{ number_format($usage->percentage, 1, ',', '.') }}%</td><td class="px-4 py-3 text-right font-medium">{{ number_format($usage->total_tokens, 0, ',', '.') }}</td></tr>@empty<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada pemakaian fitur untuk filter ini.</td></tr>@endforelse</tbody></table>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div><h2 class="font-semibold text-gray-900">Project gateway</h2><p class="mt-1 text-sm text-gray-500">Project key disimpan aman dan hanya ditampilkan sekali saat dibuat.</p></div>
            <button type="button" onclick="document.getElementById('create-gateway-project-modal').classList.remove('hidden')" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90"><i class="ri-add-line"></i> Tambah project</button>
        </div>
        <div class="mt-4 overflow-x-auto rounded-xl border border-gray-100">
            <table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Project</th><th class="px-4 py-3">Base URL</th><th class="px-4 py-3 text-right">Request</th><th class="px-4 py-3 text-right">Token</th><th class="px-4 py-3">Terakhir dipakai</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($clients as $client)<tr><td class="px-4 py-3 font-medium text-gray-900">{{ $client->name }}<p class="mt-1 text-xs font-normal text-gray-500">{{ $client->slug }}</p>@if((int) session('gateway_key_client_id') === $client->id)<code class="mt-2 inline-block select-all rounded bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-800">{{ session('gateway_key') }}</code>@endif</td><td class="px-4 py-3 text-gray-600">{{ $client->base_url ?: 'Belum diisi' }}</td><td class="px-4 py-3 text-right font-medium">{{ number_format($client->usage_logs_count, 0, ',', '.') }}</td><td class="px-4 py-3 text-right font-medium">{{ number_format($client->total_tokens, 0, ',', '.') }}</td><td class="px-4 py-3 text-gray-500">{{ $client->last_used_at?->format('d M Y H:i') ?? 'Belum pernah' }}</td><td class="px-4 py-3 text-right"><button type="button" onclick="document.getElementById('edit-gateway-{{ $client->id }}').classList.toggle('hidden')" class="text-xs font-medium text-primary hover:underline">Edit</button><form method="POST" action="{{ route('super-admin.ai-usage.projects.destroy', $client) }}" class="ml-3 inline" onsubmit="return confirm('Hapus project ini? Semua riwayat pemakaian project juga akan dihapus.')">@csrf @method('DELETE')<button class="text-xs font-medium text-red-600 hover:underline">Hapus</button></form><form id="edit-gateway-{{ $client->id }}" method="POST" action="{{ route('super-admin.ai-usage.projects.update', $client) }}" class="mt-3 hidden w-72 rounded-xl bg-gray-50 p-3 text-left">@csrf @method('PUT')<label class="block text-xs text-gray-600">Nama<input name="name" value="{{ $client->name }}" class="mt-1 w-full rounded border-gray-300 text-sm"></label><label class="mt-2 block text-xs text-gray-600">Base URL<input name="base_url" type="url" value="{{ $client->base_url }}" class="mt-1 w-full rounded border-gray-300 text-sm"></label><label class="mt-2 block text-xs text-gray-600">Kuota token<input name="monthly_token_limit" type="number" min="0" value="{{ $client->monthly_token_limit }}" class="mt-1 w-full rounded border-gray-300 text-sm"></label><button class="mt-3 rounded bg-primary px-3 py-1.5 text-xs text-white">Simpan</button></form></td></tr>@empty<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada project gateway.</td></tr>@endforelse</tbody></table>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <div><h2 class="font-semibold text-gray-900">Kuota coba gratis</h2><p class="mt-1 text-sm text-gray-500">Diatur per peserta pada masing-masing project. Isi 0 pada keduanya untuk menonaktifkan fitur coba gratis.</p></div>
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            @foreach($clients as $client)
                <form method="POST" action="{{ route('super-admin.ai-usage.projects.update', $client) }}" class="rounded-xl border border-gray-100 bg-gray-50 p-4">@csrf @method('PUT')
                    <input type="hidden" name="name" value="{{ $client->name }}"><input type="hidden" name="base_url" value="{{ $client->base_url }}"><input type="hidden" name="monthly_token_limit" value="{{ $client->monthly_token_limit }}">
                    <p class="font-medium text-gray-900">{{ $client->name }}</p>
                    <div class="mt-3 grid grid-cols-2 gap-3"><label class="block text-xs font-medium text-gray-600">Token gratis<input name="free_token_limit" type="number" min="0" value="{{ $client->free_token_limit }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label><label class="block text-xs font-medium text-gray-600">Chat gratis<input name="free_chat_limit" type="number" min="0" value="{{ $client->free_chat_limit }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label></div>
                    <button class="mt-3 rounded-lg border border-primary px-3 py-1.5 text-xs font-medium text-primary hover:bg-primary hover:text-white">Simpan kuota gratis</button>
                </form>
            @endforeach
        </div>
    </div>

    <div id="create-gateway-project-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/50 p-4"><div class="flex min-h-full items-center justify-center"><div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"><div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-semibold text-gray-900">Tambah project gateway</h2><p class="mt-1 text-sm text-gray-500">Project key dibuat satu kali untuk aplikasi ini.</p></div><button type="button" onclick="document.getElementById('create-gateway-project-modal').classList.add('hidden')" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100"><i class="ri-close-line text-xl"></i></button></div><form method="POST" action="{{ route('super-admin.ai-usage.projects.store') }}" class="mt-6 space-y-4">@csrf<label class="block"><span class="text-sm font-semibold text-gray-700">Nama project</span><input name="name" required class="mt-2 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10" placeholder="Contoh: Bimbel Cabang A"></label><label class="block"><span class="text-sm font-semibold text-gray-700">Base URL project</span><input name="base_url" type="url" class="mt-2 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10" placeholder="https://bimbel-cabang-a.com"></label><label class="block"><span class="text-sm font-semibold text-gray-700">Kuota token bulanan</span><input name="monthly_token_limit" type="number" min="0" value="0" class="mt-2 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10"><span class="mt-1 block text-xs text-gray-500">Isi 0 untuk tanpa batas.</span></label><div class="flex justify-end gap-2 pt-2"><button type="button" onclick="document.getElementById('create-gateway-project-modal').classList.add('hidden')" class="rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">Batal</button><button class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90">Buat project & key</button></div></form></div></div></div>

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <div>
            <h2 class="font-semibold text-gray-900">Paket dan kuota peserta</h2>
            <p class="mt-1 text-sm text-gray-500">Paket nonaktif tetap ditampilkan sebagai riwayat. Hanya paket aktif yang dihitung sebagai kuota peserta.</p>
        </div>
        <div class="mt-4 overflow-x-auto rounded-xl border border-gray-100">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr><th class="px-4 py-3">Peserta / project</th><th class="px-4 py-3">Paket</th><th class="px-4 py-3">Status & berakhir</th><th class="px-4 py-3 text-right">Sisa chat</th><th class="px-4 py-3 text-right">Sisa token</th><th class="px-4 py-3 text-right">Aksi</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($subscriptions as $subscription)
                        @php($subscriptionChatLimit = (int) ($subscription->chat_limit ?: $subscription->plan?->chat_limit ?: 0))
                        @php($subscriptionTokenLimit = (int) ($subscription->token_limit ?: $subscription->plan?->token_limit ?: 0))
                        @php($subscriptionIsActive = $subscription->status === 'active' && ($subscription->ends_at === null || $subscription->ends_at->isFuture()))
                        @php($subscriptionStatusLabel = match(true) { $subscriptionIsActive => 'Aktif', $subscription->status === 'revoked' => 'Dicabut', $subscription->status === 'active' => 'Kedaluwarsa', default => ucfirst($subscription->status) })
                        <tr>
                            <td class="px-4 py-3"><p class="font-medium text-gray-900">{{ $subscription->external_user_name ?: 'Peserta tidak tersedia' }}</p><p class="mt-1 text-xs text-gray-500">{{ $subscription->external_user_email ?: 'ID: ' . $subscription->external_user_id }}</p><p class="mt-1 text-xs text-gray-500">{{ $subscription->client?->name ?? 'Project dihapus' }}</p></td>
                            <td class="px-4 py-3 font-medium text-gray-700">{{ $subscription->plan?->name ?? '-' }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $subscriptionIsActive ? 'bg-green-100 text-green-700' : ($subscription->status === 'revoked' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">{{ $subscriptionStatusLabel }}</span>@if($subscription->status === 'revoked')<p class="mt-2 text-xs text-gray-500">Dicabut {{ $subscription->revoked_at?->format('d M Y H:i') }}</p><p class="mt-1 max-w-xs text-xs text-gray-500" title="{{ $subscription->revoked_reason }}">{{ $subscription->revoked_reason }}</p>@else<p class="mt-2 text-xs text-gray-500">{{ $subscription->ends_at?->format('d M Y H:i') ?? 'Tanpa masa aktif' }}</p>@endif</td>
                            <td class="px-4 py-3 text-right font-medium">{{ $subscriptionIsActive ? ($subscriptionChatLimit > 0 ? number_format(max(0, $subscriptionChatLimit - $subscription->chats_used), 0, ',', '.') : '∞') : '0' }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ $subscriptionIsActive && $subscriptionTokenLimit > 0 ? number_format(max(0, $subscriptionTokenLimit - $subscription->tokens_used), 0, ',', '.') : '0' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($subscriptionIsActive)
                                    <button type="button" onclick="document.getElementById('add-token-{{ $subscription->id }}').classList.toggle('hidden')" class="text-xs font-semibold text-violet-600 hover:underline">Tambah token</button>
                                    <button type="button" onclick="document.getElementById('revoke-subscription-{{ $subscription->id }}').classList.toggle('hidden')" class="ml-3 text-xs font-semibold text-red-600 hover:underline">Nonaktifkan paket</button>
                                    <form id="add-token-{{ $subscription->id }}" method="POST" action="{{ route('super-admin.ai-gateway-subscriptions.tokens.store', $subscription) }}" class="ml-auto mt-3 hidden w-72 space-y-3 rounded-xl border border-violet-100 bg-violet-50 p-3 text-left">
                                        @csrf
                                        <label class="block text-xs font-medium text-gray-700">Jumlah token<input type="number" name="tokens" min="1" max="100000000" value="10000" required class="mt-1 w-full rounded-lg border-gray-300 bg-white text-sm"></label>
                                        <label class="block text-xs font-medium text-gray-700">Alasan<textarea name="reason" rows="2" maxlength="255" required class="mt-1 w-full rounded-lg border-gray-300 bg-white text-sm" placeholder="Bonus atau kompensasi"></textarea></label>
                                        <button class="w-full rounded-lg bg-violet-600 px-3 py-2 text-xs font-semibold text-white hover:bg-violet-700">Tambahkan token</button>
                                    </form>
                                    <form id="revoke-subscription-{{ $subscription->id }}" method="POST" action="{{ route('super-admin.ai-gateway-subscriptions.revoke', $subscription) }}" class="ml-auto mt-3 hidden w-72 space-y-3 rounded-xl border border-red-100 bg-red-50 p-3 text-left" onsubmit="return confirm('Paket akan langsung nonaktif dan sisa kuotanya tidak dapat digunakan. Transaksi tetap tersimpan dan peserta dapat klaim atau membeli lagi. Lanjutkan?')">
                                        @csrf
                                        <p class="text-xs leading-5 text-red-700">Akses dan sisa kuota dicabut. Riwayat pembayaran serta pemakaian tidak dihapus.</p>
                                        <label class="block text-xs font-medium text-gray-700">Alasan<textarea name="reason" rows="2" maxlength="255" required class="mt-1 w-full rounded-lg border-gray-300 bg-white text-sm" placeholder="Contoh: Reset paket untuk pengujian"></textarea></label>
                                        <button class="w-full rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">Nonaktifkan paket</button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">Tersimpan sebagai riwayat</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada pembelian paket oleh peserta.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $subscriptions->links() }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <div><h2 class="font-semibold text-gray-900">Riwayat penambahan token</h2><p class="mt-1 text-sm text-gray-500">Audit jumlah token, alasan, dan super admin yang melakukan perubahan.</p></div>
        <div class="mt-4 overflow-x-auto rounded-xl border border-gray-100">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Waktu</th><th class="px-4 py-3">Peserta / project</th><th class="px-4 py-3 text-right">Token</th><th class="px-4 py-3">Alasan</th><th class="px-4 py-3">Super admin</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tokenAdjustments as $adjustment)
                        <tr><td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ $adjustment->created_at->format('d M Y H:i') }}</td><td class="px-4 py-3"><p class="font-medium text-gray-900">{{ $adjustment->subscription?->external_user_name ?: 'ID: '.$adjustment->external_user_id }}</p><p class="mt-1 text-xs text-gray-500">{{ $adjustment->client?->name ?? 'Project dihapus' }}</p></td><td class="px-4 py-3 text-right"><p class="font-semibold text-violet-700">+{{ number_format($adjustment->tokens_added, 0, ',', '.') }}</p><p class="mt-1 text-xs text-gray-500">{{ number_format($adjustment->previous_token_limit, 0, ',', '.') }} → {{ number_format($adjustment->new_token_limit, 0, ',', '.') }}</p></td><td class="px-4 py-3 text-gray-700">{{ $adjustment->reason }}</td><td class="px-4 py-3"><p class="font-medium text-gray-800">{{ $adjustment->actor_name ?: '-' }}</p><p class="mt-1 text-xs text-gray-500">{{ $adjustment->actor_email ?: '-' }}</p></td></tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada penambahan token.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $tokenAdjustments->links() }}</div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"><div><h2 class="font-semibold text-gray-900">Log request gateway</h2><p class="mt-1 text-sm text-gray-500">Data dikirim saat request terjadi dan ditampilkan terpaginasikan agar tetap ringan.</p></div><form method="GET" class="flex flex-wrap gap-2"><select name="client_id" class="rounded-lg border-gray-300 text-sm"><option value="">Semua project</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected((string) request('client_id') === (string) $client->id)>{{ $client->name }}</option>@endforeach</select><select name="feature" class="rounded-lg border-gray-300 text-sm"><option value="">Semua fitur</option>@foreach($features as $feature)<option value="{{ $feature }}" @selected(request('feature') === $feature)>{{ $featureLabels[$feature] ?? ucfirst(str_replace('_', ' ', $feature)) }}</option>@endforeach</select><input type="search" name="q" value="{{ request('q') }}" class="rounded-lg border-gray-300 text-sm" placeholder="Nama, email, ID, soal"><button class="rounded-lg bg-primary px-4 py-2 text-sm text-white hover:bg-primary/90">Filter</button></form></div>
        <div class="mt-4 overflow-x-auto rounded-xl border border-gray-100"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Waktu</th><th class="px-4 py-3">Project / sumber</th><th class="px-4 py-3">Akun asal</th><th class="px-4 py-3">Fitur</th><th class="px-4 py-3">Referensi</th><th class="px-4 py-3">Model</th><th class="px-4 py-3 text-right">Token</th><th class="px-4 py-3 text-right">Waktu respons</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($logs as $log)<tr class="align-top"><td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ $log->created_at->format('d M Y H:i') }}</td><td class="px-4 py-3"><p class="font-medium text-gray-900">{{ $log->client?->name ?? 'Project dihapus' }}</p><p class="mt-1 max-w-xs truncate text-xs text-gray-500" title="{{ $log->origin_base_url ?: $log->client?->base_url }}">{{ $log->origin_base_url ?: ($log->client?->base_url ?: '-') }}</p></td><td class="px-4 py-3"><p class="font-medium text-gray-800">{{ $log->external_user_name ?: 'Pengguna tidak tersedia' }}</p><p class="mt-1 text-xs text-gray-500">{{ $log->external_user_email ?: 'ID: ' . ($log->external_user_id ?: '-') }}</p></td><td class="whitespace-nowrap px-4 py-3"><span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-medium text-violet-700">{{ $featureLabels[$log->feature ?? 'discussion'] ?? ucfirst(str_replace('_', ' ', $log->feature ?? 'discussion')) }}</span></td><td class="px-4 py-3 text-gray-600">{{ $log->question_reference ?: '-' }}</td><td class="px-4 py-3"><span class="rounded bg-gray-100 px-2 py-1 text-xs">{{ strtoupper($log->provider) }}</span><p class="mt-1 text-xs text-gray-500">{{ $log->model }}</p></td><td class="whitespace-nowrap px-4 py-3 text-right font-medium">{{ number_format($log->input_tokens, 0, ',', '.') }} / {{ number_format($log->output_tokens, 0, ',', '.') }} / {{ number_format($log->total_tokens, 0, ',', '.') }}</td><td class="whitespace-nowrap px-4 py-3 text-right text-gray-600">{{ number_format(($log->response_time_ms ?? 0) / 1000, 2, ',', '.') }} dtk</td></tr>@empty<tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Belum ada request gateway yang sesuai.</td></tr>@endforelse</tbody></table></div>
        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
