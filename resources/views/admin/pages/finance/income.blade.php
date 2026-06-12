@extends('admin.layout.admin')
@section('title', 'Pemasukan')
@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Pemasukan</h1>
        <p class="text-sm text-gray-500">Semua transaksi masuk yang berhasil.</p>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <p class="text-sm text-gray-500">Total Pemasukan</p>
        <p class="text-2xl font-bold text-gray-900">Rp {{ number_format($totalIncome ?? 0, 0, ',', '.') }}</p>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <span class="text-sm text-gray-500">Periode:</span>
            @foreach(['day' => 'Harian', 'week' => 'Mingguan', 'month' => 'Bulanan', 'year' => 'Tahunan'] as $key => $label)
                <a href="{{ request()->fullUrlWithQuery(['period' => $key, 'page' => null]) }}"
                    class="px-3 py-1 rounded-full text-xs {{ $period === $key ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <div class="h-72">
            <canvas id="incomeChart"></canvas>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-600">
            <thead class="text-xs uppercase bg-gray-50 text-gray-700">
                <tr>
                    <th class="px-4 py-3">Transaksi</th>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Paket</th>
                    <th class="px-4 py-3">Jumlah</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr class="border-b border-gray-100">
                        <td class="px-4 py-3 text-gray-800">
                            <div>
                                <p class="font-medium">{{ $payment->transaction_id }}</p>
                                <p class="text-xs text-gray-500">ID: {{ $payment->payment_id }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-medium text-gray-800">{{ $payment->user->name ?? '-' }}</p>
                                <p class="text-xs text-gray-500">{{ $payment->user->email ?? '-' }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $payment->package->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900">Rp {{ number_format((float) $payment->total_amount, 0, ',', '.') }}</p>
                            @if((float) ($payment->discount_amount ?? 0) > 0)
                                <p class="text-xs text-gray-500">
                                    Harga awal Rp {{ number_format((float) ($payment->original_amount ?? $payment->amount), 0, ',', '.') }}
                                    - diskon Rp {{ number_format((float) $payment->discount_amount, 0, ',', '.') }}
                                </p>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ ($payment->paid_at ?? $payment->created_at)->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.pembayaran.show', $payment->payment_id) }}" class="text-primary hover:text-primary/80">
                                Lihat
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">Belum ada pemasukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $payments->links() }}
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const incomeCtx = document.getElementById('incomeChart').getContext('2d');
    new Chart(incomeCtx, {
        type: 'line',
        data: {
            labels: @json($chart['labels']),
            datasets: [{
                label: 'Pemasukan',
                data: @json($chart['values']),
                borderColor: 'rgba(37, 99, 235, 0.9)',
                backgroundColor: 'rgba(37, 99, 235, 0.15)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return 'Rp ' + ctx.parsed.y.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
</script>
@endpush
@endsection
