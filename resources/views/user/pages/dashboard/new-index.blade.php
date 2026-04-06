@extends('user.layout.new-user')

@section('title', 'Dashboard')

@section('content')
@php
$user = auth()->user();
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$isGuest = !$user;
@endphp

<!-- Welcome Card -->
<div class="bg-white rounded-2xl p-6 mb-6 border border-gray-100">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                {{ $isGuest ? 'Selamat Datang!' : 'Halo, ' . $user->name }}
            </h1>
            <p class="text-gray-500 mt-1">
                {{ $isGuest ? 'Mulai perjalanan belajarmu sekarang' : 'Tetap semangat belajar ya!' }}
            </p>
        </div>
        @if($isGuest)
            <a href="{{ route('login') }}" class="px-6 py-3 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
                Masuk / Daftar
            </a>
        @endif
    </div>
</div>

@if(!$isGuest)
<!-- Akses Cepat -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <a href="{{ route('user.material.videos') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg transition-all group">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white mb-3" style="background-color: {{ $primaryColor }}">
            <i class="ri-video-line text-xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-sm">Video Materi</h3>
    </a>
    
    <a href="{{ route('user.package.tryout.list') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg transition-all group">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white mb-3" style="background-color: {{ $primaryColor }}">
            <i class="ri-file-list-3-line text-xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-sm">Tryout</h3>
    </a>
    
    <a href="{{ route('user.package.my') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg transition-all group">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white mb-3" style="background-color: {{ $primaryColor }}">
            <i class="ri-road-map-line text-xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-sm">Paket Saya</h3>
    </a>
    
    <a href="{{ route('user.package.index') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg transition-all group">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white mb-3" style="background-color: {{ $primaryColor }}">
            <i class="ri-store-3-line text-xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-sm">Beli Paket</h3>
    </a>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Progress Paket -->
    <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-800">Progress Belajar</h3>
            <a href="{{ route('user.package.my') }}" class="text-sm hover:underline" style="color: {{ $primaryColor }}">Lihat semua</a>
        </div>
        
        @if($activePackages->count() > 0)
        <div class="space-y-4">
            @foreach($activePackages->take(3) as $access)
            @php
            $pkg = $access->package;
            $progress = $packageProgress[$pkg->package_id] ?? 0;
            @endphp
            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white shrink-0" style="background-color: {{ $primaryColor }}">
                    <i class="ri-road-map-line text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="font-semibold text-gray-800 truncate">{{ $pkg->name }}</h4>
                    <div class="flex items-center gap-2 mt-1">
                        <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all" style="width: {{ $progress }}%; background-color: {{ $primaryColor }}"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-600 w-10 text-right">{{ $progress }}%</span>
                    </div>
                </div>
                <a href="{{ route('user.package.show', $pkg->package_id) }}" class="p-2 hover:bg-gray-200 rounded-lg transition-colors shrink-0" style="color: {{ $primaryColor }}">
                    <i class="ri-arrow-right-line"></i>
                </a>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="ri-road-map-line text-2xl text-gray-400"></i>
            </div>
            <p class="text-gray-400 text-sm mb-3">Belum ada paket aktif</p>
            <a href="{{ route('user.package.index') }}" class="inline-block px-4 py-2 rounded-lg text-sm font-medium text-white hover:opacity-90" style="background-color: {{ $primaryColor }}">
                Lihat Paket
            </a>
        </div>
        @endif
    </div>
    
    <!-- Akurasi -->
    <div class="bg-white rounded-2xl p-6 border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4">Akurasi Jawaban</h3>
        <div class="flex flex-col items-center">
            <div class="relative w-32 h-32 mb-3">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#e5e7eb" stroke-width="10"/>
                    <circle cx="50" cy="50" r="42" fill="none" stroke="{{ $primaryColor }}" stroke-width="10" 
                            stroke-dasharray="264" stroke-dashoffset="{{ 264 - (264 * $accuracyPercent / 100) }}"
                            stroke-linecap="round"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-2xl font-bold" style="color: {{ $primaryColor }}">{{ $accuracyPercent }}%</span>
                </div>
            </div>
            <p class="text-sm text-gray-500 text-center">
                {{ $totalCorrect ?? 0 }} benar dari {{ $totalAnswered ?? 0 }} soal
            </p>
        </div>
    </div>
