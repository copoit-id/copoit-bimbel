@extends('user.layout.new-user')

@section('title', 'Riwayat Pembelian')

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
@endphp

<!-- Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('user.dashboard.index') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Riwayat Pembelian</h1>
        <p class="text-gray-500 text-sm">Daftar paket yang pernah kamu beli</p>
    </div>
</div>

<!-- Tabs -->
<div class="flex gap-2 mb-6 overflow-x-auto pb-2">
    <a href="{{ route('user.package.riwayatPembelian') }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap tab-active" style="background-color: {{ $primaryColor }}; color: white;">
        Semua Riwayat
    </a>
    <a href="{{ route('user.package.riwayatPembelianAktif') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        Paket Aktif
    </a>
</div>

@if($payments->count() > 0)
<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
    <div class="divide-y divide-gray-100">
        @foreach($payments as $payment)
        <div class="p-5 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white" style="background-color: {{ $primaryColor }}">
                    <i class="ri-shopping-bag-line text-xl"></i>
                </div>
                <div>
                    <h4 class="font-medium text-gray-800">{{ $payment->package->name ?? 'Paket' }}</h4>
                    <p class="text-sm text-gray-400">{{ $payment->created_at->format('d M Y, H:i') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $payment->transaction_id }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="font-semibold text-gray-800">{{ $payment->formatted_amount }}</p>
                <span class="px-2.5 py-1 text-xs rounded-full font-medium mt-1 inline-block
                    @if($payment->status == 'success') bg-green-100 text-green-700
                    @elseif($payment->status == 'pending') bg-yellow-100 text-yellow-700
                    @else bg-red-100 text-red-700 @endif">
                    {{ ucfirst($payment->status) }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
</div>

<div class="mt-6">
    {{ $payments->links() }}
</div>
@else
<div class="text-center py-16">
    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="ri-shopping-bag-line text-3xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada riwayat</h3>
    <p class="text-gray-400 text-sm mb-4">Kamu belum melakukan pembelian apapun.</p>
    <a href="{{ route('user.package.index') }}" class="inline-flex items-center px-6 py-3 text-white rounded-xl font-medium hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
        <i class="ri-store-3-line mr-2"></i>Lihat Paket
    </a>
</div>
@endif
@endsection
