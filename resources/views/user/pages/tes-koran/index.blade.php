@extends('user.layout.new-user')

@section('title', 'Tes Koran')

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
        <h1 class="text-2xl font-bold text-gray-800">Tes Koran</h1>
        <p class="text-gray-500 text-sm">Tes Pauli & Kraepelin</p>
    </div>
</div>

@if($packages->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($packages as $package)
    @php
    $availableTests = $package->tesKorans->where('is_active', true);
    @endphp
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all">
        <!-- Header -->
        <div class="h-32 relative overflow-hidden" style="background: linear-gradient(135deg, {{ $primaryColor }}30 0%, {{ $primaryColor }}10 100%);">
            <div class="w-full h-full flex items-center justify-center">
                <i class="ri-file-edit-line text-6xl" style="color: {{ $primaryColor }}40"></i>
            </div>
            <span class="absolute top-3 left-3 px-2.5 py-1 bg-white/90 backdrop-blur-sm text-gray-700 text-xs rounded-full font-medium">
                <i class="ri-file-edit-line mr-1"></i>Tes Koran
            </span>
        </div>

        <!-- Content -->
        <div class="p-5">
            <h3 class="font-bold text-lg text-gray-800 mb-2">{{ $package->name }}</h3>
            <div class="text-sm text-gray-500 mb-4 line-clamp-2">
                {{ $package->description ?? 'Tes kecermatan Pauli & Kraepelin' }}
            </div>

            <!-- Tests List -->
            @if($availableTests->count() > 0)
            <div class="space-y-2 mb-4">
                @foreach($availableTests->take(3) as $test)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <i class="ri-time-line text-primary"></i>
                        <span class="text-sm font-medium">{{ $test->name }}</span>
                    </div>
                    <span class="text-xs text-gray-400">{{ $test->duration_minutes }} menit</span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-400 mb-4">Tidak ada tes aktif</p>
            @endif

            <!-- Action -->
            <div class="pt-3 border-t">
                @if($package->has_access)
                <a href="{{ $availableTests->count() > 0 ? route('user.tes-koran.show', $availableTests->first()) : '#' }}"
                   class="block w-full py-2.5 text-center rounded-xl font-medium text-white transition-opacity {{ $availableTests->count() > 0 ? 'hover:opacity-90' : 'opacity-50 cursor-not-allowed' }}"
                   style="background-color: {{ $primaryColor }}">
                    <i class="ri-play-circle-line mr-1"></i>Mulai Tes
                </a>
                @else
                <a href="{{ route('user.package.detail', $package->package_id) }}"
                   class="block w-full py-2.5 text-center rounded-xl font-medium text-white transition-opacity hover:opacity-90"
                   style="background-color: {{ $primaryColor }}">
                    <i class="ri-shopping-cart-line mr-1"></i>Beli Paket
                </a>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="text-center py-16">
    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="ri-file-edit-line text-4xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada Tes Koran</h3>
    <p class="text-gray-400 text-sm">Tes akan segera tersedia</p>
</div>
@endif
@endsection