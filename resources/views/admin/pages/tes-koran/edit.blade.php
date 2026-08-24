@extends('admin.layout.admin')

@section('title', 'Edit Tes Koran')

@section('content')
@php
    $selectedAccessDurationUnit = old('access_duration_unit', $tesKoran->access_duration_unit ?? 'forever');
    $selectedAccessDurationValue = old('access_duration_value', $tesKoran->access_duration_value ?? 1);
    $sheetConfigs = old('sheets', $tesKoran->sheetConfigs()->map(fn ($sheet) => [
        'name' => $sheet['name'],
        'number_type' => $sheet['number_type'],
        'operation_type' => $sheet['operation_type'],
        'column_duration_seconds' => $sheet['column_duration_seconds'],
        'columns_count' => $sheet['columns_count'],
        'rows_count' => $sheet['rows_count'],
    ])->toArray());
    $sheetCount = old('sheet_count', count($sheetConfigs));
    $uniqueSheetSignatures = collect($sheetConfigs)->map(fn ($sheet) => implode('|', [
        $sheet['number_type'] ?? '',
        $sheet['operation_type'] ?? '',
        $sheet['column_duration_seconds'] ?? '',
        $sheet['columns_count'] ?? '',
        $sheet['rows_count'] ?? '',
    ]))->unique()->count();
    $isCustomSheets = old('custom_sheets', $uniqueSheetSignatures > 1);
