@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.certificate.index') }}" class="text-gray-600 hover:text-gray-800">
            <i class="ri-arrow-left-line text-xl"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tambah Sertifikat</h2>
            <p class="text-gray-600">Buat sertifikat baru</p>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200">
        <form action="{{ route('admin.certificate.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="p-6 space-y-6">
                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Sertifikat <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="certificate_name" value="{{ old('certificate_name') }}" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="e.g. Sertifikat Kelulusan CPNS">
                        @error('certificate_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Institusi Penerbit <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="institution_name" value="{{ old('institution_name', 'CPNS Academy') }}"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="CPNS Academy">
                        @error('institution_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Deskripsi sertifikat...">{{ old('description') }}</textarea>
                    @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Recipient Information -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Penerima</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Penerima <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="issued_to_name" value="{{ old('issued_to_name') }}" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Nama lengkap penerima">
                            @error('issued_to_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email Penerima <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="issued_to_email" value="{{ old('issued_to_email') }}" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="email@example.com">
                            @error('issued_to_email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Date Information -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Tanggal</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tanggal Terbit <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="issued_date" value="{{ old('issued_date', date('Y-m-d')) }}"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            @error('issued_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Kedaluwarsa</label>
                            <input type="date" name="expired_date" value="{{ old('expired_date') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            @error('expired_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Kosongkan jika sertifikat tidak memiliki masa berlaku
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Template Upload -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Template Sertifikat</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Template</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                            <div class="text-center">
                                <i class="ri-file-upload-line text-4xl text-gray-400 mb-2"></i>
                                <div class="mb-2">
                                    <label for="template" class="cursor-pointer">
                                        <span class="text-primary hover:text-primary/80">Klik untuk upload</span>
                                        <span class="text-gray-500"> atau drag and drop</span>
                                    </label>
                                    <input type="file" id="template" name="template" class="hidden"
                                        accept=".pdf,.jpg,.jpeg,.png">
                                </div>
                                <p class="text-xs text-gray-500">PDF, JPG, PNG (Max: 5MB)</p>
                            </div>
                        </div>
                        @error('template')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end px-6 py-4 bg-gray-50 border-t space-x-3">
                <a href="{{ route('admin.certificate.index') }}"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                    Simpan Sertifikat
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function addMetadata() {
    const container = document.getElementById('metadata-container');
    const newRow = document.createElement('div');
    newRow.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 metadata-row';
    newRow.innerHTML = `
        <input type="text" name="metadata[key][]" placeholder="Key" 
            class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
        <div class="flex gap-2">
            <input type="text" name="metadata[value][]" placeholder="Value" 
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            <button type="button" onclick="removeMetadata(this)" class="text-red-600 hover:text-red-800 px-2">
                <i class="ri-delete-bin-line"></i>
            </button>
        </div>
    `;
    container.appendChild(newRow);
}

function removeMetadata(button) {
    button.closest('.metadata-row').remove();
}

// File upload preview
document.getElementById('template').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // You can add preview functionality here
            console.log('File selected:', file.name);
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endsection