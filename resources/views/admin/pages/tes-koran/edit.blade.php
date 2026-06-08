@extends('admin.layout.admin')

@section('title', 'Edit Tes Koran')

@section('content')
<div class="container mx-auto px-4">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.tes-koran.index') }}" title="Tes Koran" />
            <x-breadcrumb-item href="" title="Edit Tes" />
        </x-slot>
    </x-breadcrumb>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Edit Tes Koran</h2>

        @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form id="deleteTesKoranForm" action="{{ route('admin.tes-koran.destroy', $tesKoran) }}" method="POST"
              onsubmit="return confirm('Yakin hapus tes ini?')">
            @csrf
            @method('DELETE')
        </form>

        <form action="{{ route('admin.tes-koran.update', $tesKoran) }}" method="POST" novalidate>
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Nama Tes <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required
                           value="{{ old('name', $tesKoran->name) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>

                <div>
                    <label for="test_type" class="block text-sm font-medium text-gray-700 mb-1">
                        Jenis Tes <span class="text-red-500">*</span>
                    </label>
                    <select id="test_type" name="test_type" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="pauli" {{ $tesKoran->test_type == 'pauli' ? 'selected' : '' }}>
                            Pauli (otomatis atas ke bawah)
                        </option>
                        <option value="kraepelin" {{ $tesKoran->test_type == 'kraepelin' ? 'selected' : '' }}>
                            Kraepelin (otomatis bawah ke atas)
                        </option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Arah pengerjaan otomatis mengikuti jenis tes.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="number_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Jenis Angka
                        </label>
                        <select id="number_type" name="number_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="satuan" {{ old('number_type', $tesKoran->number_type ?? 'satuan') == 'satuan' ? 'selected' : '' }}>Satuan</option>
                            <option value="puluhan" {{ old('number_type', $tesKoran->number_type ?? 'satuan') == 'puluhan' ? 'selected' : '' }}>Puluhan</option>
                            <option value="ratusan" {{ old('number_type', $tesKoran->number_type ?? 'satuan') == 'ratusan' ? 'selected' : '' }}>Ratusan</option>
                        </select>
                    </div>

                    <div>
                        <label for="operation_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Operasi Hitung
                        </label>
                        <select id="operation_type" name="operation_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="addition" {{ old('operation_type', $tesKoran->operation_type ?? 'addition') == 'addition' ? 'selected' : '' }}>Penjumlahan</option>
                            <option value="subtraction" {{ old('operation_type', $tesKoran->operation_type ?? 'addition') == 'subtraction' ? 'selected' : '' }}>Pengurangan</option>
                            <option value="division" {{ old('operation_type', $tesKoran->operation_type ?? 'addition') == 'division' ? 'selected' : '' }}>Pembagian</option>
                        </select>
                    </div>

                    <div>
                        <label for="column_duration_seconds" class="block text-sm font-medium text-gray-700 mb-1">
                            Durasi per Kolom (Detik)
                        </label>
                        <input type="number" id="column_duration_seconds" name="column_duration_seconds" required min="10" max="3600"
                               value="{{ old('column_duration_seconds', $tesKoran->column_duration_seconds ?? 60) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="text-xs text-gray-400 mt-1">Waktu tidak tampil ke peserta.</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="columns_count" class="block text-sm font-medium text-gray-700 mb-1">
                            Jumlah Kolom
                        </label>
                        <input type="number" id="columns_count" name="columns_count" required min="5" max="50"
                               value="{{ old('columns_count', $tesKoran->columns_count) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label for="rows_count" class="block text-sm font-medium text-gray-700 mb-1">
                            Baris per Kolom
                        </label>
                        <input type="number" id="rows_count" name="rows_count" required min="5" max="20"
                               value="{{ old('rows_count', $tesKoran->rows_count) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Harga (Rp)</label>
                            <input type="number" id="price" name="price" min="0" step="1"
                                   value="{{ old('price', $tesKoran->price ?? 0) }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                   placeholder="0">
                            <p class="text-xs text-gray-500 mt-1">Isi harga untuk menjual terpisah. Kosongkan atau 0 untuk tidak dijual terpisah.</p>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" id="is_for_sale" name="is_for_sale" value="1" {{ old('is_for_sale', $tesKoran->is_for_sale ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-primary focus:ring-primary">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Dijual Terpisah</span>
                                </label>
                                <p class="text-xs text-gray-500 ml-6">Centang agar tes koran ini bisa dibeli secara individual.</p>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_displayed" value="1" {{ old('is_displayed', $tesKoran->is_displayed ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-primary focus:ring-primary">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Tampilkan</span>
                                </label>
                                <p class="text-xs text-gray-500 ml-6">Centang untuk menampilkan tes koran di halaman user.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" id="is_active" name="is_active" value="1"
                           {{ $tesKoran->is_active ? 'checked' : '' }}
                           class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                    <label for="is_active" class="text-sm text-gray-700">
                        Aktif / Tersedia untuk peserta
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-between mt-6 pt-4 border-t">
                <button type="submit" form="deleteTesKoranForm" class="text-red-600 hover:text-red-800 text-sm">
                    <i class="ri-delete-bin-line mr-1"></i>Hapus Tes
                </button>

                <div class="flex gap-3">
                    <a href="{{ route('admin.tes-koran.preview', $tesKoran) }}"
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="ri-eye-line mr-1"></i>Preview
                    </a>
                    <a href="{{ route('admin.tes-koran.results', $tesKoran) }}"
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="ri-bar-chart-line mr-1"></i>Hasil
                    </a>
                    <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                        <i class="ri-save-line mr-2"></i>Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const priceInput = document.getElementById('price');
    const saleCheckbox = document.getElementById('is_for_sale');

    function syncPriceInput() {
        if (!priceInput || !saleCheckbox) return;

        if (saleCheckbox.checked) {
            priceInput.disabled = false;
            return;
        }

        priceInput.value = 0;
        priceInput.disabled = true;
    }

    saleCheckbox?.addEventListener('change', syncPriceInput);
    syncPriceInput();
});
</script>
@endpush
