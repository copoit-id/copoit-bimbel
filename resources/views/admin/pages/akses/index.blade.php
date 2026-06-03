@extends('admin.layout.admin')
@section('title', 'Akses User')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="" title="Akses User" />
        </x-slot>
    </x-breadcrumb>
</div>
<x-page-desc title="Kelola Akses User - Paket, Materi, Tryout & Tes Koran"></x-page-desc>

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
        <i class="ri-live-line mr-1"></i>Live Session
        <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $tab === 'live' ? 'bg-white/20' : 'bg-gray-100' }}">
            {{ $tab === 'live' ? $items->count() : '' }}
        </span>
    </a>
    <a href="{{ route('admin.akses.index', ['tab' => 'tryouts']) }}" 
       class="px-5 py-2.5 rounded-lg font-medium transition-all text-sm {{ $tab === 'tryouts' ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50' }}">
        <i class="ri-file-list-3-line mr-1"></i>Tryout
        <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $tab === 'tryouts' ? 'bg-white/20' : 'bg-gray-100' }}">
            {{ $tab === 'tryouts' ? $items->count() : '' }}
        </span>
    </a>
    <a href="{{ route('admin.akses.index', ['tab' => 'tes_koran']) }}"
       class="px-5 py-2.5 rounded-lg font-medium transition-all text-sm {{ $tab === 'tes_koran' ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50' }}">
        <i class="ri-file-edit-line mr-1"></i>Tes Koran
        <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $tab === 'tes_koran' ? 'bg-white/20' : 'bg-gray-100' }}">
            {{ $tab === 'tes_koran' ? $items->count() : '' }}
        </span>
    </a>
</div>

<!-- Pending Requests Alert (Only for packages tab) -->
@if($tab === 'packages' && $pendingRequests->count() > 0)
<div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <i class="ri-notification-3-line text-amber-500 text-xl mr-3"></i>
            <div>
                <p class="font-medium text-amber-800">Ada {{ $pendingRequests->count() }} pengajuan akses menunggu persetujuan</p>
                <p class="text-sm text-amber-600">Pengajuan gratis bersyarat dari peserta</p>
            </div>
        </div>
        <button onclick="document.getElementById('pending-requests').scrollIntoView({behavior: 'smooth'})"
                class="px-4 py-2 bg-amber-100 text-amber-700 rounded-lg text-sm font-medium hover:bg-amber-200 transition-colors">
            Lihat Pengajuan
        </button>
    </div>
</div>
@endif

<!-- Items Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    @forelse($items as $item)
    @php
    $itemId = $item->package_id ?? $item->material_id ?? $item->tryout_id ?? $item->id;
    $itemName = $item->name ?? $item->title ?? 'Unknown';
    $userCount = $item->user_access_count ?? $item->userAccess->count() ?? 0;
    
    // Get type-specific icon
    $icon = match($tab) {
        'packages' => 'ri-folder-3-line',
        'videos' => 'ri-video-line',
        'documents' => 'ri-file-text-line',
        'live' => 'ri-live-line',
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
        'tryouts' => 'bg-orange-100 text-orange-600',
        'tes_koran' => 'bg-emerald-100 text-emerald-600',
        default => 'bg-gray-100 text-gray-600',
    };
    @endphp
    <div class="bg-white rounded-lg border border-gray-200 hover:shadow-lg transition-shadow group">
        <div class="p-5">
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
            
            <div class="mt-4 pt-4 border-t flex items-center justify-between">
                <span class="text-xs text-gray-400">
                    @if(in_array($tab, ['packages', 'tes_koran']) && $item->price > 0)
                        Rp {{ number_format($item->price, 0, ',', '.') }}
                    @elseif(in_array($tab, ['packages', 'tes_koran']))
                        Gratis
                    @else
                        {{ ucfirst(str_replace('_', ' ', $tab)) }}
                    @endif
                </span>
                <a href="{{ route('admin.akses.manage', ['type' => $tab, 'item_id' => $itemId]) }}" 
                   class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 transition-colors">
                    Kelola Akses
                </a>
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

<!-- Pending Requests Section -->
@if($tab === 'packages' && $pendingRequests->count() > 0)
<div id="pending-requests" class="mt-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Pengajuan Akses Gratis Bersyarat</h3>
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pengguna</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paket</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bukti</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diajukan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($pendingRequests as $request)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $request->user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $request->user->email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $request->package->name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($request->requirement_proof_path)
                            <a href="{{ asset('storage/' . $request->requirement_proof_path) }}" target="_blank"
                               class="text-primary hover:underline text-sm">
                                <i class="ri-attachment-line mr-1"></i>Lihat Bukti
                            </a>
                            @else
                            <span class="text-gray-400 text-sm">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $request->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-2">
                                <form action="{{ route('admin.akses.requests.approve', $request) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-green-100 text-green-700 rounded-lg text-xs font-medium hover:bg-green-200">
                                        Setujui
                                    </button>
                                </form>
                                <form action="{{ route('admin.akses.requests.reject', $request) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-xs font-medium hover:bg-red-200">
                                        Tolak
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection
