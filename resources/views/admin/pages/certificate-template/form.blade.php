@extends('admin.layout.admin')

@section('content')
@php
    $isEditing = $certificateTemplate->exists;
    $layout = old('layout', $certificateTemplate->layout ?: []);
    $backgroundUrl = $isEditing ? route('admin.certificate.template.background', $certificateTemplate) : null;
    $additionalCustomTexts = collect($layout)->filter(fn ($config, $field) => str_starts_with((string) $field, 'custom_text_'));
    $additionalSubtestScores = collect($layout)->filter(fn ($config, $field) => str_starts_with((string) $field, 'subtest_score_'));
    $additionalOptionalFields = collect($layout)->filter(fn ($config, $field) => str_starts_with((string) $field, 'optional_'));
@endphp
@push('styles')
<style>
    .certificate-canvas-field.certificate-canvas-selected { border-style: solid; box-shadow: 0 0 0 3px rgb(28 50 89 / .28); z-index: 10; }
    [data-settings-field].certificate-settings-selected { border-color: var(--color-primary, #1C3259); box-shadow: 0 0 0 2px rgb(28 50 89 / .12); }
    #certificate-settings-panel input:not([type='checkbox']):not([type='hidden']):not([type='color']),
    #certificate-settings-panel select {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: .5rem;
        background: #fff;
        padding: .5rem .625rem;
        font-size: .75rem;
        line-height: 1rem;
    }
    #certificate-settings-panel input:focus,
    #certificate-settings-panel select:focus {
        border-color: var(--color-primary, #1C3259);
        outline: 0;
        box-shadow: 0 0 0 3px rgb(28 50 89 / .12);
    }
    #certificate-settings-panel input[type='color'] {
        height: 2.1rem;
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: .5rem;
        padding: .2rem;
    }
    #certificate-settings-panel input[name='name'] {
        padding: .625rem .75rem;
        font-size: .875rem;
        line-height: 1.25rem;
    }
    .certificate-canvas-field {
        box-sizing: border-box;
        max-width: 90%;
        padding: 0;
        border-width: 1px;
        line-height: 1.15;
        white-space: pre-line;
    }
