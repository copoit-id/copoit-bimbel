@extends('parent.layout')

@section('title', 'Akses & Pembayaran')

@section('content')
    <x-layout.page-header title="Akses & Pembayaran" description="Status program belajar dan riwayat transaksi {{ $child?->name ?? 'anak' }}.">
        <x-slot:actions>@if($child)<x-ui.button :href="route('parent.catalog')" icon="ri-store-2-line">Buka katalog</x-ui.button>@endif</x-slot:actions>
    </x-layout.page-header>

    @if($child)
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-dashboard.stat-card label="Total Akses" :value="$packageSummary['total_access']" icon="ri-archive-stack-line" />
            <x-dashboard.stat-card label="Paket Aktif" :value="$packageSummary['active_access']" icon="ri-checkbox-circle-line" color="green" />
            <x-dashboard.stat-card label="Total Transaksi" :value="$packageSummary['transactions']" icon="ri-receipt-line" color="blue" />
            <x-dashboard.stat-card label="Total Pembayaran" :value="'Rp '.number_format($packageSummary['paid_total'], 0, ',', '.')" icon="ri-wallet-3-line" color="orange" />
        </section>

        <x-ui.card variant="flat" class="mt-6 rounded-xl border border-gray-200" padding="none">
            <div class="border-b border-gray-100 px-5 py-4"><h2 class="text-lg font-semibold text-gray-900">Program Belajar</h2><p class="mt-1 text-sm text-gray-500">Seluruh akses paket aktif maupun yang telah berakhir.</p></div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">@forelse($accesses as $access)<article class="rounded-xl border border-gray-200 p-5"><div class="flex items-start justify-between gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-lg text-primary"><i class="ri-book-open-line"></i></span><x-ui.badge :variant="$access->is_active ? 'success' : 'secondary'" pill dot>{{ $access->is_active ? 'Aktif' : 'Tidak aktif' }}</x-ui.badge></div><h3 class="mt-4 font-bold text-gray-900">{{ $access->package?->name ?? 'Paket belajar' }}</h3><div class="mt-4 grid grid-cols-2 gap-2 rounded-lg bg-gray-50 p-3 text-xs text-gray-600"><span><i class="ri-book-2-line mr-1 text-primary"></i>{{ $access->package?->materials_count ?? 0 }} materi</span><span><i class="ri-file-list-3-line mr-1 text-primary"></i>{{ $access->package?->tryouts_count ?? 0 }} tryout</span><span><i class="ri-group-line mr-1 text-primary"></i>{{ $access->package?->classes_count ?? 0 }} kelas</span><span><i class="ri-timer-line mr-1 text-primary"></i>{{ $access->package?->tes_korans_count ?? 0 }} tes</span></div><dl class="mt-3 divide-y divide-gray-100 text-sm"><div class="flex justify-between gap-3 py-2"><dt class="text-gray-500">Masa aktif</dt><dd class="font-medium text-gray-800">{{ $access->end_date?->translatedFormat('d M Y') ?? 'Tanpa batas' }}</dd></div><div class="flex justify-between gap-3 py-2"><dt class="text-gray-500">Sisa hari</dt><dd class="font-medium text-gray-800">{{ $access->days_remaining ?? '—' }}</dd></div><div class="flex justify-between gap-3 py-2"><dt class="text-gray-500">Sesi booking</dt><dd class="font-medium text-gray-800">{{ $access->completed_booking_count }}</dd></div></dl></article>@empty<div class="py-12 text-center text-sm text-gray-500 md:col-span-2 xl:col-span-3">Belum ada paket untuk anak ini.</div>@endforelse</div>
            @if($accesses->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $accesses->links() }}</div>@endif
        </x-ui.card>

        <x-ui.card variant="flat" class="mt-6 rounded-xl border border-gray-200" padding="none">
            <div class="border-b border-gray-100 px-5 py-4"><h2 class="text-lg font-semibold text-gray-900">Riwayat Pembayaran</h2><p class="mt-1 text-sm text-gray-500">Transaksi yang tercatat atas nama {{ $child->name }}.</p></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[680px] text-left text-sm text-gray-600"><thead class="bg-gray-50 text-xs uppercase text-gray-600"><tr><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Paket</th><th class="px-5 py-3">Nominal</th><th class="px-5 py-3 text-center">Status</th></tr></thead><tbody>@forelse($payments as $payment)<tr class="border-t border-gray-100 hover:bg-gray-50/70"><td class="px-5 py-4">{{ $payment->created_at?->translatedFormat('d M Y, H:i') ?? '—' }}</td><td class="px-5 py-4 font-semibold text-gray-900">{{ $payment->package?->name ?? '—' }}</td><td class="px-5 py-4 font-medium text-gray-800">{{ $payment->formatted_amount }}</td><td class="px-5 py-4 text-center"><x-ui.badge :variant="$payment->status_variant" pill dot>{{ $payment->status_label }}</x-ui.badge></td></tr>@empty<tr><td colspan="4" class="px-5 py-14 text-center text-gray-500">Belum ada transaksi.</td></tr>@endforelse</tbody></table></div>
            @if($payments->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $payments->links() }}</div>@endif
        </x-ui.card>
    @else
        <x-ui.card variant="flat" class="rounded-xl border border-dashed border-gray-300 px-5 py-14 text-center">Belum ada anak yang dapat dipantau.</x-ui.card>
    @endif
@endsection
