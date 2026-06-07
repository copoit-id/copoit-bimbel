@extends('admin.layout.admin')
@section('title', 'Tambah Tryout')
@section('content')

<div class="flex justify-between items-center">
    <x-breadcrumb>
        <x-slot name="items">
            <x-breadcrumb-item href="{{ route('admin.package.index') }}" title="Kelola Paket" />
            <x-breadcrumb-item href="" title="fgfd" />
            <x-breadcrumb-item href="" title="Tambah Tryout" />
        </x-slot>
    </x-breadcrumb>
    <a href="{{ route('admin.package.tryout.index', $package->package_id) }}"
        class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2">
        <i class="ri-arrow-left-line"></i>
        Kembali
    </a>
</div>
<div class="flex w-full justify-center">
    <x-page-desc title="Tambah Tryout - {{ $package->name }}"></x-page-desc>
</div>

@if($errors->any())
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
    <ul class="list-disc list-inside">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<!-- Create Form -->
<div class="bg-white rounded-lg border border-gray-200 mt-4">
    <form action="{{ route('admin.package.tryout.store', $package->package_id) }}" method="POST">
        @csrf
        <div class="p-6 space-y-6">

            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nama Tryout <span
                            class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Contoh: Tryout SKD #1">
                </div>

                <div>
                    <label for="type_tryout" class="block text-sm font-medium text-gray-700 mb-2">Tipe Tryout <span
                            class="text-red-500">*</span></label>
                    <select id="type_tryout" name="type_tryout" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Pilih Tipe Tryout</option>
                        <option value="tiu" {{ old('type_tryout')=='tiu' ? 'selected' : '' }}>TIU (Tes Intelegensi Umum)
                        </option>
                        <option value="twk" {{ old('type_tryout')=='twk' ? 'selected' : '' }}>TWK (Tes Wawasan
                            Kebangsaan)</option>
                        <option value="tkp" {{ old('type_tryout')=='tkp' ? 'selected' : '' }}>TKP (Tes Karakteristik
                            Pribadi)</option>
                        <option value="skd_full" {{ old('type_tryout')=='skd_full' ? 'selected' : '' }}>SKD Full (TWK +
                            TIU + TKP)</option>
                        <option value="general" {{ old('type_tryout')=='general' ? 'selected' : '' }}>General</option>
                        <option value="tpa" {{ old('type_tryout')=='tpa' ? 'selected' : '' }}>TPA</option>
                        <option value="tbi" {{ old('type_tryout')=='tbi' ? 'selected' : '' }}>TBI</option>
                        <option value="certification" {{ old('type_tryout')=='certification' ? 'selected' : '' }}>
                            Certification</option>
                    </select>
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Deskripsi <span
                        class="text-red-500">*</span></label>
                <textarea id="description" name="description" rows="4" required
                    class="summernote w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    placeholder="Jelaskan tentang tryout ini...">{{ old('description') }}</textarea>
            </div>

            <!-- Duration Settings -->
            <div id="duration-section">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Pengaturan Durasi</h3>

                <!-- Duration Total (Always visible) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <div>
                        <label for="duration_total" class="block text-sm font-medium text-gray-700 mb-2">Durasi Total
                            (Menit) <span class="text-red-500">*</span></label>
                        <input type="number" id="duration_total" name="duration_total"
                            value="{{ old('duration_total', 90) }}" min="1" required readonly
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="90">
                        <p class="text-sm text-gray-500 mt-1">Otomatis dihitung dari durasi subtest</p>
                    </div>

                    <div>
                    <label for="passing_score_total" class="block text-sm font-medium text-gray-700 mb-2">Passing
                            Score <span class="text-red-500">*</span></label>
                        <input type="number" id="passing_score_total" name="passing_score_total"
                            value="{{ old('passing_score_total', 65) }}" min="0" max="100" step="0.01" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="65.00">
                    </div>
                    <div>
                        <label for="section_break_duration" class="block text-sm font-medium text-gray-700 mb-2">
                            Durasi Jeda Antar Subtest (detik)
                        </label>
                        <input type="number" id="section_break_duration" name="section_break_duration" min="0" max="3600"
                            value="{{ old('section_break_duration', 0) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Contoh: 60">
                        <p class="text-sm text-gray-500 mt-1">Peserta akan melihat layar jeda dengan hitung mundur sepanjang durasi ini (jika terdapat lebih dari satu subtest).</p>
                    </div>
                </div>

                <!-- Dynamic Duration Fields -->
                <div id="subtest-durations" class="hidden">
                    <div class="space-y-4">
                        <!-- TWK Section -->
                        <div class="twk-duration hidden">
                            <h4 class="text-md font-medium text-gray-800 mb-3">Tes Wawasan Kebangsaan (TWK)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="duration_twk" class="block text-sm font-medium text-gray-700 mb-2">
                                        Durasi (Menit) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="duration_twk" name="duration_twk"
                                        value="{{ old('duration_twk', 35) }}" min="1"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        placeholder="35">
                                </div>
                                <div>
                                    <label for="passing_score_twk" class="block text-sm font-medium text-gray-700 mb-2">
                                        Passing Score <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="passing_score_twk" name="passing_score_twk"
                                        value="{{ old('passing_score_twk', 65) }}" min="0" max="1000" step="0.01"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        placeholder="65.00">
                                </div>
                                <div>
                                    <label for="passing_type_twk" class="block text-sm font-medium text-gray-700 mb-2">
                                        Tipe Passing <span class="text-red-500">*</span>
                                    </label>
                                    <select id="passing_type_twk" name="passing_type_twk"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        <option value="score" @selected(old('passing_type_twk', 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_twk', 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- TIU Section -->
                        <div class="tiu-duration hidden">
                            <h4 class="text-md font-medium text-gray-800 mb-3">Tes Intelegensi Umum (TIU)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="duration_tiu" class="block text-sm font-medium text-gray-700 mb-2">
                                        Durasi (Menit) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="duration_tiu" name="duration_tiu"
                                        value="{{ old('duration_tiu', 90) }}" min="1"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        placeholder="90">
                                </div>
                                <div>
                                    <label for="passing_score_tiu" class="block text-sm font-medium text-gray-700 mb-2">
                                        Passing Score <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="passing_score_tiu" name="passing_score_tiu"
                                        value="{{ old('passing_score_tiu', 65) }}" min="0" max="1000" step="0.01"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        placeholder="65.00">
                                </div>
                                <div>
                                    <label for="passing_type_tiu" class="block text-sm font-medium text-gray-700 mb-2">
                                        Tipe Passing <span class="text-red-500">*</span>
                                    </label>
                                    <select id="passing_type_tiu" name="passing_type_tiu"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        <option value="score" @selected(old('passing_type_tiu', 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_tiu', 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- TKP Section -->
                        <div class="tkp-duration hidden">
                            <h4 class="text-md font-medium text-gray-800 mb-3">Tes Karakteristik Pribadi (TKP)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="duration_tkp" class="block text-sm font-medium text-gray-700 mb-2">
                                        Durasi (Menit) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="duration_tkp" name="duration_tkp"
                                        value="{{ old('duration_tkp', 45) }}" min="1"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        placeholder="45">
                                </div>
                                <div>
                                    <label for="passing_score_tkp" class="block text-sm font-medium text-gray-700 mb-2">
                                        Passing Score <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="passing_score_tkp" name="passing_score_tkp"
                                        value="{{ old('passing_score_tkp', 65) }}" min="0" max="1000" step="0.01"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        placeholder="65.00">
                                </div>
                                <div>
                                    <label for="passing_type_tkp" class="block text-sm font-medium text-gray-700 mb-2">
                                        Tipe Passing <span class="text-red-500">*</span>
                                    </label>
                                    <select id="passing_type_tkp" name="passing_type_tkp"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        <option value="score" @selected(old('passing_type_tkp', 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_tkp', 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- General Section -->
                        <div class="general-duration hidden">
                            <h4 id="general-duration-title" class="text-md font-medium text-gray-800 mb-3">General Test</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="duration_general" class="block text-sm font-medium text-gray-700 mb-2">
                                        Durasi (Menit) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="duration_general" name="duration_general"
                                        value="{{ old('duration_general', 60) }}" min="1"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        placeholder="60">
                                </div>
                                <div>
                                    <label for="passing_score_general"
                                        class="block text-sm font-medium text-gray-700 mb-2">
                                        Passing Score <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="passing_score_general" name="passing_score_general"
                                        value="{{ old('passing_score_general', 65) }}" min="0" max="1000" step="0.01"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        placeholder="65.00">
                                </div>
                                <div>
                                    <label for="passing_type_general"
                                        class="block text-sm font-medium text-gray-700 mb-2">
                                        Tipe Passing <span class="text-red-500">*</span>
                                    </label>
                                    <select id="passing_type_general" name="passing_type_general"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        <option value="score" @selected(old('passing_type_general', 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_general', 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Certification Section -->
                        <div class="certification-duration hidden">
                            <h4 class="text-md font-medium text-gray-800 mb-3">Certification Test</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="duration_certification"
                                        class="block text-sm font-medium text-gray-700 mb-2">
                                        Durasi (Menit) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="duration_certification" name="duration_certification"
                                        value="{{ old('duration_certification', 120) }}" min="1"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        placeholder="120">
                                </div>
                                <div>
                                    <label for="passing_score_certification"
                                        class="block text-sm font-medium text-gray-700 mb-2">
                                        Passing Score <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="passing_score_certification"
                                        name="passing_score_certification"
                                        value="{{ old('passing_score_certification', 70) }}" min="0" max="1000"
                                        step="0.01"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                        placeholder="70.00">
                                </div>
                                <div>
                                    <label for="passing_type_certification"
                                        class="block text-sm font-medium text-gray-700 mb-2">
                                        Tipe Passing <span class="text-red-500">*</span>
                                    </label>
                                    <select id="passing_type_certification" name="passing_type_certification"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        <option value="score" @selected(old('passing_type_certification', 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_certification', 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Date Range -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai </label>
                    <input type="datetime-local" id="start_date" name="start_date" value="{{ old('start_date') }}"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Berakhir </label>
                    <input type="datetime-local" id="end_date" name="end_date" value="{{ old('end_date') }}" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
            </div>

            <!-- Checkboxes -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex items-center">
                    <input type="checkbox" id="is_certification" name="is_certification" value="1" {{
                        old('is_certification') ? 'checked' : '' }}
                        class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                    <label for="is_certification" class="ml-2 text-sm font-medium text-gray-700">
                        Tryout Sertifikasi
                    </label>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true)
                        ? 'checked' : '' }}
                        class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                    <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">
                        Aktifkan Tryout
                    </label>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end px-6 py-5 space-x-2 border-t border-gray-200">
            <a href="{{ route('admin.package.tryout.index', $package->package_id) }}"
                class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                Batal
            </a>
            <button type="submit"
                class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                Simpan Tryout
            </button>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set default dates
        const now = new Date();
        const tomorrow = new Date(now);
        tomorrow.setDate(tomorrow.getDate() + 1);

        const startInput = document.getElementById('start_date');
        const endInput = document.getElementById('end_date');

        if (!startInput.value) {
            startInput.value = now.toISOString().slice(0, 16);
        }

        if (!endInput.value) {
            tomorrow.setMonth(tomorrow.getMonth() + 1); // Default 1 month later
            endInput.value = tomorrow.toISOString().slice(0, 16);
        }

        // Validate end date is after start date
        startInput.addEventListener('change', function() {
            endInput.min = this.value;
        });

        // Handle dynamic duration fields based on tryout type
        const typeSelect = document.getElementById('type_tryout');
        const subtestDurations = document.getElementById('subtest-durations');
        const durationTotal = document.getElementById('duration_total');

        function toggleDurationFields() {
            const selectedType = typeSelect.value;

            // Hide all duration fields first
            const allDurationFields = subtestDurations.querySelectorAll('[class*="-duration"]');
            allDurationFields.forEach(field => {
                field.classList.add('hidden');
                // Reset semua input dalam field
                const inputs = field.querySelectorAll('input');
                inputs.forEach(input => {
                    input.removeAttribute('required');
                    input.value = '';
                });
            });

            // Show subtest durations container if type is selected
            if (selectedType) {
                subtestDurations.classList.remove('hidden');
            } else {
                subtestDurations.classList.add('hidden');
                durationTotal.value = '';
                return;
            }

            // Show relevant duration fields based on type
            switch (selectedType) {
                case 'skd_full':
                    showDurationField('twk-duration', 'duration_twk', 35, 'passing_score_twk', 65);
                    showDurationField('tiu-duration', 'duration_tiu', 90, 'passing_score_tiu', 65);
                    showDurationField('tkp-duration', 'duration_tkp', 45, 'passing_score_tkp', 65);
                    break;
                case 'tiu':
                    showDurationField('tiu-duration', 'duration_tiu', 90, 'passing_score_tiu', 65);
                    break;
                case 'twk':
                    showDurationField('twk-duration', 'duration_twk', 35, 'passing_score_twk', 65);
                    break;
                case 'tkp':
                    showDurationField('tkp-duration', 'duration_tkp', 45, 'passing_score_tkp', 65);
                    break;
                case 'general':
                case 'tpa':
                case 'tbi':
                    updateGeneralDurationTitle(selectedType);
                    showDurationField('general-duration', 'duration_general', 60, 'passing_score_general', 65);
                    break;
                case 'certification':
                    showDurationField('certification-duration', 'duration_certification', 120, 'passing_score_certification', 70);
                    break;
            }

            // Calculate total duration after showing fields
            calculateTotalDuration();
        }

        function showDurationField(className, durationInputId, defaultDuration, passingScoreInputId, defaultPassingScore) {
            const field = subtestDurations.querySelector('.' + className);
            const durationInput = document.getElementById(durationInputId);
            const passingScoreInput = document.getElementById(passingScoreInputId);

            if (field && durationInput && passingScoreInput) {
                // Show field
                field.classList.remove('hidden');

                // Set required attributes
                durationInput.setAttribute('required', 'required');
                passingScoreInput.setAttribute('required', 'required');

                // Set default values if empty
                if (!durationInput.value || durationInput.value == '') {
                    durationInput.value = defaultDuration;
                }
                if (!passingScoreInput.value || passingScoreInput.value == '') {
                    passingScoreInput.value = defaultPassingScore;
                }
            }
        }

        function updateGeneralDurationTitle(selectedType) {
            const title = document.getElementById('general-duration-title');
            if (!title) return;

            const labels = {
                general: 'General Test',
                tpa: 'TPA',
                tbi: 'TBI',
            };

            title.textContent = labels[selectedType] || 'General Test';
        }

        function calculateTotalDuration() {
            // Hanya hitung input durasi yang visible dan bukan passing score
            const visibleDurationInputs = subtestDurations.querySelectorAll('input[id*="duration_"]:not(.hidden)');
            let total = 0;

            visibleDurationInputs.forEach(input => {
                if (!input.closest('.hidden') && input.value && input.value !== '') {
                    const value = parseInt(input.value) || 0;
                    total += value;
                }
            });

            durationTotal.value = total;
        }

        function syncPassingScoreLimit(selectEl) {
            if (!selectEl || !selectEl.name) return;
            const scoreName = selectEl.name.replace('passing_type_', 'passing_score_');
            const scoreInput = document.querySelector(`input[name="${scoreName}"]`);
            if (!scoreInput) return;

            if (!scoreInput.dataset.originalMax) {
                const originalMax = scoreInput.getAttribute('max') ?? '';
                scoreInput.dataset.originalMax = originalMax;
            }

            if (selectEl.value === 'percentage') {
                scoreInput.setAttribute('max', '100');
            } else if (scoreInput.dataset.originalMax) {
                scoreInput.setAttribute('max', scoreInput.dataset.originalMax);
            } else {
                scoreInput.removeAttribute('max');
            }

            clampPassingScoreIfNeeded(scoreInput, selectEl.value);
        }

        function clampPassingScoreIfNeeded(scoreInput, passingType) {
            if (!scoreInput) return;
            if (passingType !== 'percentage') {
                scoreInput.setCustomValidity('');
                return;
            }

            const value = parseFloat(scoreInput.value);
            if (!Number.isNaN(value) && value > 100) {
                scoreInput.value = '100';
                scoreInput.setCustomValidity('Maksimal 100 untuk persentase.');
            } else {
                scoreInput.setCustomValidity('');
            }
        }

        function bindPassingScoreInputs() {
            const scoreInputs = document.querySelectorAll('input[name^="passing_score_"]');
            scoreInputs.forEach(input => {
                input.addEventListener('input', () => {
                    const typeName = input.name.replace('passing_score_', 'passing_type_');
                    const typeSelect = document.querySelector(`select[name="${typeName}"]`);
                    clampPassingScoreIfNeeded(input, typeSelect?.value ?? 'score');
                });
            });
        }

        function syncAllPassingScoreLimits() {
            const passingSelects = document.querySelectorAll('select[name^="passing_type_"]');
            passingSelects.forEach(selectEl => syncPassingScoreLimit(selectEl));
        }

        // Event listeners
        typeSelect.addEventListener('change', toggleDurationFields);
        document.addEventListener('change', function (e) {
            if (e.target && e.target.matches('select[name^="passing_type_"]')) {
                syncPassingScoreLimit(e.target);
            }
        });
        bindPassingScoreInputs();

        // Add event listeners to all duration inputs for real-time calculation
        // Gunakan event delegation karena input bisa muncul/hilang secara dinamis
        subtestDurations.addEventListener('input', function(e) {
            if (e.target.id && e.target.id.includes('duration_')) {
                calculateTotalDuration();
            }
        });

        // Initialize on page load
        toggleDurationFields();
        syncAllPassingScoreLimits();

        console.log('Create tryout form scripts loaded');
    });
</script>
@endsection
