@extends('admin.layout.admin')

@section('title', 'Tambah Paket Tes Koran')

@section('content')
<div class="container mx-auto px-4">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.tes-koran.index') }}" title="Tes Koran" />
            <x-breadcrumb-item href="" title="Tambah Paket" />
        </x-slot>
    </x-breadcrumb>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Buat Paket Tes Koran Baru</h2>

            @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('admin.tes-koran.store-package') }}" method="POST">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Nama Paket <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" required
                               value="{{ old('name') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                               placeholder="Contoh: Tes Pauli TNI">
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                            Deskripsi
                        </label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                  placeholder="Deskripsi paket tes...">{{ old('description') }}</textarea>
                    </div>

                    <div>
                        <label for="type_price" class="block text-sm font-medium text-gray-700 mb-1">
                            Tipe Harga <span class="text-red-500">*</span>
                        </label>
                        <select id="type_price" name="type_price" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="free_unconditional" {{ old('type_price') == 'free_unconditional' ? 'selected' : '' }}>
                                Gratis Tanpa Syarat
                            </option>
                            <option value="free_conditional" {{ old('type_price') == 'free_conditional' ? 'selected' : '' }}>
                                Gratis Bersyarat
                            </option>
                            <option value="paid" {{ old('type_price') == 'paid' ? 'selected' : '' }}>
                                Berbayar
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1">
                            Harga (Rp)
                        </label>
                        <input type="number" id="price" name="price" min="0"
                               value="{{ old('price', 0) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                               placeholder="0">
                    </div>

                    <div>
                        <label for="duration" class="block text-sm font-medium text-gray-700 mb-1">
                            Durasi Akses (Hari)
                        </label>
                        <input type="number" id="duration" name="duration" min="1"
                               value="{{ old('duration', 30) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t">
                    <a href="{{ route('admin.tes-koran.index') }}"
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                        <i class="ri-save-line mr-2"></i>Simpan Paket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection