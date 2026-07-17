@extends('super-admin.layouts.app')

@section('title', 'Super Admin - Paket AI Gateway')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Paket AI Gateway</h2>
            <p class="text-gray-500">Kelola paket AI berbayar atau gratis yang dapat diklaim peserta project terhubung.</p>
        </div>
        <button type="button" onclick="document.getElementById('create-plan-modal').classList.remove('hidden')"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90">
            <i class="ri-add-line"></i>
            Tambah Paket
        </button>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('warning') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold">Paket belum dapat disimpan:</p>
            <ul class="mt-1 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-border bg-white p-6">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Daftar Paket</h3>
            <p class="mt-1 text-sm text-gray-500">Token wajib dibatasi. Harga 0 = gratis dan langsung aktif saat diklaim. Chat 0 = unlimited. Masa aktif 0 = tidak kedaluwarsa.</p>
        </div>

        <div class="space-y-4">
            @forelse($plans as $plan)
                <div class="rounded-xl border border-gray-200 p-5">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="text-lg font-semibold text-gray-900">{{ $plan->name }}</h4>
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $plan->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $plan->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                @if($plan->isFree())
                                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700">Gratis · klaim sekali</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ $plan->slug }} · {{ $plan->isFree() ? 'Gratis' : 'Rp '.number_format($plan->price, 0, ',', '.') }} /
                                {{ $plan->duration_days === 0 ? 'tanpa masa aktif' : $plan->duration_days.' hari' }}
                            </p>
                        </div>
                        @php($planHasHistory = $plan->subscriptions_count > 0 || $plan->transactions_count > 0)
                        <div class="flex items-center gap-2"><button type="button" onclick="document.getElementById('edit-plan-{{ $plan->id }}').classList.remove('hidden')" class="rounded-full border border-primary px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary hover:text-white">Edit</button><form method="POST" action="{{ route('super-admin.ai-gateway-plans.destroy', $plan) }}" onsubmit="return confirm('{{ $planHasHistory ? 'Paket pernah digunakan dan akan dinonaktifkan. Lanjutkan?' : 'Hapus paket ini?' }}')">@csrf @method('DELETE')<button class="rounded-full border border-red-400 px-3 py-1.5 text-xs font-semibold text-red-500 transition hover:bg-red-500 hover:text-white">{{ $planHasHistory ? 'Nonaktifkan' : 'Hapus' }}</button></form></div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-4 border-t border-gray-100 pt-4 text-sm md:grid-cols-3">
                        <div>
                            <span class="text-gray-500">Harga:</span>
                            <span class="ml-1 font-medium text-gray-800">{{ $plan->isFree() ? 'Gratis' : 'Rp '.number_format($plan->price, 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Token:</span>
                            <span class="ml-1 font-medium text-gray-800">{{ number_format($plan->token_limit, 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Limit chat:</span>
                            <span class="ml-1 font-medium text-gray-800">{{ $plan->chat_limit > 0 ? number_format($plan->chat_limit, 0, ',', '.') . ' chat AI' : 'Unlimited' }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-gray-500">Belum ada paket AI Gateway.</div>
            @endforelse
        </div>
    </div>
</div>

@foreach($plans as $plan)
<div id="edit-plan-{{ $plan->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/50 p-4"><div class="flex min-h-full items-center justify-center"><div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl"><div class="flex items-start justify-between gap-4"><div><h3 class="text-lg font-semibold text-gray-900">Edit Paket AI Gateway</h3><p class="mt-1 text-sm text-gray-500">Perubahan berlaku untuk pembelian atau klaim berikutnya; kuota langganan yang sudah aktif tidak berubah.</p></div><button type="button" onclick="document.getElementById('edit-plan-{{ $plan->id }}').classList.add('hidden')" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100"><i class="ri-close-line text-xl"></i></button></div><form method="POST" action="{{ route('super-admin.ai-gateway-plans.update', $plan) }}" class="mt-6 grid gap-4 md:grid-cols-2">@csrf @method('PUT')<label class="block md:col-span-2"><span class="text-sm font-semibold text-gray-700">Nama Paket</span><input name="name" required value="{{ $plan->name }}" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10"></label><label class="block"><span class="text-sm font-semibold text-gray-700">Harga (Rp)</span><input name="price" type="number" min="0" required value="{{ $plan->price }}" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10"><span class="mt-1 block text-xs text-gray-500">0 = gratis dan langsung aktif saat diklaim.</span></label><label class="block"><span class="text-sm font-semibold text-gray-700">Masa Aktif (hari)</span><input name="duration_days" type="number" min="0" value="{{ $plan->duration_days }}" required class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10"><span class="mt-1 block text-xs text-gray-500">Isi 0 untuk tanpa masa aktif.</span></label><label class="block"><span class="text-sm font-semibold text-gray-700">Limit Token</span><input name="token_limit" type="number" min="1" value="{{ $plan->token_limit }}" required class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10"><span class="mt-1 block text-xs text-gray-500">Jumlah wajib lebih dari 0</span></label><label class="block"><span class="text-sm font-semibold text-gray-700">Limit Chat</span><input name="chat_limit" type="number" min="0" value="{{ $plan->chat_limit }}" required class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10"><span class="mt-1 block text-xs text-gray-500">Isi 0 untuk unlimited; penggunaan berhenti saat token habis.</span></label><label class="flex items-center gap-2 text-sm font-medium text-gray-700 md:col-span-2"><input name="is_active" value="1" type="checkbox" @checked($plan->is_active) class="rounded border-gray-300 text-primary focus:ring-primary"> Paket tersedia untuk dibeli atau diklaim</label><div class="flex justify-end gap-2 pt-2 md:col-span-2"><button type="button" onclick="document.getElementById('edit-plan-{{ $plan->id }}').classList.add('hidden')" class="rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">Batal</button><button class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90">Simpan Perubahan</button></div></form></div></div></div>
@endforeach

<div id="create-plan-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/50 p-4">
    <div class="flex min-h-full items-center justify-center">
        <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Tambah Paket AI Gateway</h3>
                    <p class="mt-1 text-sm text-gray-500">Isi harga 0 untuk paket gratis yang langsung aktif ketika diklaim peserta.</p>
                </div>
                <button type="button" onclick="document.getElementById('create-plan-modal').classList.add('hidden')"
                    class="rounded-lg p-1 text-gray-400 hover:bg-gray-100">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('super-admin.ai-gateway-plans.store') }}" class="mt-6 grid gap-4 md:grid-cols-2">
                @csrf
                <label class="block md:col-span-2">
                    <span class="text-sm font-semibold text-gray-700">Nama Paket</span>
                    <input name="name" required placeholder="Contoh: Starter AI" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10">
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-gray-700">Harga (Rp)</span>
                    <input name="price" type="number" min="0" value="{{ old('price', 0) }}" required placeholder="0" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10">
                    <span class="mt-1 block text-xs text-gray-500">0 = gratis, tanpa membuka payment gateway.</span>
                </label>
                <label class="block"><span class="text-sm font-semibold text-gray-700">Masa Aktif (hari)</span><input name="duration_days" type="number" min="0" value="{{ old('duration_days', 30) }}" required class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10"><span class="mt-1 block text-xs text-gray-500">0 = tidak kedaluwarsa.</span></label>
                <label class="block"><span class="text-sm font-semibold text-gray-700">Limit Token</span><input name="token_limit" type="number" min="1" value="{{ old('token_limit', 10000) }}" required class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10"><span class="mt-1 block text-xs text-gray-500">Wajib lebih dari 0; paket berakhir saat token habis.</span></label>
                <label class="block"><span class="text-sm font-semibold text-gray-700">Limit Chat</span><input name="chat_limit" type="number" min="0" value="{{ old('chat_limit', 0) }}" required class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10"><span class="mt-1 block text-xs text-gray-500">0 = unlimited selama token masih tersedia.</span></label>
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 md:col-span-2"><input name="is_active" value="1" type="checkbox" checked class="rounded border-gray-300 text-primary focus:ring-primary"> Aktifkan paket setelah dibuat</label>
                <div class="flex justify-end gap-2 pt-2 md:col-span-2"><button type="button" onclick="document.getElementById('create-plan-modal').classList.add('hidden')" class="rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">Batal</button><button class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90">Simpan Paket</button></div>
            </form>
        </div>
    </div>
</div>
@if($errors->any())
    <script>
        document.getElementById('create-plan-modal')?.classList.remove('hidden');
    </script>
@endif
@endsection
