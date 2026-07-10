@extends('admin.layout.admin')
@section('title', 'Akses User')
@section('content')
@php
    $canManageTesKoran = auth()->user()?->hasPermission('tes_koran', 'view') ?? false;
@endphp

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="" title="Akses User" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="{{ $canManageTesKoran ? 'Kelola Akses User - Paket, Materi, Kelas, Tryout & Tes Koran' : 'Kelola Akses User - Paket, Materi, Kelas & Tryout' }}"></x-page-desc>

<!-- Tabs Navigation -->
<div class="bg-white rounded-lg border border-gray-200 p-2 mb-6 inline-flex flex-wrap gap-1">
    <a href="{{ route('admin.akses.index', ['tab' => 'packages']) }}" 
       class="px-5 py-2.5 rounded-lg font-medium transition-all text-sm {{ $tab === 'packages' ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50' }}">
        <i class="ri-folder-3-line mr-1"></i>Paket
        <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $tab === 'packages' ? 'bg-white/20' : 'bg-gray-100' }}">
            {{ $items->count() }}
        </span>
    </a>
    <a href="{{ route('admin.akses.index', ['tab' => 'videos']) }}" 
       class="px-5 py-2.5 rounded-lg font-medium transition-all text-sm {{ $tab === 'videos' ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50' }}">
        <i class="ri-video-line mr-1"></i>Video
        <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $tab === 'videos' ? 'bg-white/20' : 'bg-gray-100' }}">
            {{ $tab === 'videos' ? $items->count() : '' }}
        </span>
    </a>
    <a href="{{ route('admin.akses.index', ['tab' => 'documents']) }}" 
       class="px-5 py-2.5 rounded-lg font-medium transition-all text-sm {{ $tab === 'documents' ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50' }}">
        <i class="ri-file-text-line mr-1"></i>Dokumen
        <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $tab === 'documents' ? 'bg-white/20' : 'bg-gray-100' }}">
            {{ $tab === 'documents' ? $items->count() : '' }}
        </span>
    </a>
    <a href="{{ route('admin.akses.index', ['tab' => 'live']) }}" 
       class="px-5 py-2.5 rounded-lg font-medium transition-all text-sm {{ $tab === 'live' ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50' }}">
        <i class="ri-live-line mr-1"></i>{{ $clientBranding['live_session_label'] ?? 'Kelas Belajar' }}
        <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $tab === 'live' ? 'bg-white/20' : 'bg-gray-100' }}">
            {{ $tab === 'live' ? $items->count() : '' }}
        </span>
    </a>
    <a href="{{ route('admin.akses.index', ['tab' => 'classes']) }}"
       class="px-5 py-2.5 rounded-lg font-medium transition-all text-sm {{ $tab === 'classes' ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50' }}">
        <i class="ri-video-on-line mr-1"></i>Kelas Zoom
        <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $tab === 'classes' ? 'bg-white/20' : 'bg-gray-100' }}">
            {{ $tab === 'classes' ? $items->count() : '' }}
        </span>
    </a>
    <a href="{{ route('admin.akses.index', ['tab' => 'tryouts']) }}" 
       class="px-5 py-2.5 rounded-lg font-medium transition-all text-sm {{ $tab === 'tryouts' ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50' }}">
        <i class="ri-file-list-3-line mr-1"></i>Tryout
        <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $tab === 'tryouts' ? 'bg-white/20' : 'bg-gray-100' }}">
            {{ $tab === 'tryouts' ? $items->count() : '' }}
        </span>
    </a>
    @if($canManageTesKoran)
    <a href="{{ route('admin.akses.index', ['tab' => 'tes_koran']) }}"
       class="px-5 py-2.5 rounded-lg font-medium transition-all text-sm {{ $tab === 'tes_koran' ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50' }}">
        <i class="ri-file-edit-line mr-1"></i>Tes Koran
        <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $tab === 'tes_koran' ? 'bg-white/20' : 'bg-gray-100' }}">
            {{ $tab === 'tes_koran' ? $items->count() : '' }}
        </span>
    </a>
    @endif
</div>

<!-- Items Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
    @forelse($items as $item)
    @php
    $itemId = $item->package_id ?? $item->material_id ?? $item->class_id ?? $item->tryout_id ?? $item->id;
    $itemName = $item->name ?? $item->title ?? 'Unknown';
    $userCount = $item->user_access_count ?? $item->userAccess->count() ?? 0;
    $pendingCount = (int) ($item->pending_requests_count ?? 0);
    
    // Get type-specific icon
    $icon = match($tab) {
        'packages' => 'ri-folder-3-line',
        'videos' => 'ri-video-line',
        'documents' => 'ri-file-text-line',
        'live' => 'ri-live-line',
        'classes' => 'ri-video-on-line',
        'tryouts' => 'ri-file-list-3-line',
        'tes_koran' => 'ri-file-edit-line',
        default => 'ri-apps-line',
    };
    
    // Get color based on tab
    $colorClass = match($tab) {
        'packages' => 'bg-blue-100 text-blue-600',
        'videos' => 'bg-red-100 text-red-600',
        'documents' => 'bg-green-100 text-green-600',
        'live' => 'bg-purple-100 text-purple-600',
        'classes' => 'bg-cyan-100 text-cyan-600',
        'tryouts' => 'bg-orange-100 text-orange-600',
        'tes_koran' => 'bg-emerald-100 text-emerald-600',
        default => 'bg-gray-100 text-gray-600',
    };
    @endphp
    <div class="bg-white rounded-lg border border-gray-200 hover:shadow-lg transition-shadow group">
        <div class="p-5 flex h-full flex-col">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 {{ $colorClass }} rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="{{ $icon }} text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-gray-900 truncate" title="{{ $itemName }}">{{ $itemName }}</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $userCount }} user memiliki akses
                    </p>
                </div>
            </div>
            
            <div class="mt-auto pt-4">
                <div class="mb-3 border-t pt-4">
                    <span class="inline-flex max-w-full items-center rounded-full bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-500">
                    @if(in_array($tab, ['packages', 'tes_koran']) && $item->price > 0)
                        Rp {{ number_format($item->price, 0, ',', '.') }}
                    @elseif(in_array($tab, ['packages', 'tes_koran']))
                        Gratis
                    @else
                        {{ ucfirst(str_replace('_', ' ', $tab)) }}
                    @endif
                    </span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <a href="{{ route('admin.akses.requests.index', ['type' => $tab, 'item_id' => $itemId]) }}"
                       class="min-h-[40px] inline-flex items-center justify-center gap-1 rounded-lg px-3 py-2 text-center text-sm font-medium transition-colors {{ $pendingCount > 0 ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                        <i class="ri-inbox-archive-line mr-1"></i>Pengajuan
                        <span class="inline-flex min-w-5 justify-center rounded-full bg-white/70 px-1.5 text-xs">{{ $pendingCount }}</span>
                    </a>
                    <a href="{{ route('admin.akses.manage', ['type' => $tab, 'item_id' => $itemId]) }}"
                       class="min-h-[40px] inline-flex items-center justify-center rounded-lg bg-primary px-3 py-2 text-center text-sm font-medium text-white transition-colors hover:bg-primary/90">
                        Kelola Akses
                    </a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-full text-center py-12">
        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="ri-inbox-line text-3xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada {{ str_replace('s', '', $tab) }}</h3>
        <p class="text-gray-400 text-sm">Data akan muncul setelah ditambahkan.</p>
    </div>
    @endforelse
</div>

@endsection
