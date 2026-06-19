@extends('admin.layout.admin')

@section('title', 'Detail Tagihan Rutin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $recurringBill->name }}</h1>
            <p class="text-sm text-gray-500">Rp {{ number_format((float) $recurringBill->amount, 0, ',', '.') }} • {{ ucfirst($recurringBill->frequency) }}</p>
        </div>
        <form method="POST" action="{{ route('admin.recurring-bills.generate', $recurringBill) }}">
            @csrf
            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Generate Invoice</button>
        </form>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                <tr>
                    <th class="px-4 py-3">Invoice</th>
                    <th class="px-4 py-3">Peserta</th>
                    <th class="px-4 py-3">Nominal</th>
                    <th class="px-4 py-3">Jatuh Tempo</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ $invoice->invoice_number }}</p>
                            <p class="text-xs text-gray-500">{{ $invoice->period_start?->format('d M') }} - {{ $invoice->period_end?->format('d M Y') }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $invoice->user->name ?? '-' }}</td>
                        <td class="px-4 py-3">Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ $invoice->due_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ ucfirst($invoice->status) }}</td>
                        <td class="px-4 py-3">
                            @if(in_array($invoice->status, ['unpaid', 'overdue'], true))
                                <form method="POST" action="{{ route('admin.recurring-bills.invoices.paid', $invoice) }}">
                                    @csrf
                                    <button class="rounded-lg bg-green-600 px-3 py-1 text-xs font-semibold text-white">Tandai Lunas</button>
                                </form>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada invoice.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $invoices->links() }}
</div>
@endsection
