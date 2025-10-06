@extends('admin.layout.admin')
@section('title', 'Manajemen Pembayaran')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="" title="Manajemen Pembayaran" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="Manajemen Pembayaran" description="Monitor dan kelola semua transaksi pembayaran"></x-page-desc>

<!-- Summary Cards -->
<div class="grid grid-cols-4 gap-4 mt-6">
    <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-primary">Total Transaksi</p>
                <p class="text-2xl font-bold text-primary">{{ $totalPayments ?? 0 }}</p>
            </div>
            <i class="ri-money-dollar-circle-line text-3xl text-primary"></i>
        </div>
    </div>
    <div class="bg-green-50 border border-green-500/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-green-700">Berhasil</p>
                <p class="text-2xl font-bold text-green-700">{{ $successPayments ?? 0 }}</p>
            </div>
            <i class="ri-check-line text-3xl text-green-700"></i>
        </div>
    </div>
    <div class="bg-yellow-50 border border-yellow-500/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-yellow-700">Pending</p>
                <p class="text-2xl font-bold text-yellow-700">{{ $pendingPayments ?? 0 }}</p>
            </div>
            <i class="ri-time-line text-3xl text-yellow-700"></i>
        </div>
    </div>
    <div class="bg-red-50 border border-red-500/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-red-700">Gagal</p>
                <p class="text-2xl font-bold text-red-700">{{ $failedPayments ?? 0 }}</p>
            </div>
            <i class="ri-close-line text-3xl text-red-700"></i>
        </div>
    </div>
</div>

