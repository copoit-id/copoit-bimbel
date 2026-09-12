@extends('admin.layout.admin')

@section('title', 'Tagihan')

@section('content')
    <div class="space-y-6">
        <x-layout.page-header title="Tagihan" description="Satu daftar untuk seluruh tagihan, baik dibuat manual maupun terbentuk dari jadwal paket.">
            <x-slot:actions>
                <details class="relative">
                    <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/90"><i class="ri-add-line"></i>Tambah tagihan<i class="ri-arrow-down-s-line"></i></summary>
                    <div class="absolute right-0 z-20 mt-2 w-72 overflow-hidden rounded-xl border border-gray-200 bg-white p-1 shadow-lg">
                        <a href="{{ route('admin.recurring-bills.create') }}" class="block rounded-lg px-3 py-3 transition hover:bg-gray-50"><span class="block text-sm font-semibold text-gray-900">Buat manual</span><span class="mt-0.5 block text-xs text-gray-500">Atur peserta, nominal, dan periode sendiri.</span></a>
                        <a href="{{ route('admin.package.index') }}" class="block rounded-lg px-3 py-3 transition hover:bg-gray-50"><span class="block text-sm font-semibold text-gray-900">Dari jadwal paket</span><span class="mt-0.5 block text-xs text-gray-500">Atur paket dan siklus tagihan; invoice mengikuti jadwal secara otomatis.</span></a>
                    </div>
                </details>
            </x-slot:actions>
        </x-layout.page-header>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-5 lg:flex-row lg:items-center lg:justify-between">
                <div><h2 class="font-bold text-gray-900">Daftar tagihan</h2><p class="mt-1 text-sm text-gray-500">Satu baris mewakili satu tagihan/periode. Buka Detail untuk melihat peserta dan riwayat penerimaan.</p></div>
                <form method="GET" action="{{ route('admin.recurring-bills.index') }}" class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">
                    <x-ui.input name="search" type="search" :value="$search" placeholder="Cari paket, rombel, atau tagihan" class="sm:w-64" />
                    <select name="source" class="rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary"><option value="">Semua sumber</option><option value="schedule" @selected($source === 'schedule')>Dari jadwal</option><option value="manual" @selected($source === 'manual')>Manual</option></select>
                    <x-ui.button type="submit" variant="secondary">Terapkan</x-ui.button>
                </form>
            </div>

            <div class="overflow-x-auto"><table class="min-w-[620px] w-full text-left text-sm"><thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Nama tagihan</th><th class="px-5 py-3">Sumber</th><th class="px-5 py-3">Invoice terbit</th><th class="sticky right-0 border-l border-gray-100 bg-gray-50 px-5 py-3 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($invoiceGroups as $invoice)
                    <tr class="hover:bg-gray-50/70"><td class="px-5 py-4"><p class="font-semibold text-gray-900">{{ $invoice->group_label }}</p></td><td class="px-5 py-4"><x-ui.badge :variant="$invoice->source_variant" size="sm" pill>{{ $invoice->source_label }}</x-ui.badge></td><td class="whitespace-nowrap px-5 py-4 text-gray-700">{{ number_format($invoice->invoice_count) }} invoice</td><td class="sticky right-0 border-l border-gray-100 bg-white px-5 py-4 text-right">@if ($invoice->detail_route)<x-ui.button :href="$invoice->detail_route" variant="outline" size="sm">Detail</x-ui.button>@else<span class="text-xs text-gray-400">Detail tidak tersedia</span>@endif</td></tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-14 text-center text-sm text-gray-500">Belum ada tagihan sesuai filter.</td></tr>
                @endforelse
            </tbody></table></div>
            @if ($invoiceGroups->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $invoiceGroups->links() }}</div>@endif
        </section>
    </div>
@endsection
