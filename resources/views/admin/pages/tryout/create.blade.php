@extends('admin.layout.admin')

@section('content')
@php
    $utbkSubtests = $utbkSubtests ?? [];
    $utbkSingleTypes = $utbkSingleTypes ?? [];
    $allowUtbkTypes = $allowUtbkTypes ?? (!empty($utbkSubtests) || !empty($utbkSingleTypes));
    $tryoutTypeOptions = $tryoutTypeOptions ?? [];
    $dynamicTryoutSubtests = collect($tryoutTypeOptions)
        ->mapWithKeys(fn ($option, $type) => !empty($option['subtests']) ? [$type => $option['subtests']] : [])
        ->all();
    $selectedTryoutType = old('type_tryout', $tryout->type_tryout ?? '');
    $storedScoringMethod = isset($tryout) ? ($tryout->scoring_method ?? null) : null;
    $storedScoringMethod = $storedScoringMethod === 'irt' ? 'irt_utbk' : $storedScoringMethod;
    $selectedScoringMethod = old('scoring_method', $storedScoringMethod ?? (isset($tryout) && $tryout->is_irt ? 'irt_utbk' : (isset($tryout) && $tryout->is_toefl ? 'toefl_itp' : 'normal')));
    $isScoringMethodLocked = isset($tryout);
    $selectedAccessDurationUnit = old('access_duration_unit', $tryout->access_duration_unit ?? 'forever');
    $selectedAccessDurationValue = old('access_duration_value', $tryout->access_duration_value ?? 1);
    $answerMode = old('answer_persistence_mode', $tryout->answer_persistence_mode ?? 'client_side');
    $subtestDisplayMode = old('subtest_display_mode', $tryout->subtest_display_mode ?? 'per_subtest');
    $userCardDisplay = old('user_card_display', $tryout->user_card_display ?? 'icon');
    $hasOldInput = session()->hasOldInput();
    $selectedTypePrice = old('type_price', $tryout->type_price ?? 'paid');
    $isDisplayedChecked = old('is_displayed', $tryout->is_displayed ?? true);
    $isForSaleChecked = old('is_for_sale', $tryout->is_for_sale ?? false);
    $isActiveChecked = $hasOldInput ? (bool) old('is_active') : ($tryout->is_active ?? true);
    $isCertificationChecked = $hasOldInput ? (bool) old('is_certification') : ($tryout->is_certification ?? false);
    $certificateTemplates = $certificateTemplates ?? collect();
    $selectedCertificateTemplateId = old('certificate_template_id', $tryout->certificate_template_id ?? '');
    $showDiscussionChecked = old('show_discussion', $tryout->show_discussion ?? true);
    $lobbyTokenEnabled = old('lobby_token_enabled', $tryout->lobby_token_enabled ?? false);
    $showLeaderboardChecked = old('show_leaderboard', $tryout->show_leaderboard ?? true);
    $showPassingGradeChecked = old('show_passing_grade', $tryout->show_passing_grade ?? true);
    $showResultScoresChecked = old('show_result_scores', $tryout->show_result_scores ?? true);
    $resultScoreDisplay = old('result_score_display', $tryout->result_score_display ?? 'total_and_subtest');
    $securityOptions = [
        'enable_anti_copy' => [
            'label' => 'Anti Copy Soal',
            'description' => 'Blok seleksi teks, klik kanan, dan shortcut copy/cut di halaman ujian.',
            'default' => $securityDefaults['enable_anti_copy'] ?? true,
            'available' => $securityDefaults['enable_anti_copy'] ?? true,
        ],
        'enable_tab_switch_detection' => [
            'label' => 'Deteksi Pindah Tab',
            'description' => 'Tampilkan alert dan hitung pelanggaran saat peserta keluar dari tab/window ujian.',
            'default' => $securityDefaults['enable_tab_switch_detection'] ?? true,
            'available' => $securityDefaults['enable_tab_switch_detection'] ?? true,
        ],
        'enable_webcam_check' => [
            'label' => 'Webcam Check',
            'description' => 'Wajibkan kamera aktif dan simpan snapshot kecil setiap 10 menit.',
            'default' => $securityDefaults['enable_webcam_check'] ?? false,
            'available' => $securityDefaults['enable_webcam_check'] ?? false,
        ],
        'enable_screen_check' => [
            'label' => 'Screen Check',
            'description' => 'Wajibkan screen sharing aktif dan simpan snapshot kecil setiap 10 menit.',
            'default' => $securityDefaults['enable_screen_check'] ?? false,
            'available' => $securityDefaults['enable_screen_check'] ?? false,
        ],
    ];
    $securityOptions = array_filter($securityOptions, fn ($option) => $option['available']);
@endphp
<style>
    .tryout-toggle-input:checked + .tryout-toggle-track .tryout-toggle-knob {
        transform: translateX(1.25rem);
        border-color: #ffffff;
    }