<div class="package-bimbel bg-white p-8 rounded-lg border border-border mt-6">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 lg:gap-6 mb-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4 w-full lg:w-auto">
            <div class="relative w-full sm:w-auto">
                <input type="text" id="payment-search" placeholder="Cari transaksi..."
                    class="pl-10 pr-4 py-2 w-full sm:w-64 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <select id="payment-status-filter"
                    class="border border-gray-300 rounded-lg px-4 py-2 w-full sm:w-auto focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">Status Pembayaran</option>
                    <option value="success">Berhasil</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Gagal</option>
                    <option value="expired">Expired</option>
                </select>
                <select id="payment-method-filter"
                    class="border border-gray-300 rounded-lg px-4 py-2 w-full sm:w-auto focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">Metode Pembayaran</option>
                    <option value="bank">Transfer Bank</option>
                    <option value="ewallet">E-Wallet</option>
                    <option value="credit">Kartu Kredit</option>
                    <option value="xendit">Xendit</option>
                </select>
            </div>
            <button id="reset-payment-filters"
                class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 w-full sm:w-auto">
                <i class="ri-refresh-line"></i> Reset
            </button>
        </div>
        <div id="payment-count" class="text-sm text-gray-500 w-full lg:w-auto text-left lg:text-right">
            Total: <span class="font-medium text-gray-700">{{ $payments->total() ?? 0 }} Transaksi</span>
        </div>
    </div>

    <!-- Payment Table -->
    <div class="relative overflow-x-auto w-full">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 w-full">Transaksi</th>
                    <th scope="col" class="px-6 py-3 w-full">User</th>
                    <th scope="col" class="px-6 py-3 w-full">Paket</th>
                    <th scope="col" class="px-6 py-3 w-full">Jumlah</th>
                    <th scope="col" class="px-6 py-3 w-full">Metode</th>
                    <th scope="col" class="px-6 py-3 w-full">Status</th>
                    <th scope="col" class="px-6 py-3 w-full">Tanggal</th>
                    <th scope="col" class="px-6 py-3 w-full">Action</th>
                </tr>
            </thead>
            <tbody id="payment-table-body">
                @forelse($payments ?? [] as $payment)
                <tr class="payment-row bg-white border-b border-dashed border-gray-200"
                    data-user="{{ strtolower($payment->user->name ?? 'unknown') }}"
                    data-package="{{ strtolower($payment->package->name ?? 'unknown') }}"
                    data-status="{{ $payment->status }}" data-method="{{ $payment->payment_method }}"
                    data-date="{{ $payment->created_at->format('Y-m-d') }}">
                    <td class="px-6 py-4">
                        <div>
                            <p class="font-medium text-gray-900">{{ $payment->transaction_id }}</p>
                            <p class="text-sm text-gray-500">ID: {{ $payment->payment_id }}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($payment->user->name ?? 'Unknown') }}&background=444444&color=fff"
                                class="w-8 h-8 rounded-full">
                            <div>
                                <p class="font-medium">{{ $payment->user->name ?? 'Unknown User' }}</p>
                                <p class="text-sm text-gray-500">{{ $payment->user->email ?? 'No email' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">{{ $payment->package->name ?? 'Unknown Package' }}</td>
                    <td class="px-6 py-4">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs capitalize">
                            {{ $payment->payment_method ?? 'Unknown' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @switch($payment->status)
                        @case('success')
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Berhasil</span>
                        @break
                        @case('pending')
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">Pending</span>
                        @break
                        @case('failed')
                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">Gagal</span>
                        @break
                        @case('expired')
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Expired</span>
                        @break
                        @default
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">{{
                            ucfirst($payment->status) }}</span>
                        @endswitch
                    </td>
                    <td class="px-6 py-4">{{ $payment->created_at->format('d M Y') }}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.pembayaran.show', $payment->payment_id) }}"
                                class="text-primary hover:text-primary/80">
                                <i class="ri-eye-line"></i>
                            </a>
                            @if($payment->status === 'pending')
                            <form action="{{ route('admin.pembayaran.confirm', $payment->payment_id) }}" method="POST"
                                class="inline">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-800"
                                    onclick="return confirm('Konfirmasi pembayaran ini?')">
                                    <i class="ri-check-line"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="px-6 py-8 text-center text-gray-500 w-full">
                        <div class="flex flex-col items-center">
                            <i class="ri-money-dollar-circle-line text-4xl text-gray-300 mb-2"></i>
                            <p>Belum ada transaksi pembayaran</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if(isset($payments) && $payments->hasPages())
    <div class="flex justify-center mt-6">
        {{ $payments->links() }}
    </div>
    @endif

    <!-- No Results Message -->
    <div id="no-payment-results" class="hidden text-center py-12">
        <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
            <i class="ri-search-line text-3xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada transaksi ditemukan</h3>
        <p class="text-gray-500">Coba ubah kata kunci pencarian atau filter</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('payment-search');
    const statusFilter = document.getElementById('payment-status-filter');
    const methodFilter = document.getElementById('payment-method-filter');
    const resetButton = document.getElementById('reset-payment-filters');
    const paymentCount = document.getElementById('payment-count');
    const paymentRows = document.querySelectorAll('.payment-row');
    const tableBody = document.getElementById('payment-table-body');
    const noResults = document.getElementById('no-payment-results');

    function filterPayments() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedStatus = statusFilter.value;
        const selectedMethod = methodFilter.value;

        let visibleCount = 0;

        paymentRows.forEach(row => {
            const userName = row.dataset.user || '';
            const packageName = row.dataset.package || '';
            const paymentStatus = row.dataset.status || '';
            const paymentMethod = row.dataset.method || '';

            const matchesSearch = userName.includes(searchTerm) || packageName.includes(searchTerm);
            const matchesStatus = !selectedStatus || paymentStatus === selectedStatus;
            const matchesMethod = !selectedMethod || paymentMethod === selectedMethod;

            if (matchesSearch && matchesStatus && matchesMethod) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Show/hide no results message
        if (visibleCount === 0 && paymentRows.length > 0) {
            noResults.classList.remove('hidden');
            tableBody.parentElement.style.display = 'none';
        } else {
            noResults.classList.add('hidden');
            tableBody.parentElement.style.display = 'block';
        }

        updatePaymentCount(visibleCount);
    }

    function updatePaymentCount(count) {
        paymentCount.innerHTML = `Total: <span class="font-medium text-gray-700">${count} Transaksi</span>`;
    }

    function resetFilters() {
        searchInput.value = '';
        statusFilter.value = '';
        methodFilter.value = '';
        filterPayments();
    }

    // Event listeners
    searchInput.addEventListener('input', filterPayments);
    statusFilter.addEventListener('change', filterPayments);
    methodFilter.addEventListener('change', filterPayments);
    resetButton.addEventListener('click', resetFilters);

    // Initial render
    filterPayments();

    console.log('Payment management scripts loaded');
});
</script>

@endsection
