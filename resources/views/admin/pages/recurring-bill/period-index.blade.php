@extends('admin.layout.admin')

@section('title', $billing['title'])

@section('content')
    <div class="space-y-6">
        <x-layout.page-header :title="$billing['title']" :description="$billing['description']">
            <x-slot:actions>
                <x-ui.badge :variant="$billing['source_variant']" size="lg" pill>{{ $billing['source_label'] }}</x-ui.badge>
                <x-ui.button :href="$billing['back_route']" variant="outline" icon="ri-arrow-left-line">Kembali</x-ui.button>
            </x-slot:actions>
        </x-layout.page-header>

        <x-tab :tabs="$periodTabs" variant="pills" class="overflow-x-auto" />

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-5 py-5">
                <h2 class="font-bold text-gray-900">Periode tagihan</h2>
                <p class="mt-1 text-sm text-gray-500">Pilih periode untuk melihat peserta dan mencatat penerimaan.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[780px] w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr><th class="px-5 py-3">Periode</th><th class="px-5 py-3">Peserta</th><th class="px-5 py-3">Tagihan</th><th class="px-5 py-3">Diterima</th><th class="px-5 py-3">Sisa</th><th class="px-5 py-3">Status periode</th><th class="sticky right-0 border-l border-gray-100 bg-gray-50 px-5 py-3 text-right">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($periods as $period)
                            <tr class="transition hover:bg-gray-50">
                                <td class="px-5 py-4 font-semibold text-gray-900">{{ $period->label }}</td>
                                <td class="px-5 py-4">{{ number_format($period->participant_count) }} peserta</td>
                                <td class="px-5 py-4 font-semibold tabular-nums">Rp {{ number_format($period->amount, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 tabular-nums">Rp {{ number_format($period->paid_amount, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 font-semibold tabular-nums">Rp {{ number_format($period->remaining_amount, 0, ',', '.') }}</td>
                                <td class="px-5 py-4"><x-ui.badge :variant="$period->state_variant" size="sm" pill>{{ $period->state_label }}</x-ui.badge></td>
                                <td class="sticky right-0 border-l border-gray-100 bg-white px-5 py-4 text-right"><x-ui.button :href="$period->detail_route" variant="outline" size="sm" icon="ri-arrow-right-line">Detail</x-ui.button></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-12 text-center text-gray-500">Belum ada periode tagihan untuk filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($periods->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $periods->links() }}</div>@endif
        </section>
    </div>
@endsection