@endphp
<div class="container mx-auto px-4">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.tes-koran.index') }}" title="Tes Koran" />
            <x-breadcrumb-item href="" title="Edit Tes" />
        </x-slot>
    </x-breadcrumb>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-800">Edit Tes Koran</h2>
            <p class="text-sm text-gray-500 mt-1">Ubah jumlah lembar langsung. Jika mode custom mati, semua lembar memakai setting utama yang sama.</p>
        </div>

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

        <form id="tesKoranForm" action="{{ route('admin.tes-koran.update', array_merge(request()->query(), ['tesKoran' => $tesKoran])) }}" method="POST" novalidate>
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Nama Tes <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required
                           value="{{ old('name', $tesKoran->name) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

                    <div>
                        <label for="logic_test_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Tipe Logic Test <span class="text-red-500">*</span>
                        </label>
                        <select id="logic_test_type" name="logic_test_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="standar" {{ old('logic_test_type', $tesKoran->logic_test_type ?? 'standar') == 'standar' ? 'selected' : '' }}>
                                Standar (per kolom, waktu per kolom)
                            </option>
                            <option value="stan" {{ old('logic_test_type', $tesKoran->logic_test_type ?? 'standar') == 'stan' ? 'selected' : '' }}>
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
                            <span id="durationLabel">Durasi per Kolom (Detik)</span>
                        </label>
                        <input type="number" id="column_duration_seconds" name="column_duration_seconds" required min="10" max="3600"
                               value="{{ old('column_duration_seconds', $tesKoran->column_duration_seconds ?? 60) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="text-xs text-gray-400 mt-1" id="durationHelp">Waktu tidak tampil ke peserta.</p>
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

                <div class="rounded-2xl border border-gray-200 p-5">
                    <div class="flex flex-col gap-4 mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-800">Konfigurasi Lembar</h3>
                            <p class="text-xs text-gray-500 mt-1">Masukkan jumlah lembar langsung. Jika tidak custom, semua lembar memakai setting utama di atas.</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-[180px_1fr_auto] gap-3 md:items-end">
                            <div>
                                <label for="sheet_count" class="block text-sm font-medium text-gray-700 mb-1">Jumlah Lembar</label>
                                <input type="number" id="sheet_count" name="sheet_count" min="1" max="50" value="{{ $sheetCount }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                            <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                                <input type="checkbox" id="custom_sheets" name="custom_sheets" value="1" {{ $isCustomSheets ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span>
                                    <span class="block text-sm font-semibold text-gray-700">Tambah lembar secara custom</span>
                                    <span class="block text-xs text-gray-500">Nyalakan jika jenis angka, operasi, durasi, kolom, atau baris tiap lembar berbeda.</span>
                                </span>
                            </label>
                            <button type="button" id="addSheetBtn" class="px-3 py-2 rounded-lg bg-primary text-white text-sm hover:bg-primary/90 {{ $isCustomSheets ? '' : 'hidden' }}">
                                <i class="ri-add-line mr-1"></i>Tambah Custom
                            </button>
                        </div>
                    </div>
                    <div id="sheetsCustomPanel" class="{{ $isCustomSheets ? '' : 'hidden' }}">
                        <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            Mode custom aktif. Perubahan di kartu lembar akan disimpan per lembar.
                        </div>
                    </div>
                    <div id="sheetsContainer" class="space-y-4 {{ $isCustomSheets ? '' : 'hidden' }}">
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
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Durasi per Kolom (Standar)</label>
                                    <input type="number" name="sheets[{{ $sheetIndex }}][column_duration_seconds]" min="10" max="3600" value="{{ $sheet['column_duration_seconds'] ?? 60 }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <p class="text-[11px] text-gray-400 mt-1">Untuk STAN, durasi total memakai field utama di atas.</p>
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

                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <input type="number" id="price" name="price" min="0" step="1"
                           value="{{ old('price', $tesKoran->price ?? 0) }}"
                           class="hidden"
                           placeholder="0">
                    <?php $selectedTypePrice = old('type_price', $tesKoran->type_price ?? 'paid'); ?>
                    <div class="mb-5">
                        <h3 class="text-base font-semibold text-gray-800">Akses & Penjualan</h3>
                        <p class="text-sm text-gray-500 mt-1">Atur apakah tes koran tampil di user dan bisa dibeli terpisah.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <input type="checkbox" name="is_displayed" value="1" {{ old('is_displayed', $tesKoran->is_displayed ?? true) ? 'checked' : '' }} class="mt-1 rounded border-gray-300 text-primary focus:ring-primary">
                            <span>
                                <span class="block text-sm font-semibold text-gray-800">Tampilkan di user</span>
                                <span class="block text-xs text-gray-500 mt-1">Jika mati, tes tidak muncul di katalog user.</span>
                            </span>
                        </label>
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <input type="checkbox" id="is_for_sale" name="is_for_sale" value="1" {{ old('is_for_sale', $tesKoran->is_for_sale ?? false) ? 'checked' : '' }} class="mt-1 rounded border-gray-300 text-primary focus:ring-primary">
                            <span>
                                <span class="block text-sm font-semibold text-gray-800">Dijual terpisah</span>
                                <span class="block text-xs text-gray-500 mt-1">Jika mati, tes tampil tapi tidak bisa dibeli individual.</span>
                            </span>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="type_price" class="block text-sm font-medium text-gray-700 mb-2">Tipe Harga</label>
                            <select id="type_price" name="type_price"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="paid" @selected($selectedTypePrice === 'paid')>Berbayar</option>
                                <option value="free_unconditional" @selected($selectedTypePrice === 'free_unconditional')>Gratis Tanpa Syarat</option>
                                <option value="free_conditional" @selected($selectedTypePrice === 'free_conditional')>Gratis Bersyarat</option>
                            </select>
                        </div>
                        <div id="price-wrapper">
                            <label for="price_visible" class="block text-sm font-medium text-gray-700 mb-2">Harga (Rp)</label>
                            <input type="number" id="price_visible" min="0" step="1"
                                   value="{{ old('price', $tesKoran->price ?? 0) }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                   placeholder="0">
                        </div>
                    </div>
                    <div id="conditional-requirement-wrapper" class="mt-4 {{ $selectedTypePrice === 'free_conditional' ? '' : 'hidden' }}">
                        <label for="conditional_requirement" class="block text-sm font-medium text-gray-700 mb-2">Syarat Akses Gratis</label>
                        <textarea id="conditional_requirement" name="conditional_requirement" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                  placeholder="Syarat akses gratis bersyarat">{{ old('conditional_requirement', $tesKoran->conditional_requirement ?? '') }}</textarea>
                    </div>
                    <div id="access-duration-wrapper" class="mt-4 {{ old('is_for_sale', $tesKoran->is_for_sale ?? false) ? '' : 'hidden' }}">
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
    const visiblePriceInput = document.getElementById('price_visible');
    const saleCheckbox = document.getElementById('is_for_sale');
    const typePriceSelect = document.getElementById('type_price');
    const priceWrapper = document.getElementById('price-wrapper');
    const requirementWrapper = document.getElementById('conditional-requirement-wrapper');
    const requirementInput = document.getElementById('conditional_requirement');

    function syncPriceInput() {
        if (!priceInput || !saleCheckbox) return;
        const typePrice = typePriceSelect?.value || 'paid';

        priceWrapper?.classList.toggle('hidden', !saleCheckbox.checked || typePrice !== 'paid');
        requirementWrapper?.classList.toggle('hidden', !saleCheckbox.checked || typePrice !== 'free_conditional');
        if (requirementInput) requirementInput.disabled = !saleCheckbox.checked || typePrice !== 'free_conditional';

        if (saleCheckbox.checked && typePrice === 'paid') {
            priceInput.disabled = false;
            priceInput.value = visiblePriceInput?.value || priceInput.value;
        } else {
            priceInput.value = 0;
            priceInput.disabled = true;
        }
    }

    saleCheckbox?.addEventListener('change', syncPriceInput);
    visiblePriceInput?.addEventListener('input', () => { priceInput.value = visiblePriceInput.value; });
    typePriceSelect?.addEventListener('change', syncPriceInput);
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
    const sheetsCustomPanel = document.getElementById('sheetsCustomPanel');
    const addSheetBtn = document.getElementById('addSheetBtn');
    const sheetCountInput = document.getElementById('sheet_count');
    const customSheetsInput = document.getElementById('custom_sheets');
    const baseInputs = {
        number_type: document.getElementById('number_type'),
        operation_type: document.getElementById('operation_type'),
        column_duration_seconds: document.getElementById('column_duration_seconds'),
        columns_count: document.getElementById('columns_count'),
        rows_count: document.getElementById('rows_count'),
    };

    function baseSheetValues(index) {
        return {
            name: `Lembar ${index + 1}`,
            number_type: baseInputs.number_type?.value || 'satuan',
            operation_type: baseInputs.operation_type?.value || 'addition',
            column_duration_seconds: baseInputs.column_duration_seconds?.value || 60,
            columns_count: baseInputs.columns_count?.value || 30,
            rows_count: baseInputs.rows_count?.value || 10,
        };
    }

    function selectOptions(options, selected) {
        return options.map(([value, label]) => `<option value="${value}" ${value === selected ? 'selected' : ''}>${label}</option>`).join('');
    }

    function sheetTemplate(index, values = {}) {
        const sheet = { ...baseSheetValues(index), ...values };
        return `
            <div class="sheet-card rounded-xl border border-gray-200 bg-gray-50/70 p-4" data-sheet-index="${index}">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h4 class="font-semibold text-gray-700">Lembar <span data-sheet-number>${index + 1}</span></h4>
                    <button type="button" class="remove-sheet-btn px-3 py-1.5 rounded-lg bg-red-50 text-red-600 text-xs font-medium hover:bg-red-100">Hapus</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nama Lembar</label>
                        <input type="text" name="sheets[${index}][name]" value="${sheet.name}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Jenis Angka</label>
                        <select name="sheets[${index}][number_type]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            ${selectOptions([['satuan', 'Satuan'], ['puluhan', 'Puluhan'], ['ratusan', 'Ratusan']], sheet.number_type)}
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Operasi</label>
                        <select name="sheets[${index}][operation_type]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            ${selectOptions([['addition', 'Penjumlahan'], ['subtraction', 'Pengurangan'], ['division', 'Pembagian']], sheet.operation_type)}
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Durasi per Kolom (Standar)</label>
                        <input type="number" name="sheets[${index}][column_duration_seconds]" min="10" max="3600" value="${sheet.column_duration_seconds}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <p class="text-[11px] text-gray-400 mt-1">Untuk STAN, durasi total memakai field utama di atas.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah Kolom</label>
                        <input type="number" name="sheets[${index}][columns_count]" min="1" max="50" value="${sheet.columns_count}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Baris/Kolom</label>
                        <input type="number" name="sheets[${index}][rows_count]" min="5" max="20" value="${sheet.rows_count}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>
        `;
    }

    function sheetCount() {
        return Math.max(1, Math.min(50, parseInt(sheetCountInput?.value || '1', 10) || 1));
    }

    function rebuildUniformSheets() {
        if (!sheetsContainer) return;
        const count = sheetCount();
        sheetCountInput.value = count;
        sheetsContainer.innerHTML = Array.from({ length: count }, (_, index) => sheetTemplate(index, baseSheetValues(index))).join('');
        renumberSheets();
    }

    function renumberSheets() {
        if (!sheetsContainer) return;
        sheetsContainer.querySelectorAll('.sheet-card').forEach((card, index) => {
            card.dataset.sheetIndex = index;
            card.querySelector('[data-sheet-number]').textContent = index + 1;
            const nameInput = card.querySelector('input[name$="[name]"]');
            if (nameInput && /^Lembar \d+$/.test(nameInput.value)) {
                nameInput.value = `Lembar ${index + 1}`;
            }
            card.querySelectorAll('[name^="sheets["]').forEach(input => {
                input.name = input.name.replace(/sheets\[\d+\]/, `sheets[${index}]`);
            });
        });
        if (sheetCountInput) {
            sheetCountInput.value = sheetsContainer.querySelectorAll('.sheet-card').length || 1;
        }
        sheetsContainer.querySelectorAll('.remove-sheet-btn').forEach(button => {
            button.disabled = sheetsContainer.querySelectorAll('.sheet-card').length <= 1;
            button.classList.toggle('opacity-50', button.disabled);
        });
    }

    function syncCustomMode() {
        const isCustom = customSheetsInput?.checked ?? false;
        sheetsContainer?.classList.toggle('hidden', !isCustom);
        sheetsCustomPanel?.classList.toggle('hidden', !isCustom);
        addSheetBtn?.classList.toggle('hidden', !isCustom);
        if (!isCustom) {
            rebuildUniformSheets();
        }
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

    sheetCountInput?.addEventListener('change', function () {
        if (customSheetsInput?.checked) {
            const current = sheetsContainer.querySelectorAll('.sheet-card').length;
            const target = sheetCount();
            if (target > current) {
                for (let index = current; index < target; index++) {
                    sheetsContainer.insertAdjacentHTML('beforeend', sheetTemplate(index));
                }
            } else if (target < current) {
                Array.from(sheetsContainer.querySelectorAll('.sheet-card')).slice(target).forEach(card => card.remove());
            }
            renumberSheets();
            return;
        }
        rebuildUniformSheets();
    });

    Object.values(baseInputs).forEach(input => {
        input?.addEventListener('change', function () {
            if (!(customSheetsInput?.checked)) {
                rebuildUniformSheets();
            }
        });
    });
    customSheetsInput?.addEventListener('change', syncCustomMode);
    document.getElementById('tesKoranForm')?.addEventListener('submit', function () {
        if (!(customSheetsInput?.checked)) {
            rebuildUniformSheets();
        } else {
            sheetCountInput?.dispatchEvent(new Event('change'));
        }
    });
    syncCustomMode();
    renumberSheets();
});
</script>
@endpush
