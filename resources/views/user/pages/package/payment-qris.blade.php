@extends('user.layout.new-user')

@section('title', 'Pembayaran QRIS')

@section('content')
@php
    $primaryColor = $clientBranding['primary_color'] ?? '#10b981';
    $expiresAt = !empty($paymentDetails['expires_at']) ? \Carbon\Carbon::parse($paymentDetails['expires_at']) : null;
    $isExpired = in_array($payment->status, [\App\Models\Payment::STATUS_EXPIRED, \App\Models\IndividualPurchase::STATUS_REJECTED], true)
        || ($expiresAt && $expiresAt->isPast());
    $paymentTitle = $paymentTitle ?? ($payment->package->name ?? 'Paket');
@endphp

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Pembayaran QRIS</h1>
            <p class="text-sm text-gray-500">Scan QR berikut untuk menyelesaikan pembayaran.</p>
        </div>
        <a href="{{ route('user.package.riwayatPembelian') }}" class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50">
            Riwayat
        </a>
    </div>

    @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[320px_1fr]">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="mx-auto flex h-[280px] w-[280px] items-center justify-center rounded-xl border border-gray-100 bg-white">
                <img src="data:image/png;base64,{{ $qrisImage }}" alt="QRIS pembayaran" class="h-[260px] w-[260px]">
            </div>
            @if($isExpired)
                <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    QRIS sudah kedaluwarsa. Buat pembayaran ulang dari halaman paket.
                </div>
            @else
                <button type="button" id="checkQrisButton"
                    class="mt-4 w-full rounded-xl px-4 py-3 text-sm font-semibold text-white hover:opacity-90"
                    style="background-color: {{ $primaryColor }}">
                    Cek Status Pembayaran
                </button>
                <p id="checkQrisMessage" class="mt-3 text-center text-sm text-gray-500"></p>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">{{ $paymentTitle }}</h2>
            <dl class="mt-5 space-y-4 text-sm">
                <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-3">
                    <dt class="text-gray-500">Nominal</dt>
                    <dd class="font-bold text-gray-900">Rp {{ number_format((float) $payment->total_amount, 0, ',', '.') }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-3">
                    <dt class="text-gray-500">Status</dt>
                    <dd class="font-semibold capitalize {{ $payment->status === 'success' ? 'text-green-600' : ($payment->status === 'expired' ? 'text-red-600' : 'text-amber-600') }}">
                        {{ $payment->status }}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-3">
                    <dt class="text-gray-500">Invoice QRIS</dt>
                    <dd class="font-mono text-gray-900">{{ $paymentDetails['qris_invoiceid'] ?? '-' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-3">
                    <dt class="text-gray-500">NMID</dt>
                    <dd class="font-mono text-gray-900">{{ $paymentDetails['qris_nmid'] ?? '-' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-gray-500">Berlaku sampai</dt>
                    <dd class="font-medium text-gray-900">{{ $expiresAt ? $expiresAt->format('d M Y H:i') . ' WIB' : '-' }}</dd>
                </div>
            </dl>
            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Setelah membayar, tekan tombol cek status. Sistem tidak melakukan pengecekan otomatis terus-menerus agar API QRIS tidak terblokir.
            </div>
            <x-legal-links class="mt-4" />
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('checkQrisButton');
    const message = document.getElementById('checkQrisMessage');

    button?.addEventListener('click', async () => {
        button.disabled = true;
        button.textContent = 'Mengecek...';
        if (message) message.textContent = 'Mohon tunggu, pengecekan bisa memakan waktu beberapa detik.';

        try {
            const response = await fetch('{{ route('user.package.payment.qris.check', $payment->transaction_id) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();

            if (data.paid && data.redirect_url) {
                if (message) message.textContent = data.message || 'Pembayaran berhasil.';
                window.location.href = data.redirect_url;
                return;
            }

            if (message) message.textContent = data.message || 'Pembayaran belum ditemukan.';
            if (data.expired) window.location.reload();
        } catch (error) {
            if (message) message.textContent = 'Gagal mengecek status. Silakan coba lagi.';
        } finally {
            button.disabled = false;
            button.textContent = 'Cek Status Pembayaran';
        }
    });
});
</script>
@endpush
