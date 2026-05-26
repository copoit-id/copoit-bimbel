@extends('user.layout.new-user')

@section('title', 'Live Session')

@section('content')
@php
$user = auth()->user();
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
@endphp

<style>
.tab-active {
    background-color: #a855f7 !important;
    color: white !important;
}
</style>

<!-- Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('user.material.index') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Live Session</h1>
        <p class="text-gray-500 text-sm">Ikuti kelas online dan webinar interaktif</p>
    </div>
</div>

<!-- Filter Tabs -->
<div class="flex gap-2 mb-6 overflow-x-auto pb-2">
    <a href="{{ route('user.material.index') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        Semua
    </a>
    <a href="{{ route('user.material.videos') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        <i class="ri-video-line mr-1"></i>Video
    </a>
    <a href="{{ route('user.material.documents') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        <i class="ri-file-text-line mr-1"></i>Dokumen
    </a>
    <a href="{{ route('user.material.live-sessions') }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap tab-active">
        <i class="ri-live-line mr-1"></i>Live
    </a>
</div>

<!-- Live Sessions Grid -->
@if($materials->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @foreach($materials as $material)
    @php
    $isAccessible = $user && $material->has_access;
    $linkUrl = $isAccessible ? route('user.material.show', $material->material_id) : ($user ? route('user.package.my') . '?tab=packages' : route('login'));
    @endphp
    <a href="{{ $linkUrl }}"
       class="bg-white rounded-xl overflow-hidden border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all group">
        @if($material->thumbnail_url)
        <div class="aspect-video bg-gray-100">
            <img src="{{ $material->thumbnail_url }}" alt="{{ $material->title }}" class="w-full h-full object-cover">
        </div>
        @endif
        <div class="p-5">
        @if(!$material->thumbnail_url)
        <div class="flex items-start gap-4 mb-4">
            <div class="w-16 h-16 bg-gradient-to-br from-purple-100 to-pink-50 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="ri-live-line text-3xl text-purple-500"></i>
            </div>
            <div class="flex-1 min-w-0">
        @else
        <div>
        @endif
                <h3 class="font-medium text-gray-800 mb-1 line-clamp-2 group-hover:text-purple-500 transition-colors">{{ $material->title }}</h3>
                <p class="text-sm text-gray-400 line-clamp-2 mb-3">{{ $material->description ?? 'Live session interaktif' }}</p>

                @php
                $userAccess = $user ? $material->userAccess->first() : null;
                @endphp

                <div class="flex items-center gap-3">
                    @if(!$user)
                    <span class="px-2.5 py-1 bg-gray-100 text-gray-600 text-xs rounded-full font-medium">
                        <i class="ri-lock-line mr-1"></i>Login untuk akses
                    </span>
                    <span class="text-purple-500 text-sm font-medium group-hover:translate-x-1 transition-transform flex items-center">
                        Login <i class="ri-arrow-right-line ml-1"></i>
                    </span>
                    @elseif($userAccess && $userAccess->is_completed)
                    <span class="px-2.5 py-1 text-xs rounded-full font-medium" style="background-color: {{ $primaryColor }}20; color: {{ $primaryColor }}">
                        <i class="ri-check-line mr-1"></i>Sudah diikuti
                    </span>
                    <span class="text-purple-500 text-sm font-medium group-hover:translate-x-1 transition-transform flex items-center">
                        Detail <i class="ri-arrow-right-line ml-1"></i>
                    </span>
                    @else
                    <span class="px-2.5 py-1 bg-purple-100 text-purple-700 text-xs rounded-full font-medium">
                        <i class="ri-live-line mr-1"></i>Live Session
                    </span>
                    <span class="text-purple-500 text-sm font-medium group-hover:translate-x-1 transition-transform flex items-center">
                        Detail <i class="ri-arrow-right-line ml-1"></i>
                    </span>
                    @endif
                </div>
            </div>
            @if(!$material->thumbnail_url)
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
        <i class="ri-live-line text-3xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada live session</h3>
    <p class="text-gray-400 text-sm">Live session akan muncul di sini.</p>
</div>
@endif
@endsection
