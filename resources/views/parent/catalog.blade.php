@extends('parent.layout')

@section('title', 'Katalog Belajar')

@section('content')
<div class="space-y-5">
    <x-layout.page-header title="Katalog belajar" description="Pilih paket belajar untuk {{ $child?->name ?? 'anak' }}. Akses aktif akan langsung tercatat pada akun anak." :border="false">
        <x-slot:actions>
            @if($child)
                <x-ui.button :href="route('parent.packages', ['anak' => $child->id])" variant="outline" size="sm" icon="ri-bank-card-line">Akses & pembayaran</x-ui.button>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    @if($child)
        <section class="rounded-xl border border-indigo-100 bg-gradient-to-r from-indigo-50 via-white to-violet-50 p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-lg text-white"><i class="ri-shopping-bag-3-line"></i></span><div><h2 class="font-bold text-slate-900">Checkout untuk {{ $child->name }}</h2><p class="mt-1 text-sm text-slate-600">Paket yang dibeli akan aktif di akun anak, bukan akun orang tua.</p></div></div><span class="w-fit rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">{{ $catalogPackages->total() }} paket tersedia</span></div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($catalogPackages as $package)
                @php($isOwned = in_array($package->package_id, $ownedPackageIds, true))
                <article class="flex flex-col rounded-xl border border-slate-200 bg-white p-5 transition duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-sm">
                    <div class="flex items-start justify-between gap-3"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-xl text-indigo-600"><i class="ri-book-open-line"></i></span><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $package->type_price === 'free_unconditional' ? 'bg-emerald-50 text-emerald-700' : 'bg-violet-50 text-violet-700' }}">{{ $package->type_price === 'free_unconditional' ? 'Gratis' : 'Berbayar' }}</span></div>
                    <h2 class="mt-4 text-base font-bold text-slate-900">{{ $package->name }}</h2>
                    <p class="mt-2 line-clamp-2 min-h-10 text-sm leading-5 text-slate-500">{{ $package->description ?: 'Program belajar terstruktur untuk mendukung target belajar anak.' }}</p>
                    <div class="mt-4 grid grid-cols-2 gap-2 border-y border-slate-100 py-3 text-xs text-slate-600"><span class="flex items-center gap-1.5"><i class="ri-book-2-line text-indigo-500"></i>{{ $package->materials_count }} materi</span><span class="flex items-center gap-1.5"><i class="ri-file-list-3-line text-indigo-500"></i>{{ $package->tryouts_count }} tryout</span><span class="flex items-center gap-1.5"><i class="ri-group-line text-indigo-500"></i>{{ $package->classes_count }} kelas</span><span class="flex items-center gap-1.5"><i class="ri-timer-line text-indigo-500"></i>{{ $package->tes_korans_count }} tes</span></div>
                    <div class="mt-4 flex items-end justify-between gap-3"><div><p class="text-xs text-slate-500">Investasi belajar</p><p class="mt-1 text-lg font-bold text-slate-900">{{ $package->type_price === 'free_unconditional' ? 'Gratis' : $package->formatted_price }}</p></div>@if($isOwned)<span class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700"><i class="ri-checkbox-circle-line"></i>Sudah aktif</span>@elseif($package->type_price === 'free_conditional')<span class="text-right text-xs font-medium text-amber-700">Memerlukan syarat klaim</span>@else<form method="POST" action="{{ route('parent.catalog.checkout', ['anak' => $child->id, 'package' => $package->package_id]) }}" data-parent-checkout>@csrf<x-ui.button type="submit" size="sm" icon="ri-shopping-bag-line">{{ $package->type_price === 'free_unconditional' ? 'Aktifkan' : 'Checkout' }}</x-ui.button></form>@endif</div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-16 text-center md:col-span-2 xl:col-span-3"><i class="ri-store-2-line text-4xl text-slate-300"></i><p class="mt-3 font-semibold text-slate-700">Belum ada paket tersedia</p><p class="mt-1 text-sm text-slate-500">Paket yang dipublikasikan Admin akan tampil di sini.</p></div>
            @endforelse
        </section>
        @if($catalogPackages->hasPages()){{ $catalogPackages->links() }}@endif
    @else
        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-14 text-center text-sm text-slate-500">Pilih anak terlebih dahulu untuk melihat katalog belajar.</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-parent-checkout]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.classList.add('opacity-60');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: new FormData(form),
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.message || 'Checkout belum dapat diproses.');
            if (payload.redirect_url) { window.location.assign(payload.redirect_url); return; }
            window.location.reload();
        } catch (error) {
            window.alert(error.message || 'Checkout belum dapat diproses.');
            button.disabled = false;
            button.classList.remove('opacity-60');
        }
    });
});
</script>
@endpush
