@extends('admin.layout.admin')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
        <a href="{{ route('admin.landing-page.features.index') }}" class="hover:text-primary">Keunggulan</a>
        <i class="ri-arrow-right-s-line"></i>
        <span>Tambah Keunggulan</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900">Tambah Keunggulan</h1>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <form action="{{ route('admin.landing-page.features.store') }}" method="POST" class="p-6">
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
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Deskripsi <span class="text-red-500">*</span>
                    </label>
                    <textarea id="description" name="description" rows="4" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <label for="icon" class="block text-sm font-medium text-gray-700 mb-2">
                        Icon Class
                    </label>
                    <input type="text" id="icon" name="icon" value="{{ old('icon') }}" 
                           placeholder="ri-star-line, ri-check-line, dll."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('icon') border-red-500 @enderror">
                    @error('icon')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-1">
                        Gunakan icon Remix Icon. Lihat: <a href="https://remixicon.com" target="_blank" class="text-blue-600">remixicon.com</a>
                    </p>
                    <div class="mt-2 p-2 border rounded bg-gray-50 flex items-center gap-2">
                        <span class="text-sm text-gray-600">Preview:</span>
                        <i id="icon-preview" class="{{ old('icon') ?: 'ri-star-line' }} text-2xl text-primary"></i>
                    </div>
                </div>

                <div>
                    <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                        Urutan <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="order" name="order" value="{{ old('order', 0) }}" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('order') border-red-500 @enderror">
                    @error('order')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
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
            <a href="{{ route('admin.landing-page.features.index') }}" 
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

<script>
const iconInput = document.getElementById('icon');
const iconPreview = document.getElementById('icon-preview');
const iconBaseClasses = ['text-2xl', 'text-primary'];

function updateIconPreview(value) {
    const iconClass = value && value.trim().length > 0 ? value.trim() : 'ri-star-line';
    iconPreview.className = [iconClass, ...iconBaseClasses].join(' ');
}

iconInput.addEventListener('input', function() {
    updateIconPreview(this.value);
});
</script>
@endsection
