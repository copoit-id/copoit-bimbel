@extends('admin.layout.admin')

@section('title', 'Manajemen Materi')

@section('content')
<div class="container mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Manajemen Materi</h1>
            <p class="text-gray-600">Kelola materi pembelajaran (video, dokumen, live session)</p>
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

    <!-- Materials Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($materials as $material)
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden">
            <!-- Thumbnail -->
            <div class="relative aspect-video bg-gray-100">
                @if($material->thumbnail_url)
                <img src="{{ $material->thumbnail_url }}" alt="{{ $material->title }}" class="w-full h-full object-cover">
                @else
                <div class="w-full h-full flex items-center justify-center">
                    <i class="{{ $material->type_icon }} text-4xl text-gray-400"></i>
                </div>
                @endif
                
                <!-- Type Badge -->
                <div class="absolute top-2 left-2">
                    <span class="px-2 py-1 bg-black bg-opacity-70 text-white text-xs rounded">
                        {{ $material->type_label }}
                    </span>
                </div>
                
                <!-- Status Badge -->
                <div class="absolute top-2 right-2">
                    @if($material->is_active)
                    <span class="px-2 py-1 bg-green-500 text-white text-xs rounded">Aktif</span>
                    @else
                    <span class="px-2 py-1 bg-gray-500 text-white text-xs rounded">Nonaktif</span>
                    @endif
                </div>
                
                <!-- Duration -->
                @if($material->duration_minutes)
                <div class="absolute bottom-2 right-2">
                    <span class="px-2 py-1 bg-black bg-opacity-70 text-white text-xs rounded">
                        <i class="ri-time-line mr-1"></i>{{ $material->formatted_duration }}
                    </span>
                </div>
                @endif
            </div>
            
            <!-- Content -->
            <div class="p-4">
                <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2">{{ $material->title }}</h3>
                <p class="text-sm text-gray-500 mb-3 line-clamp-2">{{ $material->description ?: 'Tidak ada deskripsi' }}</p>
                
                <!-- Meta -->
                <div class="text-xs text-gray-400 mb-3">
                    <span>Order: {{ $material->order_number }}</span> • 
                    <span>Oleh: {{ $material->creator?->name ?? 'Unknown' }}</span>
                </div>
                
                <!-- Actions -->
                <div class="flex items-center justify-between pt-3 border-t">
                    <a href="{{ $material->content_url }}" target="_blank" class="text-primary hover:text-primary-dark text-sm">
                        <i class="ri-external-link-line mr-1"></i>Lihat
                    </a>
                    <div class="flex gap-2">
                        <form action="{{ route('admin.material.toggle', $material) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="p-1.5 {{ $material->is_active ? 'text-yellow-600 hover:text-yellow-800' : 'text-green-600 hover:text-green-800' }}" title="{{ $material->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                <i class="ri-{{ $material->is_active ? 'eye-off' : 'eye' }}-line text-lg"></i>
                            </button>
                        </form>
                        <a href="{{ route('admin.material.edit', $material) }}" class="p-1.5 text-blue-600 hover:text-blue-800" title="Edit">
                            <i class="ri-edit-line text-lg"></i>
                        </a>
                        <form action="{{ route('admin.material.destroy', $material) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus materi ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 text-red-600 hover:text-red-800" title="Hapus">
                                <i class="ri-delete-bin-line text-lg"></i>
                            </button>
                        </form>
                    </div>
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
                <i class="ri-add-line mr-1"></i>
                Tambah Materi Pertama
            </a>
        </div>
        @endforelse
    </div>
</div>
@endsection
