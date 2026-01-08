@extends('admin.layout.admin')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
        <a href="{{ route('admin.landing-page.cta.index') }}" class="hover:text-primary">Call To Action</a>
        <i class="ri-arrow-right-s-line"></i>
        <span>Edit CTA</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900">Edit CTA Section</h1>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <form action="{{ route('admin.landing-page.cta.update', $cta) }}" method="POST" class="p-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                        Judul <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="title" name="title" value="{{ old('title', $cta->title) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('title') border-red-500 @enderror">
                    @error('title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Deskripsi <span class="text-red-500">*</span>
                    </label>
                    <textarea id="description" name="description" rows="5" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('description') border-red-500 @enderror">{{ old('description', $cta->description) }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <label for="primary_button_text" class="block text-sm font-medium text-gray-700 mb-2">
                        Text Button Utama <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="primary_button_text" name="primary_button_text" value="{{ old('primary_button_text', $cta->primary_button_text) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('primary_button_text') border-red-500 @enderror">
                    @error('primary_button_text')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="secondary_button_text" class="block text-sm font-medium text-gray-700 mb-2">
                        Text Button Kedua <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="secondary_button_text" name="secondary_button_text" value="{{ old('secondary_button_text', $cta->secondary_button_text) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('secondary_button_text') border-red-500 @enderror">
                    @error('secondary_button_text')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $cta->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary focus:ring-primary focus:ring-offset-0">
                        <span class="ml-2 text-sm text-gray-700">Aktif</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div class="mt-8 pt-8 border-t border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Preview</h3>
            <div class="rounded-2xl p-8 text-white max-w-2xl" style="background-color: {{ $clientBranding['primary_color'] }}">
                <div class="flex items-center justify-center mb-6">
                    <div class="flex -space-x-2">
                        @for($i = 1; $i <= 4; $i++)
                        <div class="w-8 h-8 bg-white/20 rounded-full border-2 border-white flex items-center justify-center">
                            <i class="ri-user-line text-white text-xs"></i>
                        </div>
                        @endfor
                        <div class="w-8 h-8 bg-white/20 rounded-full border-2 border-white flex items-center justify-center">
                            <span class="text-xs text-white font-medium">+</span>
                        </div>
                    </div>
                </div>
                
                <h3 class="text-2xl font-bold mb-4 text-center" id="preview-title">
                    {{ $cta->title }}
                </h3>
                <p class="text-white/90 mb-6 text-center" id="preview-description">
                    {{ $cta->description }}
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button class="bg-white px-6 py-3 rounded-lg font-medium" id="preview-primary" style="color: {{ $clientBranding['primary_color'] }}">
                        {{ $cta->primary_button_text }}
                    </button>
                    <button class="border-2 border-white/30 text-white px-6 py-3 rounded-lg font-medium" id="preview-secondary">
                        {{ $cta->secondary_button_text }}
                    </button>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
            <a href="{{ route('admin.landing-page.cta.index') }}" 
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

<script>
// Live preview update
document.getElementById('title').addEventListener('input', function() {
    document.getElementById('preview-title').textContent = this.value || 'Siap Meraih Impian Akademik Terbaik?';
});

document.getElementById('description').addEventListener('input', function() {
    document.getElementById('preview-description').textContent = this.value || 'Bergabunglah dengan ribuan siswa yang telah merasakan transformasi belajar dan meraih prestasi gemilang bersama kami.';
});

document.getElementById('primary_button_text').addEventListener('input', function() {
    document.getElementById('preview-primary').textContent = this.value || 'Daftar Sekarang';
});

document.getElementById('secondary_button_text').addEventListener('input', function() {
    document.getElementById('preview-secondary').textContent = this.value || 'Login';
});
</script>
@endsection
