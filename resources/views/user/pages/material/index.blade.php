@extends('user.layout.new-user')

@section('title', 'Materi Pembelajaran')

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
        <h1 class="text-2xl font-bold text-gray-800">Materi</h1>
        <p class="text-gray-500 text-sm">Akses semua materi pembelajaranmu</p>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-2xl border border-gray-100 p-5 flex items-center">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center mr-4 text-white" style="background-color: {{ $primaryColor }}">
            <i class="ri-book-open-line text-2xl"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Materi</p>
            <p class="text-2xl font-bold text-gray-800">{{ $stats['total_accessible'] ?? 0 }}</p>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5 flex items-center">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center mr-4 text-white" style="background-color: {{ $primaryColor }}aa">
            <i class="ri-time-line text-2xl"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500">Sedang Dipelajari</p>
            <p class="text-2xl font-bold text-gray-800">{{ $stats['in_progress'] ?? 0 }}</p>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5 flex items-center">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center mr-4 text-white" style="background-color: {{ $primaryColor }}77">
            <i class="ri-check-double-line text-2xl"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500">Selesai</p>
            <p class="text-2xl font-bold text-gray-800">{{ $stats['completed'] ?? 0 }}</p>
        </div>
    </div>
</div>

<!-- Material Types -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <a href="{{ route('user.material.videos') }}" class="bg-white rounded-2xl border border-gray-100 p-5 flex items-center hover:shadow-lg transition-all group">
        <div class="w-14 h-14 rounded-xl flex items-center justify-center mr-4 text-white" style="background-color: {{ $primaryColor }}">
            <i class="ri-video-line text-3xl"></i>
        </div>
        <div class="flex-1">
            <h3 class="font-semibold text-gray-800">Video Materi</h3>
            <p class="text-sm text-gray-500">{{ $accessibleVideos ?? 0 }} video tersedia</p>
        </div>
        <i class="ri-arrow-right-line text-gray-400 group-hover:translate-x-1 transition-transform"></i>
    </a>
    
    <a href="{{ route('user.material.documents') }}" class="bg-white rounded-2xl border border-gray-100 p-5 flex items-center hover:shadow-lg transition-all group">
        <div class="w-14 h-14 rounded-xl flex items-center justify-center mr-4 text-white" style="background-color: {{ $primaryColor }}">
            <i class="ri-file-text-line text-3xl"></i>
        </div>
        <div class="flex-1">
            <h3 class="font-semibold text-gray-800">Dokumen</h3>
            <p class="text-sm text-gray-500">{{ $accessibleDocuments ?? 0 }} dokumen tersedia</p>
        </div>
        <i class="ri-arrow-right-line text-gray-400 group-hover:translate-x-1 transition-transform"></i>
    </a>
    
    <a href="{{ route('user.material.live-sessions') }}" class="bg-white rounded-2xl border border-gray-100 p-5 flex items-center hover:shadow-lg transition-all group">
        <div class="w-14 h-14 rounded-xl flex items-center justify-center mr-4 text-white" style="background-color: {{ $primaryColor }}">
            <i class="ri-live-line text-3xl"></i>
        </div>
        <div class="flex-1">
            <h3 class="font-semibold text-gray-800">Live Session</h3>
            <p class="text-sm text-gray-500">{{ $accessibleLiveSessions ?? 0}} sesi live</p>
        </div>
        <i class="ri-arrow-right-line text-gray-400 group-hover:translate-x-1 transition-transform"></i>
    </a>
</div>

<!-- Categories -->
@if(isset($categories) && $categories->count() > 0)
<div class="mb-8">
    <h3 class="font-semibold text-gray-800 mb-4">Kategori Materi</h3>
    <div class="flex flex-wrap gap-2">
        @foreach($categories as $category)
        <a href="{{ route('user.material.category', $category->material_category_id) }}" 
           class="px-4 py-2 rounded-xl border border-gray-200 hover:border-transparent hover:text-white transition-all"
           style="--tw-hover-bg-color: {{ $primaryColor }}"
           onmouseover="this.style.backgroundColor='{{ $primaryColor }}'; this.style.color='white'; this.style.borderColor='{{ $primaryColor }}'"
           onmouseout="this.style.backgroundColor='transparent'; this.style.color=''; this.style.borderColor=''">
            {{ $category->name }}
        </a>
        @endforeach
    </div>
</div>
@endif

<!-- Recent Materials -->
@if(isset($recentMaterials) && $recentMaterials->count() > 0)
<div class="bg-white rounded-2xl border border-gray-100 p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-800">Materi Terbaru</h3>
        <a href="{{ route('user.material.videos') }}" class="text-sm hover:underline" style="color: {{ $primaryColor }}">Lihat semua</a>
    </div>
    <div class="space-y-3">
        @foreach($recentMaterials as $material)
        <a href="{{ route('user.material.show', $material->material_id) }}" class="flex items-center gap-4 p-3 rounded-xl hover:bg-gray-50 transition-colors">
            @if($material->thumbnail_url)
            <div class="w-16 h-12 rounded-lg overflow-hidden flex-shrink-0">
                <img src="{{ $material->thumbnail_url }}" alt="{{ $material->title }}" class="w-full h-full object-cover">
            </div>
            @else
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white flex-shrink-0" style="background-color: {{ $primaryColor }}">
                <i class="{{ $material->type_icon }} text-xl"></i>
            </div>
            @endif
            <div class="flex-1">
                <h4 class="font-medium text-gray-800">{{ $material->title }}</h4>
                <p class="text-sm text-gray-500">{{ $material->type_label }} • {{ $material->category?->name ?? 'Umum' }}</p>
            </div>
            <i class="ri-arrow-right-line text-gray-400"></i>
        </a>
        @endforeach
    </div>
</div>
@endif
@endsection
