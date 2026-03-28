@extends('user.layout.new-user')

@section('title', 'Video Pembelajaran')

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
@endphp

<style>
.tab-active {
    background-color: #ef4444 !important;
    color: white !important;
}
</style>

<!-- Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('user.material.index') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Video</h1>
        <p class="text-gray-500 text-sm">Tonton video pembelajaran interaktif</p>
    </div>
</div>

<!-- Filter Tabs -->
<div class="flex gap-2 mb-6 overflow-x-auto pb-2">
    <a href="{{ route('user.material.index') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        Semua
    </a>
    <a href="{{ route('user.material.videos') }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap tab-active">
        <i class="ri-video-line mr-1"></i>Video
    </a>
    <a href="{{ route('user.material.documents') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        <i class="ri-file-text-line mr-1"></i>Dokumen
    </a>
    <a href="{{ route('user.material.live-sessions') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        <i class="ri-live-line mr-1"></i>Live
    </a>
</div>

<!-- Videos Grid -->
@if($materials->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($materials as $material)
    <a href="{{ route('user.material.show', $material->material_id) }}" 
       class="bg-white rounded-xl overflow-hidden border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all group">
        <!-- Thumbnail -->
        <div class="aspect-video bg-gray-100 relative">
            @if($material->thumbnail_url)
            <img src="{{ $material->thumbnail_url }}" alt="{{ $material->title }}" class="w-full h-full object-cover">
            @else
            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-red-50 to-pink-50">
                <i class="ri-video-line text-5xl text-red-300"></i>
            </div>
            @endif
            
            <!-- Play button overlay -->
            <div class="absolute inset-0 flex items-center justify-center bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity">
                <div class="w-14 h-14 bg-white/90 rounded-full flex items-center justify-center shadow-lg">
                    <i class="ri-play-fill text-2xl text-red-500 ml-1"></i>
                </div>
            </div>
            
            @if($material->duration_minutes)
            <div class="absolute bottom-2 right-2 px-2 py-1 bg-black/70 text-white text-xs rounded">
                {{ $material->formatted_duration }}
            </div>
            @endif
            
            @php
            $userAccess = $material->userAccess->first();
            @endphp
            
            @if($userAccess && $userAccess->is_completed)
            <div class="absolute top-2 right-2 w-8 h-8 rounded-full flex items-center justify-center shadow" style="background-color: {{ $primaryColor }}">
                <i class="ri-check-line text-white"></i>
            </div>
            @endif
        </div>
        
        <!-- Content -->
        <div class="p-4">
            <h3 class="font-medium text-gray-800 mb-1 line-clamp-2 group-hover:text-red-500 transition-colors">{{ $material->title }}</h3>
            <p class="text-sm text-gray-400 line-clamp-2">{{ $material->description ?? 'Tidak ada deskripsi' }}</p>
            
            @if($userAccess)
            <div class="mt-3 flex items-center justify-between">
                <span class="text-xs {{ $userAccess->is_completed ? 'text-emerald-600' : 'text-yellow-600' }}" style="{{ $userAccess->is_completed ? 'color: ' . $primaryColor : '' }}">
                    {{ $userAccess->is_completed ? 'Selesai ditonton' : 'Sedang ditonton' }}
                </span>
                @if(!$userAccess->is_completed)
                <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-red-400 rounded-full" style="width: {{ $userAccess->progress_percentage }}%"></div>
                </div>
                @endif
            </div>
            @endif
        </div>
    </a>
    @endforeach
</div>

@if($materials->hasPages())
<div class="mt-8">
    {{ $materials->links() }}
</div>
@endif

@else
<div class="text-center py-16">
    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="ri-video-line text-3xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada video</h3>
    <p class="text-gray-400 text-sm">Video pembelajaran akan muncul di sini.</p>
</div>
@endif
@endsection
