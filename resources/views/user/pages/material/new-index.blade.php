@extends('user.layout.new-user')

@section('title', 'Materi Pembelajaran')

@section('content')
@php
$user = auth()->user();
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
@endphp

<style>
.tab-active {
    background-color: {{ $primaryColor }} !important;
    color: white !important;
}
</style>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Materi Pembelajaran</h1>
        <p class="text-gray-500 mt-1">Pilih materi yang ingin kamu pelajari</p>
    </div>
</div>

<!-- Filter Tabs -->
<div class="flex gap-2 mb-6 overflow-x-auto pb-2">
    <a href="{{ route('user.material.index') }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap tab-active">
        Semua
    </a>
    <a href="{{ route('user.material.videos') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        <i class="ri-video-line mr-1"></i>Video
    </a>
    <a href="{{ route('user.material.documents') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        <i class="ri-file-text-line mr-1"></i>Dokumen
    </a>
    <a href="{{ route('user.material.live-sessions') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        <i class="ri-live-line mr-1"></i>Live
    </a>
</div>

<!-- Categories -->
@if(isset($categories) && $categories->count() > 0)
<div class="flex gap-2 mb-6 overflow-x-auto pb-2">
    @foreach($categories as $category)
    <a href="{{ route('user.material.category', $category->category_id) }}" 
       class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-full hover:bg-gray-50 transition-colors">
        @if($category->icon)
        <i class="{{ $category->icon }}" style="color: {{ $primaryColor }}"></i>
        @endif
        <span class="text-sm text-gray-700">{{ $category->name }}</span>
    </a>
    @endforeach
</div>
@endif

<!-- Materials Grid -->
@if(isset($materials) && $materials->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($materials as $material)
    <a href="{{ route('user.material.show', $material->material_id) }}" 
       class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all group">
        <div class="flex items-start gap-4">
            <div class="w-14 h-14 {{ $material->type === 'video' ? 'bg-red-100 text-red-500' : ($material->type === 'document' ? 'bg-blue-100 text-blue-500' : 'bg-purple-100 text-purple-500') }} rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="{{ $material->type_icon }} text-2xl"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">{{ $material->type_label }}</span>
                    @if($material->duration_minutes)
                    <span class="text-xs text-gray-400">{{ $material->formatted_duration }}</span>
                    @endif
                </div>
                <h3 class="font-medium text-gray-800 text-sm line-clamp-2 group-hover:text-emerald-600 transition-colors" style="--tw-text-opacity: 1; color: {{ $primaryColor }};">{{ $material->title }}</h3>
                <p class="text-xs text-gray-400 mt-1 line-clamp-1">{{ $material->description ?? 'Tidak ada deskripsi' }}</p>
            </div>
        </div>
        
        @php
        $userAccess = $material->userAccess->first();
        @endphp
        
        <div class="mt-4 pt-3 border-t flex items-center justify-between">
            @if($userAccess && $userAccess->is_completed)
            <span class="text-xs flex items-center gap-1" style="color: {{ $primaryColor }}">
                <i class="ri-check-line"></i>Selesai
            </span>
            @elseif($userAccess && $userAccess->is_in_progress)
            <div class="flex items-center gap-2 flex-1">
                <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full" style="width: {{ $userAccess->progress_percentage }}%; background-color: {{ $primaryColor }}"></div>
                </div>
                <span class="text-xs text-gray-500">{{ $userAccess->progress_percentage }}%</span>
            </div>
            @else
            <span class="text-xs text-gray-400">Belum dimulai</span>
            @endif
            
            <i class="ri-arrow-right-line text-gray-300 group-hover:text-emerald-500 group-hover:translate-x-1 transition-all" style="--tw-text-opacity: 1; color: {{ $primaryColor }};"></i>
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
        <i class="ri-book-open-line text-3xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada materi</h3>
    <p class="text-gray-400 text-sm">Materi akan muncul di sini setelah kamu memiliki akses.</p>
</div>
@endif
@endsection
