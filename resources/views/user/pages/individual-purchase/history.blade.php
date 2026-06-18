@extends('user.layout.new-user')

@section('title', 'Riwayat Pembelian Item')

@section('content')
@php($primaryColor = $clientBranding['primary_color'] ?? '#10b981')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Riwayat Pembelian Item</h1>
    <p class="mt-1 text-gray-500">Status pembelian materi, tryout, tes koran, dan kecermatan terpisah.</p>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">{{ session('error') }}</div>
@endif

<div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Transaksi</th>
                    <th class="px-4 py-3">Item</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Tanggal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($purchases as $purchase)
                @php
                    $itemType = class_basename($purchase->purchasable_type ?? '');
                    $typeLabel = match ($itemType) {
                        'Tryout' => 'Tryout',
                        'TesKoran' => 'Tes Koran',
                        'Kecermatan' => 'Kecermatan',
                        default => 'Materi',
                    };
                    $statusLabel = match ($purchase->status) {
                        \App\Models\IndividualPurchase::STATUS_APPROVED => 'Berhasil',
                        \App\Models\IndividualPurchase::STATUS_PENDING => 'Pending',
                        \App\Models\IndividualPurchase::STATUS_REJECTED => 'Ditolak',
                        default => ucfirst((string) $purchase->status),
                    };
                    $statusClass = match ($purchase->status) {
                        \App\Models\IndividualPurchase::STATUS_APPROVED => 'bg-green-100 text-green-700',
                        \App\Models\IndividualPurchase::STATUS_PENDING => 'bg-yellow-100 text-yellow-700',
                        \App\Models\IndividualPurchase::STATUS_REJECTED => 'bg-red-100 text-red-700',
                        default => 'bg-gray-100 text-gray-700',
                    };
                @endphp
                <tr>
                    <td class="px-4 py-4">
                        <p class="font-semibold text-gray-900">{{ $purchase->transaction_id }}</p>
                        <p class="text-xs text-gray-500">{{ ucfirst((string) $purchase->payment_method) }}</p>
                    </td>
                    <td class="px-4 py-4">
                        <p class="font-semibold text-gray-900">{{ $purchase->purchasable?->title ?? $purchase->purchasable?->name ?? 'Item tidak ditemukan' }}</p>
                        <p class="text-xs text-gray-500">{{ $typeLabel }}</p>
                    </td>
                    <td class="px-4 py-4">Rp {{ number_format((int) $purchase->total_amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-4">
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="px-4 py-4 text-gray-600">{{ $purchase->created_at?->format('d M Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-gray-500">Belum ada pembelian item.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
