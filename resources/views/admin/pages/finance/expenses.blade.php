@extends('admin.layout.admin')
@section('title', 'Pengeluaran')
@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Pengeluaran</h1>
        <p class="text-sm text-gray-500">Catat transaksi keluar secara manual.</p>
    </div>

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white border border-gray-200 rounded-lg p-4">
        <div>
            <p class="text-sm text-gray-500">Total Pengeluaran</p>
            <p class="text-2xl font-bold text-gray-900">Rp {{ number_format($totalExpense ?? 0, 0, ',', '.') }}</p>
        </div>
        <a href="{{ route('admin.finance.expenses.create') }}" class="px-4 py-2 bg-primary text-white rounded-lg text-sm">Tambah Pengeluaran</a>
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
            <canvas id="expenseChart"></canvas>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-600">
            <thead class="text-xs uppercase bg-gray-50 text-gray-700">
                <tr>
                    <th class="px-4 py-3">Judul</th>
                    <th class="px-4 py-3">Jumlah</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Catatan</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                    <tr class="border-b border-gray-100">
                        <td class="px-4 py-3 text-gray-800">{{ $expense->title }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ ($expense->spent_at ?? $expense->created_at)->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $expense->notes ?: '-' }}</td>
                        <td class="px-4 py-3">
                            <form action="{{ route('admin.finance.expenses.destroy', $expense->id) }}" method="POST" onsubmit="return confirm('Hapus pengeluaran ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">Belum ada pengeluaran.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $expenses->links() }}
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const expenseCtx = document.getElementById('expenseChart').getContext('2d');
    new Chart(expenseCtx, {
        type: 'bar',
        data: {
            labels: @json($chart['labels']),
            datasets: [{
                label: 'Pengeluaran',
                data: @json($chart['values']),
                backgroundColor: 'rgba(220, 38, 38, 0.25)',
                borderColor: 'rgba(220, 38, 38, 0.9)',
                borderWidth: 1
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
