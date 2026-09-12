@extends('admin.layout.admin')

@section('title', $billing['title'])

@section('content')
    <div class="space-y-6">
        <x-layout.page-header :title="$billing['title']" :description="$billing['description']">
            <x-slot:actions><x-ui.button :href="$billing['back_route']" variant="outline" icon="ri-arrow-left-line">Kembali</x-ui.button></x-slot:actions>
        </x-layout.page-header>

        <x-ui.card variant="flat" class="rounded-2xl border border-gray-200 p-5">
            <div class="grid gap-4 sm:grid-cols-4">
                <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Sumber</p><p class="mt-1"><x-ui.badge :variant="$billing['source_variant']" size="sm" pill>{{ $billing['source_label'] }}</x-ui.badge></p></div>
                <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Periode</p><p class="mt-1 font-semibold text-gray-900">{{ $period->label }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Siklus</p><p class="mt-1 font-semibold text-gray-900">{{ $billing['cycle_label'] }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Status periode</p><p class="mt-1"><x-ui.badge :variant="$period->state_variant" size="sm" pill>{{ $period->state_label }}</x-ui.badge></p></div>
            </div>
        </x-ui.card>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-5 py-5"><h2 class="font-bold text-gray-900">Peserta</h2><p class="mt-1 text-sm text-gray-500">Catat pembayaran atau cicilan per peserta. Status akan diperbarui otomatis.</p></div>
            <x-admin.billing-participant-table :invoices="$invoices" />
            @if ($invoices->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $invoices->links() }}</div>@endif
        </section>

        @foreach ($invoices as $invoice)
            <x-admin.billing-record-payment-modal :invoice="$invoice" />
        @endforeach
    </div>
@endsection
