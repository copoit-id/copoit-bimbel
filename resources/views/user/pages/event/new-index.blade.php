@extends('user.layout.new-user')

@section('title', 'Event Gratis')

@section('content')
<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Event Gratis</h1>
        <p class="text-gray-500 mt-1">Daftar event dan paket gratis yang tersedia</p>
    </div>
</div>

@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$hasEvents = $kelasPackages->count() > 0 || $tryoutPackages->count() > 0 || $sertifikasiPackages->count() > 0;
@endphp

@if($hasEvents)
    <!-- Tryout Events -->
    @if($tryoutPackages->count() > 0)
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <i class="ri-file-list-3-line" style="color: {{ $primaryColor }}"></i>
            <h2 class="font-bold text-gray-800">Tryout Gratis</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($tryoutPackages as $package)
            <div class="bg-white rounded-2xl p-5 border border-gray-100 hover:shadow-lg transition-all">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: {{ $primaryColor }}20">
                        <i class="ri-file-list-3-line text-xl" style="color: {{ $primaryColor }}"></i>
                    </div>
                    @if($package->user_access_count > 0)
                    <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">Sudah Join</span>
                    @else
                    <span class="px-2.5 py-1 bg-red-100 text-red-700 text-xs rounded-full font-medium">Gratis</span>
                    @endif
                </div>
                
                <h3 class="font-bold text-gray-800 mb-1">{{ $package->name }}</h3>
                <div class="text-sm text-gray-400 mb-4 line-clamp-2">{!! $package->description ?? 'Event tryout gratis' !!}</div>
                
                @if($package->user_access_count > 0)
                <a href="{{ route('user.package.show', $package->package_id) }}" 
                   class="block w-full py-2.5 text-center rounded-xl font-medium text-white hover:opacity-90 transition-opacity"
                   style="background-color: {{ $primaryColor }}">
                    Lanjutkan
                </a>
                @else
                <button onclick="joinEvent({{ $package->package_id }})" 
                        class="block w-full py-2.5 text-center rounded-xl font-medium text-white hover:opacity-90 transition-opacity"
                        style="background-color: {{ $primaryColor }}">
                    Ambil Gratis
                </button>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif
    
    <!-- Kelas/Bimbel Events -->
    @if($kelasPackages->count() > 0)
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <i class="ri-book-open-line" style="color: {{ $primaryColor }}"></i>
            <h2 class="font-bold text-gray-800">Kelas Gratis</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($kelasPackages as $package)
            <div class="bg-white rounded-2xl p-5 border border-gray-100 hover:shadow-lg transition-all">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: {{ $primaryColor }}20">
                        <i class="ri-book-open-line text-xl" style="color: {{ $primaryColor }}"></i>
                    </div>
                    @if($package->user_access_count > 0)
                    <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">Sudah Join</span>
                    @else
                    <span class="px-2.5 py-1 bg-red-100 text-red-700 text-xs rounded-full font-medium">Gratis</span>
                    @endif
                </div>
                
                <h3 class="font-bold text-gray-800 mb-1">{{ $package->name }}</h3>
                <div class="text-sm text-gray-400 mb-4 line-clamp-2">{!! $package->description ?? 'Kelas pembelajaran gratis' !!}</div>
                
                @if($package->user_access_count > 0)
                <a href="{{ route('user.package.show', $package->package_id) }}" 
                   class="block w-full py-2.5 text-center rounded-xl font-medium text-white hover:opacity-90 transition-opacity"
                   style="background-color: {{ $primaryColor }}">
                    Lanjutkan
                </a>
                @else
                <button onclick="joinEvent({{ $package->package_id }})" 
                        class="block w-full py-2.5 text-center rounded-xl font-medium text-white hover:opacity-90 transition-opacity"
                        style="background-color: {{ $primaryColor }}">
                    Ambil Gratis
                </button>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif
    
    <!-- Sertifikasi Events -->
    @if($sertifikasiPackages->count() > 0)
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <i class="ri-award-line" style="color: {{ $primaryColor }}"></i>
            <h2 class="font-bold text-gray-800">Sertifikasi Gratis</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($sertifikasiPackages as $package)
            <div class="bg-white rounded-2xl p-5 border border-gray-100 hover:shadow-lg transition-all">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: {{ $primaryColor }}20">
                        <i class="ri-award-line text-xl" style="color: {{ $primaryColor }}"></i>
                    </div>
                    @if($package->user_access_count > 0)
                    <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">Sudah Join</span>
                    @else
                    <span class="px-2.5 py-1 bg-red-100 text-red-700 text-xs rounded-full font-medium">Gratis</span>
                    @endif
                </div>
                
                <h3 class="font-bold text-gray-800 mb-1">{{ $package->name }}</h3>
                <div class="text-sm text-gray-400 mb-4 line-clamp-2">{!! $package->description ?? 'Sertifikasi gratis' !!}</div>
                
                @if($package->user_access_count > 0)
                <a href="{{ route('user.package.show', $package->package_id) }}" 
                   class="block w-full py-2.5 text-center rounded-xl font-medium text-white hover:opacity-90 transition-opacity"
                   style="background-color: {{ $primaryColor }}">
                    Lanjutkan
                </a>
                @else
                <button onclick="joinEvent({{ $package->package_id }})" 
                        class="block w-full py-2.5 text-center rounded-xl font-medium text-white hover:opacity-90 transition-opacity"
                        style="background-color: {{ $primaryColor }}">
                    Ambil Gratis
                </button>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif
@else
<div class="text-center py-16">
    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="ri-calendar-event-line text-4xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada event</h3>
    <p class="text-gray-400 text-sm">Event gratis akan muncul di sini.</p>
</div>
@endif
@endsection

@section('scripts')
<script>
function joinEvent(packageId) {
    if (!confirm('Ambil paket gratis ini?')) return;
    
    fetch(`/user/event/${packageId}/join`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert(data.message || 'Gagal mengambil paket');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan. Silakan coba lagi.');
    });
}
</script>
@endsection
