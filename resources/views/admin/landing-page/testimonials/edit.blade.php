@extends('admin.layout.admin')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
        <a href="{{ route('admin.landing-page.testimonials.index') }}" class="hover:text-primary">Testimoni</a>
        <i class="ri-arrow-right-s-line"></i>
        <span>Edit Testimoni</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900">Edit Testimoni</h1>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <form action="{{ route('admin.landing-page.testimonials.update', $testimonial) }}" method="POST" enctype="multipart/form-data" class="p-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name', $testimonial->name) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="position" class="block text-sm font-medium text-gray-700 mb-2">
                        Posisi/Jabatan
                    </label>
                    <input type="text" id="position" name="position" value="{{ old('position', $testimonial->position) }}" 
                           placeholder="Siswa Kelas 12, Alumni, dll."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('position') border-red-500 @enderror">
                    @error('position')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                        Isi Testimoni <span class="text-red-500">*</span>
                    </label>
                    <textarea id="content" name="content" rows="5" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('content') border-red-500 @enderror">{{ old('content', $testimonial->content) }}</textarea>
                    @error('content')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <label for="photo" class="block text-sm font-medium text-gray-700 mb-2">
                        Foto
                    </label>
                    @if($testimonial->photo)
                        <div class="mb-3">
                            <img src="{{ asset('storage/' . $testimonial->photo) }}" alt="{{ $testimonial->name }}" 
                                 class="w-24 h-24 rounded-full object-cover border">
                            <p class="text-sm text-gray-500 mt-1">Foto saat ini</p>
                        </div>
                    @endif
                    <input type="file" id="photo" name="photo" accept="image/*"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('photo') border-red-500 @enderror">
                    @error('photo')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-1">Format: JPG, PNG, GIF. Max: 2MB. Kosongkan jika tidak ingin mengubah.</p>
                </div>

                <div>
                    <label for="rating" class="block text-sm font-medium text-gray-700 mb-2">
                        Rating <span class="text-red-500">*</span>
                    </label>
                    <select id="rating" name="rating" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('rating') border-red-500 @enderror">
                        <option value="">Pilih Rating</option>
                        @for($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}" {{ old('rating', $testimonial->rating) == $i ? 'selected' : '' }}>
                            {{ $i }} Bintang {{ str_repeat('★', $i) }}
                        </option>
                        @endfor
                    </select>
                    @error('rating')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                        Urutan <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="order" name="order" value="{{ old('order', $testimonial->order) }}" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('order') border-red-500 @enderror">
                    @error('order')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $testimonial->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary focus:ring-primary focus:ring-offset-0">
                        <span class="ml-2 text-sm text-gray-700">Aktif</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
            <a href="{{ route('admin.landing-page.testimonials.index') }}" 
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
