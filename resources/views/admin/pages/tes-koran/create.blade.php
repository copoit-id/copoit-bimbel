@extends('admin.layout.admin')

@section('title', 'Tambah Tes Koran')

@section('content')
<div class="container mx-auto px-4">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.tes-koran.index') }}" title="Tes Koran" />
            <x-breadcrumb-item href="" title="Tambah Tes" />
        </x-slot>
    </x-breadcrumb>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Buat Tes Koran Baru</h2>

        @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('admin.tes-koran.store') }}" method="POST">
            @csrf

            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Nama Tes <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required
                           value="{{ old('name') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                           placeholder="Contoh: Tes Pauli - Batch 1">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="test_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Jenis Tes <span class="text-red-500">*</span>
                        </label>
                        <select id="test_type" name="test_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="pauli" {{ old('test_type') == 'pauli' ? 'selected' : '' }}>
                                Pauli (atas ke bawah)
                            </option>
                            <option value="kraepelin" {{ old('test_type') == 'kraepelin' ? 'selected' : '' }}>
                                Kraepelin (bawah ke atas)
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="direction" class="block text-sm font-medium text-gray-700 mb-1">
                            Arah Pengerjaan <span class="text-red-500">*</span>
                        </label>
                        <select id="direction" name="direction" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="top_to_bottom" {{ old('direction') == 'top_to_bottom' ? 'selected' : '' }}>
                                Atas ke Bawah
                            </option>
                            <option value="bottom_to_top" {{ old('direction') == 'bottom_to_top' ? 'selected' : '' }}>
                                Bawah ke Atas
                            </option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="duration_minutes" class="block text-sm font-medium text-gray-700 mb-1">
                            Durasi (Menit) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="duration_minutes" name="duration_minutes" required min="1" max="180"
                               value="{{ old('duration_minutes', 60) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="text-xs text-gray-400 mt-1">Default: 60 menit</p>
                    </div>

                    <div>
                        <label for="columns_count" class="block text-sm font-medium text-gray-700 mb-1">
                            Jumlah Kolom <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="columns_count" name="columns_count" required min="5" max="50"
                               value="{{ old('columns_count', 30) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="text-xs text-gray-400 mt-1">Default: 30 kolom</p>
                    </div>

                    <div>
                        <label for="rows_count" class="block text-sm font-medium text-gray-700 mb-1">
                            Baris per Kolom <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="rows_count" name="rows_count" required min="5" max="20"
                               value="{{ old('rows_count', 10) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="text-xs text-gray-400 mt-1">Default: 10 baris</p>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-medium text-blue-800 mb-2">
                        <i class="ri-information-line mr-2"></i>Info Konfigurasi
                    </h4>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• <strong>Pauli:</strong> Peserta menjumlahkan dari atas ke bawah</li>
                        <li>• <strong>Kraepelin:</strong> Peserta menjumlahkan dari bawah ke atas</li>
                        <li>• Angka hasil penjumlahan > 9, hanya ambil digit terakhir</li>
                        <li>• Setiap jawaban benar = 1 poin</li>
                        <li>• Stabilitas diukur dari konsistensi per kolom</li>
                    </ul>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t">
                <a href="{{ route('admin.tes-koran.index') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                    <i class="ri-save-line mr-2"></i>Simpan Tes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection