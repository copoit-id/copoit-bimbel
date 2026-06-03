@extends('admin.layout.admin')

@section('title', 'Pembelian Individual')

@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="" title="Pembelian Individual" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="Pembelian Individual"
    description="Kelola pembelian materi, tryout, dan tes koran secara terpisah (tanpa paket)"></x-page-desc>

<!-- Type Tabs -->
<div class="flex gap-2 mt-6">
    <a href="{{ route('admin.individual-purchase.index', ['type' => 'material', 'status' => $status]) }}"
       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $type === 'material' ? 'bg-primary text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
        <i class="ri-book-line mr-1"></i>Materi
    </a>
    <a href="{{ route('admin.individual-purchase.index', ['type' => 'tryout', 'status' => $status]) }}"
       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $type === 'tryout' ? 'bg-primary text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
        <i class="ri-file-list-3-line mr-1"></i>Tryout
    </a>
    <a href="{{ route('admin.individual-purchase.index', ['type' => 'tes_koran', 'status' => $status]) }}"
       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $type === 'tes_koran' ? 'bg-primary text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
        <i class="ri-file-edit-line mr-1"></i>Tes Koran
    </a>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-4 gap-4 mt-4">
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total</p>
                <p class="text-xl font-bold text-gray-800">{{ $stats['total'] }}</p>
            </div>
            <i class="ri-list-check text-2xl text-gray-400"></i>
        </div>
    </div>
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-amber-600">Pending</p>
                <p class="text-xl font-bold text-amber-700">{{ $stats['pending'] }}</p>
            </div>
            <i class="ri-time-line text-2xl text-amber-400"></i>
        </div>
    </div>
    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-green-600">Disetujui</p>
                <p class="text-xl font-bold text-green-700">{{ $stats['approved'] }}</p>
            </div>
            <i class="ri-check-line text-2xl text-green-400"></i>
        </div>
    </div>
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-red-600">Ditolak</p>
                <p class="text-xl font-bold text-red-700">{{ $stats['rejected'] }}</p>
            </div>
            <i class="ri-close-line text-2xl text-red-400"></i>
        </div>
    </div>
</div>

<div class="bg-white p-6 rounded-lg border border-gray-100 mt-6">
    <!-- Filters -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div class="flex gap-2">
            @php $statuses = ['pending' => 'Pending', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'all' => 'Semua']; @endphp
            @foreach($statuses as $key => $label)
            <a href="{{ route('admin.individual-purchase.index', ['type' => $type, 'status' => $key]) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $status === $key ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>
        <div class="text-sm text-gray-500">
            Total: <span class="font-medium text-gray-700">{{ $purchases->total() }} transaksi</span>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-4 py-3">Transaksi</th>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Item</th>
                    <th class="px-4 py-3">Jumlah</th>
                    <th class="px-4 py-3">Metode</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                <tr class="border-b border-dashed border-gray-200 hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 text-xs">{{ $purchase->transaction_id }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($purchase->user->name ?? 'U') }}&background=444&color=fff&size=32"
                                 class="w-8 h-8 rounded-full flex-shrink-0">
                            <div>
                                <p class="font-medium text-gray-800 text-xs">{{ $purchase->user->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-400">{{ $purchase->user->email ?? '' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @php
                        $itemTitle = $purchase->purchasable?->title ?? $purchase->purchasable?->name ?? 'N/A';
                        $itemType = class_basename($purchase->purchasable_type ?? '');
                        $typeIcon = match ($itemType) {
                            'Tryout' => 'ri-file-list-3-line',
                            'TesKoran' => 'ri-file-edit-line',
                            default => 'ri-book-line',
                        };
                        $typeColor = match ($itemType) {
                            'Tryout' => 'text-purple-600 bg-purple-50',
                            'TesKoran' => 'text-emerald-600 bg-emerald-50',
                            default => 'text-blue-600 bg-blue-50',
                        };
                        @endphp
                        <div>
                            <span class="px-1.5 py-0.5 rounded text-xs font-medium {{ $typeColor }}">
                                <i class="{{ $typeIcon }} mr-0.5"></i>{{ $itemType }}
                            </span>
                            <p class="text-xs text-gray-700 mt-1 line-clamp-1 max-w-[150px]">{{ $itemTitle }}</p>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="font-semibold text-gray-800 text-sm">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs capitalize">
                            {{ $purchase->payment_method }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($purchase->status === 'pending')
                        <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">
                            <i class="ri-time-line mr-0.5"></i>Pending
                        </span>
                        @elseif($purchase->status === 'approved')
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                            <i class="ri-check-line mr-0.5"></i>Disetujui
                        </span>
                        @else
                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">
                            <i class="ri-close-line mr-0.5"></i>Ditolak
                        </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        {{ $purchase->created_at->format('d M Y, H:i') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.individual-purchase.show', $purchase) }}"
                               class="p-1.5 text-gray-500 hover:text-primary rounded-lg hover:bg-primary/10 transition-colors"
                               title="Detail">
                                <i class="ri-eye-line text-base"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                        <i class="ri-inbox-line text-3xl mb-2 block"></i>
                        <p>Belum ada transaksi</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($purchases->hasPages())
    <div class="mt-4">
        {{ $purchases->links() }}
    </div>
    @endif
</div>
@endsection
