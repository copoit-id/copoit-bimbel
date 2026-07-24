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
        <p class="text-sm text-gray-500">Masukkan satu transaksi pembayaran manual untuk satu peserta dan paket yang dibelinya.</p>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <form method="POST" action="{{ route('admin.pembayaran.manual') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div>
                <x-form.searchable-user-select
                    :users="$users"
                    :selected="old('user_id')"
                    label="Peserta"
                    placeholder="Pilih peserta"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Paket yang Dibeli</label>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Total Tagihan Paket</label>
                <input id="manualAmount" type="number" name="amount" min="0" value="{{ old('amount') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Metode Pembayaran</label>
                <select id="manualPaymentMethod" name="payment_method" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @foreach(['manual' => 'Manual', 'cash' => 'Tunai', 'transfer' => 'Transfer Bank', 'qris' => 'QRIS'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('payment_method', 'manual') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="manualPaymentStatus" class="block text-sm font-medium text-gray-700 mb-1">Status Pembayaran</label>
                <select id="manualPaymentStatus" name="payment_status_choice" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="paid" @selected(old('payment_status_choice', 'paid') === 'paid')>Lunas</option>
                    <option value="unpaid" @selected(old('payment_status_choice') === 'unpaid')>Belum Lunas</option>
                </select>
            </div>
            <div id="initialPaymentField" class="hidden">
                <label for="initialPaymentAmount" class="block text-sm font-medium text-gray-700 mb-1">Uang Diterima Sekarang</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">Rp</span>
                    <input id="initialPaymentAmount" type="number" name="initial_payment_amount" min="0" value="{{ old('initial_payment_amount', 0) }}" class="w-full border border-gray-300 rounded-lg py-2 pl-10 pr-3 text-sm" placeholder="0" />
                </div>
                @error('initial_payment_amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const amountInput = document.getElementById('manualAmount');
    const methodInput = document.getElementById('manualPaymentMethod');
    const paymentStatusInput = document.getElementById('manualPaymentStatus');
    const initialPaymentInput = document.getElementById('initialPaymentAmount');
    const initialPaymentField = document.getElementById('initialPaymentField');
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
        const total = amount + (useUniqueCode ? uniqueCode : 0);

        uniqueSummary?.classList.toggle('hidden', !useUniqueCode);
        if (totalDisplay) {
            totalDisplay.textContent = 'Rp ' + formatNumber(total);
        }
        if (initialPaymentInput) {
            initialPaymentInput.max = total > 0 ? total : '';
        }
        if (initialPaymentField && initialPaymentInput) {
            const isUnpaid = paymentStatusInput?.value === 'unpaid';
            initialPaymentField.classList.toggle('hidden', !isUnpaid);
            initialPaymentInput.disabled = !isUnpaid;
        }
    }

    amountInput?.addEventListener('input', refreshTotal);
    methodInput?.addEventListener('input', refreshTotal);
    initialPaymentInput?.addEventListener('input', refreshTotal);
    paymentStatusInput?.addEventListener('change', refreshTotal);
    refreshTotal();
});
</script>
@endsection
