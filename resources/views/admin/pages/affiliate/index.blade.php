@extends('admin.layout.admin')
@section('title', 'Affiliate')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="" title="Affiliate" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="Affiliate" description="Atur komisi affiliate dan pantau komisi peserta." />

@if(session('success'))
<div class="mt-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mt-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
    @foreach(['pending' => 'Pending', 'approved' => 'Disetujui', 'paid' => 'Dibayar', 'total' => 'Total'] as $key => $label)
    <div class="bg-white border border-border rounded-lg p-4">
        <p class="text-sm text-gray-500">{{ $label }}</p>
        <p class="text-xl font-bold text-gray-800 mt-1">Rp {{ number_format((float) $summary[$key], 0, ',', '.') }}</p>
    </div>
    @endforeach
</div>

<form action="{{ route('admin.affiliate.settings.update') }}" method="POST" class="bg-white p-6 rounded-lg border border-border mt-6">
    @csrf
    @method('PUT')
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Pengaturan Affiliate</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <label class="flex items-start gap-3 p-4 border border-gray-200 rounded-lg md:col-span-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="mt-1" @checked(old('is_active', $setting->is_active))>
            <span>
                <span class="block font-medium text-gray-800">Affiliate aktif</span>
                <span class="block text-sm text-gray-500">Komisi akan dicatat saat pembayaran paket berhasil.</span>
            </span>
        </label>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Komisi</label>
            <select name="commission_type" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                <option value="percent" @selected(old('commission_type', $setting->commission_type) === 'percent')>Persen (%)</option>
                <option value="fixed" @selected(old('commission_type', $setting->commission_type) === 'fixed')>Nominal Rupiah</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nilai Komisi</label>
            <input type="number" name="commission_value" value="{{ old('commission_value', (int) $setting->commission_value) }}" min="0" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
        </div>
        <label class="flex items-start gap-3 p-4 border border-gray-200 rounded-lg md:col-span-2">
            <input type="hidden" name="invitee_discount_enabled" value="0">
            <input type="checkbox" name="invitee_discount_enabled" value="1" class="mt-1" @checked(old('invitee_discount_enabled', $setting->invitee_discount_enabled))>
            <span>
                <span class="block font-medium text-gray-800">Diskon untuk peserta yang diundang</span>
                <span class="block text-sm text-gray-500">Diskon otomatis dipakai pada pembelian paket pertama jika peserta tidak memakai kode diskon lain.</span>
            </span>
        </label>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Diskon Undangan</label>
            <select name="invitee_discount_type" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                <option value="percent" @selected(old('invitee_discount_type', $setting->invitee_discount_type) === 'percent')>Persen (%)</option>
                <option value="fixed" @selected(old('invitee_discount_type', $setting->invitee_discount_type) === 'fixed')>Nominal Rupiah</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nilai Diskon Undangan</label>
            <input type="number" name="invitee_discount_value" value="{{ old('invitee_discount_value', (int) $setting->invitee_discount_value) }}" min="0" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Maksimal Diskon Undangan</label>
            <input type="number" name="invitee_max_discount_amount" value="{{ old('invitee_max_discount_amount', $setting->invitee_max_discount_amount !== null ? (int) $setting->invitee_max_discount_amount : '') }}" min="0" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg" placeholder="Opsional">
        </div>
    </div>
    <div class="flex justify-end mt-6">
        <button type="submit" class="px-4 py-2.5 bg-primary text-white rounded-lg hover:bg-primary/90">Simpan Pengaturan</button>
    </div>
</form>

<div class="bg-white p-6 rounded-lg border border-border mt-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Daftar Komisi</h3>
    <div class="relative overflow-x-auto">
        <table class="w-full text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3">Affiliate</th>
                    <th class="px-6 py-3">Peserta</th>
                    <th class="px-6 py-3">Paket</th>
                    <th class="px-6 py-3">Komisi</th>
                    <th class="px-6 py-3 text-center">Status</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($commissions as $commission)
                <tr class="bg-white border-b border-dashed border-gray-200">
                    <td class="px-6 py-4">
                        <p class="font-semibold text-gray-900">{{ $commission->affiliateUser->name ?? '-' }}</p>
                        <p class="text-sm text-gray-500">{{ $commission->affiliateUser->email ?? '-' }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <p class="font-semibold text-gray-900">{{ $commission->referredUser->name ?? '-' }}</p>
                        <p class="text-sm text-gray-500">{{ $commission->referredUser->email ?? '-' }}</p>
                    </td>
                    <td class="px-6 py-4">{{ $commission->package->name ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <p class="font-semibold text-gray-900">Rp {{ number_format((float) $commission->commission_amount, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500">Base Rp {{ number_format((float) $commission->base_amount, 0, ',', '.') }}</p>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">{{ ucfirst($commission->status) }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center gap-2">
                            @if($commission->status === \App\Models\AffiliateCommission::STATUS_PENDING)
                            <form method="POST" action="{{ route('admin.affiliate.commissions.approve', $commission) }}">
                                @csrf
                                <button class="px-3 py-1 text-xs font-semibold rounded-full border border-primary text-primary hover:bg-primary hover:text-white">Setujui</button>
                            </form>
                            @endif
                            @if(in_array($commission->status, [\App\Models\AffiliateCommission::STATUS_PENDING, \App\Models\AffiliateCommission::STATUS_APPROVED], true))
                            <form method="POST" action="{{ route('admin.affiliate.commissions.pay', $commission) }}">
                                @csrf
                                <button class="px-3 py-1 text-xs font-semibold rounded-full border border-green-200 text-green-700 hover:bg-green-50">Bayar</button>
                            </form>
                            <form method="POST" action="{{ route('admin.affiliate.commissions.cancel', $commission) }}">
                                @csrf
                                <button class="px-3 py-1 text-xs font-semibold rounded-full border border-red-200 text-red-600 hover:bg-red-50">Batal</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">Belum ada komisi affiliate.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $commissions->links() }}</div>
</div>

@endsection
