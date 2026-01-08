@extends('admin.layout.admin')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Gallery</h1>
        <p class="text-gray-600">Kelola gambar gallery yang ditampilkan di landing page</p>
    </div>
    <a href="{{ route('admin.landing-page.gallery.create') }}" 
       class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
        <i class="ri-add-line mr-2"></i>Tambah Gallery
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="p-6">
        @if($gallery->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($gallery as $item)
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                    <div class="aspect-video relative">
                        <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->title }}" 
                             class="w-full h-full object-cover">
                        <div class="absolute top-2 right-2">
                            @if($item->is_active)
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">
                                    Aktif
                                </span>
                            @else
                                <span class="bg-red-100 text-red-800 text-xs font-medium px-2 py-1 rounded-full">
                                    Tidak Aktif
                                </span>
                            @endif
                        </div>
                        @if($item->category)
                        <div class="absolute bottom-2 left-2">
                            <span class="bg-primary text-white text-xs px-2 py-1 rounded-full">
                                {{ $item->category }}
                            </span>
                        </div>
                        @endif
                    </div>
                    <div class="p-4">
                        <h3 class="font-medium text-gray-900 mb-2 truncate">{{ $item->title }}</h3>
                        @if($item->description)
                        <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ $item->description }}</p>
                        @endif
                        <div class="flex justify-between items-center">
                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2 py-1 rounded-full">
                                Order: {{ $item->order }}
                            </span>
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('admin.landing-page.gallery.edit', $item) }}" 
                                   class="text-blue-600 hover:text-blue-800 text-sm p-1">
                                    <i class="ri-edit-line"></i>
                                </a>
                                <form action="{{ route('admin.landing-page.gallery.destroy', $item) }}" method="POST" 
                                      class="inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm p-1">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <i class="ri-gallery-line text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada gallery</h3>
                <p class="text-gray-600 mb-4">Mulai dengan menambahkan gambar pertama untuk gallery landing page.</p>
                <a href="{{ route('admin.landing-page.gallery.create') }}" 
                   class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
                    Tambah Gallery
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
