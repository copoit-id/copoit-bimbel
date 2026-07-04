@extends('admin.layout.admin')
@section('title', 'Tambah Pembayaran Manual')
@section('content')
@php
$paymentUniqueCodeEnabled = (bool) ($paymentUniqueCodeEnabled ?? false);
$manualPaymentUniqueCode = (int) ($manualPaymentUniqueCode ?? 0);
@endphp
<div class="space-y-6">
    <div>
        <a href="{{ route('admin.pembayaran.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Tambah Pembayaran Manual</h1>
        <p class="text-sm text-gray-500">Masukkan pembayaran yang dibuat secara manual.</p>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <form method="POST" action="{{ route('admin.pembayaran.manual') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email User</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Paket</label>
                <select name="package_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Pilih Paket</option>
                    @foreach($packages as $package)
                        <option value="{{ $package->package_id }}" {{ old('package_id') == $package->package_id ? 'selected' : '' }}>
                            {{ $package->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Dasar</label>
                <input id="manualAmount" type="number" name="amount" min="0" value="{{ old('amount') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Metode</label>
                <input id="manualPaymentMethod" type="text" name="payment_method" value="{{ old('payment_method', 'manual') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div class="md:col-span-2 {{ $paymentUniqueCodeEnabled ? '' : 'hidden' }}" id="uniqueCodeSummary">
                <input type="hidden" name="payment_unique_code" value="{{ old('payment_unique_code', $manualPaymentUniqueCode) }}">
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-700">
                    <div class="flex justify-between gap-4">
                        <span>Kode Unik</span>
                        <span class="font-semibold" id="uniqueCodeDisplay">{{ str_pad((string) old('payment_unique_code', $manualPaymentUniqueCode), 3, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 mt-2">
                        <span>Total Tagihan</span>
                        <span class="font-bold text-gray-900" id="totalAmountDisplay">Rp 0</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Kode unik hanya dipakai jika metode pembayaran bernilai manual dan jumlah dasar lebih dari 0.</p>
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <input type="text" name="notes" value="{{ old('notes') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>
@if($paymentUniqueCodeEnabled)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const amountInput = document.getElementById('manualAmount');
    const methodInput = document.getElementById('manualPaymentMethod');
    const uniqueSummary = document.getElementById('uniqueCodeSummary');
    const uniqueCode = Number(@json((int) old('payment_unique_code', $manualPaymentUniqueCode)));
    const totalDisplay = document.getElementById('totalAmountDisplay');

    function formatNumber(value) {
        return new Intl.NumberFormat('id-ID').format(value);
    }

    function refreshTotal() {
        const amount = Number(amountInput?.value || 0);
        const method = String(methodInput?.value || '').toLowerCase();
        const useUniqueCode = method === 'manual' && amount > 0;

        uniqueSummary?.classList.toggle('hidden', !useUniqueCode);
        if (totalDisplay) {
            totalDisplay.textContent = 'Rp ' + formatNumber(amount + (useUniqueCode ? uniqueCode : 0));
        }
    }

    amountInput?.addEventListener('input', refreshTotal);
    methodInput?.addEventListener('input', refreshTotal);
    refreshTotal();
});
</script>
@endif
@endsection
