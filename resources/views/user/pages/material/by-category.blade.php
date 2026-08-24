@extends('user.layout.user')

@section('title', 'Kategori: ' . $category->name)

@section('content')
@php
    $primaryColor = $clientBranding['primary_color'] ?? '#10b981';
@endphp
<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex items-center text-sm text-gray-500">
            <a href="{{ route('user.material.index') }}" class="hover:text-primary">Materi</a>
            <i class="ri-arrow-right-s-line mx-2"></i>
            <span class="text-gray-900">{{ $category->name }}</span>
        </nav>
    </div>

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            @if($category->icon)
            <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mr-4">
                <i class="{{ $category->icon }} text-2xl text-primary"></i>
            </div>
            @endif
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $category->name }}</h1>
                @if($category->description)
                <p class="text-gray-600 mt-1">{{ $category->description }}</p>
                @endif
            </div>
        </div>
    </div>

    @include('user.pages.material.partials.filter-sort', ['action' => route('user.material.category', $category->category_id)])

    <!-- Materials Grid -->
    @if($materials->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($materials as $material)
        <a href="{{ route('user.material.show', $material->material_id) }}" class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden group">
            <div class="aspect-video bg-gray-100 relative">
                @if($material->thumbnail_url)
                <img src="{{ $material->thumbnail_url }}" alt="{{ $material->title }}" loading="lazy" decoding="async" width="480" height="270" class="w-full h-full object-cover">
                @else
                <div class="w-full h-full flex items-center justify-center">
                    <i class="{{ $material->type_icon }} text-4xl text-gray-400"></i>
                </div>
                @endif
                <div class="absolute top-2 right-2 px-2 py-1 bg-black bg-opacity-70 text-white text-xs rounded">
                    {{ $material->type_label }}
                </div>
                @if($material->duration_minutes)
                <div class="absolute bottom-2 right-2 px-2 py-1 bg-black bg-opacity-70 text-white text-xs rounded">
                    <i class="ri-time-line mr-1"></i>{{ $material->formatted_duration }}
                </div>
                @endif
            </div>
            <div class="p-4">
                <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2">{{ $material->title }}</h3>
                <p class="text-sm text-gray-500 line-clamp-2">{{ $material->description ?: 'Tidak ada deskripsi' }}</p>
            </div>
        </a>
        @endforeach
    </div>
    
    <!-- Pagination -->
    <div class="mt-6">
        {{ $materials->appends(request()->query())->links() }}
    </div>
    @else
    <div class="text-center py-16 bg-white rounded-lg shadow">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-4">
            <i class="ri-folder-open-line text-4xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">Tidak ada materi</h3>
        <p class="text-gray-500">Belum ada materi dalam kategori ini.</p>
    </div>
    @endif
</div>
@endsection
