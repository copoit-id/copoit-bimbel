@extends('admin.layout.admin')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
        <a href="{{ route('admin.landing-page.hero.index') }}" class="hover:text-primary">Hero Section</a>
        <i class="ri-arrow-right-s-line"></i>
        <span>Tambah Hero</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900">Tambah Hero Section</h1>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <form action="{{ route('admin.landing-page.hero.store') }}" method="POST" enctype="multipart/form-data" class="p-6">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                        Judul <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('title') border-red-500 @enderror">
                    @error('title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="subtitle" class="block text-sm font-medium text-gray-700 mb-2">
                        Subtitle <span class="text-red-500">*</span>
                    </label>
                    <textarea id="subtitle" name="subtitle" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('subtitle') border-red-500 @enderror">{{ old('subtitle') }}</textarea>
                    @error('subtitle')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Deskripsi
                    </label>
                    <textarea id="description" name="description" rows="4" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Statistik Section -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Statistik</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="stat_1_number" class="block text-sm font-medium text-gray-700 mb-2">
                                Angka Statistik 1 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="stat_1_number" name="stat_1_number" value="{{ old('stat_1_number', '1000+') }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('stat_1_number') border-red-500 @enderror">
                            @error('stat_1_number')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="stat_1_text" class="block text-sm font-medium text-gray-700 mb-2">
                                Label Statistik 1 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="stat_1_text" name="stat_1_text" value="{{ old('stat_1_text', 'Siswa Aktif') }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('stat_1_text') border-red-500 @enderror">
                            @error('stat_1_text')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="stat_2_number" class="block text-sm font-medium text-gray-700 mb-2">
                                Angka Statistik 2 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="stat_2_number" name="stat_2_number" value="{{ old('stat_2_number', '95%') }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('stat_2_number') border-red-500 @enderror">
                            @error('stat_2_number')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="stat_2_text" class="block text-sm font-medium text-gray-700 mb-2">
                                Label Statistik 2 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="stat_2_text" name="stat_2_text" value="{{ old('stat_2_text', 'Tingkat Kelulusan') }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('stat_2_text') border-red-500 @enderror">
                            @error('stat_2_text')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="stat_3_number" class="block text-sm font-medium text-gray-700 mb-2">
                                Angka Statistik 3 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="stat_3_number" name="stat_3_number" value="{{ old('stat_3_number', '50+') }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('stat_3_number') border-red-500 @enderror">
                            @error('stat_3_number')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="stat_3_text" class="block text-sm font-medium text-gray-700 mb-2">
                                Label Statistik 3 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="stat_3_text" name="stat_3_text" value="{{ old('stat_3_text', 'Instruktur Expert') }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('stat_3_text') border-red-500 @enderror">
                            @error('stat_3_text')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <label for="button_text" class="block text-sm font-medium text-gray-700 mb-2">
                        Text Button <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="button_text" name="button_text" value="{{ old('button_text', 'Get Started') }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('button_text') border-red-500 @enderror">
                    @error('button_text')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="button_link" class="block text-sm font-medium text-gray-700 mb-2">
                        Link Button
                    </label>
                    <input type="url" id="button_link" name="button_link" value="{{ old('button_link') }}" 
                           placeholder="https://example.com"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('button_link') border-red-500 @enderror">
                    @error('button_link')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                        Gambar
                    </label>
                    <input type="file" id="image" name="image" accept="image/*"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('image') border-red-500 @enderror">
                    @error('image')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-1">Format: JPG, PNG, GIF. Max: 2MB</p>
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary focus:ring-primary focus:ring-offset-0">
                        <span class="ml-2 text-sm text-gray-700">Aktif</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
            <a href="{{ route('admin.landing-page.hero.index') }}" 
               class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                Batal
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                Simpan
            </button>
        </div>
    </form>
</div>
@endsection
