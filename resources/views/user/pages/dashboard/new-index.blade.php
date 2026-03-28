@extends('user.layout.new-user')

@section('title', 'Dashboard')

@section('content')
@php
$user = auth()->user();
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$quotes = [
    'The only way to do great work is to love what you do. - Steve Jobs',
    'Belajar bukan tentang siapa yang paling pintar, tapi siapa yang paling konsisten.',
    'Setiap langkah kecil membawamu lebih dekat ke impian.',
    'Jangan takut gagal, takutlah untuk tidak mencoba.',
    'Kesuksesan adalah hasil dari persiapan, kerja keras, dan belajar dari kegagalan.'
];
$randomQuote = $quotes[array_rand($quotes)];
@endphp

<style>
.bg-gradient-welcome {
    background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $primaryColor }}dd 50%, {{ $primaryColor }}bb 100%);
}
</style>

<!-- Welcome Card -->
<div class="bg-gradient-welcome rounded-2xl p-6 mb-8 text-white relative overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/3"></div>
    <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/3"></div>
    
    <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-full flex items-center justify-center text-2xl font-bold">
                {{ $user ? substr($user->name, 0, 1) : 'G' }}
            </div>
            <div>
                <p class="text-white/80 text-sm">Halo,</p>
                <h2 class="text-2xl font-bold">{{ $user ? explode(' ', $user->name)[0] : 'Guest' }}!</h2>
                <p class="text-white/80 text-sm mt-1 italic max-w-md">"{{ $randomQuote }}"</p>
            </div>
        </div>
        
        @if($user)
        <!-- XP & Level -->
        <div class="flex items-center gap-3 bg-white/20 backdrop-blur px-4 py-2 rounded-xl">
            <div class="flex items-center gap-2">
                <i class="ri-fire-line text-yellow-300 text-xl"></i>
                <span class="font-semibold">XP 0</span>
            </div>
            <div class="w-px h-6 bg-white/30"></div>
            <div class="flex items-center gap-2">
                <span class="text-sm">Lv.1</span>
                <div class="w-24 h-2 bg-white/30 rounded-full overflow-hidden">
                    <div class="h-full bg-yellow-300 rounded-full" style="width: 0%"></div>
                </div>
                <span class="text-xs text-white/70">0/500</span>
            </div>
        </div>
        
        <!-- Target Score -->
        <div class="bg-white/95 text-gray-800 rounded-xl p-4 min-w-[200px]">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500">Target Skor</span>
                <button class="text-gray-400 hover:text-gray-600"><i class="ri-pencil-line"></i></button>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold" style="color: {{ $primaryColor }}">700</span>
                <span class="text-sm text-gray-500">Ilmu Komputer</span>
            </div>
            <p class="text-xs text-gray-400">Universitas Indonesia</p>
            <button class="mt-3 w-full py-2 rounded-lg text-sm font-medium transition-colors hover:opacity-90" style="background-color: {{ $primaryColor }}20; color: {{ $primaryColor }}">
                <i class="ri-flashlight-line mr-1"></i>Buka Prediksi Skor
            </button>
        </div>
        @else
        <!-- Guest CTA -->
        <div class="bg-white/95 text-gray-800 rounded-xl p-4 min-w-[200px]">
            <p class="text-sm text-gray-600 mb-3">Masuk untuk melihat progress belajarmu</p>
            <a href="{{ route('login') }}" class="block w-full py-2 rounded-lg text-sm font-medium text-white text-center hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
                Masuk / Daftar
            </a>
        </div>
        @endif
    </div>
</div>

