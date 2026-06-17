@extends('admin.layout.admin')
@section('title', 'Manajemen Pembayaran')
@section('content')
@php
    $summaryMetric = $summaryMetric ?? 'count';
    $formatSummary = fn ($value) => $summaryMetric === 'amount'
        ? 'Rp ' . number_format((float) $value, 0, ',', '.')
        : number_format((int) $value, 0, ',', '.');
@endphp

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="" title="Manajemen Pembayaran" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="Manajemen Pembayaran" description="Monitor dan kelola semua transaksi pembayaran paket dan individual"></x-page-desc>

<form method="GET" action="{{ route('admin.pembayaran.index') }}" class="mt-6 flex flex-col sm:flex-row sm:items-center gap-2">
    <input type="hidden" name="search" value="{{ $search ?? '' }}">
    <input type="hidden" name="product_type" value="{{ $productType ?? 'all' }}">
    <input type="hidden" name="status" value="{{ $status ?? 'pending' }}">
    <input type="hidden" name="method" value="{{ $method ?? '' }}">
    <label for="summary_metric" class="text-sm font-medium text-gray-700">Tampilan ringkasan</label>
    <select id="summary_metric" name="summary_metric" onchange="this.form.submit()"
        class="border border-gray-300 rounded-lg px-4 py-2 w-full sm:w-auto focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
        <option value="count" {{ ($summaryMetric ?? 'count') === 'count' ? 'selected' : '' }}>Jumlah Transaksi</option>
        <option value="amount" {{ ($summaryMetric ?? 'count') === 'amount' ? 'selected' : '' }}>Nominal Rupiah</option>
    </select>
</form>

<!-- Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
    <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-primary">Total Transaksi</p>
                <p class="text-2xl font-bold text-primary">{{ $formatSummary($summary['total'] ?? 0) }}</p>
            </div>
            <i class="ri-money-dollar-circle-line text-3xl text-primary"></i>
        </div>
    </div>
    <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-primary">Berhasil</p>
                <p class="text-2xl font-bold text-primary">{{ $formatSummary($summary['success'] ?? 0) }}</p>
            </div>
            <i class="ri-check-line text-3xl text-primary"></i>
        </div>
    </div>
    <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-primary">Pending</p>
                <p class="text-2xl font-bold text-primary">{{ $formatSummary($summary['pending'] ?? 0) }}</p>
            </div>
            <i class="ri-time-line text-3xl text-primary"></i>
        </div>
    </div>
    <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-primary">Gagal</p>
                <p class="text-2xl font-bold text-primary">{{ $formatSummary($summary['failed'] ?? 0) }}</p>
            </div>
            <i class="ri-close-line text-3xl text-primary"></i>
        </div>
    </div>
</div>

