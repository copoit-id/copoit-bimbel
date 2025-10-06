@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">{{ isset($package) ? 'Edit Paket' : 'Tambah Paket Baru' }}</h2>
            <p class="text-gray-500">{{ isset($package) ? 'Perbarui informasi paket' : 'Buat paket bimbel atau tryout
                baru' }}</p>
        </div>
        <a href="{{ route('admin.package.index') }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2">
            <i class="ri-arrow-left-line"></i>
            Kembali
        </a>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        <ul class="list-disc list-inside text-sm">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Create/Edit Form -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <form
            action="{{ isset($package) ? route('admin.package.update', $package->package_id) : route('admin.package.store') }}"
            method="POST" enctype="multipart/form-data">
            @csrf
            @if(isset($package))
            @method('PUT')
            @endif

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nama Paket <span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name"
                                value="{{ isset($package) ? $package->name : old('name') }}" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>

                        <div>
                            <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Gambar Paket</label>
                            @if(isset($package) && $package->image)
                            <div class="mb-3">
                                <img src="{{ Storage::url($package->image) }}" alt="Current image"
                                    class="w-32 h-20 object-cover rounded-lg border">
                                <p class="text-sm text-gray-500 mt-1">Gambar saat ini</p>
                            </div>
                            @endif
                            <input type="file" id="image" name="image" accept="image/*"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <p class="text-sm text-gray-500 mt-1">Format: JPG, PNG, WEBP (Max: 2MB)</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status <span
                                    class="text-red-500">*</span></label>
                            <select id="status" name="status" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="active" {{ (isset($package) && $package->status === 'active') ||
                                    old('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="inactive" {{ (isset($package) && $package->status === 'inactive') ||
                                    old('status') === 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                            </select>
                        </div>

                        <div>
                            <label for="type_package" class="block text-sm font-medium text-gray-700 mb-2">Tipe Paket
                                <span class="text-red-500">*</span></label>
                            <select id="type_package" name="type_package" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="bimbel" {{ (isset($package) && $package->type_package === 'bimbel') ||
                                    old('type_package') === 'bimbel' ? 'selected' : '' }}>Bimbel</option>
                                <option value="tryout" {{ (isset($package) && $package->type_package === 'tryout') ||
                                    old('type_package') === 'tryout' ? 'selected' : '' }}>Tryout</option>
                                <option value="sertifikasi" {{ (isset($package) && $package->type_package ===
                                    'sertifikasi') || old('type_package') === 'sertifikasi' ? 'selected' : ''
                                    }}>Sertifikasi</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="type_price" class="block text-sm font-medium text-gray-700 mb-2">Tipe Harga
                                <span class="text-red-500">*</span></label>
                            <select id="type_price" name="type_price" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="free" {{ (isset($package) && $package->type_price === 'free') ||
                                    old('type_price') === 'free' ? 'selected' : '' }}>Gratis</option>
                                <option value="paid" {{ (isset($package) && $package->type_price === 'paid') ||
                                    old('type_price') === 'paid' ? 'selected' : '' }}>Berbayar</option>
                            </select>
                        </div>

                        <div id="price-field"
                            class="{{ (isset($package) && $package->type_price === 'free') || old('type_price') === 'free' ? 'hidden' : '' }}">
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Harga <span
                                    class="text-red-500">*</span></label>
                            <input type="number" id="price" name="price" min="0"
                                value="{{ isset($package) ? $package->price : old('price', 0) }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                        <textarea id="description" name="description" rows="4"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Masukkan deskripsi paket...">{{ isset($package) ? $package->description : old('description') }}</textarea>
                    </div>

                    <div x-data="{
                        features: {{ isset($package) && $package->features ? json_encode(json_decode($package->features)) : (old('features') ? json_encode(old('features')) : "
                        ['']") }} }" class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fitur Paket</label>
                        <template x-for="(feature, index) in features" :key="index">
                            <div class="flex gap-2">
                                <input type="text" :name="'features[' + index + ']'" x-model="features[index]"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="Contoh: 50 PDF Materi Lengkap" />

                                <button type="button" @click="features.splice(index, 1)"
                                    class="px-3 py-2 text-red-500 hover:text-red-700 border border-red-300 rounded-lg hover:bg-red-50">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </template>

                        <button type="button" @click="features.push('')"
                            class="flex items-center gap-2 text-sm text-primary hover:text-primary/80 mt-2">
                            <i class="ri-add-line"></i>
                            Tambah Fitur
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end px-6 py-5 space-x-2 border-t border-gray-200">
                <a href="{{ route('admin.package.index') }}"
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900">
                    Batal
                </a>
                <button type="submit"
                    class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5">
                    {{ isset($package) ? 'Perbarui Paket' : 'Simpan Paket' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const typePriceSelect = document.getElementById('type_price');
    const priceField = document.getElementById('price-field');
    const priceInput = document.getElementById('price');

    function togglePriceField() {
        if (typePriceSelect.value === 'free') {
            priceField.classList.add('hidden');
            priceInput.value = 0;
            priceInput.removeAttribute('required');
        } else {
            priceField.classList.remove('hidden');
            priceInput.setAttribute('required', 'required');
        }
    }

    // Initial check
    togglePriceField();

    // Listen for changes
    typePriceSelect.addEventListener('change', togglePriceField);
});
</script>
@endsection
