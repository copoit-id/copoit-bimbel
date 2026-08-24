@extends('admin.layout.admin')

@section('title', 'Detail Tagihan Rutin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $recurringBill->name }}</h1>
            <p class="text-sm text-gray-500">Rp {{ number_format((float) $recurringBill->amount, 0, ',', '.') }} · {{ ucfirst($recurringBill->frequency) }} · {{ $recurringBill->targets->count() }} peserta sasaran</p>
        </div>
        <form method="POST" action="{{ route('admin.recurring-bills.generate', $recurringBill) }}">
            @csrf
            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Generate invoice</button>
        </form>
    </div>

    <div class="border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-4 py-4 sm:px-5">
            <h2 class="font-semibold text-gray-900">Periode Tagihan</h2>
            <p class="mt-1 text-sm text-gray-500">Buka satu periode untuk melihat peserta, sisa tagihan, dan mencatat cicilan.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                    <tr>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3">Jatuh tempo</th>
                        <th class="px-4 py-3">Peserta</th>
                        <th class="px-4 py-3">Tagihan</th>
                        <th class="px-4 py-3">Terbayar</th>
                        <th class="px-4 py-3">Sisa</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($periods as $period)
                        @php
                            $remainingAmount = max(0, (int) $period->total_amount - (int) $period->paid_amount);
                        @endphp
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-3"><p class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($period->period_start)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($period->period_end)->translatedFormat('d M Y') }}</p></td>
                            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($period->due_date)->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-3">{{ number_format($period->participant_count) }} peserta</td>
                            <td class="px-4 py-3">Rp {{ number_format((float) $period->total_amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-green-700">Rp {{ number_format((float) $period->paid_amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900">Rp {{ number_format($remainingAmount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('admin.recurring-bills.periods.show', [$recurringBill, $period->period_start]) }}" class="inline-flex items-center rounded-lg border border-primary px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary hover:text-white">Lihat peserta</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">Belum ada periode tagihan. Gunakan Generate invoice untuk membuatnya.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $periods->links() }}
</div>
@endsection
