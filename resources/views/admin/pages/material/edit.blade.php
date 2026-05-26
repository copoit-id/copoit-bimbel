@extends('admin.layout.admin')

@section('title', 'Edit Materi')

@section('content')
<div class="container mx-auto px-4">
    <div class="mb-6">
        <a href="{{ route('admin.material.index') }}" class="text-gray-600 hover:text-gray-800 flex items-center gap-1 mb-2">
            <i class="ri-arrow-left-line"></i>
            Kembali ke Daftar Materi
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Edit Materi</h1>
    </div>

    @if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.material.update', $material) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Judul Materi <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $material->title) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Materi <span class="text-red-500">*</span></label>
                        <select name="type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" onchange="updatePlaceholder()">
                            <option value="video" {{ old('type', $material->type) == 'video' ? 'selected' : '' }}>Video</option>
                            <option value="document" {{ old('type', $material->type) == 'document' ? 'selected' : '' }}>Dokumen/PDF</option>
                            <option value="live_session" {{ old('type', $material->type) == 'live_session' ? 'selected' : '' }}>Live Session</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL Konten <span class="text-red-500">*</span></label>
                        <input type="url" name="content_url" id="contentUrl" value="{{ old('content_url', $material->content_url) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="https://...">
                        <p class="text-xs text-gray-500 mt-1" id="urlHint">Masukkan URL video (YouTube, Vimeo, dll)</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Durasi (menit)</label>
                        <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $material->duration_minutes) }}" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Contoh: 30">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Urut</label>
                        <input type="number" name="order_number" value="{{ old('order_number', $material->order_number) }}" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="0">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Harga (Rp)</label>
                        <input type="number" name="price" value="{{ old('price', $material->price ?? 0) }}" min="0" step="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="0">
                        <p class="text-xs text-gray-500 mt-1">Isi harga untuk menjual terpisah. Kosongkan atau 0 untuk tidak dijual terpisah.</p>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $material->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-primary focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Aktif</span>
                        </label>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Thumbnail</label>
                        @if($material->thumbnail_url)
                        <div id="currentThumbnail" class="mb-2">
                            <div class="text-xs text-gray-500 mb-1">Thumbnail saat ini:</div>
                            <div class="bg-gray-100 rounded-lg overflow-hidden border border-gray-300" style="aspect-ratio: 16/9; max-width: 320px;">
                                <img src="{{ $material->thumbnail_url }}" alt="Current thumbnail" class="w-full h-full object-cover">
                            </div>
                        </div>
                        @endif
                        <div id="newThumbnailPreview" class="mb-2 hidden">
                            <div class="text-xs text-gray-500 mb-1">Preview baru (16:9):</div>
                            <div class="bg-gray-100 rounded-lg overflow-hidden border border-gray-300" style="aspect-ratio: 16/9; max-width: 320px;">
                                <img id="previewImage" src="" alt="Preview" class="w-full h-full object-cover">
                            </div>
                        </div>
                        <input type="file" name="thumbnail" accept="image/*" id="thumbnailInput" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-xs text-gray-500 mt-1">Rasio ideal: <strong>16:9</strong> (contoh: 640x360, 1280x720). Format: JPG, PNG, GIF. Maks: 2MB</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                        <select name="category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">(Tidak ada)</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->category_id }}" {{ old('category_id', $selectedCategory) == $category->category_id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <textarea name="description" rows="5" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Deskripsi singkat tentang materi...">{{ old('description', $material->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-6 border-t">
                <a href="{{ route('admin.material.index') }}" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Batal</a>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                    <i class="ri-save-line mr-1"></i>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function updatePlaceholder() {
        const type = document.querySelector('select[name="type"]').value;
        const urlInput = document.getElementById('contentUrl');
        const hint = document.getElementById('urlHint');
        
        switch(type) {
            case 'video':
                urlInput.placeholder = 'https://youtube.com/watch?v=...';
                hint.textContent = 'Masukkan URL video (YouTube, Vimeo, Google Drive, dll)';
                break;
            case 'document':
                urlInput.placeholder = 'https://drive.google.com/file/d/...';
                hint.textContent = 'Masukkan URL dokumen/PDF (Google Drive, Dropbox, dll)';
                break;
            case 'live_session':
                urlInput.placeholder = 'https://zoom.us/j/...';
                hint.textContent = 'Masukkan URL live session (Zoom, Google Meet, dll)';
                break;
        }
    }
    
    // Initialize on load
    updatePlaceholder();

    // Thumbnail preview for new upload
    const thumbnailInput = document.getElementById('thumbnailInput');
    const newThumbnailPreview = document.getElementById('newThumbnailPreview');
    const previewImage = document.getElementById('previewImage');

    if (thumbnailInput) {
        thumbnailInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    previewImage.src = event.target.result;
                    newThumbnailPreview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });
    }
</script>
@endsection
