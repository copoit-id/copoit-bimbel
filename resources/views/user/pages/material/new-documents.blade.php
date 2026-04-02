@extends('user.layout.new-user')

@section('title', 'Dokumen Belajar')

@section('content')
@php
$user = auth()->user();
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
@endphp

<style>
.tab-active {
    background-color: #3b82f6 !important;
    color: white !important;
}
</style>

<!-- Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('user.material.index') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Dokumen</h1>
        <p class="text-gray-500 text-sm">Baca dan pelajari modul pembelajaran</p>
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
    <a href="{{ route('user.material.documents') }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap tab-active">
        <i class="ri-file-text-line mr-1"></i>Dokumen
    </a>
    <a href="{{ route('user.material.live-sessions') }}" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 whitespace-nowrap">
        <i class="ri-live-line mr-1"></i>Live
    </a>
</div>

<!-- Documents Grid -->
@if($materials->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @foreach($materials as $material)
    @php
    $isAccessible = $user && $material->has_access;
    $linkUrl = $isAccessible ? route('user.material.show', $material->material_id) : ($user ? route('user.package.my') . '?tab=packages' : route('login'));
    @endphp
    <a href="{{ $linkUrl }}" 
       class="bg-white rounded-xl p-5 border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all group">
        <div class="flex items-start gap-4">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="ri-file-text-line text-3xl text-blue-500"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="font-medium text-gray-800 mb-1 line-clamp-2 group-hover:text-blue-500 transition-colors">{{ $material->title }}</h3>
                <p class="text-sm text-gray-400 line-clamp-2 mb-3">{{ $material->description ?? 'Tidak ada deskripsi' }}</p>
                
                @php
                $userAccess = $user ? $material->userAccess->first() : null;
                @endphp
                
                <div class="flex items-center gap-3">
                    @if(!$user)
                    <span class="px-2.5 py-1 bg-gray-100 text-gray-600 text-xs rounded-full font-medium">
                        <i class="ri-lock-line mr-1"></i>Login untuk akses
                    </span>
                    <span class="text-blue-500 text-sm font-medium group-hover:translate-x-1 transition-transform flex items-center">
                        Login <i class="ri-arrow-right-line ml-1"></i>
                    </span>
                    @elseif($userAccess && $userAccess->is_completed)
                    <span class="px-2.5 py-1 text-xs rounded-full font-medium" style="background-color: {{ $primaryColor }}20; color: {{ $primaryColor }}">
                        <i class="ri-check-line mr-1"></i>Selesai dibaca
                    </span>
                    <span class="text-blue-500 text-sm font-medium group-hover:translate-x-1 transition-transform flex items-center">
                        Baca Lagi <i class="ri-arrow-right-line ml-1"></i>
                    </span>
                    @elseif($userAccess && $userAccess->is_in_progress)
                    <span class="px-2.5 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full font-medium">
                        <i class="ri-time-line mr-1"></i>Sedang dibaca
                    </span>
                    <span class="text-blue-500 text-sm font-medium group-hover:translate-x-1 transition-transform flex items-center">
                        Lanjutkan <i class="ri-arrow-right-line ml-1"></i>
                    </span>
                    @else
                    <span class="px-2.5 py-1 bg-gray-100 text-gray-600 text-xs rounded-full font-medium">
                        Belum dibaca
                    </span>
                    <span class="text-blue-500 text-sm font-medium group-hover:translate-x-1 transition-transform flex items-center">
                        Baca <i class="ri-arrow-right-line ml-1"></i>
                    </span>
                    @endif
                </div>
            </div>
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
        <i class="ri-file-text-line text-3xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada dokumen</h3>
    <p class="text-gray-400 text-sm">Dokumen pembelajaran akan muncul di sini.</p>
</div>
@endif
@endsection
