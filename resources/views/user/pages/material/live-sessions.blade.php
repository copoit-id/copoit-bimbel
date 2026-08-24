@extends('user.layout.user')

<?php $liveSessionLabel = $clientBranding['live_session_label'] ?? 'Kelas Belajar'; ?>
@section('title', $liveSessionLabel)

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex items-center text-sm text-gray-500">
            <a href="{{ route('user.material.index') }}" class="hover:text-primary">Materi</a>
            <i class="ri-arrow-right-s-line mx-2"></i>
            <span class="text-gray-900">{{ $liveSessionLabel }}</span>
        </nav>
    </div>

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $liveSessionLabel }}</h1>
            <p class="text-gray-600 mt-1">Ikuti kelas online dan webinar dengan pengajar</p>
        </div>
    </div>

    <!-- Materials Grid -->
    @if($materials->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($materials as $material)
        <a href="{{ route('user.material.show', $material->material_id) }}" class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden group">
            <div class="p-6">
                <div class="flex items-start">
                    <div class="w-16 h-16 bg-purple-100 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                        <i class="ri-live-line text-3xl text-purple-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2">{{ $material->title }}</h3>
                        <p class="text-sm text-gray-500 line-clamp-2">{{ $material->description ?: 'Tidak ada deskripsi' }}</p>
                    </div>
                </div>
                
                @php
                $userAccess = $material->userAccess->first();
                @endphp
                
                <div class="mt-4 pt-4 border-t flex items-center justify-between">
                    <div class="flex items-center">
                        @if($userAccess && $userAccess->is_completed)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="ri-check-line mr-1"></i>Selesai
                        </span>
                        @elseif($userAccess && $userAccess->is_in_progress)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="ri-time-line mr-1"></i>Sudah diikuti
                        </span>
                        @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            <i class="ri-live-line mr-1"></i>{{ $liveSessionLabel }}
                        </span>
                        @endif
                    </div>
                    <span class="text-primary group-hover:translate-x-1 transition-transform">
                        Detail <i class="ri-arrow-right-line"></i>
                    </span>
                </div>
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
            <i class="ri-live-line text-4xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">Tidak ada {{ strtolower($liveSessionLabel) }}</h3>
        <p class="text-gray-500">Anda belum memiliki akses ke {{ strtolower($liveSessionLabel) }}.</p>
    </div>
    @endif
</div>
@endsection