</style>
@endpush
<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.certificate.template.index') }}" class="text-gray-500 hover:text-gray-800"><i class="ri-arrow-left-line text-xl"></i></a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $isEditing ? 'Atur Template Sertifikat' : 'Buat Template Sertifikat' }}</h1>
            <p class="mt-1 text-sm text-gray-500">Geser label di canvas untuk menentukan posisi isi sertifikat.</p>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" action="{{ $isEditing ? route('admin.certificate.template.update', $certificateTemplate) : route('admin.certificate.template.store') }}" class="space-y-6">
        @csrf
        @if($isEditing) @method('PUT') @endif

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-gray-900">Canvas Template</h2>
                        <p class="text-sm text-gray-500">Klik elemen atau pengaturannya untuk memilih, lalu tarik elemen berwarna untuk memindahkannya.</p>
                    </div>
                    <label class="cursor-pointer rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        <i class="ri-upload-2-line mr-1"></i> Ganti Background
                        <input id="background" name="background" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @required(! $isEditing)>
                    </label>
                </div>
                @error('background') <p class="mb-3 text-sm text-red-600">{{ $message }}</p> @enderror
                <div id="certificate-canvas-wrap" class="relative mx-auto hidden max-w-4xl overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                    <img id="certificate-background" src="{{ $backgroundUrl }}" alt="Preview background sertifikat" class="block h-auto w-full select-none">
                    @foreach($fieldDefinitions as $field => $label)
                        @php $config = array_merge(['enabled' => false, 'x' => 0, 'y' => 0, 'font_size' => 16, 'color' => '#1C3259', 'align' => 'left', 'text' => ''], $layout[$field] ?? []); @endphp
                        <button type="button" data-canvas-field="{{ $field }}" class="certificate-canvas-field absolute hidden cursor-move border border-dashed border-primary bg-white/80 text-primary" data-label="{{ $field === 'custom_text' ? ($config['text'] ?: $label) : $label }}">{{ $field === 'custom_text' ? ($config['text'] ?: $label) : $label }}</button>
                    @endforeach
                    @foreach($additionalCustomTexts as $field => $config)
                        <button type="button" data-canvas-field="{{ $field }}" class="certificate-canvas-field absolute hidden cursor-move border border-dashed border-primary bg-white/80 text-primary" data-label="{{ $config['text'] ?: 'Teks Bebas' }}">{{ $config['text'] ?: 'Teks Bebas' }}</button>
                    @endforeach
                    @foreach($additionalSubtestScores as $field => $config)
                        <button type="button" data-canvas-field="{{ $field }}" class="certificate-canvas-field absolute hidden cursor-move border border-dashed border-primary bg-white/80 text-primary" data-label="Nilai Subtest {{ $config['subtest_index'] ?? 1 }}">Nilai Subtest {{ $config['subtest_index'] ?? 1 }}</button>
                    @endforeach
                    @foreach($additionalOptionalFields as $field => $config)
                        @php $optionalLabel = $addableFieldDefinitions[$config['field_type'] ?? 'custom_text'] ?? 'Elemen Tambahan'; @endphp
                        <button type="button" data-canvas-field="{{ $field }}" class="certificate-canvas-field absolute hidden cursor-move border border-dashed border-primary bg-white/80 text-primary" data-label="{{ $optionalLabel }}">{{ $optionalLabel }}</button>
                    @endforeach
                </div>
                <div id="certificate-canvas-empty" class="flex min-h-80 items-center justify-center rounded-lg border-2 border-dashed border-gray-200 bg-gray-50 p-8 text-center text-sm text-gray-500">Upload background JPG, PNG, atau WEBP untuk mulai mengatur layout.</div>
            </section>

            <aside id="certificate-settings-panel" class="flex min-h-0 flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Nama Template</label>
                    <input name="name" value="{{ old('name', $certificateTemplate->name) }}" required class="w-full rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary" placeholder="Contoh: Sertifikat SKD Client A">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700"><input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-primary focus:ring-primary" @checked(old('is_active', $certificateTemplate->is_active))> Template aktif dan dapat dipilih di Tryout</label>
                <div id="certificate-field-settings" class="min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                    @foreach($fieldDefinitions as $field => $label)
                        @php $config = array_merge(['enabled' => false, 'x' => 0, 'y' => 0, 'font_size' => 16, 'color' => '#1C3259', 'align' => 'left', 'text' => ''], $layout[$field] ?? []); @endphp
                        <div class="rounded-lg border border-gray-200 p-3" data-settings-field="{{ $field }}">
                            <label class="flex items-center justify-between gap-2 text-sm font-semibold text-gray-800"><span>{{ $label }}</span><input data-enabled="{{ $field }}" type="checkbox" name="layout[{{ $field }}][enabled]" value="1" class="rounded border-gray-300 text-primary focus:ring-primary" @checked($config['enabled'])></label>
                            @if(str_starts_with($field, 'subtest_score_'))
                                <input type="hidden" name="layout[{{ $field }}][subtest_index]" value="{{ $config['subtest_index'] ?? (int) Str::afterLast($field, '_') }}">
                                <p class="mt-1 text-xs text-gray-500">Otomatis mengambil nilai subtest urutan ke-{{ $config['subtest_index'] ?? (int) Str::afterLast($field, '_') }}.</p>
                            @endif
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <label class="text-xs text-gray-500">X<input data-layout-input="x" data-field="{{ $field }}" type="number" step="0.1" name="layout[{{ $field }}][x]" value="{{ $config['x'] }}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                                <label class="text-xs text-gray-500">Y<input data-layout-input="y" data-field="{{ $field }}" type="number" step="0.1" name="layout[{{ $field }}][y]" value="{{ $config['y'] }}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                                <label class="text-xs text-gray-500">Ukuran @if($field === 'qr_code') QR @else font @endif<input data-layout-input="font_size" data-field="{{ $field }}" type="number" min="8" max="300" step="1" name="layout[{{ $field }}][font_size]" value="{{ $config['font_size'] }}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                                <label class="text-xs text-gray-500">Warna<input type="color" name="layout[{{ $field }}][color]" value="{{ $config['color'] }}" class="mt-1 h-7 w-full rounded border-gray-300 p-0.5"></label>
                                <label class="col-span-2 text-xs text-gray-500">Posisi teks<select name="layout[{{ $field }}][align]" class="mt-1 w-full rounded border-gray-300 text-xs"><option value="left" @selected($config['align'] === 'left')>Kiri</option><option value="center" @selected($config['align'] === 'center')>Tengah</option><option value="right" @selected($config['align'] === 'right')>Kanan</option></select></label>
                            </div>
                        </div>
                    @endforeach
                    <div id="custom-text-fields" class="space-y-3">
                        @foreach($additionalCustomTexts as $field => $config)
                            <div class="rounded-lg border border-gray-200 p-3" data-settings-field="{{ $field }}">
                                <div class="flex items-center justify-between gap-2"><label class="flex items-center gap-2 text-sm font-semibold text-gray-800"><input data-enabled="{{ $field }}" type="checkbox" name="layout[{{ $field }}][enabled]" value="1" class="rounded border-gray-300 text-primary focus:ring-primary" @checked($config['enabled'] ?? false)> Teks Bebas</label><button type="button" data-remove-field="{{ $field }}" class="rounded p-1 text-red-600 hover:bg-red-50" title="Hapus teks"><i class="ri-delete-bin-line"></i></button></div>
                                <input data-custom-text="{{ $field }}" type="text" name="layout[{{ $field }}][text]" value="{{ $config['text'] ?? '' }}" class="mt-2 w-full rounded border-gray-300 text-xs" placeholder="Isi teks bebas">
                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    <label class="text-xs text-gray-500">X<input data-layout-input="x" data-field="{{ $field }}" type="number" step="0.1" name="layout[{{ $field }}][x]" value="{{ $config['x'] ?? 527 }}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                                    <label class="text-xs text-gray-500">Y<input data-layout-input="y" data-field="{{ $field }}" type="number" step="0.1" name="layout[{{ $field }}][y]" value="{{ $config['y'] ?? 1020 }}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                                    <label class="text-xs text-gray-500">Ukuran font<input data-layout-input="font_size" data-field="{{ $field }}" type="number" min="8" max="300" step="1" name="layout[{{ $field }}][font_size]" value="{{ $config['font_size'] ?? 16 }}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                                    <label class="text-xs text-gray-500">Warna<input type="color" name="layout[{{ $field }}][color]" value="{{ $config['color'] ?? '#1C3259' }}" class="mt-1 h-7 w-full rounded border-gray-300 p-0.5"></label>
                                    <label class="col-span-2 text-xs text-gray-500">Posisi teks<select name="layout[{{ $field }}][align]" class="mt-1 w-full rounded border-gray-300 text-xs"><option value="left" @selected(($config['align'] ?? 'center') === 'left')>Kiri</option><option value="center" @selected(($config['align'] ?? 'center') === 'center')>Tengah</option><option value="right" @selected(($config['align'] ?? 'center') === 'right')>Kanan</option></select></label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div id="subtest-score-fields" class="space-y-3">
                        @foreach($additionalSubtestScores as $field => $config)
                            <div class="rounded-lg border border-gray-200 p-3" data-settings-field="{{ $field }}">
                                <div class="flex items-center justify-between gap-2"><label class="flex items-center gap-2 text-sm font-semibold text-gray-800"><input data-enabled="{{ $field }}" type="checkbox" name="layout[{{ $field }}][enabled]" value="1" class="rounded border-gray-300 text-primary focus:ring-primary" @checked($config['enabled'] ?? false)> Nilai Subtest</label><button type="button" data-remove-field="{{ $field }}" class="rounded p-1 text-red-600 hover:bg-red-50" title="Hapus nilai subtest"><i class="ri-delete-bin-line"></i></button></div>
                                <label class="mt-2 block text-xs text-gray-500">Ambil nilai subtest urutan ke-<input data-subtest-index="{{ $field }}" type="number" min="1" max="100" name="layout[{{ $field }}][subtest_index]" value="{{ $config['subtest_index'] ?? 1 }}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                                <p class="mt-1 text-xs text-gray-500">Urutan mengikuti konfigurasi subtest Tryout.</p>
                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    <label class="text-xs text-gray-500">X<input data-layout-input="x" data-field="{{ $field }}" type="number" step="0.1" name="layout[{{ $field }}][x]" value="{{ $config['x'] ?? 527 }}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                                    <label class="text-xs text-gray-500">Y<input data-layout-input="y" data-field="{{ $field }}" type="number" step="0.1" name="layout[{{ $field }}][y]" value="{{ $config['y'] ?? 1020 }}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                                    <label class="text-xs text-gray-500">Ukuran font<input data-layout-input="font_size" data-field="{{ $field }}" type="number" min="8" max="300" step="1" name="layout[{{ $field }}][font_size]" value="{{ $config['font_size'] ?? 16 }}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                                    <label class="text-xs text-gray-500">Warna<input type="color" name="layout[{{ $field }}][color]" value="{{ $config['color'] ?? '#1C3259' }}" class="mt-1 h-7 w-full rounded border-gray-300 p-0.5"></label>
                                    <label class="col-span-2 text-xs text-gray-500">Posisi teks<select name="layout[{{ $field }}][align]" class="mt-1 w-full rounded border-gray-300 text-xs"><option value="left" @selected(($config['align'] ?? 'center') === 'left')>Kiri</option><option value="center" @selected(($config['align'] ?? 'center') === 'center')>Tengah</option><option value="right" @selected(($config['align'] ?? 'center') === 'right')>Kanan</option></select></label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div id="optional-fields" class="space-y-3">
                        @foreach($additionalOptionalFields as $field => $config)
                            @php
                                $fieldType = $config['field_type'] ?? 'custom_text';
                                $optionalLabel = $addableFieldDefinitions[$fieldType] ?? 'Elemen Tambahan';
                            @endphp
                            <div class="rounded-lg border border-gray-200 p-3" data-settings-field="{{ $field }}">
                                <div class="flex items-center justify-between gap-2"><label class="flex items-center gap-2 text-sm font-semibold text-gray-800"><input data-enabled="{{ $field }}" type="checkbox" name="layout[{{ $field }}][enabled]" value="1" class="rounded border-gray-300 text-primary focus:ring-primary" @checked($config['enabled'] ?? false)> {{ $optionalLabel }}</label><button type="button" data-remove-field="{{ $field }}" class="rounded p-1 text-red-600 hover:bg-red-50" title="Hapus elemen"><i class="ri-delete-bin-line"></i></button></div>
                                <input type="hidden" name="layout[{{ $field }}][field_type]" value="{{ $fieldType }}">
                                @if($fieldType === 'custom_text')
                                    <input data-custom-text="{{ $field }}" type="text" name="layout[{{ $field }}][text]" value="{{ $config['text'] ?? '' }}" class="mt-2 w-full rounded border-gray-300 text-xs" placeholder="Isi teks bebas">
                                @endif
                                @if($fieldType === 'subtest_score')
                                    <label class="mt-2 block text-xs text-gray-500">Ambil nilai subtest urutan ke-<input data-subtest-index="{{ $field }}" type="number" min="1" max="100" name="layout[{{ $field }}][subtest_index]" value="{{ $config['subtest_index'] ?? 1 }}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                                @endif
                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    <label class="text-xs text-gray-500">X<input data-layout-input="x" data-field="{{ $field }}" type="number" step="0.1" name="layout[{{ $field }}][x]" value="{{ $config['x'] ?? 527 }}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                                    <label class="text-xs text-gray-500">Y<input data-layout-input="y" data-field="{{ $field }}" type="number" step="0.1" name="layout[{{ $field }}][y]" value="{{ $config['y'] ?? 1020 }}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                                    <label class="text-xs text-gray-500">Ukuran font<input data-layout-input="font_size" data-field="{{ $field }}" type="number" min="8" max="300" step="1" name="layout[{{ $field }}][font_size]" value="{{ $config['font_size'] ?? 16 }}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                                    <label class="text-xs text-gray-500">Warna<input type="color" name="layout[{{ $field }}][color]" value="{{ $config['color'] ?? '#1C3259' }}" class="mt-1 h-7 w-full rounded border-gray-300 p-0.5"></label>
                                    <label class="col-span-2 text-xs text-gray-500">Posisi teks<select name="layout[{{ $field }}][align]" class="mt-1 w-full rounded border-gray-300 text-xs"><option value="left" @selected(($config['align'] ?? 'center') === 'left')>Kiri</option><option value="center" @selected(($config['align'] ?? 'center') === 'center')>Tengah</option><option value="right" @selected(($config['align'] ?? 'center') === 'right')>Kanan</option></select></label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="rounded-lg border border-dashed border-primary/50 bg-primary/5 p-3">
                        <label for="add-element-type" class="mb-1 block text-sm font-semibold text-primary">Tambah Elemen</label>
                        <div class="flex gap-2"><select id="add-element-type" data-admin-select-auto-direction class="min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"><option value="">Pilih data yang ingin ditampilkan</option>@foreach($addableFieldDefinitions as $type => $label)<option value="{{ $type }}">{{ $label }}</option>@endforeach</select><button id="add-element" type="button" class="rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary/90">Tambah</button></div>
                    </div>
                </div>
                <div class="flex gap-3 border-t border-gray-100 pt-4"><a href="{{ route('admin.certificate.template.index') }}" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700">Batal</a><button class="flex-1 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Simpan</button></div>
            </aside>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const image = document.getElementById('certificate-background');
    const canvas = document.getElementById('certificate-canvas-wrap');
    const empty = document.getElementById('certificate-canvas-empty');
    const settingsPanel = document.getElementById('certificate-settings-panel');
    const backgroundInput = document.getElementById('background');
    const settings = document.getElementById('certificate-field-settings');
    const customTextFields = document.getElementById('custom-text-fields');
    const subtestScoreFields = document.getElementById('subtest-score-fields');
    const optionalFields = document.getElementById('optional-fields');
    const addCustomTextButton = document.getElementById('add-custom-text');
    const addSubtestScoreButton = document.getElementById('add-subtest-score');
    const addElementType = document.getElementById('add-element-type');
    const addElementButton = document.getElementById('add-element');
    let naturalWidth = 1054, naturalHeight = 1492;
    let activeField = null;
    let selectedField = null;

    const syncSettingsPanelHeight = () => {
        window.requestAnimationFrame(() => {
            if (!settingsPanel || !canvas || canvas.classList.contains('hidden')) return;
            settingsPanel.style.height = `${Math.ceil(canvas.getBoundingClientRect().height)}px`;
        });
    };

    const value = (field, property) => Number(document.querySelector(`[data-layout-input="${property}"][data-field="${field}"]`)?.value || 0);
    const selectField = (field, shouldScroll = true) => {
        selectedField = field;
        document.querySelectorAll('[data-canvas-field]').forEach((item) => item.classList.toggle('certificate-canvas-selected', item.dataset.canvasField === field));
        document.querySelectorAll('[data-settings-field]').forEach((item) => item.classList.toggle('certificate-settings-selected', item.dataset.settingsField === field));
        const fieldSettings = document.querySelector(`[data-settings-field="${field}"]`);
        if (shouldScroll && fieldSettings) fieldSettings.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };
    const previewText = (field, fieldType, subtestIndex, customText, fallback) => {
        if ((field.startsWith('custom_text') || fieldType === 'custom_text') && customText) return customText;
        if (field.startsWith('subtest_score_') || fieldType === 'subtest_score') return ['86', '82', '89'][Math.max(0, Number(subtestIndex || 1) - 1)] || '85';

        const valueType = fieldType || field;
        return {
            participant_name: 'Nama Peserta',
            participant_email: 'peserta@example.com',
            date_of_birth: '15 Januari 2000',
            certificate_number: 'PREVIEW/001/2026',
            tryout_name: 'Tryout Contoh',
            package_name: 'Paket Contoh',
            institution_name: 'Nama Bimbel Anda',
            issued_date: '13 Agustus 2026',
            completion_date: '12 Agustus 2026',
            exam_date: '12 Agustus 2026',
            total_score: '85.5',
            subtest_scores: 'Subtest 1: 86\nSubtest 2: 82\nSubtest 3: 89',
            qr_code: 'QR',
        }[valueType] || fallback;
    };
    const syncCanvas = () => {
        if (!image?.src || image.naturalWidth === 0) return;
        naturalWidth = image.naturalWidth;
        naturalHeight = image.naturalHeight;
        canvas.classList.remove('hidden'); empty.classList.add('hidden');
        syncSettingsPanelHeight();
        const imageScale = image.getBoundingClientRect().width / naturalWidth;
        document.querySelectorAll('[data-canvas-field]').forEach((item) => {
            const field = item.dataset.canvasField;
            const enabled = document.querySelector(`[data-enabled="${field}"]`)?.checked;
            const x = value(field, 'x'); const y = value(field, 'y');
            const fontSize = Math.max(8, value(field, 'font_size'));
            const align = document.querySelector(`[name="layout[${field}][align]"]`)?.value || 'left';
            item.classList.toggle('hidden', !enabled);
            item.style.left = `${(x / naturalWidth) * 100}%`;
            item.style.top = `${(y / naturalHeight) * 100}%`;
            item.style.color = document.querySelector(`[name="layout[${field}][color]"]`)?.value || '#1C3259';
            const customText = document.querySelector(`[data-custom-text="${field}"]`)?.value;
            const subtestIndex = document.querySelector(`[data-subtest-index="${field}"]`)?.value;
            const fieldType = document.querySelector(`[name="layout[${field}][field_type]"]`)?.value;
            const isQrCode = (fieldType || field) === 'qr_code';
            item.textContent = previewText(field, fieldType, subtestIndex, customText, item.dataset.label);
            item.style.fontSize = `${fontSize * imageScale}px`;
            item.style.fontFamily = 'Poppins, sans-serif';
            item.style.fontWeight = field === 'participant_name' || fieldType === 'participant_name' ? '600' : '400';
            item.style.textAlign = align;
            item.style.transform = isQrCode
                ? 'translate(0, 0)'
                : (align === 'center' ? 'translateX(-50%)' : (align === 'right' ? 'translateX(-100%)' : 'translateX(0)'));
            item.style.width = isQrCode ? `${fontSize * imageScale}px` : 'auto';
            item.style.height = isQrCode ? `${fontSize * imageScale}px` : 'auto';
            item.style.lineHeight = isQrCode ? `${fontSize * imageScale}px` : '1.15';
        });
    };
    const bindCanvasItem = (item) => {
        if (item.dataset.bound) return;
        item.dataset.bound = '1';
        item.addEventListener('click', () => selectField(item.dataset.canvasField));
        item.addEventListener('pointerdown', (event) => {
            activeField = item.dataset.canvasField;
            selectField(activeField, false);
            item.setPointerCapture(event.pointerId);
            event.preventDefault();
        });
        item.addEventListener('pointermove', (event) => {
            if (activeField !== item.dataset.canvasField) return;
            const rect = image.getBoundingClientRect();
            const x = Math.max(0, Math.min(naturalWidth, (event.clientX - rect.left) * naturalWidth / rect.width));
            const y = Math.max(0, Math.min(naturalHeight, (event.clientY - rect.top) * naturalHeight / rect.height));
            const xInput = document.querySelector(`[data-layout-input="x"][data-field="${activeField}"]`);
            const yInput = document.querySelector(`[data-layout-input="y"][data-field="${activeField}"]`);
            if (!xInput || !yInput) return;
            xInput.value = x.toFixed(1);
            yInput.value = y.toFixed(1);
            syncCanvas();
        });
        item.addEventListener('pointerup', () => { activeField = null; });
        item.addEventListener('pointercancel', () => { activeField = null; });
    };
    const addCustomText = () => {
        const field = `custom_text_${Date.now()}`;
        const card = document.createElement('div');
        card.className = 'rounded-lg border border-gray-200 p-3';
        card.dataset.settingsField = field;
        card.innerHTML = `
            <div class="flex items-center justify-between gap-2"><label class="flex items-center gap-2 text-sm font-semibold text-gray-800"><input data-enabled="${field}" type="checkbox" name="layout[${field}][enabled]" value="1" class="rounded border-gray-300 text-primary focus:ring-primary" checked> Teks Bebas</label><button type="button" data-remove-field="${field}" class="rounded p-1 text-red-600 hover:bg-red-50" title="Hapus teks"><i class="ri-delete-bin-line"></i></button></div>
            <input data-custom-text="${field}" type="text" name="layout[${field}][text]" class="mt-2 w-full rounded border-gray-300 text-xs" placeholder="Isi teks bebas">
            <div class="mt-3 grid grid-cols-2 gap-2">
                <label class="text-xs text-gray-500">X<input data-layout-input="x" data-field="${field}" type="number" step="0.1" name="layout[${field}][x]" value="527" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                <label class="text-xs text-gray-500">Y<input data-layout-input="y" data-field="${field}" type="number" step="0.1" name="layout[${field}][y]" value="1020" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                <label class="text-xs text-gray-500">Ukuran font<input data-layout-input="font_size" data-field="${field}" type="number" min="8" max="300" step="1" name="layout[${field}][font_size]" value="16" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                <label class="text-xs text-gray-500">Warna<input type="color" name="layout[${field}][color]" value="#1C3259" class="mt-1 h-7 w-full rounded border-gray-300 p-0.5"></label>
                <label class="col-span-2 text-xs text-gray-500">Posisi teks<select name="layout[${field}][align]" class="mt-1 w-full rounded border-gray-300 text-xs"><option value="left">Kiri</option><option value="center" selected>Tengah</option><option value="right">Kanan</option></select></label>
            </div>`;
        customTextFields.append(card);
        const canvasItem = document.createElement('button');
        canvasItem.type = 'button';
        canvasItem.dataset.canvasField = field;
        canvasItem.dataset.label = 'Teks Bebas';
        canvasItem.className = 'certificate-canvas-field absolute cursor-move border border-dashed border-primary bg-white/80 text-primary';
        canvasItem.textContent = 'Teks Bebas';
        canvas.append(canvasItem);
        bindCanvasItem(canvasItem);
        syncCanvas();
        selectField(field);
        card.querySelector('[data-custom-text]')?.focus();
    };
    const addSubtestScore = () => {
        const field = `subtest_score_${Date.now()}`;
        const existingIndexes = [...document.querySelectorAll('[data-subtest-index]')]
            .map((input) => Number(input.value) || 0);
        const subtestIndex = Math.max(0, ...existingIndexes) + 1;
        const card = document.createElement('div');
        card.className = 'rounded-lg border border-gray-200 p-3';
        card.dataset.settingsField = field;
        card.innerHTML = `
            <div class="flex items-center justify-between gap-2"><label class="flex items-center gap-2 text-sm font-semibold text-gray-800"><input data-enabled="${field}" type="checkbox" name="layout[${field}][enabled]" value="1" class="rounded border-gray-300 text-primary focus:ring-primary" checked> Nilai Subtest</label><button type="button" data-remove-field="${field}" class="rounded p-1 text-red-600 hover:bg-red-50" title="Hapus nilai subtest"><i class="ri-delete-bin-line"></i></button></div>
            <label class="mt-2 block text-xs text-gray-500">Ambil nilai subtest urutan ke-<input data-subtest-index="${field}" type="number" min="1" max="100" name="layout[${field}][subtest_index]" value="${subtestIndex}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
            <p class="mt-1 text-xs text-gray-500">Urutan mengikuti konfigurasi subtest Tryout.</p>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <label class="text-xs text-gray-500">X<input data-layout-input="x" data-field="${field}" type="number" step="0.1" name="layout[${field}][x]" value="527" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                <label class="text-xs text-gray-500">Y<input data-layout-input="y" data-field="${field}" type="number" step="0.1" name="layout[${field}][y]" value="1020" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                <label class="text-xs text-gray-500">Ukuran font<input data-layout-input="font_size" data-field="${field}" type="number" min="8" max="300" step="1" name="layout[${field}][font_size]" value="16" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                <label class="text-xs text-gray-500">Warna<input type="color" name="layout[${field}][color]" value="#1C3259" class="mt-1 h-7 w-full rounded border-gray-300 p-0.5"></label>
                <label class="col-span-2 text-xs text-gray-500">Posisi teks<select name="layout[${field}][align]" class="mt-1 w-full rounded border-gray-300 text-xs"><option value="left">Kiri</option><option value="center" selected>Tengah</option><option value="right">Kanan</option></select></label>
            </div>`;
        subtestScoreFields.append(card);
        const canvasItem = document.createElement('button');
        canvasItem.type = 'button';
        canvasItem.dataset.canvasField = field;
        canvasItem.dataset.label = `Nilai Subtest ${subtestIndex}`;
        canvasItem.className = 'certificate-canvas-field absolute cursor-move border border-dashed border-primary bg-white/80 text-primary';
        canvasItem.textContent = `Nilai Subtest ${subtestIndex}`;
        canvas.append(canvasItem);
        bindCanvasItem(canvasItem);
        syncCanvas();
        selectField(field);
    };
    const addOptionalElement = () => {
        const fieldType = addElementType?.value;
        if (!fieldType) return;
        const field = `optional_${fieldType}_${Date.now()}`;
        const label = addElementType.options[addElementType.selectedIndex].text;
        const existingIndexes = [...document.querySelectorAll('[data-subtest-index]')].map((input) => Number(input.value) || 0);
        const subtestIndex = Math.max(0, ...existingIndexes) + 1;
        const isCustomText = fieldType === 'custom_text';
        const isSubtestScore = fieldType === 'subtest_score';
        const card = document.createElement('div');
        card.className = 'rounded-lg border border-gray-200 p-3';
        card.dataset.settingsField = field;
        card.innerHTML = `
            <div class="flex items-center justify-between gap-2"><label class="flex items-center gap-2 text-sm font-semibold text-gray-800"><input data-enabled="${field}" type="checkbox" name="layout[${field}][enabled]" value="1" class="rounded border-gray-300 text-primary focus:ring-primary" checked> ${label}</label><button type="button" data-remove-field="${field}" class="rounded p-1 text-red-600 hover:bg-red-50" title="Hapus elemen"><i class="ri-delete-bin-line"></i></button></div>
            <input type="hidden" name="layout[${field}][field_type]" value="${fieldType}">
            ${isCustomText ? `<input data-custom-text="${field}" type="text" name="layout[${field}][text]" class="mt-2 w-full rounded border-gray-300 text-xs" placeholder="Isi teks bebas">` : ''}
            ${isSubtestScore ? `<label class="mt-2 block text-xs text-gray-500">Ambil nilai subtest urutan ke-<input data-subtest-index="${field}" type="number" min="1" max="100" name="layout[${field}][subtest_index]" value="${subtestIndex}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>` : ''}
            <div class="mt-3 grid grid-cols-2 gap-2">
                <label class="text-xs text-gray-500">X<input data-layout-input="x" data-field="${field}" type="number" step="0.1" name="layout[${field}][x]" value="527" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                <label class="text-xs text-gray-500">Y<input data-layout-input="y" data-field="${field}" type="number" step="0.1" name="layout[${field}][y]" value="1020" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                <label class="text-xs text-gray-500">Ukuran ${fieldType === 'qr_code' ? 'QR' : 'font'}<input data-layout-input="font_size" data-field="${field}" type="number" min="8" max="300" step="1" name="layout[${field}][font_size]" value="${fieldType === 'qr_code' ? 120 : 16}" class="mt-1 w-full rounded border-gray-300 text-xs"></label>
                <label class="text-xs text-gray-500">Warna<input type="color" name="layout[${field}][color]" value="#1C3259" class="mt-1 h-7 w-full rounded border-gray-300 p-0.5"></label>
                <label class="col-span-2 text-xs text-gray-500">Posisi teks<select name="layout[${field}][align]" class="mt-1 w-full rounded border-gray-300 text-xs"><option value="left">Kiri</option><option value="center" selected>Tengah</option><option value="right">Kanan</option></select></label>
            </div>`;
        optionalFields.append(card);
        const canvasItem = document.createElement('button');
        canvasItem.type = 'button';
        canvasItem.dataset.canvasField = field;
        canvasItem.dataset.label = label;
        canvasItem.className = 'certificate-canvas-field absolute cursor-move border border-dashed border-primary bg-white/80 text-primary';
        canvasItem.textContent = label;
        canvas.append(canvasItem);
        bindCanvasItem(canvasItem);
        syncCanvas();
        selectField(field);
        card.querySelector('[data-custom-text]')?.focus();
        addElementType.value = '';
    };

    image?.addEventListener('load', syncCanvas);
    if (image?.complete && image.naturalWidth) syncCanvas();
    if (canvas && window.ResizeObserver) {
        new ResizeObserver(syncSettingsPanelHeight).observe(canvas);
    }
    backgroundInput?.addEventListener('change', (event) => {
        const file = event.target.files?.[0]; if (!file) return;
        image.src = URL.createObjectURL(file);
    });
    document.addEventListener('input', (event) => {
        if (event.target.matches('[data-layout-input], [data-enabled], [data-custom-text], [data-subtest-index], [name$="[color]"]')) syncCanvas();
    });
    document.addEventListener('change', (event) => {
        if (event.target.matches('[data-layout-input], [data-enabled], [data-custom-text], [data-subtest-index], [name$="[color]"], [name$="[align]"]')) syncCanvas();
    });
    settings?.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-field]');
        if (removeButton) {
            const field = removeButton.dataset.removeField;
            document.querySelector(`[data-canvas-field="${field}"]`)?.remove();
            document.querySelector(`[data-settings-field="${field}"]`)?.remove();
            if (selectedField === field) selectedField = null;
            return;
        }
        const card = event.target.closest('[data-settings-field]');
        if (card) selectField(card.dataset.settingsField, false);
    });
    addCustomTextButton?.addEventListener('click', addCustomText);
    addSubtestScoreButton?.addEventListener('click', addSubtestScore);
    addElementButton?.addEventListener('click', addOptionalElement);
    document.querySelectorAll('[data-canvas-field]').forEach(bindCanvasItem);
})();
</script>
@endpush