@if($user)
<!-- Akses Cepat - Warna Seragam Primary -->
<div class="mb-8">
    <div class="flex items-center gap-2 mb-4">
        <i class="ri-flash-line" style="color: {{ $primaryColor }}"></i>
        <h3 class="font-semibold text-gray-800">Akses Cepat</h3>
    </div>
    
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
        {{-- Video Materi --}}
        <a href="{{ route('user.material.videos') }}" class="bg-white rounded-xl p-4 hover:shadow-lg hover:-translate-y-1 transition-all border border-gray-100 group">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform text-white" style="background-color: {{ $primaryColor }}">
                <i class="ri-video-line text-lg"></i>
            </div>
            <h4 class="font-medium text-gray-800 text-sm mb-0.5">Video Materi</h4>
            <p class="text-xs text-gray-400">Tonton semua video</p>
        </a>
        
        {{-- Tryout --}}
        <a href="{{ route('user.package.tryout.list') }}" class="bg-white rounded-xl p-4 hover:shadow-lg hover:-translate-y-1 transition-all border border-gray-100 group">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform text-white" style="background-color: {{ $primaryColor }}">
                <i class="ri-file-list-3-line text-lg"></i>
            </div>
            <h4 class="font-medium text-gray-800 text-sm mb-0.5">Tryout UTBK</h4>
            <p class="text-xs text-gray-400">Uji diri kamu!</p>
        </a>
        
        {{-- Paket Saya --}}
        <a href="{{ route('user.package.my') }}" class="bg-white rounded-xl p-4 hover:shadow-lg hover:-translate-y-1 transition-all border border-gray-100 group">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform text-white" style="background-color: {{ $primaryColor }}">
                <i class="ri-road-map-line text-lg"></i>
            </div>
            <h4 class="font-medium text-gray-800 text-sm mb-0.5">Belajar Sekarang</h4>
            <p class="text-xs text-gray-400">Ikuti jalur belajar</p>
        </a>
        
        {{-- Live Session --}}
        <a href="{{ route('user.material.live-sessions') }}" class="bg-white rounded-xl p-4 hover:shadow-lg hover:-translate-y-1 transition-all border border-gray-100 group">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform text-white" style="background-color: {{ $primaryColor }}">
                <i class="ri-live-line text-lg"></i>
            </div>
            <h4 class="font-medium text-gray-800 text-sm mb-0.5">LIVE</h4>
            <p class="text-xs text-gray-400">Belajar bareng live</p>
        </a>
        
        {{-- Paket (gabungan berbayar & gratis) --}}
        <a href="{{ route('user.package.index') }}" class="bg-white rounded-xl p-4 hover:shadow-lg hover:-translate-y-1 transition-all border border-gray-100 group">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform text-white" style="background-color: {{ $primaryColor }}">
                <i class="ri-store-3-line text-lg"></i>
            </div>
            <h4 class="font-medium text-gray-800 text-sm mb-0.5">Paket</h4>
            <p class="text-xs text-gray-400">Lihat semua paket</p>
        </a>
        
        {{-- Riwayat --}}
        <a href="{{ route('user.package.riwayatPembelian') }}" class="bg-white rounded-xl p-4 hover:shadow-lg hover:-translate-y-1 transition-all border border-gray-100 group">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform text-white" style="background-color: {{ $primaryColor }}">
                <i class="ri-history-line text-lg"></i>
            </div>
            <h4 class="font-medium text-gray-800 text-sm mb-0.5">Riwayat</h4>
            <p class="text-xs text-gray-400">Progress & pembelian</p>
        </a>
    </div>
</div>

<!-- Stats Section -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Progress Overview -->
    <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <i class="ri-bar-chart-box-line" style="color: {{ $primaryColor }}"></i>
                <h3 class="font-semibold text-gray-800">Progress Belajar</h3>
            </div>
            <a href="{{ route('user.package.my') }}" class="text-sm hover:underline" style="color: {{ $primaryColor }}">Lihat semua ></a>
        </div>
        
        @if(isset($activePackages) && $activePackages->count() > 0)
        <div class="space-y-4">
            @foreach($activePackages->take(3) as $access)
            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white" style="background-color: {{ $primaryColor }}">
                    <i class="ri-road-map-line text-xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium text-gray-800">{{ $access->package->name }}</h4>
                    <div class="flex items-center gap-2 mt-1">
                        <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full" style="width: {{ rand(20, 80) }}%; background-color: {{ $primaryColor }}"></div>
                        </div>
                        <span class="text-xs text-gray-500">{{ rand(20, 80) }}%</span>
                    </div>
                </div>
                <a href="{{ route('user.package.show', $access->package->package_id) }}" class="p-2 hover:bg-gray-200 rounded-lg transition-colors" style="color: {{ $primaryColor }}">
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
            <a href="{{ route('user.package.index') }}" class="inline-block px-4 py-2 rounded-lg text-sm font-medium text-white hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
                Lihat Paket
            </a>
        </div>
        @endif
    </div>
    
    <!-- Mini Stats -->
    <div class="space-y-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-800">Akurasi</h4>
                <a href="#" class="text-xs hover:underline" style="color: {{ $primaryColor }}">Lihat statistik ></a>
            </div>
            <p class="text-xs text-gray-400 mb-4">Lakukan lebih banyak latihan untuk melihat analisis akurasimu.</p>
            <div class="flex justify-center">
                <div class="w-32 h-32 relative">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-2xl font-bold text-gray-300">0%</span>
                    </div>
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="64" cy="64" r="56" stroke="#f3f4f6" stroke-width="12" fill="none"/>
                        <circle cx="64" cy="64" r="56" stroke="{{ $primaryColor }}" stroke-width="12" fill="none" stroke-dasharray="351" stroke-dashoffset="351"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tryout Results -->
