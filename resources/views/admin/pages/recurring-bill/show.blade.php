@extends('admin.layout.admin')

@section('title', 'Detail Tagihan Rutin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('admin.recurring-bills.show', $recurringBill) }}" class="mb-2 inline-flex text-sm font-medium text-gray-500 hover:text-primary"><i class="ri-arrow-left-line mr-1"></i>Semua periode</a>
            <h1 class="text-2xl font-bold text-gray-900">{{ $recurringBill->name }}</h1>
            <p class="text-sm text-gray-500">Periode {{ $period->period_start?->translatedFormat('d M Y') }} - {{ $period->period_end?->translatedFormat('d M Y') }} · jatuh tempo {{ $period->due_date?->translatedFormat('d M Y') }}</p>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                <tr>
                    <th class="px-4 py-3">Invoice</th>
                    <th class="px-4 py-3">Peserta</th>
                    <th class="px-4 py-3">Nominal</th>
                    <th class="px-4 py-3">Pembayaran</th>
                    <th class="px-4 py-3">Jatuh Tempo</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    @php
                        $status = match ($invoice->status) {
                            'paid' => ['label' => 'Lunas', 'class' => 'border-green-200 bg-green-50 text-green-700'],
                            'partial' => ['label' => 'Cicilan', 'class' => 'border-blue-200 bg-blue-50 text-blue-700'],
                            'overdue' => ['label' => 'Jatuh tempo', 'class' => 'border-red-200 bg-red-50 text-red-700'],
                            'cancelled' => ['label' => 'Dibatalkan', 'class' => 'border-gray-200 bg-gray-50 text-gray-600'],
                            default => ['label' => 'Belum dibayar', 'class' => 'border-amber-200 bg-amber-50 text-amber-700'],
                        };
                    @endphp
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ $invoice->invoice_number }}</p>
                            <p class="text-xs text-gray-500">{{ $invoice->period_start?->format('d M') }} - {{ $invoice->period_end?->format('d M Y') }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $invoice->user->name ?? '-' }}</td>
                        <td class="px-4 py-3">Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">Sisa Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</p>
                            @if($invoice->payments->isNotEmpty())
                                <p class="mt-1 text-xs font-medium text-primary">{{ $invoice->payments->count() === 1 ? '1 kali pembayaran' : 'Dicicil ' . $invoice->payments->count() . ' kali' }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $invoice->due_date->format('d M Y') }}</td>
                        <td class="px-4 py-3"><span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $status['class'] }}">{{ $status['label'] }}</span></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <button type="button" data-modal-target="edit-bill-invoice-modal-{{ $invoice->id }}" data-modal-toggle="edit-bill-invoice-modal-{{ $invoice->id }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-400 text-amber-600 transition-colors hover:bg-amber-500 hover:text-white" title="Edit invoice" aria-label="Edit invoice {{ $invoice->invoice_number }}">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                @if(in_array($invoice->status, ['unpaid', 'overdue', 'partial'], true))
                                    <button type="button" data-modal-target="record-bill-payment-modal-{{ $invoice->id }}" data-modal-toggle="record-bill-payment-modal-{{ $invoice->id }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-primary text-primary transition-colors hover:bg-primary hover:text-white" title="Catat pembayaran" aria-label="Catat pembayaran invoice {{ $invoice->invoice_number }}">
                                        <i class="ri-hand-coin-line"></i>
                                    </button>
                                @endif
                                <form method="POST" action="{{ route('admin.recurring-bills.invoices.destroy', $invoice) }}" onsubmit="return confirm(@js('Hapus invoice ' . $invoice->invoice_number . '? Riwayat pembayarannya juga akan dihapus.'))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-300 text-red-600 transition-colors hover:bg-red-600 hover:text-white" title="Hapus invoice" aria-label="Hapus invoice {{ $invoice->invoice_number }}">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada invoice.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $invoices->links() }}

    @foreach($invoices as $invoice)
        <div id="edit-bill-invoice-modal-{{ $invoice->id }}" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full overflow-y-auto overflow-x-hidden p-4 md:inset-0">
            <div class="relative max-h-full w-full max-w-lg">
                <div class="relative overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 p-5">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Edit Invoice</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $invoice->user->name ?? '-' }} · {{ $invoice->invoice_number }}</p>
                        </div>
                        <button type="button" data-modal-hide="edit-bill-invoice-modal-{{ $invoice->id }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-900"><i class="ri-close-line text-xl"></i></button>
                    </div>
                    <form method="POST" action="{{ route('admin.recurring-bills.invoices.update', $invoice) }}">
                        @csrf
                        @method('PUT')
                        <div class="space-y-4 p-5">
                            <div>
                                <label for="invoice_title_{{ $invoice->id }}" class="mb-2 block text-sm font-semibold text-gray-700">Judul invoice</label>
                                <input id="invoice_title_{{ $invoice->id }}" name="title" value="{{ old('title', $invoice->title) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="invoice_amount_{{ $invoice->id }}" class="mb-2 block text-sm font-semibold text-gray-700">Nominal</label>
                                    <div class="relative"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">Rp</span><input id="invoice_amount_{{ $invoice->id }}" type="number" name="amount" min="{{ max(1, $invoice->paid_amount) }}" value="{{ old('amount', $invoice->amount) }}" required class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"></div>
                                    @if($invoice->paid_amount > 0)<p class="mt-1 text-xs text-gray-500">Minimum Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }} karena sudah ada pembayaran.</p>@endif
                                </div>
                                <div>
                                    <label for="invoice_due_date_{{ $invoice->id }}" class="mb-2 block text-sm font-semibold text-gray-700">Jatuh tempo</label>
                                    <input id="invoice_due_date_{{ $invoice->id }}" type="date" name="due_date" value="{{ old('due_date', $invoice->due_date?->toDateString()) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                                </div>
                            </div>
                            <div>
                                <label for="invoice_notes_{{ $invoice->id }}" class="mb-2 block text-sm font-semibold text-gray-700">Catatan</label>
                                <textarea id="invoice_notes_{{ $invoice->id }}" name="notes" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Opsional">{{ old('notes', $invoice->notes) }}</textarea>
                            </div>
                            <p class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">Status pembayaran dihitung otomatis dari nominal dan pembayaran yang sudah tercatat.</p>
                        </div>
                        <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-4"><button type="button" data-modal-hide="edit-bill-invoice-modal-{{ $invoice->id }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button><button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Simpan perubahan</button></div>
                    </form>
                </div>
            </div>
        </div>

        @if(in_array($invoice->status, ['unpaid', 'overdue', 'partial'], true))
            <div id="record-bill-payment-modal-{{ $invoice->id }}" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full overflow-y-auto overflow-x-hidden p-4 md:inset-0">
                <div class="relative max-h-full w-full max-w-lg">
                    <div class="relative overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-100 p-5">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Catat Pembayaran</h3>
                                <p class="mt-1 text-sm text-gray-500">{{ $invoice->user->name ?? '-' }} · {{ $invoice->invoice_number }}</p>
                            </div>
                            <button type="button" data-modal-hide="record-bill-payment-modal-{{ $invoice->id }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-900"><i class="ri-close-line text-xl"></i></button>
                        </div>
                        <form method="POST" action="{{ route('admin.recurring-bills.invoices.payments.store', $invoice) }}">
                            @csrf
                            <div class="space-y-4 p-5">
                                <div class="grid grid-cols-2 gap-px border border-gray-100 bg-gray-100 text-sm">
                                    <div class="bg-white p-3"><p class="text-xs text-gray-500">Total tagihan</p><p class="mt-1 font-semibold text-gray-900">Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</p></div>
                                    <div class="bg-white p-3"><p class="text-xs text-gray-500">Sisa tagihan</p><p class="mt-1 font-semibold text-primary">Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</p></div>
                                </div>
                                <div>
                                    <label for="amount_{{ $invoice->id }}" class="mb-2 block text-sm font-semibold text-gray-700">Nominal dibayar</label>
                                    <div class="relative"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">Rp</span><input id="amount_{{ $invoice->id }}" type="number" name="amount" min="1" max="{{ $invoice->remaining_amount }}" value="{{ old('amount', $invoice->remaining_amount) }}" required class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"></div>
                                    <p class="mt-1 text-xs text-gray-500">Boleh diisi kurang dari sisa tagihan untuk mencatat cicilan.</p>
                                </div>
                                <div><label for="payment_method_{{ $invoice->id }}" class="mb-2 block text-sm font-semibold text-gray-700">Metode pembayaran</label><select id="payment_method_{{ $invoice->id }}" name="payment_method" class="w-full rounded-lg border border-gray-300 px-3 py-2.5"><option value="cash">Tunai</option><option value="transfer">Transfer bank</option><option value="qris">QRIS</option><option value="manual">Manual</option></select></div>
                                <div><label for="notes_{{ $invoice->id }}" class="mb-2 block text-sm font-semibold text-gray-700">Catatan</label><textarea id="notes_{{ $invoice->id }}" name="notes" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2.5" placeholder="Opsional">{{ old('notes') }}</textarea></div>
                                @if($invoice->payments->isNotEmpty())
                                    <div class="border-t border-gray-100 pt-4"><p class="mb-2 text-sm font-semibold text-gray-700">Riwayat cicilan</p><div class="space-y-2">@foreach($invoice->payments as $payment)<div class="flex items-center justify-between border border-gray-100 px-3 py-2 text-xs"><div><p class="font-medium text-gray-800">{{ $payment->receipt_number }} · {{ strtoupper($payment->payment_method) }}</p><p class="mt-0.5 text-gray-500">{{ $payment->paid_at->format('d M Y H:i') }}</p></div><p class="font-semibold text-gray-900">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</p></div>@endforeach</div></div>
                                @endif
                            </div>
                            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-4"><button type="button" data-modal-hide="record-bill-payment-modal-{{ $invoice->id }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button><button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Simpan pembayaran</button></div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>
@endsection
