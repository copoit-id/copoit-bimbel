@extends('admin.layout.admin')

@section('content')
@php
    $utbkSubtests = $utbkSubtests ?? [];
    $utbkSingleTypes = $utbkSingleTypes ?? [];
    $allowUtbkTypes = $allowUtbkTypes ?? (!empty($utbkSubtests) || !empty($utbkSingleTypes));
@endphp
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">{{ isset($tryout) ? __('Edit Tryout') : __('Tambah Tryout Baru') }}</h2>
            <p class="text-gray-500">{{ isset($tryout) ? __('Perbarui informasi tryout') : __('Buat tryout baru untuk ujian') }}
            </p>
        </div>
        <a href="{{ route('admin.tryout.index') }}"
            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2">
            <i class="ri-arrow-left-line"></i>
            {{ __('Kembali') }}
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
            action="{{ isset($tryout) ? route('admin.tryout.update', $tryout->tryout_id) : route('admin.tryout.store') }}"
            method="POST">
            @csrf
            @if(isset($tryout))
            @method('PUT')
            @endif

            <div class="p-6 space-y-6">
                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Nama Tryout') }} <span
                                class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name"
                            value="{{ isset($tryout) ? $tryout->name : old('name') }}" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label for="type_tryout" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Tipe Tryout') }} <span
                                class="text-red-500">*</span></label>
                        <select id="type_tryout" name="type_tryout" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">{{ __('Pilih Tipe') }}</option>
                            <option value="skd_full" {{ (isset($tryout) && $tryout->type_tryout === 'skd_full') ||
                                old('type_tryout') === 'skd_full' ? 'selected' : '' }}>{{ __('SKD Full (TWK + TIU + TKP)') }}
                            </option>
                            @if($allowUtbkTypes)
                            <option value="utbk_full" {{ (isset($tryout) && $tryout->type_tryout === 'utbk_full') ||
                                old('type_tryout') === 'utbk_full' ? 'selected' : '' }}>{{ __('UTBK TPS (Full)') }}</option>
                            @foreach($utbkSingleTypes as $typeKey => $singleConfig)
                            <option value="{{ $typeKey }}" {{ (isset($tryout) && $tryout->type_tryout === $typeKey) ||
                                old('type_tryout') === $typeKey ? 'selected' : '' }}>
                                {{ __('UTBK - :label', ['label' => $singleConfig['label']]) }}
                            </option>
                            @endforeach
                            @endif
                            <option value="twk" {{ (isset($tryout) && $tryout->type_tryout === 'twk') ||
                                old('type_tryout') === 'twk' ? 'selected' : '' }}>TWK</option>
                            <option value="tiu" {{ (isset($tryout) && $tryout->type_tryout === 'tiu') ||
                                old('type_tryout') === 'tiu' ? 'selected' : '' }}>TIU</option>
                            <option value="tkp" {{ (isset($tryout) && $tryout->type_tryout === 'tkp') ||
                                old('type_tryout') === 'tkp' ? 'selected' : '' }}>TKP</option>
                            <option value="certification" {{ (isset($tryout) && $tryout->type_tryout ===
                                'certification') || old('type_tryout') === 'certification' ? 'selected' : ''
                                }}>{{ __('Certification Full (TOEFL ITP)') }}</option>
                            <option value="listening" {{ (isset($tryout) && $tryout->type_tryout === 'listening') ||
                                old('type_tryout') === 'listening' ? 'selected' : '' }}>Listening</option>
                            <option value="reading" {{ (isset($tryout) && $tryout->type_tryout === 'reading') ||
                                old('type_tryout') === 'reading' ? 'selected' : '' }}>Reading</option>
                            <option value="writing" {{ (isset($tryout) && $tryout->type_tryout === 'writing') ||
                                old('type_tryout') === 'writing' ? 'selected' : '' }}>Writing</option>
                            <option value="pppk_full" {{ (isset($tryout) && $tryout->type_tryout === 'pppk_full') ||
                                old('type_tryout') === 'pppk_full' ? 'selected' : '' }}>{{ __('PPPK Full') }}</option>
                            <option value="teknis" {{ (isset($tryout) && $tryout->type_tryout === 'teknis') ||
                                old('type_tryout') === 'teknis' ? 'selected' : '' }}>{{ __('Teknis') }}</option>
                            <option value="social culture" {{ (isset($tryout) && $tryout->type_tryout === 'social
                                culture') || old('type_tryout') === 'social culture' ? 'selected' : '' }}>{{ __('Sosial Kultural') }}</option>
                            <option value="interview" {{ (isset($tryout) && $tryout->type_tryout === 'interview') ||
                                old('type_tryout') === 'interview' ? 'selected' : '' }}>{{ __('Interview') }}</option>
                            <option value="word" {{ (isset($tryout) && $tryout->type_tryout === 'word') ||
                                old('type_tryout') === 'word' ? 'selected' : '' }}>Microsoft Word</option>
                            <option value="excel" {{ (isset($tryout) && $tryout->type_tryout === 'excel') ||
                                old('type_tryout') === 'excel' ? 'selected' : '' }}>Microsoft Excel</option>
                            <option value="ppt" {{ (isset($tryout) && $tryout->type_tryout === 'ppt') ||
                                old('type_tryout') === 'ppt' ? 'selected' : '' }}>Microsoft PowerPoint</option>
                            <option value="computer" {{ (isset($tryout) && $tryout->type_tryout === 'computer') ||
                                old('type_tryout') === 'computer' ? 'selected' : '' }}>{{ __('Computer Full (Word + Excel + PPT)') }}</option>
                            <option value="general" {{ (isset($tryout) && $tryout->type_tryout === 'general') ||
                                old('type_tryout') === 'general' ? 'selected' : '' }}>General</option>
                        </select>
                    </div>

                    <div>
                        <label for="assessment_type" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Kategori Penilaian') }} <span class="text-red-500">*</span></label>
                        <select id="assessment_type" name="assessment_type" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="standard" {{ (isset($tryout) && $tryout->assessment_type === 'standard') || old('assessment_type', 'standard') === 'standard' ? 'selected' : '' }}>{{ __('Tryout Reguler') }}</option>
                            <option value="pre_test" {{ (isset($tryout) && $tryout->assessment_type === 'pre_test') || old('assessment_type') === 'pre_test' ? 'selected' : '' }}>{{ __('Pre Test') }}</option>
                            <option value="post_test" {{ (isset($tryout) && $tryout->assessment_type === 'post_test') || old('assessment_type') === 'post_test' ? 'selected' : '' }}>{{ __('Post Test') }}</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">{{ __('Gunakan kategori ini untuk membedakan tryout reguler dengan pre test atau post test di kelas.') }}</p>
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Deskripsi') }}</label>
                    <textarea id="description" name="description" rows="4"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="{{ __('Masukkan deskripsi tryout...') }}">{{ isset($tryout) ? $tryout->description : old('description') }}</textarea>
                </div>

                <!-- Schedule -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Tanggal Mulai') }} <span
                                class="text-red-500">*</span></label>
                        <input type="datetime-local" id="start_date" name="start_date"
                            value="{{ isset($tryout) ? $tryout->start_date->format('Y-m-d\TH:i') : old('start_date') }}"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Tanggal Selesai') }} <span
                                class="text-red-500">*</span></label>
                        <input type="datetime-local" id="end_date" name="end_date"
                            value="{{ isset($tryout) ? $tryout->end_date->format('Y-m-d\TH:i') : old('end_date') }}"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="section_break_duration" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('Durasi Jeda Antar Subtest (detik)') }}
                        </label>
                        <input type="number" id="section_break_duration" name="section_break_duration" min="0" max="3600"
                            value="{{ old('section_break_duration', isset($tryout) ? $tryout->section_break_duration : 0) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="{{ __('Contoh: 60') }}">
                        <p class="text-xs text-gray-500 mt-1">{{ __('Saat lebih dari satu subtest, peserta akan melihat layar jeda dengan hitung mundur selama durasi ini.') }}</p>
                    </div>
                </div>

                <!-- Options -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ (isset($tryout) &&
                            $tryout->is_active) || old('is_active') ? 'checked' : '' }}
                        class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary
                        focus:ring-2">
                        <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">
                            {{ __('Tryout Aktif') }}
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="is_certification" name="is_certification" value="1" {{
                            (isset($tryout) && $tryout->is_certification) || old('is_certification') ? 'checked' : '' }}
                        class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary
                        focus:ring-2">
                        <label for="is_certification" class="ml-2 text-sm font-medium text-gray-700">
                            {{ __('Tryout Sertifikasi') }}
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="is_toefl" name="is_toefl" value="1" {{ (isset($tryout) &&
                            $tryout->is_toefl) || old('is_toefl') ? 'checked' : '' }}
                        class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary
                        focus:ring-2">
                        <label for="is_toefl" class="ml-2 text-sm font-medium text-gray-700">
                            {{ __('TOEFL IRT Scoring') }}
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="is_premium" name="is_premium" value="1" {{ (isset($tryout) &&
                            $tryout->is_premium) || old('is_premium') ? 'checked' : '' }}
                        class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary
                        focus:ring-2">
                        <label for="is_premium" class="ml-2 text-sm font-medium text-gray-700">
                            {{ __('Tryout Premium') }}
                        </label>
                    </div>

                    <div class="flex flex-col md:flex-row md:items-center md:col-span-1">
                        <div class="flex items-center">
                            <input type="checkbox" id="allow_calculator" name="allow_calculator" value="1"
                                {{ old('allow_calculator', isset($tryout) ? $tryout->allow_calculator : false) ? 'checked' : '' }}
                                class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                            <label for="allow_calculator" class="ml-2 text-sm font-medium text-gray-700">
                                {{ __('Izinkan Kalkulator') }}
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-2 md:mt-0 md:ml-4">{{ __('Aktifkan jika peserta boleh memakai kalkulator di halaman tryout.') }}</p>
                    </div>

                    @if($allowUtbkTypes)
                    <div class="flex flex-col md:flex-row md:items-center hidden" data-irt-toggle>
                        <div class="flex items-center">
                            <input type="checkbox" id="is_irt" name="is_irt" value="1" {{
                                old('is_irt', isset($tryout) ? (bool) $tryout->is_irt : true) ? 'checked' : '' }}
                                class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                            <label for="is_irt" class="ml-2 text-sm font-medium text-gray-700">
                                {{ __('Aktifkan Penilaian IRT UTBK') }}
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-2 md:mt-0 md:ml-4">{{ __('Nilai dirilis otomatis setelah periode UTBK berakhir.') }}</p>
                    </div>
                    @endif
                </div>

                <!-- Dynamic Configuration Sections -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">{{ __('Konfigurasi Subtest') }}</h3>

                    <!-- SKD Full Configuration -->
                    <!-- UTBK Full Configuration -->
                    <div id="utbk_full_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">{{ __('Konfigurasi UTBK TPS Full') }}</h4>
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
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                    <input type="number" name="duration_{{ $slug }}" min="1" max="300"
                                        value="{{ $durationValue }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                    <input type="number" name="passing_score_{{ $slug }}" min="0" max="100" step="0.1"
                                        value="{{ $passingValue }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                    <select name="passing_type_{{ $slug }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected($passingType === 'score')>{{ __('Skor') }}</option>
                                        <option value="percentage" @selected($passingType === 'percentage')>{{ __('Persentase') }}</option>
                                    </select>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500">{{ __('Durasi dan passing score default mengikuti struktur TPS, tetapi masih bisa disesuaikan.') }}</p>
                    </div>

                    <!-- UTBK Single Configuration -->
                    <div id="utbk_single_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">{{ __('Konfigurasi UTBK Per Subtest') }}</h4>
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
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                    <input type="number" name="duration_{{ $slug }}" min="1" max="300"
                                        value="{{ $singleDuration }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                    <input type="number" name="passing_score_{{ $slug }}" min="0" max="100" step="0.1"
                                        value="{{ $singlePassing }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                    <select name="passing_type_{{ $slug }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected($singlePassingType === 'score')>{{ __('Skor') }}</option>
                                        <option value="percentage" @selected($singlePassingType === 'percentage')>{{ __('Persentase') }}</option>
                                    </select>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500">{{ __('Input yang relevan akan muncul otomatis saat tipe UTBK subtest dipilih.') }}</p>
                    </div>

                    <div id="skd_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">{{ __('Konfigurasi SKD Full') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- TWK -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">{{ __('Tes Wawasan Kebangsaan (TWK)') }}</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                    <input type="number" name="duration_twk" min="1" max="300"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'twk')->first()?->duration : old('duration_twk', 35) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                    <input type="number" name="passing_score_twk" min="0" max="100" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'twk')->first()?->passing_score : old('passing_score_twk', 65) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                    <select name="passing_type_twk"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_twk', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'twk')->first()?->passing_type : 'score') === 'score')>{{ __('Skor') }}</option>
                                        <option value="percentage" @selected(old('passing_type_twk', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'twk')->first()?->passing_type : 'score') === 'percentage')>{{ __('Persentase') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- TIU -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">{{ __('Tes Intelegensi Umum (TIU)') }}</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                    <input type="number" name="duration_tiu" min="1" max="300"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'tiu')->first()?->duration : old('duration_tiu', 90) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                    <input type="number" name="passing_score_tiu" min="0" max="100" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'tiu')->first()?->passing_score : old('passing_score_tiu', 80) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                    <select name="passing_type_tiu"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_tiu', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'tiu')->first()?->passing_type : 'score') === 'score')>{{ __('Skor') }}</option>
                                        <option value="percentage" @selected(old('passing_type_tiu', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'tiu')->first()?->passing_type : 'score') === 'percentage')>{{ __('Persentase') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- TKP -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">{{ __('Tes Karakteristik Pribadi (TKP)') }}</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                    <input type="number" name="duration_tkp" min="1" max="300"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'tkp')->first()?->duration : old('duration_tkp', 45) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                    <input type="number" name="passing_score_tkp" min="0" max="300" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'tkp')->first()?->passing_score : old('passing_score_tkp', 166) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                    <select name="passing_type_tkp"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_tkp', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'tkp')->first()?->passing_type : 'score') === 'score')>{{ __('Skor') }}</option>
                                        <option value="percentage" @selected(old('passing_type_tkp', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'tkp')->first()?->passing_type : 'score') === 'percentage')>{{ __('Persentase') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Certification Configuration -->
                    <div id="certification_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">{{ __('Konfigurasi TOEFL ITP') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Listening -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">{{ __('Listening Comprehension') }}</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                    <input type="number" name="duration_listening" min="1" max="300"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'listening')->first()?->duration : old('duration_listening', 35) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                    <input type="number" name="passing_score_listening" min="0" max="100" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'listening')->first()?->passing_score : old('passing_score_listening', 60) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                    <select name="passing_type_listening"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_listening', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'listening')->first()?->passing_type : 'score') === 'score')>{{ __('Skor') }}</option>
                                        <option value="percentage" @selected(old('passing_type_listening', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'listening')->first()?->passing_type : 'score') === 'percentage')>{{ __('Persentase') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Structure and Written Expression -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">{{ __('Structure & Written Expression') }}</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                    <input type="number" name="duration_writing" min="1" max="300"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'writing')->first()?->duration : old('duration_writing', 25) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                    <input type="number" name="passing_score_writing" min="0" max="100" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'writing')->first()?->passing_score : old('passing_score_writing', 60) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                    <select name="passing_type_writing"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_writing', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'writing')->first()?->passing_type : 'score') === 'score')>{{ __('Skor') }}</option>
                                        <option value="percentage" @selected(old('passing_type_writing', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'writing')->first()?->passing_type : 'score') === 'percentage')>{{ __('Persentase') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Reading Comprehension -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">{{ __('Reading Comprehension') }}</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                    <input type="number" name="duration_reading" min="1" max="300"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'reading')->first()?->duration : old('duration_reading', 55) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                    <input type="number" name="passing_score_reading" min="0" max="100" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'reading')->first()?->passing_score : old('passing_score_reading', 60) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                    <select name="passing_type_reading"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_reading', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'reading')->first()?->passing_type : 'score') === 'score')>{{ __('Skor') }}</option>
                                        <option value="percentage" @selected(old('passing_type_reading', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'reading')->first()?->passing_type : 'score') === 'percentage')>{{ __('Persentase') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PPPK Full Configuration -->
                    <div id="pppk_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">{{ __('Konfigurasi PPPK Full') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Teknis -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">{{ __('Kompetensi Teknis') }}</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                    <input type="number" name="duration_teknis" min="1" max="300"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'teknis')->first()?->duration : old('duration_teknis', 90) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                    <input type="number" name="passing_score_teknis" min="0" max="540" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'teknis')->first()?->passing_score : old('passing_score_teknis', 65) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                    <select name="passing_type_teknis"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_teknis', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'teknis')->first()?->passing_type : 'score') === 'score')>{{ __('Skor') }}</option>
                                        <option value="percentage" @selected(old('passing_type_teknis', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'teknis')->first()?->passing_type : 'score') === 'percentage')>{{ __('Persentase') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Sosial Kultural -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">Kompetensi Sosial Kultural & Manajerial
                                </h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                    <input type="number" name="duration_social_culture" min="1" max="300"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'social culture')->first()?->duration : old('duration_social_culture', 60) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                    <input type="number" name="passing_score_social_culture" min="0" max="180"
                                        step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'social culture')->first()?->passing_score : old('passing_score_social_culture', 65) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                    <select name="passing_type_social_culture"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_social_culture', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'social culture')->first()?->passing_type : 'score') === 'score')>{{ __('Skor') }}</option>
                                        <option value="percentage" @selected(old('passing_type_social_culture', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'social culture')->first()?->passing_type : 'score') === 'percentage')>{{ __('Persentase') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Wawancara -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">{{ __('Wawancara') }}</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                    <input type="number" name="duration_interview" min="1" max="300"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'interview')->first()?->duration : old('duration_interview', 30) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                    <input type="number" name="passing_score_interview" min="0" max="40" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'interview')->first()?->passing_score : old('passing_score_interview', 70) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                    <select name="passing_type_interview"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_interview', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'interview')->first()?->passing_type : 'score') === 'score')>{{ __('Skor') }}</option>
                                        <option value="percentage" @selected(old('passing_type_interview', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'interview')->first()?->passing_type : 'score') === 'percentage')>{{ __('Persentase') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Computer Full Configuration -->
                    <div id="computer_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">{{ __('Konfigurasi Computer Full') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Word -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">Microsoft Word</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                    <input type="number" name="duration_word" min="1" max="300"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'word')->first()?->duration : old('duration_word', 30) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                    <input type="number" name="passing_score_word" min="0" max="100" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'word')->first()?->passing_score : old('passing_score_word', 70) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                    <select name="passing_type_word"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_word', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'word')->first()?->passing_type : 'score') === 'score')>{{ __('Skor') }}</option>
                                        <option value="percentage" @selected(old('passing_type_word', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'word')->first()?->passing_type : 'score') === 'percentage')>{{ __('Persentase') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Excel -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">Microsoft Excel</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                    <input type="number" name="duration_excel" min="1" max="300"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'excel')->first()?->duration : old('duration_excel', 30) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                    <input type="number" name="passing_score_excel" min="0" max="100" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'excel')->first()?->passing_score : old('passing_score_excel', 70) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                    <select name="passing_type_excel"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_excel', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'excel')->first()?->passing_type : 'score') === 'score')>{{ __('Skor') }}</option>
                                        <option value="percentage" @selected(old('passing_type_excel', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'excel')->first()?->passing_type : 'score') === 'percentage')>{{ __('Persentase') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- PowerPoint -->
                            <div class="space-y-2">
                                <h5 class="font-medium text-sm text-gray-700">Microsoft PowerPoint</h5>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                    <input type="number" name="duration_ppt" min="1" max="300"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'ppt')->first()?->duration : old('duration_ppt', 30) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                    <input type="number" name="passing_score_ppt" min="0" max="100" step="0.1"
                                        value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'ppt')->first()?->passing_score : old('passing_score_ppt', 70) }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                    <select name="passing_type_ppt"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="score" @selected(old('passing_type_ppt', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'ppt')->first()?->passing_type : 'score') === 'score')>{{ __('Skor') }}</option>
                                        <option value="percentage" @selected(old('passing_type_ppt', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'ppt')->first()?->passing_type : 'score') === 'percentage')>{{ __('Persentase') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Individual Computer Tests -->
                    <div id="word_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">{{ __('Konfigurasi Microsoft Word') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                <input type="number" name="duration_word_single" min="1" max="300"
                                    value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'word')->first()?->duration : old('duration_word_single', 30) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                <input type="number" name="passing_score_word_single" min="0" max="100" step="0.1"
                                    value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'word')->first()?->passing_score : old('passing_score_word_single', 70) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                <select name="passing_type_word_single"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="score" @selected(old('passing_type_word_single', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'word')->first()?->passing_type : 'score') === 'score')>{{ __('Skor') }}</option>
                                    <option value="percentage" @selected(old('passing_type_word_single', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'word')->first()?->passing_type : 'score') === 'percentage')>{{ __('Persentase') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="excel_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">{{ __('Konfigurasi Microsoft Excel') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                <input type="number" name="duration_excel_single" min="1" max="300"
                                    value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'excel')->first()?->duration : old('duration_excel_single', 30) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                <input type="number" name="passing_score_excel_single" min="0" max="100" step="0.1"
                                    value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'excel')->first()?->passing_score : old('passing_score_excel_single', 70) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                <select name="passing_type_excel_single"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="score" @selected(old('passing_type_excel_single', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'excel')->first()?->passing_type : 'score') === 'score')>{{ __('Skor') }}</option>
                                    <option value="percentage" @selected(old('passing_type_excel_single', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'excel')->first()?->passing_type : 'score') === 'percentage')>{{ __('Persentase') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="ppt_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">{{ __('Konfigurasi Microsoft PowerPoint') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                <input type="number" name="duration_ppt_single" min="1" max="300"
                                    value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'ppt')->first()?->duration : old('duration_ppt_single', 30) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                <input type="number" name="passing_score_ppt_single" min="0" max="100" step="0.1"
                                    value="{{ isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'ppt')->first()?->passing_score : old('passing_score_ppt_single', 70) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                <select name="passing_type_ppt_single"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="score" @selected(old('passing_type_ppt_single', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'ppt')->first()?->passing_type : 'score') === 'score')>{{ __('Skor') }}</option>
                                    <option value="percentage" @selected(old('passing_type_ppt_single', isset($tryout) ? $tryout->tryoutDetails->where('type_subtest', 'ppt')->first()?->passing_type : 'score') === 'percentage')>{{ __('Persentase') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- General Config (for other single tests) -->
                    <div id="general_config" class="config-section hidden space-y-4">
                        <h4 class="font-medium text-gray-800">{{ __('Konfigurasi Tryout') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">{{ __('Durasi (menit)') }}</label>
                                <input type="number" name="duration_general" min="1" max="300"
                                    value="{{ isset($tryout) ? $tryout->tryoutDetails->first()?->duration : old('duration_general', 60) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">{{ __('Passing Score') }}</label>
                                <input type="number" name="passing_score_general" min="0" max="100" step="0.1"
                                    value="{{ isset($tryout) ? $tryout->tryoutDetails->first()?->passing_score : old('passing_score_general', 60) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">{{ __('Tipe Passing') }}</label>
                                <select name="passing_type_general"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="score" @selected(old('passing_type_general', isset($tryout) ? $tryout->tryoutDetails->first()?->passing_type : 'score') === 'score')>{{ __('Skor') }}</option>
                                    <option value="percentage" @selected(old('passing_type_general', isset($tryout) ? $tryout->tryoutDetails->first()?->passing_type : 'score') === 'percentage')>{{ __('Persentase') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end px-6 py-5 space-x-2 border-t border-gray-200">
                <a href="{{ route('admin.tryout.index') }}"
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900">
                    {{ __('Batal') }}
                </a>
                <button type="submit"
                    class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5">
                    {{ isset($tryout) ? __('Perbarui Tryout') : __('Simpan Tryout') }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
  const utbkSingleTypeMap = @json(collect($utbkSingleTypes ?? [])->mapWithKeys(fn($config, $type) => [$type => $config['slug']])->toArray());
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
    const irtBlock = root.querySelector('[data-irt-toggle]');
    const irtInput = irtBlock ? irtBlock.querySelector('#is_irt') : null;
    if (!typeSelect || typeSelect.__tryoutBound) return;

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

    function showConfigSection() {
      const selectedType = String(typeSelect.value || '').trim();
      const targetId = configSectionMap[selectedType];

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

      toggleIrtVisibility(selectedType);
      toggleUtbkSingleCards(selectedType);
    }

    function toggleIrtVisibility(selectedType) {
      if (!irtBlock || !irtInput) return;
      if (selectedType === 'utbk_full') {
        irtBlock.classList.remove('hidden');
        irtInput.disabled = false;
      } else {
        irtBlock.classList.add('hidden');
        irtInput.disabled = true;
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

    window.__tryoutChange = function () {
      showConfigSection();
      updateFieldNames();
      syncAllPassingScoreLimits();
    };

    window.__tryoutChange();

    // bind event
    typeSelect.addEventListener('change', window.__tryoutChange);
    root.addEventListener('change', (event) => {
      if (event.target && event.target.matches('select[name^="passing_type_"]')) {
        syncPassingScoreLimit(event.target);
      }
    });
    bindPassingScoreInputs();
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
