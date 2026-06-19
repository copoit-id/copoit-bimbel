@extends('admin.layout.admin')

@section('title', 'Tagihan Rutin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Tagihan Rutin</h1>
            <p class="text-sm text-gray-500">Buat tagihan SPP atau iuran yang berulang untuk peserta tertentu.</p>
        </div>
        <a href="{{ route('admin.recurring-bills.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">
            <i class="ri-add-line"></i>
            Buat Tagihan
        </a>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                    <tr>
                        <th class="px-4 py-3">Tagihan</th>
                        <th class="px-4 py-3">Nominal</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3">Target</th>
                        <th class="px-4 py-3">Invoice</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bills as $bill)
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900">{{ $bill->name }}</p>
                                <p class="text-xs text-gray-500">{{ $bill->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                            </td>
                            <td class="px-4 py-3">Rp {{ number_format((float) $bill->amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ ucfirst($bill->frequency) }}</td>
                            <td class="px-4 py-3">{{ $bill->targets_count }}</td>
                            <td class="px-4 py-3">{{ $bill->invoices_count }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.recurring-bills.show', $bill) }}" class="text-primary hover:underline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada tagihan rutin.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <h2 class="font-semibold text-gray-900">Invoice Terbaru</h2>
            <div class="mt-4 space-y-3">
                @forelse($invoices as $invoice)
                    <div class="rounded-lg border border-gray-100 p-3">
                        <p class="font-medium text-gray-900">{{ $invoice->title }}</p>
                        <p class="text-xs text-gray-500">{{ $invoice->user->name ?? '-' }} • jatuh tempo {{ $invoice->due_date->format('d M Y') }}</p>
                        <p class="mt-1 text-sm font-semibold">Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada invoice.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{ $bills->links() }}
</div>
@endsection