@if(isset($recentAttempts) && count($recentAttempts) > 0)
<div class="bg-white rounded-2xl p-6 border border-gray-100 mb-8">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <i class="ri-file-list-3-line" style="color: {{ $primaryColor }}"></i>
            <h3 class="font-semibold text-gray-800">Hasil Tryout Terakhir</h3>
        </div>
        <a href="{{ route('user.package.tryout.list') }}" class="text-sm hover:underline" style="color: {{ $primaryColor }}">Lihat semua ></a>
    </div>
    
    <div class="space-y-3">
        @foreach($recentAttempts->take(3) as $attempt)
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white" style="background-color: {{ $primaryColor }}">
                    <i class="ri-file-list-3-line"></i>
                </div>
                <div>
                    <h5 class="font-medium text-gray-800 text-sm">{{ $attempt->tryout->name ?? 'Tryout' }}</h5>
                    <p class="text-xs text-gray-400">{{ $attempt->created_at->diffForHumans() }}</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-lg font-bold" style="color: {{ $primaryColor }}">{{ $attempt->score ?? 0 }}</span>
                <p class="text-xs {{ $attempt->is_passed ? 'text-green-500' : 'text-red-500' }}">
                    {{ $attempt->is_passed ? 'Lulus' : 'Belum Lulus' }}
                </p>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@else
<!-- Guest View - Paket yang Tersedia -->
<div class="mb-8">
    <div class="flex items-center gap-2 mb-4">
        <i class="ri-store-3-line" style="color: {{ $primaryColor }}"></i>
        <h3 class="font-semibold text-gray-800">Paket Tersedia</h3>
    </div>
    
    @php
    // Get public packages for guests
    $publicPackages = \App\Models\Package::where('is_active', true)
        ->where('status', 'active')
        ->limit(6)
        ->get();
    @endphp
    
    @if($publicPackages->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($publicPackages as $pkg)
        <div class="bg-white rounded-2xl p-5 border border-gray-100 hover:shadow-lg transition-all">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white mb-4" style="background-color: {{ $primaryColor }}">
                <i class="ri-package-line text-xl"></i>
            </div>
            
            <h3 class="font-bold text-gray-800 mb-1">{{ $pkg->name }}</h3>
            <p class="text-sm text-gray-400 mb-4 line-clamp-2">{{ $pkg->description ?? 'Paket pembelajaran lengkap' }}</p>
            
            <div class="flex items-center justify-between mb-4">
                <span class="text-lg font-bold" style="color: {{ $primaryColor }}">{{ $pkg->formatted_price }}</span>
                <span class="text-xs text-gray-400">{{ $pkg->duration }} Hari</span>
            </div>
            
            <a href="{{ route('login') }}" class="block w-full py-2.5 text-white text-center rounded-xl font-medium hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
                Lihat Detail
            </a>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-12 bg-white rounded-2xl border border-gray-100">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="ri-package-line text-2xl text-gray-400"></i>
        </div>
        <p class="text-gray-400">Belum ada paket tersedia</p>
    </div>
    @endif
    
    <div class="text-center mt-6">
        <p class="text-gray-500 mb-3">Masuk untuk melihat lebih banyak paket dan fitur</p>
        <a href="{{ route('login') }}" class="inline-flex items-center px-6 py-3 text-white rounded-xl font-medium hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
            <i class="ri-login-box-line mr-2"></i>Masuk / Daftar
        </a>
    </div>
</div>
@endif
@endsection
