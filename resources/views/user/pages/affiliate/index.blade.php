@extends('user.layout.new-user')
@section('title', 'Affiliate')
@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
@endphp

<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('user.dashboard.index') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Affiliate</h1>
        <p class="text-gray-500 text-sm">Bagikan link undangan dan pantau komisi kamu.</p>
    </div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">Link Undangan</label>
    <div class="flex flex-col md:flex-row gap-3">
        <input id="referralLink" type="text" readonly value="{{ $referralLink }}" class="flex-1 px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-700">
        <button type="button" onclick="copyReferralLink(this)" class="px-5 py-3 rounded-xl text-white font-medium transition-all duration-300" style="background-color: {{ $primaryColor }}">
            <i class="ri-file-copy-line mr-1"></i>Salin
        </button>
    </div>
    <p class="text-sm text-gray-500 mt-3">Kode affiliate kamu: <span class="font-semibold text-gray-800">{{ $code }}</span></p>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Total Referral</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $summary['referrals_count'] }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Pending</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">Rp {{ number_format((float) $summary['pending'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Disetujui</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">Rp {{ number_format((float) $summary['approved'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Dibayar</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">Rp {{ number_format((float) $summary['paid'], 0, ',', '.') }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="font-bold text-gray-800 mb-4">Riwayat Komisi</h2>
        <div class="space-y-3">
            @forelse($commissions as $commission)
            <div class="flex items-center justify-between gap-4 p-4 bg-gray-50 rounded-xl">
                <div>
                    <p class="font-semibold text-gray-800">{{ $commission->package->name ?? '-' }}</p>
                    <p class="text-sm text-gray-500">{{ $commission->referredUser->name ?? '-' }} · {{ $commission->created_at->format('d M Y H:i') }}</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-gray-800">Rp {{ number_format((float) $commission->commission_amount, 0, ',', '.') }}</p>
                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ ucfirst($commission->status) }}</span>
                </div>
            </div>
            @empty
            <p class="text-center text-gray-500 py-8">Belum ada komisi.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $commissions->links() }}</div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="font-bold text-gray-800 mb-4">Peserta Diundang</h2>
        <div class="space-y-3">
            @forelse($referrals as $referral)
            <div class="p-3 bg-gray-50 rounded-xl">
                <p class="font-semibold text-gray-800">{{ $referral->name }}</p>
                <p class="text-sm text-gray-500">{{ $referral->email }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $referral->referred_at ? \Carbon\Carbon::parse($referral->referred_at)->format('d M Y H:i') : '-' }}</p>
            </div>
            @empty
            <p class="text-center text-gray-500 py-8">Belum ada referral.</p>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyReferralLink(button) {
    const input = document.getElementById('referralLink');
    input.select();
    input.setSelectionRange(0, 99999);
    
    try {
        navigator.clipboard?.writeText(input.value);
    } catch (err) {
        console.error('Failed to copy: ', err);
    }
    
    if (button) {
        const originalHTML = button.innerHTML;
        const originalBg = button.style.backgroundColor;
        
        button.innerHTML = '<i class="ri-checkbox-circle-line mr-1"></i>Tersalin!';
        button.style.backgroundColor = '#10b981'; // Green color for success state
        button.disabled = true;
        
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.style.backgroundColor = originalBg;
            button.disabled = false;
        }, 2000);
    }
}
</script>
@endpush
@endsection
