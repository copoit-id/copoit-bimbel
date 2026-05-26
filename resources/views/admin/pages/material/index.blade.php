@extends('admin.layout.admin')

@section('title', 'Manajemen Materi')

@section('content')
<div class="container mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Manajemen Materi</h1>
            <p class="text-gray-600">Kelola materi pembelajaran</p>
        </div>
        <a href="{{ route('admin.material.create') }}" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <i class="ri-add-line"></i>
            Tambah Materi
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ session('error') }}
    </div>
    @endif

    <!-- Filter Tabs -->
    <div class="flex gap-2 mb-6">
        <a href="{{ route('admin.material.index') }}"
           class="px-4 py-2 rounded-lg text-sm font-medium {{ !request('type') ? 'bg-primary text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            Semua
        </a>
        <a href="{{ route('admin.material.index', ['type' => 'video']) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium {{ request('type') == 'video' ? 'bg-primary text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            <i class="ri-video-line mr-1"></i>Video
        </a>
        <a href="{{ route('admin.material.index', ['type' => 'document']) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium {{ request('type') == 'document' ? 'bg-primary text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            <i class="ri-file-text-line mr-1"></i>Dokumen
        </a>
        <a href="{{ route('admin.material.index', ['type' => 'live']) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium {{ request('type') == 'live' ? 'bg-primary text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            <i class="ri-live-line mr-1"></i>Live Session
        </a>
    </div>

    <!-- Materials Grid -->
    @php
    $typeColors = [
        'video' => 'bg-primary/10 text-primary',
        'document' => 'bg-primary/10 text-primary',
        'live' => 'bg-primary/10 text-primary',
    ];
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($materials as $material)
        <div class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-md hover:-translate-y-0.5 transition-all">
            <!-- Thumbnail / Icon -->
            <div class="flex items-start gap-3 mb-3">
                <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="{{ $material->type_icon ?? 'ri-file-text-line' }} text-primary text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">{{ $material->type_label ?? 'Video' }}</span>
                        @if($material->duration_minutes)
                        <span class="text-xs text-gray-400">{{ $material->formatted_duration }}</span>
                        @endif
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm line-clamp-2">{{ $material->title }}</h3>
                </div>
            </div>

            <!-- Price & Sale Label -->
            <div class="mb-2 flex items-center justify-between">
                @if($material->is_for_sale)
                <span class="text-sm font-bold" style="color: var(--client-color-primary, #1C3259)">Rp {{ number_format($material->price ?? 0, 0, ',', '.') }}</span>
                <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-medium">
                    <i class="ri-checkbox-circle-fill mr-0.5"></i>Dijual
                </span>
                @else
                <span></span>
                <span class="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full font-medium">
                    <i class="ri-forbid-line mr-0.5"></i>Tidak dijual
                </span>
                @endif
            </div>

            <!-- Description -->
            @if($material->description)
            <p class="text-xs text-gray-500 mb-3 line-clamp-2">{{ Str::limit($material->description, 80) }}</p>
            @endif

            <!-- Status & Actions -->
            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                <div>
                    @if($material->is_active)
                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">
                        <i class="ri-checkbox-circle-fill mr-1"></i>Aktif
                    </span>
                    @else
                    <span class="px-2 py-1 bg-gray-100 text-gray-500 text-xs rounded-full">
                        <i class="ri-close-circle-fill mr-1"></i>Nonaktif
                    </span>
                    @endif
                </div>
                <div class="flex items-center gap-1">
                    <form action="{{ route('admin.material.toggle', $material) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="p-1.5 text-gray-500 hover:text-primary rounded-lg hover:bg-primary/10 transition-colors"
                                title="{{ $material->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                            <i class="ri-{{ $material->is_active ? 'eye-off' : 'eye' }}-line text-lg"></i>
                        </button>
                    </form>
                    <a href="{{ route('admin.material.edit', $material) }}" class="p-1.5 text-gray-500 hover:text-primary rounded-lg hover:bg-primary/10 transition-colors" title="Edit">
                        <i class="ri-edit-line text-lg"></i>
                    </a>
                    <form action="{{ route('admin.material.destroy', $material) }}" method="POST" class="inline"
                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-1.5 text-gray-500 hover:text-red-600 rounded-lg hover:bg-red-100 transition-colors" title="Hapus">
                            <i class="ri-delete-bin-line text-lg"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full py-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                <i class="ri-book-open-line text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-1">Belum ada materi</h3>
            <p class="text-gray-500 mb-4">Mulai tambahkan materi pembelajaran</p>
            <a href="{{ route('admin.material.create') }}" class="inline-flex items-center text-primary hover:text-primary-dark">
                <i class="ri-add-line mr-1"></i>Tambah Materi Pertama
            </a>
        </div>
        @endforelse
    </div>

    @if($materials->hasPages())
    <div class="mt-6">
        {{ $materials->links() }}
    </div>
    @endif
</div>
@endsection