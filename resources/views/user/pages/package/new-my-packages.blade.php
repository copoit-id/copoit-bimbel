@extends('user.layout.new-user')

@section('title', 'Paket Saya')

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$user = auth()->user();
@endphp

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Paket Saya</h1>
        <p class="text-gray-500 mt-1">Lanjutkan perjalanan belajarmu</p>
    </div>
    <a href="{{ route('user.package.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition-opacity text-white" style="background-color: {{ $primaryColor }}">
        <i class="ri-store-3-line mr-1"></i>Beli Paket
    </a>
</div>

<!-- Active Packages -->
@if($activePackages->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($activePackages as $access)
    @php
    $package = $access->package;
    
    // Load materials and tryouts with counts
    $package->loadCount(['materials', 'tryouts']);
    $materials = $package->materials;
    $tryouts = $package->tryouts;
    $totalItems = $materials->count() + $tryouts->count();
    
    // Calculate actual progress
    $completedCount = 0;
    
    // Check completed materials
    foreach ($materials as $material) {
        $progress = \App\Models\MaterialProgressLog::where('user_id', $user->id)
            ->where('material_id', $material->material_id)
            ->where('is_completed', true)
            ->first();
        if ($progress) {
            $completedCount++;
        }
    }
    
    // Check completed tryouts
    foreach ($tryouts as $tryout) {
        $attempt = \App\Models\UserAnswer::where('user_id', $user->id)
            ->where('tryout_id', $tryout->tryout_id)
            ->where('status', 'completed')
            ->first();
        if ($attempt) {
            $completedCount++;
        }
    }
    
    $progressPercent = $totalItems > 0 ? round(($completedCount / $totalItems) * 100) : 0;
    @endphp
    <div class="bg-white rounded-2xl p-5 border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white" style="background-color: {{ $primaryColor }}">
                <i class="ri-road-map-line text-xl"></i>
            </div>
            <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">Aktif</span>
        </div>
        
        <h3 class="font-bold text-gray-800 mb-1">{{ $package->name }}</h3>
        <p class="text-sm text-gray-400 mb-4 line-clamp-2">{{ $package->description ?? 'Paket pembelajaran lengkap' }}</p>
        
        <!-- Progress -->
        <div class="mb-4">
            <div class="flex items-center justify-between text-sm mb-2">
                <span class="text-gray-500">Progress Belajar</span>
                <span class="font-semibold" style="color: {{ $primaryColor }}">{{ $progressPercent }}%</span>
            </div>
            <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all" style="width: {{ $progressPercent }}%; background-color: {{ $primaryColor }}"></div>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ $completedCount }}/{{ $totalItems }} selesai</p>
        </div>
        
        <!-- Meta info -->
        <div class="flex items-center justify-between text-sm text-gray-400 mb-4">
            <span><i class="ri-calendar-line mr-1"></i>{{ $access->end_date ? $access->end_date->format('d M Y') : 'Lifetime' }}</span>
            <span><i class="ri-book-open-line mr-1"></i>{{ $totalItems }} Item</span>
        </div>
        
        <!-- Actions -->
        <a href="{{ route('user.package.show', $package->package_id) }}" 
           class="block w-full py-2.5 text-white text-center rounded-xl font-medium hover:opacity-90 transition-opacity"
           style="background-color: {{ $primaryColor }}">
            <i class="ri-play-circle-line mr-1"></i>Lanjutkan Belajar
        </a>
    </div>
    @endforeach
</div>
@else
<div class="text-center py-16">
    <div class="w-24 h-24 rounded-full mx-auto mb-4 flex items-center justify-center" style="background-color: {{ $primaryColor }}10">
        <i class="ri-package-line text-4xl" style="color: {{ $primaryColor }}"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada paket aktif</h3>
    <p class="text-gray-400 text-sm mb-6">Yuk, pilih paket belajar yang sesuai dengan kebutuhanmu!</p>
    <a href="{{ route('user.package.index') }}" class="inline-flex items-center px-6 py-3 text-white rounded-xl font-medium hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
        <i class="ri-store-3-line mr-2"></i>Lihat Paket
    </a>
</div>
@endif
@endsection
