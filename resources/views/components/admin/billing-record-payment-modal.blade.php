@props(['invoice'])

@if (in_array($invoice->status, ['unpaid', 'overdue', 'partial'], true))
    <div id="record-bill-payment-modal-{{ $invoice->id }}" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full overflow-y-auto overflow-x-hidden p-4 md:inset-0">
        <div class="relative max-h-full w-full max-w-lg">
            <div class="relative overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 p-5">
                    <div><h3 class="text-lg font-semibold text-gray-900">Catat Pembayaran</h3><p class="mt-1 text-sm text-gray-500">{{ $invoice->user?->name ?? '-' }} · {{ $invoice->invoice_number }}</p></div>
                    <button type="button" data-modal-hide="record-bill-payment-modal-{{ $invoice->id }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-900"><i class="ri-close-line text-xl"></i></button>
                </div>
                <form method="POST" action="{{ route('admin.recurring-bills.invoices.payments.store', $invoice) }}">
                    @csrf
                    <div class="space-y-4 p-5">
                        <div class="grid grid-cols-2 gap-px border border-gray-100 bg-gray-100 text-sm"><div class="bg-white p-3"><p class="text-xs text-gray-500">Total tagihan</p><p class="mt-1 font-semibold text-gray-900">Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</p></div><div class="bg-white p-3"><p class="text-xs text-gray-500">Sisa tagihan</p><p class="mt-1 font-semibold text-primary">Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</p></div></div>
                        <div><label for="amount_{{ $invoice->id }}" class="mb-2 block text-sm font-semibold text-gray-700">Nominal dibayar</label><div class="relative"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">Rp</span><input id="amount_{{ $invoice->id }}" type="number" name="amount" min="1" max="{{ $invoice->remaining_amount }}" value="{{ old('amount', $invoice->remaining_amount) }}" required class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"></div><p class="mt-1 text-xs text-gray-500">Nominal kurang dari sisa tagihan akan tercatat sebagai cicilan.</p></div>
                        <div><label for="payment_method_{{ $invoice->id }}" class="mb-2 block text-sm font-semibold text-gray-700">Metode pembayaran</label><select id="payment_method_{{ $invoice->id }}" name="payment_method" class="w-full rounded-lg border border-gray-300 px-3 py-2.5"><option value="cash">Tunai</option><option value="transfer">Transfer bank</option><option value="qris">QRIS</option><option value="manual">Manual</option></select></div>
                        <div><label for="notes_{{ $invoice->id }}" class="mb-2 block text-sm font-semibold text-gray-700">Catatan</label><textarea id="notes_{{ $invoice->id }}" name="notes" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2.5" placeholder="Opsional">{{ old('notes') }}</textarea></div>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-4"><button type="button" data-modal-hide="record-bill-payment-modal-{{ $invoice->id }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button><button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Simpan pembayaran</button></div>
                </form>
            </div>
        </div>
    </div>
@endif