</div>

<!-- Hasil Tryout Terakhir -->
@if($recentTryouts->count() > 0)
<div class="bg-white rounded-2xl p-6 border border-gray-100 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-gray-800">Hasil Tryout Terakhir</h3>
        <a href="{{ route('user.package.tryout.list') }}" class="text-sm hover:underline" style="color: {{ $primaryColor }}">Lihat semua</a>
    </div>
    
    <div class="space-y-3">
        @foreach($recentTryouts as $attempt)
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white shrink-0" style="background-color: {{ $primaryColor }}">
                    <i class="ri-file-list-3-line"></i>
                </div>
                <div>
                    <h5 class="font-semibold text-gray-800 text-sm">{{ $attempt->tryout->name ?? 'Tryout' }}</h5>
                    <p class="text-xs text-gray-400">{{ $attempt->created_at->diffForHumans() }}</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-lg font-bold" style="color: {{ $primaryColor }}">{{ $attempt->score ?? 0 }}</span>
                <span class="text-xs {{ ($attempt->is_passed ?? false) ? 'text-green-500' : 'text-red-500' }} block">
                    {{ ($attempt->is_passed ?? false) ? 'Lulus' : 'Belum Lulus' }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@else
<!-- Guest View -->
<div class="bg-white rounded-2xl p-6 border border-gray-100 mb-6">
    <h3 class="font-bold text-gray-800 mb-4">Paket Tersedia</h3>
    
    @if($publicPackages->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($publicPackages as $pkg)
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all group flex flex-col h-full">
            <!-- Package Image/Header -->
            <div class="h-32 relative overflow-hidden shrink-0" style="background: linear-gradient(135deg, {{ $primaryColor }}20 0%, {{ $primaryColor }}10 100%);">
                @if($pkg->thumbnail)
                <img src="{{ asset('storage/' . $pkg->thumbnail) }}" alt="{{ $pkg->name }}" class="w-full h-full object-cover">
                @else
                <div class="w-full h-full flex items-center justify-center">
                    <i class="ri-book-3-line text-6xl" style="color: {{ $primaryColor }}40"></i>
                </div>
                @endif
                
                @if($pkg->type_price == 'paid')
                <div class="absolute top-3 right-3 px-3 py-1 rounded-full text-xs font-semibold" style="background-color: {{ $primaryColor }}; color: white;">
                    {{ $pkg->formatted_price }}
                </div>
                @else
                <div class="absolute top-3 right-3 px-3 py-1 rounded-full text-xs font-semibold bg-green-500 text-white">
                    GRATIS
                </div>
                @endif
            </div>
            
            <!-- Content -->
            <div class="p-5 flex flex-col flex-grow">
                <a href="{{ route('user.package.detail', $pkg->package_id) }}" class="block">
                    <h3 class="font-bold text-lg text-gray-800 mb-2 hover:text-primary transition-colors">{{ $pkg->name }}</h3>
                </a>
                <div class="text-gray-500 text-sm mb-4 line-clamp-2 plan-description flex-grow">{!! $pkg->description ?? 'Paket pembelajaran' !!}</div>
                
                <!-- Action Button -->
                <a href="{{ route('user.package.detail', $pkg->package_id) }}" 
                   class="block w-full py-2.5 text-center rounded-xl font-medium text-white hover:opacity-90 transition-opacity mt-auto"
                   style="background-color: {{ $primaryColor }}">
                    <i class="ri-eye-line mr-1"></i>Lihat Detail
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <p class="text-gray-400 text-center py-8">Belum ada paket tersedia</p>
    @endif
</div>
@endif
@section('styles')
<style>
.plan-description p { margin-bottom: 0.5rem; }
.plan-description p:last-child { margin-bottom: 0; }
.plan-description ul { list-style-type: disc; padding-left: 1.25rem; margin-bottom: 0.5rem; }
.plan-description ol { list-style-type: decimal; padding-left: 1.25rem; margin-bottom: 0.5rem; }
.plan-description a { color: var(--primary-color, #10b981); text-decoration: underline; }
.plan-description strong { font-weight: 600; }
.plan-description em { font-style: italic; }
</style>
@endsection
@endsection
