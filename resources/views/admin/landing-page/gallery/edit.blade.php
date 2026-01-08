@extends('admin.layout.admin')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
        <a href="{{ route('admin.landing-page.gallery.index') }}" class="hover:text-primary">Gallery</a>
        <i class="ri-arrow-right-s-line"></i>
        <span>Edit Gallery</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900">Edit Gallery</h1>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <form action="{{ route('admin.landing-page.gallery.update', $gallery) }}" method="POST" enctype="multipart/form-data" class="p-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                        Judul <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="title" name="title" value="{{ old('title', $gallery->title) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('title') border-red-500 @enderror">
                    @error('title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Deskripsi
                    </label>
                    <textarea id="description" name="description" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('description') border-red-500 @enderror">{{ old('description', $gallery->description) }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                        Kategori
                    </label>
                    <input type="text" id="category" name="category" value="{{ old('category', $gallery->category) }}" 
                           placeholder="Kegiatan, Fasilitas, dll."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('category') border-red-500 @enderror">
                    @error('category')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                        Gambar
                    </label>
                    @if($gallery->image)
                        <div class="mb-3">
                            <img src="{{ asset('storage/' . $gallery->image) }}" alt="{{ $gallery->title }}" 
                                 class="w-full max-w-xs h-32 object-cover rounded border">
                            <p class="text-sm text-gray-500 mt-1">Gambar saat ini</p>
                        </div>
                    @endif
                    <input type="file" id="image" name="image" accept="image/*"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('image') border-red-500 @enderror">
                    @error('image')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-1">Format: JPG, PNG, GIF. Max: 2MB. Kosongkan jika tidak ingin mengubah.</p>
                </div>

                <div>
                    <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                        Urutan <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="order" name="order" value="{{ old('order', $gallery->order) }}" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('order') border-red-500 @enderror">
                    @error('order')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $gallery->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary focus:ring-primary focus:ring-offset-0">
                        <span class="ml-2 text-sm text-gray-700">Aktif</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
            <a href="{{ route('admin.landing-page.gallery.index') }}" 
               class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                Batal
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                Update
            </button>
        </div>
    </form>
</div>
@endsection
