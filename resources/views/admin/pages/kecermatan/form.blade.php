@extends('admin.layout.admin')

@section('title', isset($kecermatan) ? 'Edit Kecermatan' : 'Tambah Kecermatan')

@section('content')
@php
    $isEdit = isset($kecermatan);
    $selectedType = old('type', $kecermatan->type ?? 'kecermatan_polri');
    $selectedAccessDurationUnit = old('access_duration_unit', $kecermatan->access_duration_unit ?? 'forever');
    $selectedAccessDurationValue = old('access_duration_value', $kecermatan->access_duration_value ?? 1);
    $columnInputs = old('columns', [[
        'name' => 'Kolom 1',
        'duration_seconds' => 60,
        'questions_count' => 50,
        'column_type' => 'huruf',
        'references' => ['A', 'B', 'C', 'D', 'E'],
    ]]);
@endphp
<div class="container mx-auto px-4 space-y-6">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.kecermatan.index') }}" title="Kecermatan" />
            <x-breadcrumb-item href="" title="{{ $isEdit ? 'Edit Kecermatan' : 'Tambah Kecermatan' }}" />
        </x-slot>
    </x-breadcrumb>

    {{-- Page Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">{{ $isEdit ? 'Edit Kecermatan' : 'Tambah Kecermatan Baru' }}</h2>
            <p class="text-gray-500">Atur tipe, kolom, durasi per kolom, jumlah soal, dan status penjualan terpisah.</p>
        </div>
        <a href="{{ route('admin.kecermatan.index') }}"
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
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
    @endif

    <div class="bg-white rounded-lg shadow border border-gray-200">
        <form id="kecermatanForm" action="{{ $isEdit ? route('admin.kecermatan.update', $kecermatan) : route('admin.kecermatan.store') }}" method="POST" novalidate>
            @csrf
            @if($isEdit)
            @method('PUT')
            @endif

            <div class="p-6 space-y-6">
                {{-- Nama Kecermatan --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nama Kecermatan <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" required value="{{ old('name', $kecermatan->name ?? '') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Contoh: Kecermatan POLRI Batch 1">
                </div>

                {{-- Tipe & Status --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Tipe Kecermatan <span class="text-red-500">*</span></label>
                        <select id="type" name="type" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="kecermatan_polri" @selected($selectedType === 'kecermatan_polri')>Kecermatan POLRI</option>
                            <option value="kecermatan_tni" @selected($selectedType === 'kecermatan_tni')>Kecermatan TNI</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-2">POLRI memilih referensi yang hilang. TNI menjumlahkan dua angka.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 self-start md:pt-7">
                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $kecermatan->is_active ?? false)) class="rounded border-gray-300 text-primary">
                            Aktif
                        </label>
                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700 cursor-pointer">
                            <input type="checkbox" name="is_displayed" value="1" @checked(old('is_displayed', $kecermatan->is_displayed ?? false)) class="rounded border-gray-300 text-primary">
                            Tampilkan
                        </label>
                    </div>
                </div>

                {{-- Deskripsi --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                    <textarea id="description" name="description" rows="4"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Masukkan deskripsi singkat kecermatan...">{{ old('description', $kecermatan->description ?? '') }}</textarea>
                </div>

                {{-- Column Configuration (Create Mode Only) --}}
                @if(!$isEdit)
                <div class="p-6 bg-gray-50 rounded-lg space-y-6">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-3">
                        <div>
                            <h3 class="font-bold text-gray-700 text-lg">Konfigurasi Kolom</h3>
                            <p class="text-xs text-gray-500 mt-1">Kolom bisa dibuat langsung di menu tambah. Soal akan digenerate otomatis saat disimpan.</p>
                        </div>
                        <button type="button" id="addColumnBtn" class="flex items-center gap-2 text-sm text-primary hover:text-primary/80 font-semibold">
                            <i class="ri-add-line"></i>Tambah Kolom
                        </button>
                    </div>
                    <div id="columnsContainer" class="space-y-4">
                        @foreach($columnInputs as $columnIndex => $columnInput)
                        <div class="column-card bg-white border border-gray-200 rounded-lg p-6 space-y-4" data-column-index="{{ $columnIndex }}">
                            <div class="flex items-center justify-between border-b pb-3">
                                <h4 class="font-bold text-gray-900">Kolom <span data-column-number>{{ $columnIndex + 1 }}</span></h4>
                                <button type="button" class="remove-column-btn text-red-500 hover:text-red-700 border border-red-300 rounded-lg hover:bg-red-50 px-3 py-2 text-xs font-semibold">
                                    Hapus
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kolom</label>
                                    <input type="text" name="columns[{{ $columnIndex }}][name]" value="{{ $columnInput['name'] ?? 'Kolom ' . ($columnIndex + 1) }}" required 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Durasi (Detik)</label>
                                    <input type="number" name="columns[{{ $columnIndex }}][duration_seconds]" min="5" max="3600" value="{{ $columnInput['duration_seconds'] ?? 60 }}" required 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Soal</label>
                                    <input type="number" name="columns[{{ $columnIndex }}][questions_count]" min="1" max="500" value="{{ $columnInput['questions_count'] ?? 50 }}" required 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Kolom</label>
                                    <select name="columns[{{ $columnIndex }}][column_type]" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        @foreach(['huruf' => 'Huruf', 'angka' => 'Angka', 'simbol' => 'Simbol', 'campuran' => 'Campuran'] as $value => $label)
                                        <option value="{{ $value }}" @selected(($columnInput['column_type'] ?? 'huruf') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="references-grid grid gap-4 grid-cols-5 bg-gray-50 p-4 rounded-lg">
                                @foreach(['A', 'B', 'C', 'D', 'E'] as $referenceIndex => $referenceLabel)
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1 text-center">Referensi {{ $referenceLabel }}</label>
                                    <input type="text" name="columns[{{ $columnIndex }}][references][{{ $referenceIndex }}]" value="{{ $columnInput['references'][$referenceIndex] ?? $referenceLabel }}" 
                                        class="w-full text-center uppercase font-bold px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Pricing Configuration --}}
                <div class="p-6 bg-gray-50 rounded-lg space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Harga (Rp)</label>
                            <input type="number" id="price" name="price" min="0" step="1" value="{{ old('price', $kecermatan->price ?? 0) }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="0">
                            <p class="text-xs text-gray-500 mt-2">Isi harga untuk menjual terpisah. Kosongkan atau 0 untuk tidak dijual terpisah.</p>
                        </div>
                        <div class="flex items-center md:pt-7">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="is_for_sale" name="is_for_sale" value="1" @checked(old('is_for_sale', $kecermatan->is_for_sale ?? false))
                                    class="rounded border-gray-300 text-primary focus:ring-primary/20">
                                <span class="text-sm font-medium text-gray-700">Dijual Terpisah</span>
                            </label>
                        </div>
                    </div>

                    <div id="access-duration-wrapper" class="mt-6 {{ old('is_for_sale', $kecermatan->is_for_sale ?? false) ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Durasi Akses Setelah Dibeli</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <select name="access_duration_unit" id="access_duration_unit"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <option value="forever" @selected($selectedAccessDurationUnit === 'forever')>Selamanya</option>
                                    <option value="day" @selected($selectedAccessDurationUnit === 'day')>Hari</option>
                                    <option value="week" @selected($selectedAccessDurationUnit === 'week')>Minggu</option>
                                    <option value="month" @selected($selectedAccessDurationUnit === 'month')>Bulan</option>
                                    <option value="year" @selected($selectedAccessDurationUnit === 'year')>Tahun</option>
                                </select>
                            </div>
                            <div>
                                <input type="number" name="access_duration_value" id="access_duration_value" value="{{ $selectedAccessDurationValue }}" min="1" max="1200"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="Jumlah durasi">
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Pilih Selamanya untuk akses tanpa batas waktu.</p>
                    </div>
                </div>

                {{-- Alert Info --}}
                <div class="bg-blue-50 border border-blue-200 text-blue-700 p-4 rounded-lg">
                    <h4 class="font-medium mb-2"><i class="ri-information-line mr-2"></i>Info Kecermatan</h4>
                    <ul class="space-y-1 text-sm">
                        <li>• POLRI: Peserta memilih referensi A-E yang tidak muncul pada soal.</li>
                        <li>• TNI: Peserta memilih hasil penjumlahan dari dua angka, opsi 1 sampai 10.</li>
                        <li>• Timer berlaku per kolom dan divalidasi backend saat submit.</li>
                        <li>• Satu attempt digabung memakai token lintas kolom.</li>
                    </ul>
                </div>
            </div>

            {{-- Main Form Action Buttons --}}
            <div class="flex items-center justify-end px-6 py-5 space-x-2 border-t border-gray-200">
                <a href="{{ route('admin.kecermatan.index') }}"
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900">
                    Batal
                </a>
                <button type="submit"
                    class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5">
                    Simpan Kecermatan
                </button>
            </div>
        </form>
    </div>

    {{-- Add Column & Column List Section (Edit Mode Only) --}}
    @if($isEdit)
    <form id="addColumnForm" action="{{ route('admin.kecermatan.columns.store', $kecermatan) }}" method="POST">
        @csrf
    </form>

    <div class="mt-8 space-y-6">
        {{-- Add Column Box --}}
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 space-y-4">
            <div>
                <h3 class="font-bold text-gray-955 text-lg">Tambah Kolom Baru</h3>
                <p class="text-xs text-gray-500">Generate kolom tambahan langsung ke paket kecermatan ini.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-white p-4 rounded-lg border border-gray-200">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kolom</label>
                    <input type="text" name="name" form="addColumnForm" placeholder="Nama kolom" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Durasi (Detik)</label>
                    <input type="number" name="duration_seconds" form="addColumnForm" min="5" value="60" placeholder="Durasi detik" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Soal</label>
                    <input type="number" name="questions_count" form="addColumnForm" min="1" value="50" placeholder="Jumlah soal" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Kolom</label>
                    <select name="column_type" form="addColumnForm" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="huruf">Huruf</option>
                        <option value="angka">Angka</option>
                        <option value="simbol">Simbol</option>
                        <option value="campuran">Campuran</option>
                    </select>
                </div>
            </div>
            
            @if($kecermatan->type === 'kecermatan_polri')
            <div class="bg-white p-4 rounded-lg border border-gray-200">
                <label class="block text-sm font-medium text-gray-700 mb-3">Referensi Kolom (A - E)</label>
                <div class="grid gap-3 grid-cols-5">
                    @foreach(['A', 'B', 'C', 'D', 'E'] as $label)
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1 text-center font-mono">Referensi {{ $label }}</label>
                        <input type="text" name="references[]" form="addColumnForm" 
                            class="w-full text-center uppercase font-bold px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" required>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            
            <div class="flex justify-end">
                <button type="submit" form="addColumnForm" 
                    class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5">
                    Generate Kolom
                </button>
            </div>
        </div>

        {{-- Column List Table --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-bold text-gray-955 text-lg">Daftar Kolom Terdaftar</h3>
                @if($kecermatan->columns->count() > 0)
                <a href="{{ route('admin.kecermatan.preview', $kecermatan) }}" 
                    class="text-primary hover:text-primary/80 text-sm font-semibold flex items-center gap-1.5">
                    <i class="ri-eye-line"></i>
                    Preview Semua Soal
                </a>
                @endif
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3">Nama Kolom</th>
                            <th class="px-6 py-3">Durasi</th>
                            <th class="px-6 py-3">Jumlah Soal</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($kecermatan->columns as $column)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-bold text-gray-900">{{ $column->name }}</td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $column->duration_seconds }} detik
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $column->questions->count() }} soal
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ route('admin.kecermatan.columns.destroy', [$kecermatan, $column]) }}" method="POST" onsubmit="return confirm('Hapus kolom ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 font-semibold text-sm">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i class="ri-inbox-line text-3xl text-gray-300"></i>
                                    <span class="text-sm font-medium">Belum ada kolom terdaftar.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('type');
    const columnsContainer = document.getElementById('columnsContainer');
    const addColumnBtn = document.getElementById('addColumnBtn');
    const priceInput = document.getElementById('price');
    const saleCheckbox = document.getElementById('is_for_sale');
    const accessDurationWrapper = document.getElementById('access-duration-wrapper');
    const accessDurationUnit = document.getElementById('access_duration_unit');
    const accessDurationValue = document.getElementById('access_duration_value');

    function syncSaleFields() {
        if (!priceInput || !saleCheckbox) return;
        if (!saleCheckbox.checked) {
            priceInput.value = 0;
            priceInput.disabled = true;
        } else {
            priceInput.disabled = false;
        }

        accessDurationWrapper?.classList.toggle('hidden', !saleCheckbox.checked);
        if (accessDurationUnit) accessDurationUnit.disabled = !saleCheckbox.checked;
        if (accessDurationValue) accessDurationValue.disabled = !saleCheckbox.checked || accessDurationUnit?.value === 'forever';
    }

    function syncReferenceVisibility() {
        const isPolri = typeSelect?.value === 'kecermatan_polri';
        document.querySelectorAll('.references-grid').forEach((grid) => {
            grid.classList.toggle('hidden', !isPolri);
            grid.querySelectorAll('input').forEach((input) => {
                input.disabled = !isPolri;
                input.required = isPolri;
            });
        });
    }

    function reindexColumns() {
        document.querySelectorAll('.column-card').forEach((card, index) => {
            card.dataset.columnIndex = index;
            card.querySelector('[data-column-number]').textContent = index + 1;
            card.querySelectorAll('input, select').forEach((input) => {
                input.name = input.name.replace(/columns\[\d+]/, `columns[${index}]`);
            });
            const nameInput = card.querySelector('input[name$="[name]"]');
            if (nameInput && !nameInput.value.trim()) {
                nameInput.value = `Kolom ${index + 1}`;
            }
        });
    }

    function columnTemplate(index) {
        return `
            <div class="column-card bg-white border border-gray-200 rounded-lg p-6 space-y-4" data-column-index="${index}">
                <div class="flex items-center justify-between border-b pb-3">
                    <h4 class="font-bold text-gray-900">Kolom <span data-column-number>${index + 1}</span></h4>
                    <button type="button" class="remove-column-btn text-red-500 hover:text-red-700 border border-red-300 rounded-lg hover:bg-red-50 px-3 py-2 text-xs font-semibold">
                        Hapus
                    </button>
                </div>
                
                <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kolom</label>
                        <input type="text" name="columns[\${index}][name]" value="Kolom \${index + 1}" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Durasi (Detik)</label>
                        <input type="number" name="columns[\${index}][duration_seconds]" min="5" max="3600" value="60" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Soal</label>
                        <input type="number" name="columns[\${index}][questions_count]" min="1" max="500" value="50" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Kolom</label>
                        <select name="columns[\${index}][column_type]" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="huruf">Huruf</option>
                            <option value="angka">Angka</option>
                            <option value="simbol">Simbol</option>
                            <option value="campuran">Campuran</option>
                        </select>
                    </div>
                </div>
                
                <div class="references-grid grid gap-4 grid-cols-5 bg-gray-50 p-4 rounded-lg">
                    \${['A', 'B', 'C', 'D', 'E'].map((label, refIndex) => \`
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1 text-center">Referensi \${label}</label>
                            <input type="text" name="columns[\${index}][references][\${refIndex}]" value="\${label}" 
                                class="w-full text-center uppercase font-bold px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    \`).join('')}
                </div>
            </div>
        `;
    }

    addColumnBtn?.addEventListener('click', function () {
        const index = document.querySelectorAll('.column-card').length;
        columnsContainer.insertAdjacentHTML('beforeend', columnTemplate(index));
        syncReferenceVisibility();
    });

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-column-btn');
        if (!button) return;
        const cards = document.querySelectorAll('.column-card');
        if (cards.length <= 1) {
            alert('Minimal harus ada 1 kolom.');
            return;
        }
        button.closest('.column-card').remove();
        reindexColumns();
        syncReferenceVisibility();
    });

    typeSelect?.addEventListener('change', syncReferenceVisibility);
    saleCheckbox?.addEventListener('change', syncSaleFields);
    accessDurationUnit?.addEventListener('change', syncSaleFields);
    syncReferenceVisibility();
    syncSaleFields();
});
</script>
@endpush