</style>
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">{{ isset($tryout) ? 'Edit Tryout' : 'Tambah Tryout Baru' }}</h2>
            <p class="text-gray-500">{{ isset($tryout) ? 'Perbarui informasi tryout' : 'Buat tryout baru untuk ujian' }}
            </p>
        </div>
        <a href="{{ route('admin.tryout.index', request()->query()) }}"
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
            action="{{ isset($tryout) ? route('admin.tryout.update', array_merge(request()->query(), ['tryout' => $tryout->tryout_id])) : route('admin.tryout.store') }}"
            method="POST"
            enctype="multipart/form-data">
            @csrf
            @if(isset($tryout))
            @method('PUT')
            @endif

            <div class="p-6 space-y-6">
                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nama Tryout <span
                                class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name"
                            value="{{ isset($tryout) ? $tryout->name : old('name') }}" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div class="md:col-span-2">
                        <label for="type_tryout" class="block text-sm font-medium text-gray-700 mb-2">Tipe Tryout <span
                                class="text-red-500">*</span></label>
                        <select id="type_tryout" name="type_tryout" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Pilih Tipe</option>
                            @foreach($tryoutTypeOptions as $typeKey => $option)
                                <option value="{{ $typeKey }}" @selected($selectedTryoutType === $typeKey)>
                                    {{ $option['label'] ?? \Illuminate\Support\Str::headline((string) $typeKey) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="assessment_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Kategori Penilaian <span class="text-red-500">*</span>
                            <x-ui.tooltip>Gunakan kategori ini untuk membedakan tryout reguler dengan pre test atau post test di kelas.</x-ui.tooltip>
                        </label>
                        <select id="assessment_type" name="assessment_type" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="standard" {{ (isset($tryout) && $tryout->assessment_type === 'standard') || old('assessment_type', 'standard') === 'standard' ? 'selected' : '' }}>Tryout Reguler</option>
                            <option value="pre_test" {{ (isset($tryout) && $tryout->assessment_type === 'pre_test') || old('assessment_type') === 'pre_test' ? 'selected' : '' }}>Pre Test</option>
                            <option value="post_test" {{ (isset($tryout) && $tryout->assessment_type === 'post_test') || old('assessment_type') === 'post_test' ? 'selected' : '' }}>Post Test</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="max_attempts" class="block text-sm font-medium text-gray-700 mb-2">
                            Batas Pengerjaan
                            <x-ui.tooltip>Jumlah maksimal peserta bisa menyelesaikan tryout ini. Isi 0 untuk tidak dibatasi.</x-ui.tooltip>
                        </label>
                        <input type="number" id="max_attempts" name="max_attempts" min="0" max="1000"
                            value="{{ old('max_attempts', isset($tryout) ? ($tryout->max_attempts ?? 0) : 0) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="0 = tidak dibatasi">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                    <textarea id="description" name="description" rows="4"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Masukkan deskripsi tryout...">{{ isset($tryout) ? $tryout->description : old('description') }}</textarea>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <div class="mb-5">
                        <h3 class="text-base font-semibold text-gray-800">Tampilan Kartu User</h3>
                        <p class="text-sm text-gray-500 mt-1">Icon memakai default sistem. Thumbnail hanya perlu diupload kalau mode thumbnail dipilih.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <input type="radio" name="user_card_display" value="icon" class="mt-1 rounded border-gray-300 text-primary focus:ring-primary"
                                @checked($userCardDisplay !== 'thumbnail')>
                            <span>
                                <span class="block text-sm font-semibold text-gray-800">Icon Default</span>
                                <span class="mt-1 flex items-center gap-2 text-sm text-gray-500">
                                    <i class="ri-file-list-3-line text-lg text-primary"></i>
                                    Pakai icon default tryout.
                                </span>
                            </span>
                        </label>
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <input type="radio" name="user_card_display" value="thumbnail" class="mt-1 rounded border-gray-300 text-primary focus:ring-primary"
                                @checked($userCardDisplay === 'thumbnail')>
                            <span>
                                <span class="block text-sm font-semibold text-gray-800">Thumbnail</span>
                                <span class="block mt-1 text-sm text-gray-500">Upload gambar untuk kartu tryout di user.</span>
                            </span>
                        </label>
                    </div>
                    <div id="tryoutThumbnailField" class="mt-4 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4">
                        <label for="thumbnail" class="block text-sm font-medium text-gray-700 mb-2">Upload Thumbnail</label>
                        @if(isset($tryout) && filled($tryout->thumbnail_url))
                            <div class="mb-3 h-28 w-44 overflow-hidden rounded-lg border border-gray-200 bg-white">
                                <img src="{{ $tryout->thumbnail_url }}" alt="Thumbnail tryout saat ini" class="h-full w-full object-cover">
                            </div>
                            <p class="mb-2 text-xs text-gray-500">Kosongkan jika tidak ingin mengganti thumbnail.</p>
                        @endif
                        <input type="file" id="thumbnail" name="thumbnail" accept="image/*"
                            data-has-current="{{ isset($tryout) && filled($tryout->thumbnail_url) ? '1' : '0' }}"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="mt-2 text-xs text-gray-500">Ukuran ideal: 1280 × 720 px (rasio 16:9). Format: JPG, PNG, GIF, atau WEBP. Maksimal 2MB.</p>
                    </div>
                </div>

                <!-- Access & Sale -->
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <div class="mb-5">
                        <h3 class="text-base font-semibold text-gray-800">Akses & Penjualan</h3>
                        <p class="text-sm text-gray-500 mt-1">Atur apakah tryout tampil di user dan bisa dibeli terpisah.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <input type="checkbox" name="is_displayed" value="1" {{ $isDisplayedChecked ? 'checked' : '' }} class="mt-1 rounded border-gray-300 text-primary focus:ring-primary">
                            <span>
                                <span class="block text-sm font-semibold text-gray-800">Tampilkan di user</span>
                                <span class="block text-xs text-gray-500 mt-1">Jika mati, tryout tidak muncul di katalog user.</span>
                            </span>
                        </label>
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <input type="checkbox" id="is_for_sale" name="is_for_sale" value="1" {{ $isForSaleChecked ? 'checked' : '' }} class="mt-1 rounded border-gray-300 text-primary focus:ring-primary">
                            <span>
                                <span class="block text-sm font-semibold text-gray-800">Dijual terpisah</span>
                                <span class="block text-xs text-gray-500 mt-1">Jika mati, tryout tampil tapi tidak bisa dibeli individual.</span>
                            </span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div id="price-type-wrapper">
                            <label for="type_price" class="block text-sm font-medium text-gray-700 mb-2">Tipe Harga</label>
                            <select id="type_price" name="type_price"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="paid" @selected($selectedTypePrice === 'paid')>Berbayar</option>
                                <option value="free_unconditional" @selected($selectedTypePrice === 'free_unconditional')>Gratis Tanpa Syarat</option>
                                <option value="free_conditional" @selected($selectedTypePrice === 'free_conditional')>Gratis Bersyarat</option>
                            </select>
                        </div>
                        <div id="price-wrapper">
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Harga (Rp)</label>
                            <input type="number" id="price" name="price" min="0" step="1" inputmode="numeric"
                                value="{{ old('price', isset($tryout) ? $tryout->price : 0) }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="0">
                        </div>
                    </div>

                    <div id="conditional-requirement-wrapper" class="mt-4 {{ $selectedTypePrice === 'free_conditional' ? '' : 'hidden' }}">
                        <label for="conditional_requirement" class="block text-sm font-medium text-gray-700 mb-2">Syarat Akses Gratis</label>
                        <textarea id="conditional_requirement" name="conditional_requirement" rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Contoh: follow Instagram, upload bukti, atau hubungi admin.">{{ old('conditional_requirement', isset($tryout) ? ($tryout->conditional_requirement ?? '') : '') }}</textarea>
                    </div>

                    <div id="access-duration-wrapper" class="mt-4 {{ $isForSaleChecked ? '' : 'hidden' }}">
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
                            <input type="number" name="access_duration_value" id="access_duration_value" min="1" max="1200"
                                value="{{ $selectedAccessDurationValue }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Jumlah durasi">
                        </div>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai</label>
                        <input type="datetime-local" id="start_date" name="start_date"
                            value="{{ old('start_date', isset($tryout) ? $tryout->start_date?->format('Y-m-d\TH:i') : null) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="mt-1 text-xs text-gray-500">Kosongkan agar tryout dapat dimulai kapan saja.</p>
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Selesai</label>
                        <input type="datetime-local" id="end_date" name="end_date"
                            value="{{ old('end_date', isset($tryout) ? $tryout->end_date?->format('Y-m-d\TH:i') : null) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="mt-1 text-xs text-gray-500">Kosongkan agar tryout tidak memiliki batas waktu. IRT tanpa tanggal selesai dirilis manual oleh admin.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="section_break_duration" class="block text-sm font-medium text-gray-700 mb-2">
                            Durasi Jeda Antar Subtest (detik)
                            <x-ui.tooltip>Saat lebih dari satu subtest, peserta akan melihat layar jeda dengan hitung mundur selama durasi ini.</x-ui.tooltip>
                        </label>
                        <input type="number" id="section_break_duration" name="section_break_duration" min="0" max="3600"
                            value="{{ old('section_break_duration', isset($tryout) ? $tryout->section_break_duration : 0) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Contoh: 60">
                    </div>
                    <div>
                        <label for="answer_persistence_mode" class="block text-sm font-medium text-gray-700 mb-2">
                            Mode Penyimpanan Jawaban
                            <x-ui.tooltip>Hybrid cocok untuk live score per subtest, sementara Client Side mempertahankan perilaku lama.</x-ui.tooltip>
                        </label>
                        <select id="answer_persistence_mode" name="answer_persistence_mode"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="client_side" @selected($answerMode === 'client_side')>Client Side (simpan saat selesai tryout)</option>
                            <option value="hybrid_subtest" @selected($answerMode === 'hybrid_subtest')>Hybrid (simpan setiap selesai subtest)</option>
                        </select>
                        <p id="answerPersistenceModeNotice" class="hidden text-xs text-amber-600 mt-1">Hybrid hanya tersedia untuk tampilan Per Subtest.</p>
                    </div>
                    <div>
                        <label for="subtest_display_mode" class="block text-sm font-medium text-gray-700 mb-2">
                            Tampilan Multi Subtest
                            <x-ui.tooltip>Per Subtest mengikuti alur jeda dan pembatasan subtest. Gabung menampilkan navigasi semua soal sekaligus.</x-ui.tooltip>
                        </label>
                        <select id="subtest_display_mode" name="subtest_display_mode"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="per_subtest" @selected($subtestDisplayMode === 'per_subtest')>Per Subtest (bertahap)</option>
                            <option value="combined" @selected($subtestDisplayMode === 'combined')>Gabung Semua Subtest</option>
                        </select>
                    </div>
                </div>

                <!-- Options -->
                <div class="space-y-4">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{
                            $isActiveChecked ? 'checked' : '' }} class="sr-only peer tryout-toggle-input">
                        <span
                            class="tryout-toggle-track relative inline-flex h-6 w-11 items-center rounded-full border border-gray-300 bg-white transition-colors peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary peer-focus:ring-offset-2 peer-checked:bg-primary peer-checked:border-primary">
                            <span
                                class="tryout-toggle-knob inline-block h-5 w-5 translate-x-0 rounded-full border border-gray-300 bg-white transition-transform"></span>
                        </span>
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            Tryout Aktif
                            <x-ui.tooltip>Tryout tidak akan tampil di user jika dinonaktifkan.</x-ui.tooltip>
                        </span>
                    </label>

                    <div id="certificateTemplateField" class="{{ $isCertificationChecked ? '' : 'hidden' }} rounded-lg border border-primary/20 bg-primary/5 p-4">
                        <label for="certificate_template_id" class="mb-1 block text-sm font-semibold text-gray-800">Template Sertifikat</label>
                        <select id="certificate_template_id" name="certificate_template_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary">
                            <option value="">Pilih template</option>
                            @foreach($certificateTemplates as $certificateTemplate)
                                <option value="{{ $certificateTemplate->certificate_template_id }}" @selected((string) $selectedCertificateTemplateId === (string) $certificateTemplate->certificate_template_id)>{{ $certificateTemplate->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-500">Template yang dipilih akan tersimpan khusus untuk tryout ini. Atur background dan posisi isi di <a href="{{ route('admin.certificate.template.index') }}" class="font-semibold text-primary hover:underline">Template Sertifikat</a>.</p>
                    </div>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" id="is_certification" name="is_certification" value="1" {{
                            $isCertificationChecked ? 'checked' : '' }}
                            class="sr-only peer tryout-toggle-input">
                        <span
                            class="tryout-toggle-track relative inline-flex h-6 w-11 items-center rounded-full border border-gray-300 bg-white transition-colors peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary peer-focus:ring-offset-2 peer-checked:bg-primary peer-checked:border-primary">
                            <span
                                class="tryout-toggle-knob inline-block h-5 w-5 translate-x-0 rounded-full border border-gray-300 bg-white transition-transform"></span>
                        </span>
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            Generate Sertifikat Otomatis
                            <x-ui.tooltip>Sertifikat akan digenerate jika diaktifkan. Wajib memiliki template.</x-ui.tooltip>
                        </span>
                    </label>

                    <div class="space-y-3">
                        <label class="flex items-center gap-3">
                            <input type="checkbox" id="lobby_token_enabled" name="lobby_token_enabled" value="1"
                                {{ $lobbyTokenEnabled ? 'checked' : '' }} class="sr-only peer tryout-toggle-input">
                            <span class="tryout-toggle-track relative inline-flex h-6 w-11 items-center rounded-full border border-gray-300 bg-white transition-colors peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary peer-focus:ring-offset-2 peer-checked:bg-primary peer-checked:border-primary">
                                <span class="tryout-toggle-knob inline-block h-5 w-5 translate-x-0 rounded-full border border-gray-300 bg-white transition-transform"></span>
                            </span>
                            <span class="text-sm font-medium text-gray-700">Wajibkan Token di Lobby</span>
                        </label>
                        <div id="lobby-token-input-wrapper" class="mt-3 {{ $lobbyTokenEnabled ? '' : 'hidden' }}">
                            <label for="lobby_token" class="mb-2 block text-sm font-medium text-gray-700">Token Lobby</label>
                            <input type="text" id="lobby_token" name="lobby_token" minlength="6" maxlength="100" autocomplete="off"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                placeholder="{{ isset($tryout) && $tryout->lobby_token_hash ? 'Kosongkan untuk mempertahankan token lama' : 'Minimal 6 karakter' }}">
                            <p class="mt-2 text-xs text-gray-600">Peserta wajib memasukkan token ini di lobby sebelum dapat memulai. Ini berbeda dari syarat klaim paket.</p>
                            @error('lobby_token')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" id="show_discussion" name="show_discussion" value="1"
                            {{ $showDiscussionChecked ? 'checked' : '' }}
                            class="sr-only peer tryout-toggle-input">
                        <span
                            class="tryout-toggle-track relative inline-flex h-6 w-11 items-center rounded-full border border-gray-300 bg-white transition-colors peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary peer-focus:ring-offset-2 peer-checked:bg-primary peer-checked:border-primary">
                            <span
                                class="tryout-toggle-knob inline-block h-5 w-5 translate-x-0 rounded-full border border-gray-300 bg-white transition-transform"></span>
                        </span>
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            Tampilkan Pembahasan di User
                            <x-ui.tooltip>Jika dimatikan, tombol pembahasan disembunyikan dan URL pembahasan akan ditolak.</x-ui.tooltip>
                        </span>
                    </label>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" id="show_leaderboard" name="show_leaderboard" value="1"
                            {{ $showLeaderboardChecked ? 'checked' : '' }}
                            class="sr-only peer tryout-toggle-input">
                        <span
                            class="tryout-toggle-track relative inline-flex h-6 w-11 items-center rounded-full border border-gray-300 bg-white transition-colors peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary peer-focus:ring-offset-2 peer-checked:bg-primary peer-checked:border-primary">
                            <span
                                class="tryout-toggle-knob inline-block h-5 w-5 translate-x-0 rounded-full border border-gray-300 bg-white transition-transform"></span>
                        </span>
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            Tampilkan Leaderboard di User
                            <x-ui.tooltip>Jika dimatikan, tombol ranking disembunyikan dan URL ranking akan ditolak.</x-ui.tooltip>
                        </span>
                    </label>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" id="show_passing_grade" name="show_passing_grade" value="1"
                            {{ $showPassingGradeChecked ? 'checked' : '' }}
                            class="sr-only peer tryout-toggle-input">
                        <span
                            class="tryout-toggle-track relative inline-flex h-6 w-11 items-center rounded-full border border-gray-300 bg-white transition-colors peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary peer-focus:ring-offset-2 peer-checked:bg-primary peer-checked:border-primary">
                            <span
                                class="tryout-toggle-knob inline-block h-5 w-5 translate-x-0 rounded-full border border-gray-300 bg-white transition-transform"></span>
                        </span>
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            Tampilkan Passing Grade di User
                            <x-ui.tooltip>Atur tampilan passing grade secara terpisah. Nilai passing grade 0 tetap valid dan tidak memengaruhi opsi ini.</x-ui.tooltip>
                        </span>
                    </label>

                    <div class="rounded-xl border border-primary/15 bg-primary/5 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-800">Tampilan Nilai Peserta</p>
                                    <x-ui.tooltip>Jika dimatikan, peserta tetap melihat status hasil tetapi tidak melihat angka nilai.</x-ui.tooltip>
                                </div>
                                <p class="mt-1 text-xs leading-relaxed text-gray-500">
                                    Atur nilai apa yang muncul pada halaman hasil tryout peserta.
                                </p>
                            </div>

                            <label class="flex shrink-0 cursor-pointer items-center gap-2">
                                <span class="text-xs font-medium text-gray-600">Tampilkan</span>
                                <input type="checkbox" id="show_result_scores" name="show_result_scores" value="1"
                                    {{ $showResultScoresChecked ? 'checked' : '' }}
                                    class="sr-only peer tryout-toggle-input">
                                <span
                                    class="tryout-toggle-track relative inline-flex h-6 w-11 items-center rounded-full border border-gray-300 bg-white transition-colors peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary peer-focus:ring-offset-2 peer-checked:bg-primary peer-checked:border-primary">
                                    <span
                                        class="tryout-toggle-knob inline-block h-5 w-5 translate-x-0 rounded-full border border-gray-300 bg-white transition-transform"></span>
                                </span>
                            </label>
                        </div>

                        <fieldset id="resultScoreDisplayOptions" class="mt-4 {{ $showResultScoresChecked ? '' : 'hidden' }}">
                            <legend class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Jenis nilai yang ditampilkan</legend>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <label class="cursor-pointer">
                                    <input type="radio" name="result_score_display" value="total_and_subtest"
                                        @checked($resultScoreDisplay === 'total_and_subtest') class="peer sr-only">
                                    <span class="block rounded-lg border border-gray-200 bg-white px-3 py-3 text-sm text-gray-700 transition-colors hover:border-primary/50 peer-checked:border-primary peer-checked:bg-primary/5">
                                        <span class="block font-semibold">Total + subtest</span>
                                        <span class="mt-0.5 block text-xs text-gray-500">Tampilkan nilai keseluruhan dan setiap subtest.</span>
                                    </span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="result_score_display" value="subtest_only"
                                        @checked($resultScoreDisplay === 'subtest_only') class="peer sr-only">
                                    <span class="block rounded-lg border border-gray-200 bg-white px-3 py-3 text-sm text-gray-700 transition-colors hover:border-primary/50 peer-checked:border-primary peer-checked:bg-primary/5">
                                        <span class="block font-semibold">Subtest saja</span>
                                        <span class="mt-0.5 block text-xs text-gray-500">Sembunyikan nilai total, tampilkan nilai tiap subtest.</span>
                                    </span>
                                </label>
                            </div>
                        </fieldset>
                    </div>

                    <div class="md:col-span-2">
                        <label for="scoring_method" class="block text-sm font-medium text-gray-700 mb-2">
                            Metode Scoring
                            <x-ui.tooltip>Metode scoring hanya dapat dipilih saat membuat tryout agar arti nilai dan riwayat hasil peserta tetap konsisten.</x-ui.tooltip>
                        </label>
                        @if($isScoringMethodLocked)
                            <input type="hidden" name="scoring_method" value="{{ $selectedScoringMethod }}">
                        @endif
                        <select id="scoring_method" name="scoring_method"
                            data-locked="{{ $isScoringMethodLocked ? 'true' : 'false' }}"
                            @disabled($isScoringMethodLocked)
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="normal" @selected($selectedScoringMethod === 'normal')>Normal</option>
                            <option value="irt_utbk" @selected($selectedScoringMethod === 'irt_utbk')>IRT (Skala 0 - 1000)</option>
                            <option value="toefl_itp" data-type="certification,listening,reading,writing" @selected($selectedScoringMethod === 'toefl_itp')>TOEFL ITP</option>
                        </select>
                        @if($isScoringMethodLocked)
                            <p class="mt-1 text-xs text-gray-500">Metode scoring dikunci setelah tryout dibuat.</p>
                        @endif
                        <p id="scoringMethodNotice" class="hidden text-xs text-amber-600 mt-1"></p>
                    </div>
                </div>

                <!-- Dynamic Configuration Sections -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Konfigurasi Subtest</h3>

                    <!-- Dynamic Category Configuration -->
                    <div id="dynamic_category_config" class="config-section hidden space-y-4">
                        @foreach($dynamicTryoutSubtests as $type => $subtests)
                            <div class="dynamic-category-card hidden" data-dynamic-category-card="{{ $type }}">
                                <h4 class="font-medium text-gray-800">Konfigurasi {{ $tryoutTypeOptions[$type]['label'] ?? \Illuminate\Support\Str::headline($type) }}</h4>
                                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($subtests as $subtest)
                                        @php
                                            $subtestCode = $subtest['code'];
                                            $subtestDetail = isset($tryout) ? $tryout->tryoutDetails->firstWhere('type_subtest', $subtestCode) : null;
                                            $durationValue = old('duration_'.$subtestCode, $subtestDetail?->duration ?? 60);
                                            $passingValue = old('passing_score_'.$subtestCode, $subtestDetail?->passing_score ?? 60);
                                            $passingType = old('passing_type_'.$subtestCode, $subtestDetail?->passing_type ?? 'score');
                                        @endphp
                                        <div class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                            <h5 class="font-medium text-sm text-gray-800">{{ $subtest['name'] }}</h5>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Durasi (menit)</label>
                                                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_{{ $subtestCode }}" min="1" max="300" placeholder="Contoh: 0,5"
                                                    value="{{ $durationValue }}"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Passing Score</label>
                                                <input type="number" name="passing_score_{{ $subtestCode }}" min="0" max="100" step="0.1"
                                                    value="{{ $passingValue }}"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Tipe Passing</label>
                                                <select name="passing_type_{{ $subtestCode }}"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                                    <option value="score" @selected($passingType === 'score')>Skor</option>
                                                    <option value="percentage" @selected($passingType === 'percentage')>Persentase</option>
                                                </select>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- SKD Full Configuration -->
                    <!-- UTBK Full Configuration -->
                    <div id="utbk_full_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">Konfigurasi UTBK TPS Full</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            @foreach($utbkSubtests as $slug => $config)
                            @php
                                $utbkDetail = isset($tryout) ? $tryout->tryoutDetails->firstWhere('type_subtest', $slug) : null;
                                $durationValue = old('duration_'.$slug, $utbkDetail?->duration ?? $config['default_duration']);
                                $passingValue = old('passing_score_'.$slug, $utbkDetail?->passing_score ?? $config['default_passing']);
                                $passingType = old('passing_type_'.$slug, $utbkDetail?->passing_type ?? 'score');
                            @endphp
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">{{ $config['label'] }}</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Durasi (menit)</label>
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_{{ $slug }}" min="1" max="300" placeholder="Contoh: 0,5"
                                        value="{{ $durationValue }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Passing Score</label>
                                    <input type="number" name="passing_score_{{ $slug }}" min="0" max="100" step="0.1"
                                        value="{{ $passingValue }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Tipe Passing</label>
                                    <select name="passing_type_{{ $slug }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected($passingType === 'score')>Skor</option>
                                        <option value="percentage" @selected($passingType === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500">Durasi dan passing score default mengikuti struktur TPS, tetapi
                            masih bisa disesuaikan.</p>
                    </div>

                    <!-- UTBK Single Configuration -->
                    <div id="utbk_single_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">Konfigurasi UTBK Per Subtest</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($utbkSubtests as $slug => $config)
                            @php
                                $singleDetail = isset($tryout) ? $tryout->tryoutDetails->firstWhere('type_subtest', $slug) : null;
                                $singleDuration = old('duration_'.$slug, $singleDetail?->duration ?? $config['default_duration']);
                                $singlePassing = old('passing_score_'.$slug, $singleDetail?->passing_score ?? $config['default_passing']);
                                $singlePassingType = old('passing_type_'.$slug, $singleDetail?->passing_type ?? 'score');
                            @endphp
                            <div class="space-y-2 utbk-single-card hidden" data-utbk-single-card="{{ $slug }}">
                                <h5 class="font-medium text-sm text-gray-700">{{ $config['label'] }}</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Durasi (menit)</label>
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_{{ $slug }}" min="1" max="300" placeholder="Contoh: 0,5"
                                        value="{{ $singleDuration }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Passing Score</label>
                                    <input type="number" name="passing_score_{{ $slug }}" min="0" max="100" step="0.1"
                                        value="{{ $singlePassing }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Tipe Passing</label>
                                    <select name="passing_type_{{ $slug }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected($singlePassingType === 'score')>Skor</option>
                                        <option value="percentage" @selected($singlePassingType === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500">Input yang relevan akan muncul otomatis saat tipe UTBK subtest dipilih.</p>
                    </div>

                    <div id="skd_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">Konfigurasi SKD Full</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- TWK -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">Tes Wawasan Kebangsaan (TWK)</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Durasi (menit)</label>
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_twk" min="1" max="300" placeholder="Contoh: 0,5"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'twk')->first()?->duration : old('duration_twk', 35) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Passing Score</label>
                                    <input type="number" name="passing_score_twk" min="0" max="100" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'twk')->first()?->passing_score : old('passing_score_twk', 65) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Tipe Passing</label>
                                    <select name="passing_type_twk"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_twk', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'twk')->first()?->passing_type : 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_twk', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'twk')->first()?->passing_type : 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>

                            <!-- TIU -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">Tes Intelegensi Umum (TIU)</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Durasi (menit)</label>
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_tiu" min="1" max="300" placeholder="Contoh: 0,5"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'tiu')->first()?->duration : old('duration_tiu', 90) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Passing Score</label>
                                    <input type="number" name="passing_score_tiu" min="0" max="100" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'tiu')->first()?->passing_score : old('passing_score_tiu', 80) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Tipe Passing</label>
                                    <select name="passing_type_tiu"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_tiu', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'tiu')->first()?->passing_type : 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_tiu', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'tiu')->first()?->passing_type : 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>

                            <!-- TKP -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">Tes Karakteristik Pribadi (TKP)</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Durasi (menit)</label>
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_tkp" min="1" max="300" placeholder="Contoh: 0,5"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'tkp')->first()?->duration : old('duration_tkp', 45) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Passing Score</label>
                                    <input type="number" name="passing_score_tkp" min="0" max="300" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'tkp')->first()?->passing_score : old('passing_score_tkp', 166) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Tipe Passing</label>
                                    <select name="passing_type_tkp"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_tkp', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'tkp')->first()?->passing_type : 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_tkp', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'tkp')->first()?->passing_type : 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Certification Configuration -->
                    <div id="certification_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">Konfigurasi TOEFL ITP</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Listening -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">Listening Comprehension</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Durasi (menit)</label>
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_listening" min="1" max="300" placeholder="Contoh: 0,5"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'listening')->first()?->duration : old('duration_listening', 35) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Passing Score</label>
                                    <input type="number" name="passing_score_listening" min="0" max="100" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'listening')->first()?->passing_score : old('passing_score_listening', 60) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Tipe Passing</label>
                                    <select name="passing_type_listening"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_listening', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'listening')->first()?->passing_type : 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_listening', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'listening')->first()?->passing_type : 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Structure and Written Expression -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">Structure & Written Expression</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Durasi (menit)</label>
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_writing" min="1" max="300" placeholder="Contoh: 0,5"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'writing')->first()?->duration : old('duration_writing', 25) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Passing Score</label>
                                    <input type="number" name="passing_score_writing" min="0" max="100" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'writing')->first()?->passing_score : old('passing_score_writing', 60) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Tipe Passing</label>
                                    <select name="passing_type_writing"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_writing', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'writing')->first()?->passing_type : 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_writing', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'writing')->first()?->passing_type : 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Reading Comprehension -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">Reading Comprehension</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Durasi (menit)</label>
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_reading" min="1" max="300" placeholder="Contoh: 0,5"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'reading')->first()?->duration : old('duration_reading', 55) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Passing Score</label>
                                    <input type="number" name="passing_score_reading" min="0" max="100" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'reading')->first()?->passing_score : old('passing_score_reading', 60) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Tipe Passing</label>
                                    <select name="passing_type_reading"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_reading', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'reading')->first()?->passing_type : 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_reading', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'reading')->first()?->passing_type : 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PPPK Full Configuration -->
                    <div id="pppk_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">Konfigurasi PPPK Full</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Teknis -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">Kompetensi Teknis</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Durasi (menit)</label>
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_teknis" min="1" max="300" placeholder="Contoh: 0,5"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'teknis')->first()?->duration : old('duration_teknis', 90) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Passing Score</label>
                                    <input type="number" name="passing_score_teknis" min="0" max="540" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'teknis')->first()?->passing_score : old('passing_score_teknis', 65) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Tipe Passing</label>
                                    <select name="passing_type_teknis"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_teknis', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'teknis')->first()?->passing_type : 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_teknis', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'teknis')->first()?->passing_type : 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Sosial Kultural -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">Kompetensi Sosial Kultural & Manajerial
                                </h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Durasi (menit)</label>
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_social_culture" min="1" max="300" placeholder="Contoh: 0,5"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'social culture')->first()?->duration : old('duration_social_culture', 60) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Passing Score</label>
                                    <input type="number" name="passing_score_social_culture" min="0" max="180"
                                        step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'social culture')->first()?->passing_score : old('passing_score_social_culture', 65) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Tipe Passing</label>
                                    <select name="passing_type_social_culture"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_social_culture', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'social culture')->first()?->passing_type : 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_social_culture', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'social culture')->first()?->passing_type : 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Wawancara -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">Wawancara</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Durasi (menit)</label>
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_interview" min="1" max="300" placeholder="Contoh: 0,5"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'interview')->first()?->duration : old('duration_interview', 30) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Passing Score</label>
                                    <input type="number" name="passing_score_interview" min="0" max="40" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'interview')->first()?->passing_score : old('passing_score_interview', 70) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Tipe Passing</label>
                                    <select name="passing_type_interview"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_interview', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'interview')->first()?->passing_type : 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_interview', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'interview')->first()?->passing_type : 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Computer Full Configuration -->
                    <div id="computer_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">Konfigurasi Computer Full</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Word -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">Microsoft Word</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Durasi (menit)</label>
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_word" min="1" max="300" placeholder="Contoh: 0,5"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'word')->first()?->duration : old('duration_word', 30) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Passing Score</label>
                                    <input type="number" name="passing_score_word" min="0" max="100" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'word')->first()?->passing_score : old('passing_score_word', 70) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Tipe Passing</label>
                                    <select name="passing_type_word"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_word', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'word')->first()?->passing_type : 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_word', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'word')->first()?->passing_type : 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Excel -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">Microsoft Excel</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Durasi (menit)</label>
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_excel" min="1" max="300" placeholder="Contoh: 0,5"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'excel')->first()?->duration : old('duration_excel', 30) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Passing Score</label>
                                    <input type="number" name="passing_score_excel" min="0" max="100" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'excel')->first()?->passing_score : old('passing_score_excel', 70) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Tipe Passing</label>
                                    <select name="passing_type_excel"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_excel', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'excel')->first()?->passing_type : 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_excel', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'excel')->first()?->passing_type : 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>

                            <!-- PowerPoint -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">Microsoft PowerPoint</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Durasi (menit)</label>
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_ppt" min="1" max="300" placeholder="Contoh: 0,5"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'ppt')->first()?->duration : old('duration_ppt', 30) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Passing Score</label>
                                    <input type="number" name="passing_score_ppt" min="0" max="100" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'ppt')->first()?->passing_score : old('passing_score_ppt', 70) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Tipe Passing</label>
                                    <select name="passing_type_ppt"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_ppt', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'ppt')->first()?->passing_type : 'score') === 'score')>Skor</option>
                                        <option value="percentage" @selected(old('passing_type_ppt', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'ppt')->first()?->passing_type : 'score') === 'percentage')>Persentase</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Individual Computer Tests -->
                    <div id="word_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">Konfigurasi Microsoft Word</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Durasi (menit)</label>
                                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_word_single" min="1" max="300" placeholder="Contoh: 0,5"
                                    value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'word')->first()?->duration : old('duration_word_single', 30) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Passing Score</label>
                                <input type="number" name="passing_score_word_single" min="0" max="100" step="0.1"
                                    value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'word')->first()?->passing_score : old('passing_score_word_single', 70) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Tipe Passing</label>
                                <select name="passing_type_word_single"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="score" @selected(old('passing_type_word_single', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'word')->first()?->passing_type : 'score') === 'score')>Skor</option>
                                    <option value="percentage" @selected(old('passing_type_word_single', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'word')->first()?->passing_type : 'score') === 'percentage')>Persentase</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="excel_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">Konfigurasi Microsoft Excel</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Durasi (menit)</label>
                                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_excel_single" min="1" max="300" placeholder="Contoh: 0,5"
                                    value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'excel')->first()?->duration : old('duration_excel_single', 30) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Passing Score</label>
                                <input type="number" name="passing_score_excel_single" min="0" max="100" step="0.1"
                                    value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'excel')->first()?->passing_score : old('passing_score_excel_single', 70) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Tipe Passing</label>
                                <select name="passing_type_excel_single"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="score" @selected(old('passing_type_excel_single', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'excel')->first()?->passing_type : 'score') === 'score')>Skor</option>
                                    <option value="percentage" @selected(old('passing_type_excel_single', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'excel')->first()?->passing_type : 'score') === 'percentage')>Persentase</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="ppt_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">Konfigurasi Microsoft PowerPoint</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Durasi (menit)</label>
                                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_ppt_single" min="1" max="300" placeholder="Contoh: 0,5"
                                    value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'ppt')->first()?->duration : old('duration_ppt_single', 30) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Passing Score</label>
                                <input type="number" name="passing_score_ppt_single" min="0" max="100" step="0.1"
                                    value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'ppt')->first()?->passing_score : old('passing_score_ppt_single', 70) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Tipe Passing</label>
                                <select name="passing_type_ppt_single"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="score" @selected(old('passing_type_ppt_single', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'ppt')->first()?->passing_type : 'score') === 'score')>Skor</option>
                                    <option value="percentage" @selected(old('passing_type_ppt_single', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'ppt')->first()?->passing_type : 'score') === 'percentage')>Persentase</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- General Config (for other single tests) -->
                    <div id="general_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">Konfigurasi Tryout</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Durasi (menit)</label>
                                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" name="duration_general" min="1" max="300" placeholder="Contoh: 0,5"
                                    value="{{ isset($tryout) ? $tryout->tryoutDetails->first()?->duration : old('duration_general', 60) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Passing Score</label>
                                <input type="number" name="passing_score_general" min="0" max="100" step="0.1"
                                    value="{{ isset($tryout) ? $tryout->tryoutDetails->first()?->passing_score : old('passing_score_general', 60) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Tipe Passing</label>
                                <select name="passing_type_general"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="score" @selected(old('passing_type_general', isset($tryout) ? $tryout->tryoutDetails->first()?->passing_type : 'score') === 'score')>Skor</option>
                                    <option value="percentage" @selected(old('passing_type_general', isset($tryout) ? $tryout->tryoutDetails->first()?->passing_type : 'score') === 'percentage')>Persentase</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-900">Keamanan Ujian</h3>
                        <p class="text-sm text-gray-500">Atur fitur pengawasan yang aktif saat peserta mengerjakan tryout.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach($securityOptions as $field => $option)
                            @php
                                $isChecked = $hasOldInput
                                    ? (bool) old($field)
                                    : (isset($tryout) ? (bool) $tryout->{$field} : $option['default']);
                            @endphp
                            <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-4">
                                <input type="hidden" name="{{ $field }}" value="0">
                                <input type="checkbox" name="{{ $field }}" value="1" {{ $isChecked ? 'checked' : '' }}
                                    class="mt-1 rounded border-gray-300 text-primary focus:ring-primary">
                                <span>
                                    <span class="block text-sm font-semibold text-gray-800">{{ $option['label'] }}</span>
                                    <span class="mt-1 block text-xs leading-relaxed text-gray-500">{{ $option['description'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end px-6 py-5 space-x-2 border-t border-gray-200">
                <a href="{{ route('admin.tryout.index', request()->query()) }}"
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900">
                    Batal
                </a>
                <button type="submit"
                    class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5">
                    {{ isset($tryout) ? 'Perbarui Tryout' : 'Simpan Tryout' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
  const utbkSingleTypeMap = @json(collect($utbkSingleTypes ?? [])->mapWithKeys(fn($config, $type) => [$type => $config['slug']])->toArray());
  const dynamicTryoutSubtests = @json($dynamicTryoutSubtests);
  function setSectionEnabled(sectionEl, enabled) {
    if (!sectionEl) return;
    const fields = sectionEl.querySelectorAll('input, select, textarea, button');
    fields.forEach(el => {
      if (!enabled) {
        if (el.hasAttribute('required') && !el.dataset._req) el.dataset._req = '1';
        el.disabled = true;
      } else {
        el.disabled = false;
      }
    });
  }

    function initTryoutForm(root = document) {
      const typeSelect = root.querySelector('#type_tryout');
      const configSections = root.querySelectorAll('.config-section');
      const scoringMethodSelect = root.querySelector('#scoring_method');
      const scoringMethodLocked = scoringMethodSelect?.dataset.locked === 'true';
      const scoringMethodNotice = root.querySelector('#scoringMethodNotice');
      const answerModeSelect = root.querySelector('#answer_persistence_mode');
      const subtestDisplaySelect = root.querySelector('#subtest_display_mode');
      const answerModeNotice = root.querySelector('#answerPersistenceModeNotice');
      const showResultScoresCheckbox = root.querySelector('#show_result_scores');
      const resultScoreDisplayOptions = root.querySelector('#resultScoreDisplayOptions');
      const tryoutThumbnailField = root.querySelector('#tryoutThumbnailField');
      const thumbnailInput = root.querySelector('#thumbnail');
      const certificationCheckbox = root.querySelector('#is_certification');
      const certificateTemplateField = root.querySelector('#certificateTemplateField');
      const dynamicCategoryCards = root.querySelectorAll('[data-dynamic-category-card]');
      if (!typeSelect || typeSelect.__tryoutBound) return;

      const formatDurationInput = (input) => {
        const value = input.value.trim();
        if (!/^\d+(?:[.,]\d{1,2})?$/.test(value)) return;

        const duration = Number.parseFloat(value.replace(',', '.'));
        input.value = duration.toLocaleString('id-ID', {
          useGrouping: false,
          maximumFractionDigits: 2,
        });
      };

      root.querySelectorAll('input[name^="duration_"]').forEach(input => {
        formatDurationInput(input);
        input.addEventListener('blur', () => formatDurationInput(input));
      });

    const configSectionMap = {
      'utbk_full': 'utbk_full_config',
      'skd_full': 'skd_config',
      'certification': 'certification_config',
      'pppk_full': 'pppk_config',
      'computer': 'computer_config',
      'word': 'word_config',
      'excel': 'excel_config',
      'ppt': 'ppt_config',
      // single tests → general_config
      'twk': 'general_config',
      'tiu': 'general_config',
      'tkp': 'general_config',
      'tpa': 'general_config',
      'tbi': 'general_config',
      'tob': 'general_config',
      'listening': 'general_config',
      'structure': 'general_config',
      'reading': 'general_config',
      'teknis': 'general_config',
      'social culture': 'general_config',
      'management': 'general_config',
      'interview': 'general_config',
      'general': 'general_config'
    };
    Object.keys(utbkSingleTypeMap).forEach(type => {
      configSectionMap[type] = 'utbk_single_config';
    });
    Object.keys(dynamicTryoutSubtests).forEach(type => {
      configSectionMap[type] = 'dynamic_category_config';
    });

    function toggleDynamicCategoryCards(selectedType) {
      dynamicCategoryCards.forEach(card => {
        const isSelected = card.dataset.dynamicCategoryCard === selectedType;
        card.classList.toggle('hidden', !isSelected);
        card.querySelectorAll('input, select, textarea, button').forEach(field => {
          field.disabled = !isSelected;
        });
      });
    }

    function showConfigSection() {
      const selectedType = String(typeSelect.value || '').trim();
      const targetId = configSectionMap[selectedType] || (selectedType ? 'general_config' : null);

      configSections.forEach(section => {
        section.classList.add('hidden');
        setSectionEnabled(section, false);
      });

      if (targetId) {
        const target = document.getElementById(targetId);
        if (target) {
          target.classList.remove('hidden');
          setSectionEnabled(target, true);
        }
      }

      syncScoringMethod(selectedType);
      toggleUtbkSingleCards(selectedType);
      toggleDynamicCategoryCards(selectedType);
    }

    function syncScoringMethod(selectedType) {
      if (!scoringMethodSelect) return;

      const optionRules = {
        toefl_itp: ['certification', 'listening', 'reading', 'writing']
      };

      if (scoringMethodLocked) {
        scoringMethodNotice?.classList.add('hidden');
        return;
      }
      const currentValue = scoringMethodSelect.value;
      let currentIsAllowed = true;

      Array.from(scoringMethodSelect.options).forEach(option => {
        const requiredType = optionRules[option.value] || null;
        const isAllowed = !requiredType || requiredType.includes(selectedType);
        option.disabled = !isAllowed;

        if (option.value === currentValue && !isAllowed) {
          currentIsAllowed = false;
        }
      });

      if (!currentIsAllowed) {
        scoringMethodSelect.value = 'normal';
      }

      if (!scoringMethodNotice) return;

      if (scoringMethodSelect.value === 'irt_utbk') {
        scoringMethodNotice.textContent = 'IRT menggunakan skala skor 0 - 1000 untuk setiap subtest.';
        scoringMethodNotice.classList.remove('hidden');
      } else if (optionRules.toefl_itp.includes(selectedType)) {
        scoringMethodNotice.textContent = 'TOEFL ITP tersedia untuk Certification Full dan section TOEFL.';
        scoringMethodNotice.classList.remove('hidden');
      } else {
        scoringMethodNotice.textContent = '';
        scoringMethodNotice.classList.add('hidden');
      }
    }

    const utbkSingleCards = root.querySelectorAll('[data-utbk-single-card]');
    function toggleUtbkSingleCards(selectedType) {
      const slug = utbkSingleTypeMap[selectedType] || null;
      utbkSingleCards.forEach(card => {
        if (slug && card.dataset.utbkSingleCard === slug) {
          card.classList.remove('hidden');
          setSectionEnabled(card, true);
        } else {
          card.classList.add('hidden');
          setSectionEnabled(card, false);
        }
      });
    }

    function updateFieldNames() {
      const selectedType = String(typeSelect.value || '').trim();

      if (selectedType === 'word') {
        const d = document.querySelector('input[name="duration_word_single"]');
        const s = document.querySelector('input[name="passing_score_word_single"]');
        const p = document.querySelector('select[name="passing_type_word_single"]');
        if (d) d.name = 'duration_word';
        if (s) s.name = 'passing_score_word';
        if (p) p.name = 'passing_type_word';
      } else if (selectedType === 'excel') {
        const d = document.querySelector('input[name="duration_excel_single"]');
        const s = document.querySelector('input[name="passing_score_excel_single"]');
        const p = document.querySelector('select[name="passing_type_excel_single"]');
        if (d) d.name = 'duration_excel';
        if (s) s.name = 'passing_score_excel';
        if (p) p.name = 'passing_type_excel';
      } else if (selectedType === 'ppt') {
        const d = document.querySelector('input[name="duration_ppt_single"]');
        const s = document.querySelector('input[name="passing_score_ppt_single"]');
        const p = document.querySelector('select[name="passing_type_ppt_single"]');
        if (d) d.name = 'duration_ppt';
        if (s) s.name = 'passing_score_ppt';
        if (p) p.name = 'passing_type_ppt';
      }
    }

    function syncPassingScoreLimit(selectEl) {
      if (!selectEl || !selectEl.name) return;
      const scoreName = selectEl.name.replace('passing_type_', 'passing_score_');
      const scoreInput = root.querySelector(`input[name="${scoreName}"]`);
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
      const scoreInputs = root.querySelectorAll('input[name^="passing_score_"]');
      scoreInputs.forEach(input => {
        input.addEventListener('input', () => {
          const typeName = input.name.replace('passing_score_', 'passing_type_');
          const typeSelect = root.querySelector(`select[name="${typeName}"]`);
          clampPassingScoreIfNeeded(input, typeSelect?.value ?? 'score');
        });
      });
    }

    function syncAllPassingScoreLimits() {
      const passingSelects = root.querySelectorAll('select[name^="passing_type_"]');
      passingSelects.forEach(selectEl => syncPassingScoreLimit(selectEl));
    }

    function syncAnswerPersistenceAvailability() {
      if (!answerModeSelect || !subtestDisplaySelect) return;

      const hybridOption = answerModeSelect.querySelector('option[value="hybrid_subtest"]');
      const isCombinedView = subtestDisplaySelect.value === 'combined';

      if (hybridOption) hybridOption.disabled = isCombinedView;
      if (isCombinedView && answerModeSelect.value === 'hybrid_subtest') {
        answerModeSelect.value = 'client_side';
      }

      answerModeNotice?.classList.toggle('hidden', !isCombinedView);
    }

    function syncUserCardDisplay() {
      const selectedMode = root.querySelector('input[name="user_card_display"]:checked')?.value || 'icon';
      const useThumbnail = selectedMode === 'thumbnail';

      tryoutThumbnailField?.classList.toggle('hidden', !useThumbnail);
      if (thumbnailInput) {
        thumbnailInput.disabled = !useThumbnail;
        thumbnailInput.required = useThumbnail && thumbnailInput.dataset.hasCurrent !== '1';
      }
    }

    function syncResultScoreDisplay() {
      resultScoreDisplayOptions?.classList.toggle('hidden', !showResultScoresCheckbox?.checked);
    }

    function syncCertificateTemplateField() {
      certificateTemplateField?.classList.toggle('hidden', !certificationCheckbox?.checked);
    }

    window.__tryoutChange = function () {
      showConfigSection();
      updateFieldNames();
      syncAllPassingScoreLimits();
      syncAnswerPersistenceAvailability();
      syncUserCardDisplay();
      syncResultScoreDisplay();
      syncCertificateTemplateField();
    };

    window.__tryoutChange();

    // bind event
    typeSelect.addEventListener('change', window.__tryoutChange);
    root.addEventListener('change', (event) => {
      if (event.target && event.target.matches('select[name^="passing_type_"]')) {
        syncPassingScoreLimit(event.target);
      }

      if (event.target && event.target.matches('#subtest_display_mode')) {
        syncAnswerPersistenceAvailability();
      }

      if (event.target && event.target.matches('input[name="user_card_display"]')) {
        syncUserCardDisplay();
      }

      if (event.target && event.target.matches('#show_result_scores')) {
        syncResultScoreDisplay();
      }

      if (event.target && event.target.matches('#is_certification')) {
        syncCertificateTemplateField();
      }
    });
    bindPassingScoreInputs();

    const durationUnit = root.querySelector('#access_duration_unit');
    const durationValue = root.querySelector('#access_duration_value');
    const saleCheckbox = root.querySelector('#is_for_sale');
    const typePriceSelect = root.querySelector('#type_price');
    const priceInput = root.querySelector('#price');
    const priceWrapper = root.querySelector('#price-wrapper');
    const requirementWrapper = root.querySelector('#conditional-requirement-wrapper');
    const requirementInput = root.querySelector('#conditional_requirement');
    const lobbyTokenEnabled = root.querySelector('#lobby_token_enabled');
    const lobbyTokenInputWrapper = root.querySelector('#lobby-token-input-wrapper');
    const lobbyTokenInput = root.querySelector('#lobby_token');
    const durationWrapper = root.querySelector('#access-duration-wrapper');
    const syncAccessDuration = () => {
      if (!durationUnit || !durationValue) return;
      const isForSale = saleCheckbox?.checked ?? false;
      const typePrice = typePriceSelect?.value || 'paid';
      priceWrapper?.classList.toggle('hidden', !isForSale || typePrice !== 'paid');
      if (priceInput) {
        priceInput.disabled = !isForSale || typePrice !== 'paid';
        if (typePrice !== 'paid') priceInput.value = 0;
      }
      requirementWrapper?.classList.toggle('hidden', !isForSale || typePrice !== 'free_conditional');
      if (requirementInput) requirementInput.disabled = !isForSale || typePrice !== 'free_conditional';
      durationWrapper?.classList.toggle('hidden', !isForSale);
      durationUnit.disabled = !isForSale;
      durationValue.disabled = !isForSale || durationUnit.value === 'forever';
    };
    syncAccessDuration();
    const hasExistingLobbyToken = @json(isset($tryout) && filled($tryout->lobby_token_hash));
    const syncLobbyToken = () => {
      const enabled = Boolean(lobbyTokenEnabled?.checked);
      lobbyTokenInputWrapper?.classList.toggle('hidden', !enabled);
      lobbyTokenInput?.toggleAttribute('required', enabled && !hasExistingLobbyToken);
    };
    syncLobbyToken();
    durationUnit?.addEventListener('change', syncAccessDuration);
    saleCheckbox?.addEventListener('change', syncAccessDuration);
    typePriceSelect?.addEventListener('change', syncAccessDuration);
    lobbyTokenEnabled?.addEventListener('change', syncLobbyToken);

    typeSelect.__tryoutBound = true;
  }

  // berbagai lifecycle supaya jalan di Livewire/Turbo/Turbolinks
  document.addEventListener('DOMContentLoaded', () => initTryoutForm());
  window.addEventListener('load', () => initTryoutForm());
  document.addEventListener('turbolinks:load', () => initTryoutForm());
  document.addEventListener('turbo:load', () => initTryoutForm());
  document.addEventListener('livewire:load', () => initTryoutForm());
  document.addEventListener('livewire:navigated', () => initTryoutForm());
  document.addEventListener('alpine:init', () => initTryoutForm());

  // fallback terakhir: observe DOM (mis. page swap)
  const mo = new MutationObserver(() => initTryoutForm());
  mo.observe(document.documentElement, { childList: true, subtree: true });
})();
</script>

@endsection
