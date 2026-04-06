@extends('user.layout.user')

@section('title', 'Paket Saya')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Paket Saya</h1>
        <p class="text-gray-600 mt-1">Kelola dan akses semua paket pembelajaran Anda</p>
    </div>

    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ session('error') }}
    </div>
    @endif

    <!-- Active Packages -->
    @if($activePackages->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($activePackages as $access)
        @php
        $package = $access->package;
        $progress = rand(20, 80); // TODO: Calculate actual progress
        @endphp
        <a href="{{ route('user.package.show', $package->package_id) }}" class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden group">
            {{-- Package Image --}}
            <div class="relative h-40 bg-gray-100 overflow-hidden">
                @if($package->image && file_exists(public_path('storage/' . $package->image)))
                    <img src="{{ asset('storage/' . $package->image) }}" 
                         alt="{{ $package->name }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="absolute inset-0 flex items-center justify-center bg-gray-100" style="display: none;">
                        <i class="ri-image-line text-4xl text-gray-300"></i>
                    </div>
                @else
                    <div class="w-full h-full flex items-center justify-center bg-gray-100">
                        <i class="ri-image-line text-4xl text-gray-300"></i>
                    </div>
                @endif
                <span class="absolute top-3 right-3 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Aktif
                </span>
            </div>
            
            <div class="p-6">
                <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">{{ $package->name }}</h3>
                <div class="text-sm text-gray-500 mb-4 line-clamp-2">{!! $package->description ?: 'Tidak ada deskripsi' !!}</div>
                
                <!-- Progress -->
                <div class="mb-4">
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-gray-600">Progress</span>
                        <span class="font-medium text-primary">{{ $progress }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-primary h-2 rounded-full transition-all" style="width: {{ $progress }}%"></div>
                    </div>
                </div>
                
                <!-- Meta -->
                <div class="flex items-center justify-between text-sm text-gray-500 pt-4 border-t">
                    <span>
                        <i class="ri-calendar-line mr-1"></i>
                        Exp: {{ $access->end_date ? $access->end_date->format('d M Y') : 'Unlimited' }}
                    </span>
                    <span class="text-primary group-hover:translate-x-1 transition-transform">
                        Lanjutkan <i class="ri-arrow-right-line"></i>
                    </span>
                </div>
            </div>
        </a>
        @endforeach
    </div>
    @else
    <div class="text-center py-16 bg-white rounded-lg shadow">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-4">
            <i class="ri-package-line text-4xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">Belum ada paket aktif</h3>
        <p class="text-gray-500 mb-4">Anda belum memiliki paket pembelajaran yang aktif.</p>
        <a href="{{ route('user.package.index') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
            <i class="ri-store-3-line mr-2"></i>Lihat Paket
        </a>
    </div>
    @endif
</div>
@endsection
