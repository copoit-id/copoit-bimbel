@extends('admin.layout.admin')
@section('title', 'Detail Pembayaran')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.pembayaran.index') }}" title="Pembayaran" />
            <x-breadcrumb-item href="" title="Detail Transaksi" />
        </x-slot>
    </x-breadcrumb>
    <div class="flex gap-2">
        @if($payment->status == 'pending')
        <form action="{{ route('admin.pembayaran.confirm', $payment->payment_id) }}" method="POST" class="inline">
            @csrf
            <button type="submit"
                class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                onclick="return confirm('Yakin ingin mengkonfirmasi pembayaran ini?')">
                <i class="ri-check-line"></i>
                Konfirmasi Pembayaran
            </button>
        </form>
        <button onclick="openRejectModal()"
            class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
            <i class="ri-close-line"></i>
            Tolak Pembayaran
        </button>
        @endif
        {{-- <button class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700">
            <i class="ri-download-line"></i>
            Download Invoice
        </button> --}}
    </div>
</div>
<x-page-desc title="Detail Pembayaran - {{ $payment->transaction_id }}"></x-page-desc>

<!-- Transaction Status -->
<div class="bg-white rounded-lg border border-border p-6 mt-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $payment->transaction_id }}</h2>
            <p class="text-gray-600">{{ $payment->created_at ? $payment->created_at->format('d F Y, H:i') : '-' }} WIB
            </p>
        </div>
        <div class="text-right">
            @if($payment->status == 'success')
            <div class="flex items-center gap-2 text-green-600">
                <i class="ri-check-circle-fill text-2xl"></i>
                <div>
                    <p class="text-lg font-bold">Pembayaran Berhasil</p>
                    <p class="text-sm">{{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('d M Y,
                        H:i') : '-' }}</p>
                </div>
            </div>
            @elseif($payment->status == 'pending')
            <div class="flex items-center gap-2 text-yellow-600">
                <i class="ri-time-fill text-2xl"></i>
                <div>
                    <p class="text-lg font-bold">Menunggu Konfirmasi</p>
                    <p class="text-sm">Dibuat {{ $payment->created_at ? $payment->created_at->format('H:i') : '-' }}</p>
                </div>
            </div>
            @else
            <div class="flex items-center gap-2 text-red-600">
                <i class="ri-close-circle-fill text-2xl"></i>
                <div>
                    <p class="text-lg font-bold">Pembayaran {{ ucfirst($payment->status) }}</p>
                    <p class="text-sm">{{ $payment->updated_at ? $payment->updated_at->format('d M Y, H:i') : '-' }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-2 gap-6 mt-6">
    <!-- Transaction Details -->
    <div class="bg-white border border-border rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Detail Transaksi</h3>
        <div class="space-y-4">
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600">Produk:</span>
                <span class="font-medium">{{ $payment->package->name ?? 'Paket Tidak Ditemukan' }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600">Harga Paket:</span>
                <span class="font-medium">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600">Biaya Admin:</span>
                <span class="font-medium">Rp {{ number_format($payment->admin_fee, 0, ',', '.') }}</span>
            </div>
            @if($payment->unique_code)
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600">Kode Unik:</span>
                <span class="font-medium">Rp {{ number_format($payment->unique_code, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="flex justify-between py-3 border-t-2 border-gray-200">
                <span class="text-lg font-semibold text-gray-800">Total Bayar:</span>
                <span class="text-lg font-bold text-primary">Rp {{ number_format($payment->total_amount, 0, ',', '.')
                    }}</span>
            </div>
        </div>
    </div>

    <!-- Customer Info -->
    <div class="bg-white border border-border rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Pembeli</h3>

        <div class="flex items-center gap-4 mb-4">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($payment->user->name ?? 'Unknown') }}&background=6366f1&color=fff&size=60"
                class="w-15 h-15 rounded-full">
            <div>
                <h4 class="text-lg font-bold text-gray-800">{{ $payment->user->name ?? 'User Tidak Ditemukan' }}</h4>
                <p class="text-gray-600">{{ $payment->user->email ?? '-' }}</p>
                <p class="text-gray-600">{{ $payment->user->phone ?? '-' }}</p>
            </div>
        </div>

        <div class="space-y-3 pt-4 border-t border-gray-100">
            <div class="flex justify-between">
                <span class="text-gray-600">User ID:</span>
                <span class="font-medium">{{ $payment->user->id ?? '-' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Bergabung:</span>
                <span class="font-medium">{{ $payment->user && $payment->user->created_at ?
                    $payment->user->created_at->format('d M Y') : '-' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Total Transaksi:</span>
                <span class="font-medium">{{ $userTotalTransactions }} transaksi</span>
            </div>
        </div>
    </div>
</div>

<!-- Payment Method & Proof -->
<div class="grid grid-cols-2 gap-6 mt-6">
    <div class="bg-white border border-border rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Metode Pembayaran</h3>

        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
            <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                <i class="ri-bank-line text-2xl text-primary"></i>
            </div>
            <div>
                <p class="font-semibold text-gray-800">{{ ucfirst($payment->payment_method) }}</p>
                <p class="text-gray-600">{{ $payment->transaction_id }}</p>
                <p class="text-sm text-gray-500">Via {{ ucfirst($payment->payment_method) }}</p>
            </div>
        </div>

        <div class="mt-4 space-y-3">
            <div class="flex justify-between">
                <span class="text-gray-600">Waktu Transaksi:</span>
                <span class="font-medium">{{ $payment->created_at ? $payment->created_at->format('d M Y, H:i') : '-'
                    }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Nominal:</span>
                <span class="font-medium">Rp {{ number_format($payment->total_amount, 0, ',', '.') }}</span>
            </div>
            @if($payment->status !== 'failed')
            <div class="flex justify-between">
                <span class="text-gray-600">Referensi:</span>
                <span class="font-medium font-mono">{{ $payment->transaction_id }}</span>
            </div>
            @endif
        </div>
    </div>

    <div class="bg-white border border-border rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Status & Bukti Pembayaran</h3>

        @if($payment->status == 'failed')
        <div class="text-center py-8">
            <i class="ri-close-circle-line text-6xl text-red-300 mb-4"></i>
            <p class="text-gray-500">Pembayaran gagal</p>
            <p class="text-sm text-red-600 mt-2">{{ $payment->notes ?? 'Transaksi gagal atau dibatalkan' }}</p>
        </div>
        @else
        @php
            $proofPath = $paymentDetails['proof_path'] ?? null;
            $proofName = $paymentDetails['proof_name'] ?? null;
            $proofUrl = $proofPath ? Storage::url($proofPath) : null;
            $proofExt = $proofPath ? strtolower(pathinfo($proofPath, PATHINFO_EXTENSION)) : null;
            $isProofImage = $proofExt && in_array($proofExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
            $isProofPdf = $proofExt === 'pdf';
        @endphp
        <div class="space-y-4">
            <div class="flex justify-between">
                <span class="text-gray-600">Status:</span>
                <span
                    class="font-medium {{ $payment->status === 'success' ? 'text-green-600' : ($payment->status === 'pending' ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ ucfirst($payment->status) }}
                </span>
            </div>
            @if($payment->paid_at)
            <div class="flex justify-between">
                <span class="text-gray-600">Dibayar:</span>
                <span class="font-medium">{{ \Carbon\Carbon::parse($payment->paid_at)->format('d M Y, H:i') }}</span>
            </div>
            @endif
            @if($paymentDetails)
            <div class="p-3 bg-gray-50 rounded-lg">
                <p class="text-sm font-semibold text-gray-700">Detail Pembayaran:</p>
                <div class="text-xs text-gray-600 mt-1 space-y-1">
                    @if(isset($paymentDetails['invoice_id']))
                    <p>Invoice ID: {{ $paymentDetails['invoice_id'] }}</p>
                    @endif
                    @if(isset($paymentDetails['external_id']))
                    <p>External ID: {{ $paymentDetails['external_id'] }}</p>
                    @endif
                </div>
            </div>
            @endif

            <div class="p-4 border border-dashed border-gray-200 rounded-lg bg-white">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-semibold text-gray-800">Bukti Pembayaran</p>
                    @if($proofUrl)
                    <div class="flex items-center gap-2">
                        <a href="{{ $proofUrl }}" target="_blank"
                            class="text-xs px-3 py-1 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50">
                            Buka
                        </a>
                        <a href="{{ $proofUrl }}" download
                            class="text-xs px-3 py-1 rounded-lg bg-primary text-white hover:bg-primary/90">
                            Unduh
                        </a>
                    </div>
                    @endif
                </div>
                @if($proofUrl)
                    @if($isProofImage)
                        <a href="{{ $proofUrl }}" target="_blank" class="block">
                            <img src="{{ $proofUrl }}" alt="Bukti pembayaran"
                                class="w-full max-h-64 object-contain rounded-lg border border-gray-100 bg-white">
                        </a>
                    @elseif($isProofPdf)
                        <div class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 px-3 py-3">
                            <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center">
                                <i class="ri-file-pdf-line text-xl text-red-500"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ $proofName ?? 'Bukti pembayaran.pdf' }}</p>
                                <p class="text-xs text-gray-500">PDF</p>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 px-3 py-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                                <i class="ri-file-line text-xl text-blue-500"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ $proofName ?? 'Bukti pembayaran' }}</p>
                                <p class="text-xs text-gray-500">File terlampir</p>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-sm text-gray-500">
                        Belum ada bukti pembayaran yang diunggah.
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Notes -->
@if($payment->notes)
<div class="bg-white border border-border rounded-lg p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Catatan</h3>
    <p class="text-gray-700">{{ $payment->notes }}</p>
</div>
@endif

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Tolak Pembayaran</h3>
            <form action="{{ route('admin.pembayaran.reject', $payment->payment_id) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Alasan Penolakan</label>
                    <textarea name="rejection_reason" rows="3"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Masukkan alasan penolakan..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeRejectModal()"
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Tolak Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRejectModal();
    }
});
</script>
@endsection