<div class="package-bimbel bg-white p-8 rounded-lg border border-border mt-6">
    <div class="flex flex-col gap-5 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div id="payment-count" class="text-sm text-gray-500">
                Total: <span class="font-medium text-gray-700">{{ $payments->total() ?? 0 }} Transaksi</span>
            </div>
            <a href="{{ route('admin.pembayaran.manual.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium whitespace-nowrap">
                <i class="ri-add-line mr-1"></i>Tambah Manual
            </a>
        </div>

        <form method="GET" action="{{ route('admin.pembayaran.index') }}" class="w-full rounded-lg border border-gray-200 bg-gray-50 p-4">
            <input type="hidden" name="summary_metric" value="{{ $summaryMetric ?? 'count' }}">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-12 gap-3">
                <div class="xl:col-span-4">
                    <label for="payment-search" class="block text-xs font-medium text-gray-500 mb-1">Cari</label>
                    <div class="relative">
                        <input id="payment-search" type="text" name="search" value="{{ $search ?? '' }}" placeholder="Transaksi, user, produk..."
                            class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
                    </div>
                </div>
                <div class="xl:col-span-2">
                    <label for="product-type" class="block text-xs font-medium text-gray-500 mb-1">Produk</label>
                    <select id="product-type" name="product_type"
                        class="border border-gray-300 rounded-lg px-4 py-2 w-full bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @foreach($productTypeOptions ?? [] as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}" {{ ($productType ?? 'all') === $optionValue ? 'selected' : '' }}>
                            {{ $optionLabel }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="xl:col-span-2">
                    <label for="payment-status" class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                    <select id="payment-status" name="status"
                        class="border border-gray-300 rounded-lg px-4 py-2 w-full bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="all" {{ ($status ?? 'pending') === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="pending" {{ ($status ?? 'pending') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="success" {{ ($status ?? 'pending') === 'success' ? 'selected' : '' }}>Berhasil</option>
                        <option value="failed" {{ ($status ?? 'pending') === 'failed' ? 'selected' : '' }}>Gagal</option>
                    </select>
                </div>
                <div class="xl:col-span-2">
                    <label for="payment-method" class="block text-xs font-medium text-gray-500 mb-1">Metode</label>
                    <select id="payment-method" name="method"
                        class="border border-gray-300 rounded-lg px-4 py-2 w-full bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Semua Metode</option>
                        @foreach($paymentMethods ?? [] as $paymentMethod)
                        <option value="{{ $paymentMethod }}" {{ ($method ?? '') === $paymentMethod ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_', ' ', $paymentMethod)) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2 xl:col-span-2 flex flex-col flex-row gap-2 xl:pt-5">
                    <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 w-full">
                        Terapkan
                    </button>
                    <a href="{{ route('admin.pembayaran.index') }}"
                        class="inline-flex items-center justify-center px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-white w-full">
                        <i class="ri-refresh-line mr-1"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Payment Table -->
    <div class="relative overflow-x-auto w-full">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 min-w-[180px]">Transaksi</th>
                    <th scope="col" class="px-4 py-3 min-w-[220px]">User</th>
                    <th scope="col" class="px-4 py-3 min-w-[180px]">Produk</th>
                    <th scope="col" class="px-4 py-3 min-w-[120px]">Jumlah</th>
                    <th scope="col" class="px-4 py-3 min-w-[130px]">Metode</th>
                    <th scope="col" class="px-4 py-3 min-w-[110px]">Status</th>
                    <th scope="col" class="px-4 py-3 min-w-[110px]">Tanggal</th>
                    <th scope="col" class="px-4 py-3 min-w-[150px]">Aksi</th>
                </tr>
            </thead>
            <tbody id="payment-table-body">
                @forelse($payments ?? [] as $payment)
                <tr class="payment-row bg-white border-b border-dashed border-gray-200">
                    <td class="px-4 py-4">
                        <div>
                            <p class="font-medium text-gray-900">{{ $payment['transaction_id'] }}</p>
                            <p class="text-sm text-gray-500">ID: {{ $payment['id'] }}</p>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($payment['user_name']) }}&background=444444&color=fff"
                                class="w-8 h-8 rounded-full">
                            <div>
                                <p class="font-medium">{{ $payment['user_name'] }}</p>
                                <p class="text-sm text-gray-500">{{ $payment['user_email'] }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <div>
                            <p class="font-medium text-gray-900">{{ $payment['item_name'] }}</p>
                            <p class="text-sm text-gray-500">{{ $payment['item_type'] }}</p>
                        </div>
                    </td>
                    <td class="px-4 py-4">Rp {{ number_format($payment['amount'], 0, ',', '.') }}</td>
                    <td class="px-4 py-4">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs capitalize">
                            {{ $payment['payment_method'] }}
                        </span>
                    </td>
                    <td class="px-4 py-4">
                        <span class="px-2 py-1 {{ $payment['status_class'] }} rounded-full text-xs">
                            {{ $payment['status_label'] }}
                        </span>
                    </td>
                    <td class="px-4 py-4">{{ $payment['created_at']->format('d M Y') }}</td>
                    <td class="px-4 py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ $payment['detail_route'] }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 text-gray-600 hover:bg-primary/10 hover:text-primary"
                                title="Detail">
                                <i class="ri-eye-line text-base"></i>
                            </a>
                            @if($payment['status'] === 'pending')
                            <form action="{{ $payment['confirm_route'] }}" method="POST"
                                class="inline">
                                @csrf
                                <button type="submit"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-green-100 text-green-700 hover:bg-green-200"
                                    title="Konfirmasi"
                                    onclick="return confirm('Konfirmasi pembayaran ini?')">
                                    <i class="ri-check-line text-base"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-500 w-full">
                        <div class="flex flex-col items-center">
                            <i class="ri-money-dollar-circle-line text-4xl text-gray-300 mb-2"></i>
                            <p>{{ ($status ?? 'pending') !== 'pending' || ($method ?? '') || ($search ?? '') || ($productType ?? 'all') !== 'all' ? 'Tidak ada transaksi sesuai filter' : 'Belum ada transaksi pembayaran pending' }}</p>
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

</div>

@endsection
