@extends('user.layout.new-user')

@section('title', 'Tagihan Saya')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl border border-gray-100 bg-white p-6">
        <h1 class="text-2xl font-bold text-gray-900">Tagihan Saya</h1>
        <p class="mt-1 text-sm text-gray-500">Pantau tagihan rutin dan status pembayarannya.</p>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-gray-100 bg-white">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                <tr>
                    <th class="px-4 py-3">Tagihan</th>
                    <th class="px-4 py-3">Nominal</th>
                    <th class="px-4 py-3">Sisa</th>
                    <th class="px-4 py-3">Jatuh Tempo</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900">{{ $invoice->title }}</p>
                            <p class="text-xs text-gray-500">{{ $invoice->invoice_number }}</p>
                        </td>
                        <td class="px-4 py-3">Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3"><p>Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</p><p class="mt-0.5 text-xs text-gray-500">Terbayar Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</p></td>
                        <td class="px-4 py-3">{{ $invoice->due_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs font-medium @if($invoice->status === 'paid') bg-green-50 text-green-700 @elseif($invoice->status === 'partial') bg-blue-50 text-blue-700 @elseif($invoice->status === 'overdue') bg-red-50 text-red-700 @else bg-yellow-50 text-yellow-700 @endif">
                                {{ $invoice->status === 'partial' ? 'Cicilan' : ($invoice->status === 'paid' ? 'Lunas' : ucfirst($invoice->status)) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada tagihan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $invoices->links() }}
</div>
@endsection
