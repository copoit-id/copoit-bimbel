@extends('user.layout.user')

@section('title', 'Video Pembelajaran')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex items-center text-sm text-gray-500">
            <a href="{{ route('user.material.index') }}" class="hover:text-primary">Materi</a>
            <i class="ri-arrow-right-s-line mx-2"></i>
            <span class="text-gray-900">Video</span>
        </nav>
    </div>

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Video Pembelajaran</h1>
            <p class="text-gray-600 mt-1">Tonton video pembelajaran untuk meningkatkan pemahaman Anda</p>
        </div>
    </div>

    <!-- Materials Grid -->
    @if($materials->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($materials as $material)
        <a href="{{ route('user.material.show', $material->material_id) }}" class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden group">
            <div class="aspect-video bg-gray-100 relative">
                @if($material->thumbnail_url)
                <img src="{{ $material->thumbnail_url }}" alt="{{ $material->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                @else
                <div class="w-full h-full flex items-center justify-center">
                    <i class="ri-video-line text-5xl text-gray-400"></i>
                </div>
                @endif
                <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all">
                    <div class="w-14 h-14 bg-white bg-opacity-90 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <i class="ri-play-fill text-3xl text-primary ml-1"></i>
                    </div>
                </div>
                @if($material->duration_minutes)
                <div class="absolute bottom-2 right-2 px-2 py-1 bg-black bg-opacity-70 text-white text-xs rounded">
                    {{ $material->formatted_duration }}
                </div>
                @endif
                @php
                $userAccess = $material->userAccess->first();
                @endphp
                @if($userAccess && $userAccess->is_completed)
                <div class="absolute top-2 right-2 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                    <i class="ri-check-line text-white"></i>
                </div>
                @endif
            </div>
            <div class="p-4">
                <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2">{{ $material->title }}</h3>
                <p class="text-sm text-gray-500 line-clamp-2 mb-3">{{ $material->description ?: 'Tidak ada deskripsi' }}</p>
                @if($userAccess)
                <div class="flex items-center justify-between">
                    <span class="text-xs {{ $userAccess->is_completed ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ $userAccess->is_completed ? 'Selesai' : 'Sedang dipelajari' }}
                    </span>
                    @if(!$userAccess->is_completed)
                    <div class="w-16 bg-gray-200 rounded-full h-1.5">
                        <div class="bg-primary h-1.5 rounded-full" style="width: {{ $userAccess->progress_percentage }}%"></div>
                    </div>
                    @endif
                </div>
                @else
                <span class="text-xs text-gray-400">Belum dimulai</span>
                @endif
            </div>
        </a>
        @endforeach
    </div>
    
    <!-- Pagination -->
    <div class="mt-6">
        {{ $materials->links() }}
    </div>
    @else
    <div class="text-center py-16 bg-white rounded-lg shadow">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-4">
            <i class="ri-video-line text-4xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">Tidak ada video</h3>
        <p class="text-gray-500">Anda belum memiliki akses ke video pembelajaran.</p>
    </div>
    @endif
</div>
@endsection
