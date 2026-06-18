@php
    $canManageTesKoran = auth()->user()?->hasPermission('tes_koran', 'view') ?? false;
    $typeOptions = [
        'material' => 'Materi',
        'tryout' => 'Tryout',
        'kecermatan' => 'Kecermatan',
    ];

    if ($canManageTesKoran) {
        $typeOptions['tes_koran'] = 'Tes Koran';
    }
@endphp

<!-- Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
    <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-primary">Total Transaksi</p>
                <p class="text-2xl font-bold text-primary">{{ $stats['total'] ?? 0 }}</p>
            </div>
            <i class="ri-shopping-cart-line text-3xl text-primary"></i>
        </div>
    </div>
    <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-primary">Berhasil</p>
                <p class="text-2xl font-bold text-primary">{{ $stats['approved'] ?? 0 }}</p>
            </div>
            <i class="ri-check-line text-3xl text-primary"></i>
        </div>
    </div>
    <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-primary">Pending</p>
                <p class="text-2xl font-bold text-primary">{{ $stats['pending'] ?? 0 }}</p>
            </div>
            <i class="ri-time-line text-3xl text-primary"></i>
        </div>
    </div>
    <div class="bg-primary/5 border border-primary/50 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-primary">Gagal</p>
                <p class="text-2xl font-bold text-primary">{{ $stats['rejected'] ?? 0 }}</p>
            </div>
            <i class="ri-close-line text-3xl text-primary"></i>
        </div>
    </div>
</div>

<div class="package-bimbel bg-white p-8 rounded-lg border border-border mt-6">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 lg:gap-6 mb-6">
        <form method="GET" action="{{ route('admin.pembayaran.index') }}" class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4 w-full lg:w-auto">
            <div class="relative w-full sm:w-64">
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari transaksi..."
                    class="pl-10 pr-4 py-2 w-full sm:w-64 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <select name="product_type"
                    class="border border-gray-300 rounded-lg px-4 py-2 w-full sm:w-auto focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    @foreach($typeOptions as $typeKey => $typeLabel)
                    <option value="{{ $typeKey }}" {{ ($type ?? 'material') === $typeKey ? 'selected' : '' }}>
                        {{ $typeLabel }}
                    </option>
                    @endforeach
                </select>
                <select name="status"
                    class="border border-gray-300 rounded-lg px-4 py-2 w-full sm:w-auto focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="all" {{ ($status ?? 'pending') === 'all' ? 'selected' : '' }}>Semua Status ({{ $stats['total'] ?? 0 }})</option>
                    <option value="pending" {{ ($status ?? 'pending') === 'pending' ? 'selected' : '' }}>Pending ({{ $stats['pending'] ?? 0 }})</option>
                    <option value="approved" {{ ($status ?? 'pending') === 'approved' ? 'selected' : '' }}>Berhasil ({{ $stats['approved'] ?? 0 }})</option>
                    <option value="rejected" {{ ($status ?? 'pending') === 'rejected' ? 'selected' : '' }}>Gagal ({{ $stats['rejected'] ?? 0 }})</option>
                </select>
                <select name="method"
                    class="border border-gray-300 rounded-lg px-4 py-2 w-full sm:w-auto focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">Metode Pembayaran</option>
                    @foreach($paymentMethods ?? [] as $paymentMethod)
                    <option value="{{ $paymentMethod }}" {{ ($method ?? '') === $paymentMethod ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_', ' ', $paymentMethod)) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 w-full sm:w-auto">
                Terapkan
            </button>
            <a href="{{ route('admin.pembayaran.index', ['product_type' => 'all']) }}"
                class="inline-flex items-center justify-center px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 w-full sm:w-auto">
                <i class="ri-refresh-line"></i> Reset
            </a>
        </form>
        <div class="flex items-center gap-3 w-full lg:w-auto justify-between lg:justify-end">
            <div id="payment-count" class="text-sm text-gray-500">
                Total: <span class="font-medium text-gray-700">{{ $purchases->total() ?? 0 }} Transaksi</span>
            </div>
        </div>
    </div>

    <!-- Payment Table -->
    <div class="relative overflow-x-auto w-full">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 min-w-[180px]">Transaksi</th>
                    <th scope="col" class="px-4 py-3 min-w-[220px]">User</th>
                    <th scope="col" class="px-4 py-3 min-w-[180px]">Item</th>
                    <th scope="col" class="px-4 py-3 min-w-[120px]">Jumlah</th>
                    <th scope="col" class="px-4 py-3 min-w-[130px]">Metode</th>
                    <th scope="col" class="px-4 py-3 min-w-[110px]">Status</th>
                    <th scope="col" class="px-4 py-3 min-w-[110px]">Tanggal</th>
                    <th scope="col" class="px-4 py-3 min-w-[150px]">Aksi</th>
                </tr>
            </thead>
            <tbody id="payment-table-body">
                @forelse($purchases ?? [] as $purchase)
                @php
                    $itemTitle = $purchase->purchasable?->title ?? $purchase->purchasable?->name ?? 'N/A';
                    $itemType = class_basename($purchase->purchasable_type ?? '');
                    $typeLabel = match ($itemType) {
                        'Tryout' => 'Tryout',
                        'TesKoran' => 'Tes Koran',
                        'Kecermatan' => 'Kecermatan',
                        default => 'Materi',
                    };
                @endphp
                <tr class="payment-row bg-white border-b border-dashed border-gray-200">
                    <td class="px-4 py-4">
                        <div>
                            <p class="font-medium text-gray-900">{{ $purchase->transaction_id }}</p>
                            <p class="text-sm text-gray-500">ID: {{ $purchase->id }}</p>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($purchase->user->name ?? 'Unknown') }}&background=444444&color=fff"
                                class="w-8 h-8 rounded-full">
                            <div>
                                <p class="font-medium">{{ $purchase->user->name ?? 'Unknown User' }}</p>
                                <p class="text-sm text-gray-500">{{ $purchase->user->email ?? 'No email' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <div>
                            <p class="font-medium text-gray-900">{{ $itemTitle }}</p>
                            <p class="text-sm text-gray-500">{{ $typeLabel }}</p>
                        </div>
                    </td>
                    <td class="px-4 py-4">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-4">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs capitalize">
                            {{ $purchase->payment_method ?? 'Unknown' }}
                        </span>
                    </td>
                    <td class="px-4 py-4">
                        @switch($purchase->status)
                        @case('approved')
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Berhasil</span>
                        @break
                        @case('pending')
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">Pending</span>
                        @break
                        @case('rejected')
                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">Gagal</span>
                        @break
                        @default
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">{{ ucfirst($purchase->status) }}</span>
                        @endswitch
                    </td>
                    <td class="px-4 py-4">{{ $purchase->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('admin.pembayaran.item.show', $purchase) }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 text-gray-600 hover:bg-primary/10 hover:text-primary"
                                title="Detail">
                                <i class="ri-eye-line text-base"></i>
                            </a>
                            @if($purchase->status === 'pending')
                            <form action="{{ route('admin.pembayaran.item.confirm', $purchase) }}" method="POST"
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
                            <p>{{ ($status ?? 'pending') !== 'pending' || ($method ?? '') || ($search ?? '') ? 'Tidak ada transaksi sesuai filter' : 'Belum ada transaksi pembayaran pending' }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if(isset($purchases) && $purchases->hasPages())
    <div class="flex justify-center mt-6">
        {{ $purchases->links() }}
    </div>
    @endif
</div>
