@props([
    'invoices',
])

<div class="overflow-x-auto">
    <table class="min-w-[900px] w-full text-left text-sm">
        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
            <tr>
                <th class="px-5 py-3">Peserta</th>
                <th class="px-5 py-3">Nominal</th>
                <th class="px-5 py-3">Diterima</th>
                <th class="px-5 py-3">Sisa</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3">Penerimaan terakhir</th>
                <th class="sticky right-0 border-l border-gray-100 bg-gray-50 px-5 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse ($invoices as $invoice)
                @php($isPaid = $invoice->remaining_amount <= 0)
                <tr>
                    <td class="px-5 py-4"><p class="font-semibold text-gray-900">{{ $invoice->user?->name ?? 'Peserta dihapus' }}</p><p class="mt-1 text-xs text-gray-500">{{ $invoice->user?->email ?? '—' }}</p></td>
                    <td class="px-5 py-4 tabular-nums">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                    <td class="px-5 py-4 tabular-nums">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
                    <td class="px-5 py-4 font-semibold tabular-nums">Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</td>
                    <td class="px-5 py-4"><x-ui.badge :variant="$isPaid ? 'success' : ($invoice->paid_amount > 0 ? 'warning' : 'secondary')" size="sm" pill>{{ $isPaid ? 'Lunas' : ($invoice->paid_amount > 0 ? 'Belum lunas' : 'Belum bayar') }}</x-ui.badge></td>
                    <td class="px-5 py-4 text-gray-500">{{ $invoice->payments->first()?->paid_at?->translatedFormat('d M Y, H:i') ?? 'Belum ada' }}</td>
                    <td class="sticky right-0 border-l border-gray-100 bg-white px-5 py-4 text-right">
                        @if (in_array($invoice->status, ['unpaid', 'overdue', 'partial'], true))
                            <button type="button" data-modal-target="record-bill-payment-modal-{{ $invoice->id }}" data-modal-toggle="record-bill-payment-modal-{{ $invoice->id }}" class="inline-flex items-center gap-1.5 rounded-lg border border-primary px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary hover:text-white"><i class="ri-hand-coin-line"></i>Catat bayar</button>
                        @else
                            <span class="text-xs font-semibold text-emerald-700">Lunas</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-12 text-center text-gray-500">Belum ada peserta pada tagihan ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
