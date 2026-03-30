@extends('user.layout.new-user')

@section('title', 'Paket Aktif')

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
        <h1 class="text-2xl font-bold text-gray-800">Paket Aktif</h1>
        <p class="text-gray-500 text-sm">Paket yang sedang aktif dan bisa kamu akses</p>
    </div>
</div>

<!-- Tabs -->
<div class="flex gap-2 mb-6 overflow-x-auto pb-2">
    <a href="{{ route('user.package.riwayatPembelian') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        Semua Riwayat
    </a>
    <a href="{{ route('user.package.riwayatPembelianAktif') }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap" style="background-color: {{ $primaryColor }}; color: white;">
        Paket Aktif
    </a>
</div>

@if($activePackages->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($activePackages as $access)
    @php
    $package = $access->package;
    $endDate = $access->end_date ? \Carbon\Carbon::parse($access->end_date) : null;
    $daysLeft = $endDate ? now()->diffInDays($endDate, false) : null;
    @endphp
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all">
        <!-- Package Image -->
        <div class="h-40 relative overflow-hidden" style="background: linear-gradient(135deg, {{ $primaryColor }}20 0%, {{ $primaryColor }}10 100%);">
            @if($package->image)
            <img src="{{ Storage::url($package->image) }}" alt="{{ $package->name }}" class="w-full h-full object-cover">
            @else
            <div class="w-full h-full flex items-center justify-center">
                <i class="ri-book-3-line text-6xl" style="color: {{ $primaryColor }}40"></i>
            </div>
            @endif
            
            @if($daysLeft !== null && $daysLeft <= 7)
            <div class="absolute top-3 right-3 px-3 py-1 rounded-full text-xs font-semibold bg-red-500 text-white">
                {{ $daysLeft }} hari lagi
            </div>
            @endif
        </div>
        
        <!-- Content -->
        <div class="p-5">
            <h3 class="font-bold text-lg text-gray-800 mb-2">{{ $package->name }}</h3>
            <p class="text-gray-500 text-sm mb-4">{{ $package->description ?? 'Paket belajar lengkap.' }}</p>
            
            <!-- Info -->
            <div class="space-y-2 mb-4 text-sm">
                <div class="flex items-center gap-2 text-gray-500">
                    <i class="ri-calendar-line" style="color: {{ $primaryColor }}"></i>
                    <span>Aktif sampai: {{ $endDate ? $endDate->format('d M Y') : 'Selamanya' }}</span>
                </div>
                @if($access->payment_amount > 0)
                <div class="flex items-center gap-2 text-gray-500">
                    <i class="ri-money-rupiah-circle-line" style="color: {{ $primaryColor }}"></i>
                    <span>Rp {{ number_format($access->payment_amount, 0, ',', '.') }}</span>
                </div>
                @endif
            </div>
            
            <!-- Action -->
            <a href="{{ route('user.package.show', $package->package_id) }}" 
               class="block w-full py-2.5 rounded-xl text-center font-medium text-white hover:opacity-90 transition-opacity"
               style="background-color: {{ $primaryColor }}">
                <i class="ri-play-circle-line mr-2"></i>Mulai Belajar
            </a>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="text-center py-16">
    <div class="w-24 h-24 rounded-full mx-auto mb-4 flex items-center justify-center" style="background-color: {{ $primaryColor }}10">
        <i class="ri-inbox-line text-4xl" style="color: {{ $primaryColor }}"></i>
    </div>
    <h3 class="font-semibold text-gray-700 mb-1">Belum ada paket aktif</h3>
    <p class="text-gray-400 text-sm mb-4">Yuk, beli paket untuk mulai belajar!</p>
    <a href="{{ route('user.package.index') }}" class="inline-flex items-center px-6 py-3 text-white rounded-xl font-medium hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
        <i class="ri-store-3-line mr-2"></i>Lihat Paket
    </a>
</div>
@endif
@endsection
