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
                    <p class="text-lg font-bold">Lunas</p>
                    <p class="text-sm">{{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('d M Y,
                        H:i') : '-' }}</p>
                </div>
            </div>
            @elseif($payment->status == 'pending')
            <div class="flex items-center gap-2 text-yellow-600">
                <i class="ri-time-fill text-2xl"></i>
                <div>
                    <p class="text-lg font-bold">Belum Lunas</p>
                    <p class="text-sm">Terbayar Rp {{ number_format($payment->paid_amount, 0, ',', '.') }} · Sisa Rp {{ number_format($payment->remaining_amount, 0, ',', '.') }}</p>
                </div>
            </div>
            @elseif($payment->status == 'partial')
            <div class="flex items-center gap-2 text-blue-600">
                <i class="ri-hand-coin-fill text-2xl"></i>
                <div>
                    <p class="text-lg font-bold">Belum Lunas</p>
                    <p class="text-sm">Terbayar Rp {{ number_format($payment->paid_amount, 0, ',', '.') }} · Sisa Rp {{ number_format($payment->remaining_amount, 0, ',', '.') }}</p>
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
                <span class="font-medium">Rp {{ number_format($payment->original_amount ?? $payment->amount, 0, ',', '.') }}</span>
            </div>
            @if(($payment->discount_amount ?? 0) > 0)
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600">Diskon{{ $payment->discount_code ? ' (' . $payment->discount_code . ')' : '' }}:</span>
                <span class="font-medium text-green-600">- Rp {{ number_format($payment->discount_amount, 0, ',', '.') }}</span>
            </div>
            @endif
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
            @if($payment->isManualEntry() || $payment->installments->isNotEmpty())
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600">Total Terbayar:</span>
                <span class="font-semibold text-green-600">Rp {{ number_format($payment->paid_amount, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between py-2">
                <span class="text-gray-600">Sisa Pembayaran:</span>
                <span class="font-semibold {{ $payment->remaining_amount > 0 ? 'text-blue-600' : 'text-green-600' }}">Rp {{ number_format($payment->remaining_amount, 0, ',', '.') }}</span>
            </div>
            @endif
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

@if($payment->isManualEntry() && in_array($payment->status, ['pending', 'partial'], true))
<div id="cicilan" class="mt-6 rounded-lg border border-blue-200 bg-blue-50/40 p-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Catat Cicilan Paket</h3>
            <p class="mt-1 text-sm text-gray-600">Akses paket baru aktif otomatis setelah seluruh pembayaran lunas.</p>
        </div>
        <div class="rounded-lg bg-white px-3 py-2 text-sm shadow-sm">
            <p class="text-xs text-gray-500">Sisa pembayaran</p>
            <p class="font-bold text-primary">Rp {{ number_format($payment->remaining_amount, 0, ',', '.') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.pembayaran.installments.store', $payment) }}" class="mt-5 grid gap-4 md:grid-cols-3">
        @csrf
        <div>
            <label for="installment_amount" class="mb-2 block text-sm font-semibold text-gray-700">Nominal diterima</label>
            <div class="relative"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">Rp</span><input id="installment_amount" type="number" name="amount" min="1" max="{{ $payment->remaining_amount }}" value="{{ old('amount', $payment->remaining_amount) }}" required class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"></div>
            @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="installment_method" class="mb-2 block text-sm font-semibold text-gray-700">Metode</label>
            <select id="installment_method" name="payment_method" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                <option value="cash">Tunai</option>
                <option value="transfer">Transfer bank</option>
                <option value="qris">QRIS</option>
                <option value="manual">Manual</option>
            </select>
        </div>
        <div>
            <label for="installment_notes" class="mb-2 block text-sm font-semibold text-gray-700">Catatan</label>
            <input id="installment_notes" name="notes" value="{{ old('notes') }}" maxlength="1000" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Opsional">
        </div>
        <div class="md:col-span-3 flex justify-end">
            <button class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary/90">Simpan Cicilan</button>
        </div>
    </form>

    @if($payment->installments->isNotEmpty())
    <div class="mt-5 border-t border-blue-100 pt-4">
        <h4 class="mb-3 text-sm font-semibold text-gray-800">Riwayat Cicilan</h4>
        <div class="space-y-2">
            @foreach($payment->installments as $installment)
            <div class="flex items-center justify-between gap-4 rounded-lg border border-white bg-white px-3 py-2 text-sm">
                <div>
                    <p class="font-medium text-gray-800">{{ $installment->receipt_number }} · {{ strtoupper($installment->payment_method) }}</p>
                    <p class="text-xs text-gray-500">{{ $installment->paid_at?->format('d M Y H:i') }} · {{ $installment->paidBy?->name ?? 'Admin' }}</p>
                    @if($installment->notes)<p class="mt-1 text-xs text-gray-500">{{ $installment->notes }}</p>@endif
                </div>
                <p class="shrink-0 font-semibold text-gray-900">Rp {{ number_format($installment->amount, 0, ',', '.') }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@elseif($payment->installments->isNotEmpty())
<div class="mt-6 rounded-lg border border-gray-200 bg-white p-6">
    <h3 class="mb-3 text-lg font-semibold text-gray-900">Riwayat Pembayaran</h3>
    <div class="space-y-2">
        @foreach($payment->installments as $installment)
        <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-100 px-3 py-2 text-sm">
            <span>{{ $installment->receipt_number }} · {{ $installment->paid_at?->format('d M Y H:i') }}</span>
            <span class="font-semibold">Rp {{ number_format($installment->amount, 0, ',', '.') }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

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
            @if($payment->gatewayReference())
            <div class="flex justify-between gap-4">
                <span class="text-gray-600">Ref Gateway:</span>
                <span class="font-medium font-mono text-xs text-right">{{ $payment->gatewayReference() }}</span>
            </div>
            @endif
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
                    class="font-medium {{ $payment->status === 'success' ? 'text-green-600' : 'text-primary' }}">
                    {{ $payment->status === 'success' ? 'Lunas' : 'Belum Lunas' }}
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
                    @if(isset($paymentDetails['qris_invoiceid']))
                    <p>QRIS Invoice ID: {{ $paymentDetails['qris_invoiceid'] }}</p>
                    @endif
                    @if(isset($paymentDetails['qris_nmid']))
                    <p>QRIS NMID: {{ $paymentDetails['qris_nmid'] }}</p>
                    @endif
                    @if(isset($paymentDetails['expires_at']))
                    <p>Expired: {{ \Carbon\Carbon::parse($paymentDetails['expires_at'])->format('d M Y, H:i') }} WIB</p>
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
