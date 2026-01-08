@extends('admin.layout.admin')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Hero Section</h1>
        <p class="text-gray-600">Kelola konten hero section landing page</p>
    </div>
    <a href="{{ route('admin.landing-page.hero.create') }}" 
       class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
        <i class="ri-add-line mr-2"></i>Tambah Hero
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="p-6">
        @if($heroes->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Gambar</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Judul</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Subtitle</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Status</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($heroes as $hero)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-4 px-4">
                                @if($hero->image)
                                    <img src="{{ asset('storage/' . $hero->image) }}" alt="{{ $hero->title }}" 
                                         class="w-16 h-12 object-cover rounded">
                                @else
                                    <div class="w-16 h-12 bg-gray-200 rounded flex items-center justify-center">
                                        <i class="ri-image-line text-gray-400"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-medium text-gray-900">{{ $hero->title }}</div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-gray-600 max-w-xs truncate">{{ $hero->subtitle }}</div>
                            </td>
                            <td class="py-4 px-4">
                                @if($hero->is_active)
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                        Aktif
                                    </span>
                                @else
                                    <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                        Tidak Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.landing-page.hero.edit', $hero) }}" 
                                       class="text-blue-600 hover:text-blue-800 text-sm">
                                        <i class="ri-edit-line"></i>
                                    </a>
                                    <form action="{{ route('admin.landing-page.hero.destroy', $hero) }}" method="POST" 
                                          class="inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <i class="ri-image-line text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada hero section</h3>
                <p class="text-gray-600 mb-4">Mulai dengan membuat hero section pertama untuk landing page.</p>
                <a href="{{ route('admin.landing-page.hero.create') }}" 
                   class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
                    Tambah Hero Section
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
