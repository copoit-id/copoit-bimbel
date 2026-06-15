@extends('admin.layout.admin')

@section('title', 'Tambah Tes Koran')

@section('content')
@php
    $selectedAccessDurationUnit = old('access_duration_unit', 'forever');
    $selectedAccessDurationValue = old('access_duration_value', 1);
    $sheetConfigs = old('sheets', [[
        'name' => 'Lembar 1',
        'number_type' => old('number_type', 'satuan'),
        'operation_type' => old('operation_type', 'addition'),
        'column_duration_seconds' => old('column_duration_seconds', 60),
        'columns_count' => old('columns_count', 30),
        'rows_count' => old('rows_count', 10),
    ]]);
@endphp
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

        <form action="{{ route('admin.tes-koran.store') }}" method="POST" novalidate>
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="test_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Jenis Tes <span class="text-red-500">*</span>
                        </label>
                        <select id="test_type" name="test_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="pauli" {{ old('test_type') == 'pauli' ? 'selected' : '' }}>
                                Pauli (otomatis atas ke bawah)
                            </option>
                            <option value="kraepelin" {{ old('test_type') == 'kraepelin' ? 'selected' : '' }}>
                                Kraepelin (otomatis bawah ke atas)
                            </option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Arah pengerjaan otomatis mengikuti jenis tes.</p>
                    </div>

                    <div>
                        <label for="logic_test_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Tipe Logic Test <span class="text-red-500">*</span>
                        </label>
                        <select id="logic_test_type" name="logic_test_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="standar" {{ old('logic_test_type') == 'standar' ? 'selected' : '' }}>
                                Standar (per kolom, waktu per kolom)
                            </option>
                            <option value="stan" {{ old('logic_test_type') == 'stan' ? 'selected' : '' }}>
                                STAN (full soal, waktu keseluruhan)
                            </option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">
                            <strong>Standar:</strong> soal dibagi per kolom dengan waktu per kolom.<br>
                            <strong>STAN:</strong> semua soal aktif sekaligus, timer total, tidak ada pindah kolom.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="number_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Jenis Angka <span class="text-red-500">*</span>
                        </label>
                        <select id="number_type" name="number_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="satuan" {{ old('number_type', 'satuan') == 'satuan' ? 'selected' : '' }}>Satuan</option>
                            <option value="puluhan" {{ old('number_type') == 'puluhan' ? 'selected' : '' }}>Puluhan</option>
                            <option value="ratusan" {{ old('number_type') == 'ratusan' ? 'selected' : '' }}>Ratusan</option>
                        </select>
                    </div>

                    <div>
                        <label for="operation_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Operasi Hitung <span class="text-red-500">*</span>
                        </label>
                        <select id="operation_type" name="operation_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="addition" {{ old('operation_type', 'addition') == 'addition' ? 'selected' : '' }}>Penjumlahan</option>
                            <option value="subtraction" {{ old('operation_type') == 'subtraction' ? 'selected' : '' }}>Pengurangan</option>
                            <option value="division" {{ old('operation_type') == 'division' ? 'selected' : '' }}>Pembagian</option>
                        </select>
                    </div>

                    <div>
                        <label for="column_duration_seconds" class="block text-sm font-medium text-gray-700 mb-1">
                            <span id="durationLabel">Durasi per Kolom (Detik)</span> <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="column_duration_seconds" name="column_duration_seconds" required min="10" max="3600"
                               value="{{ old('column_duration_seconds', 60) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="text-xs text-gray-400 mt-1" id="durationHelp">Waktu tidak tampil ke peserta.</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
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

                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-800">Konfigurasi Lembar</h3>
                            <p class="text-xs text-gray-500 mt-1">Atur jumlah lembar dan setting berbeda untuk tiap lembar.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="addSheetBtn" class="px-3 py-2 rounded-lg bg-primary text-white text-sm hover:bg-primary/90">
                                <i class="ri-add-line mr-1"></i>Tambah Lembar
                            </button>
                        </div>
                    </div>
                    <div id="sheetsContainer" class="space-y-4">
                        @foreach($sheetConfigs as $sheetIndex => $sheet)
                        <div class="sheet-card rounded-xl border border-gray-200 bg-gray-50/70 p-4" data-sheet-index="{{ $sheetIndex }}">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <h4 class="font-semibold text-gray-700">Lembar <span data-sheet-number>{{ $sheetIndex + 1 }}</span></h4>
                                <button type="button" class="remove-sheet-btn px-3 py-1.5 rounded-lg bg-red-50 text-red-600 text-xs font-medium hover:bg-red-100">
                                    Hapus
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Nama Lembar</label>
                                    <input type="text" name="sheets[{{ $sheetIndex }}][name]" value="{{ $sheet['name'] ?? 'Lembar ' . ($sheetIndex + 1) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Jenis Angka</label>
                                    <select name="sheets[{{ $sheetIndex }}][number_type]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="satuan" @selected(($sheet['number_type'] ?? 'satuan') === 'satuan')>Satuan</option>
                                        <option value="puluhan" @selected(($sheet['number_type'] ?? 'satuan') === 'puluhan')>Puluhan</option>
                                        <option value="ratusan" @selected(($sheet['number_type'] ?? 'satuan') === 'ratusan')>Ratusan</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Operasi</label>
                                    <select name="sheets[{{ $sheetIndex }}][operation_type]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="addition" @selected(($sheet['operation_type'] ?? 'addition') === 'addition')>Penjumlahan</option>
                                        <option value="subtraction" @selected(($sheet['operation_type'] ?? 'addition') === 'subtraction')>Pengurangan</option>
                                        <option value="division" @selected(($sheet['operation_type'] ?? 'addition') === 'division')>Pembagian</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Durasi/Kolom atau Total (Detik)</label>
                                    <input type="number" name="sheets[{{ $sheetIndex }}][column_duration_seconds]" min="10" max="3600" value="{{ $sheet['column_duration_seconds'] ?? 60 }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah Kolom</label>
                                    <input type="number" name="sheets[{{ $sheetIndex }}][columns_count]" min="1" max="50" value="{{ $sheet['columns_count'] ?? 30 }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Baris/Kolom</label>
                                    <input type="number" name="sheets[{{ $sheetIndex }}][rows_count]" min="5" max="20" value="{{ $sheet['rows_count'] ?? 10 }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Harga (Rp)</label>
                            <input type="number" id="price" name="price" min="0" step="1"
                                   value="{{ old('price', 0) }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                   placeholder="0">
                            <p class="text-xs text-gray-500 mt-1">Isi harga untuk menjual terpisah. Kosongkan atau 0 untuk tidak dijual terpisah.</p>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" id="is_for_sale" name="is_for_sale" value="1" {{ old('is_for_sale') ? 'checked' : '' }} class="rounded border-gray-300 text-primary focus:ring-primary">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Dijual Terpisah</span>
                                </label>
                                <p class="text-xs text-gray-500 ml-6">Centang agar tes koran ini bisa dibeli secara individual.</p>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_displayed" value="1" {{ old('is_displayed', true) ? 'checked' : '' }} class="rounded border-gray-300 text-primary focus:ring-primary">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Tampilkan</span>
                                </label>
                                <p class="text-xs text-gray-500 ml-6">Centang untuk menampilkan tes koran di halaman user.</p>
                            </div>
                        </div>
                    </div>

                    <div id="access-duration-wrapper" class="mt-6 {{ old('is_for_sale') ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Durasi Akses Setelah Dibeli</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <select name="access_duration_unit" id="access_duration_unit"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="forever" @selected($selectedAccessDurationUnit === 'forever')>Selamanya</option>
                                <option value="day" @selected($selectedAccessDurationUnit === 'day')>Hari</option>
                                <option value="week" @selected($selectedAccessDurationUnit === 'week')>Minggu</option>
                                <option value="month" @selected($selectedAccessDurationUnit === 'month')>Bulan</option>
                                <option value="year" @selected($selectedAccessDurationUnit === 'year')>Tahun</option>
                            </select>
                            <input type="number" name="access_duration_value" id="access_duration_value"
                                   value="{{ $selectedAccessDurationValue }}" min="1" max="1200"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                   placeholder="Jumlah durasi">
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Dipakai untuk pembelian tes koran terpisah. Jika tes koran masuk paket, aksesnya mengikuti durasi paket.</p>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-medium text-blue-800 mb-2">
                        <i class="ri-information-line mr-2"></i>Info Konfigurasi
                    </h4>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• <strong>Pauli:</strong> Peserta mengerjakan dari atas ke bawah</li>
                        <li>• <strong>Kraepelin:</strong> Peserta mengerjakan dari bawah ke atas</li>
                        <li>• Setiap kolom punya durasi yang sama dan peserta otomatis pindah kolom saat waktu habis</li>
                        <li>• Timer tidak ditampilkan ke peserta, hanya instruksi pindah kolom</li>
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

    const accessDurationUnit = document.getElementById('access_duration_unit');
    const accessDurationValue = document.getElementById('access_duration_value');
    const accessDurationWrapper = document.getElementById('access-duration-wrapper');
    function syncAccessDuration() {
        if (!accessDurationUnit || !accessDurationValue) return;
        const isForSale = saleCheckbox?.checked ?? false;
        accessDurationWrapper?.classList.toggle('hidden', !isForSale);
        accessDurationUnit.disabled = !isForSale;
        accessDurationValue.disabled = !isForSale || accessDurationUnit.value === 'forever';
    }
    accessDurationUnit?.addEventListener('change', syncAccessDuration);
    saleCheckbox?.addEventListener('change', syncAccessDuration);
    syncAccessDuration();

    const logicTestType = document.getElementById('logic_test_type');
    const durationLabel = document.getElementById('durationLabel');
    const durationHelp = document.getElementById('durationHelp');

    function syncDurationLabel() {
        if (!logicTestType || !durationLabel) return;
        if (logicTestType.value === 'stan') {
            durationLabel.textContent = 'Durasi Total (Detik)';
            durationHelp.textContent = 'Waktu keseluruhan untuk mengerjakan semua soal.';
        } else {
            durationLabel.textContent = 'Durasi per Kolom (Detik)';
            durationHelp.textContent = 'Waktu tidak tampil ke peserta.';
        }
    }

    logicTestType?.addEventListener('change', syncDurationLabel);
    syncDurationLabel();

    const sheetsContainer = document.getElementById('sheetsContainer');
    const addSheetBtn = document.getElementById('addSheetBtn');

    function sheetTemplate(index) {
        return `
            <div class="sheet-card rounded-xl border border-gray-200 bg-gray-50/70 p-4" data-sheet-index="${index}">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h4 class="font-semibold text-gray-700">Lembar <span data-sheet-number>${index + 1}</span></h4>
                    <button type="button" class="remove-sheet-btn px-3 py-1.5 rounded-lg bg-red-50 text-red-600 text-xs font-medium hover:bg-red-100">Hapus</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nama Lembar</label>
                        <input type="text" name="sheets[${index}][name]" value="Lembar ${index + 1}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Jenis Angka</label>
                        <select name="sheets[${index}][number_type]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="satuan">Satuan</option>
                            <option value="puluhan">Puluhan</option>
                            <option value="ratusan">Ratusan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Operasi</label>
                        <select name="sheets[${index}][operation_type]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="addition">Penjumlahan</option>
                            <option value="subtraction">Pengurangan</option>
                            <option value="division">Pembagian</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Durasi/Kolom atau Total (Detik)</label>
                        <input type="number" name="sheets[${index}][column_duration_seconds]" min="10" max="3600" value="60" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah Kolom</label>
                        <input type="number" name="sheets[${index}][columns_count]" min="1" max="50" value="30" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Baris/Kolom</label>
                        <input type="number" name="sheets[${index}][rows_count]" min="5" max="20" value="10" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>
        `;
    }

    function renumberSheets() {
        if (!sheetsContainer) return;
        sheetsContainer.querySelectorAll('.sheet-card').forEach((card, index) => {
            card.dataset.sheetIndex = index;
            card.querySelector('[data-sheet-number]').textContent = index + 1;
            card.querySelectorAll('[name^="sheets["]').forEach(input => {
                input.name = input.name.replace(/sheets\[\d+\]/, `sheets[${index}]`);
            });
        });
        sheetsContainer.querySelectorAll('.remove-sheet-btn').forEach(button => {
            button.disabled = sheetsContainer.querySelectorAll('.sheet-card').length <= 1;
            button.classList.toggle('opacity-50', button.disabled);
        });
    }

    addSheetBtn?.addEventListener('click', function () {
        const index = sheetsContainer.querySelectorAll('.sheet-card').length;
        sheetsContainer.insertAdjacentHTML('beforeend', sheetTemplate(index));
        renumberSheets();
    });

    sheetsContainer?.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-sheet-btn');
        if (!button || button.disabled) return;
        button.closest('.sheet-card')?.remove();
        renumberSheets();
    });

    renumberSheets();
});
</script>
@endpush
