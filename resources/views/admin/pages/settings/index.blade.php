@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <p class="text-sm text-gray-500">Pengaturan</p>
            <h1 class="text-2xl font-semibold text-gray-900">Branding & Identitas</h1>
            <p class="text-gray-500">Perbarui tampilan umum platform bimbel sesuai kebutuhan klien.</p>
        </div>
        <div class="hidden md:flex items-center gap-3 bg-white border border-border rounded-2xl px-4 py-2 shadow-sm">
            <img src="{{ $branding['logo_url'] ?? asset('img/logo/logo-copoit.png') }}" class="w-10 h-10 rounded-full object-cover"
                alt="Logo Preview">
            <div>
                <p class="text-xs text-gray-500">Saat ini</p>
                <p class="font-semibold text-gray-900">{{ $branding['name'] ?? config('app.name') }}</p>
            </div>
        </div>
    </div>

    @php
    $settingErrorKeys = $errors->keys();
    $aiGeneratorEnabled = (bool) ($branding['ai_question_generator_enabled'] ?? false);
    $aiDiscussionFeatureEnabled = (bool) ($branding['ai_discussion_feature_enabled'] ?? false);
    $activeSettingsTab = old('settings_tab', session('active_tab', 'identity'));
    if ($errors->isNotEmpty() && !old('settings_tab') && !session('active_tab')) {
    if (collect($settingErrorKeys)->intersect(['logo', 'favicon'])->isNotEmpty()) {
    $activeSettingsTab = 'visual';
    } elseif (collect($settingErrorKeys)->intersect(['header_primary_color', 'sidebar_primary_color'])->isNotEmpty()) {
    $activeSettingsTab = 'ui';
    } elseif (collect($settingErrorKeys)->intersect([
    'payment_mode',
    'payment_bank_name',
    'payment_account_number',
    'payment_account_holder',
    'payment_bank_note',
    'payment_unique_code_enabled'
    ])->isNotEmpty()) {
    $activeSettingsTab = 'payment';
    } elseif (collect($settingErrorKeys)->intersect([
    'smtp_email',
    'smtp_app_password',
    'smtp_notification_email'
    ])->isNotEmpty()) {
    $activeSettingsTab = 'smtp';
    } elseif (collect($settingErrorKeys)->intersect([
    'contact_whatsapp_number',
    'contact_whatsapp_button_text',
    'concurrent_login_limit'
    ])->isNotEmpty()) {
    $activeSettingsTab = 'contact';
    } elseif (collect($settingErrorKeys)->intersect([
    'footer_enabled',
    'footer_description',
    'footer_copyright',
    'footer_links',
    'footer_address',
    'footer_phone',
    'footer_email',
    'footer_whatsapp',
    'footer_facebook',
    'footer_instagram',
    'footer_twitter',
    'footer_youtube'
    ])->isNotEmpty()) {
    $activeSettingsTab = 'footer';
    } elseif (collect($settingErrorKeys)->intersect([
    'ai_openai_api_key',
    'ai_openai_base_url',
    'ai_openai_timeout',
    'ai_gemini_api_key',
    'ai_gemini_base_url',
    'ai_gemini_timeout',
    'ai_question_default_model',
    'ai_question_models_json',
    'ai_discussion_enabled',
    'ai_discussion_credential_mode',
    'ai_discussion_model',
    'ai_discussion_openai_api_key',
    'ai_discussion_openai_base_url',
    'ai_discussion_openai_timeout',
    'ai_discussion_gemini_api_key',
    'ai_discussion_gemini_base_url',
    'ai_discussion_gemini_timeout',
    'ai_discussion_max_output_tokens',
    'ai_discussion_instruction'
    ])->isNotEmpty()) {
    $activeSettingsTab = 'ai';
    }
    }

    $aiSettings = old('ai_question_generator_settings', $profile->ai_question_generator_settings ?? ($branding['ai_question_generator_settings'] ?? []));
    $aiProviders = is_array($aiSettings['providers'] ?? null) ? $aiSettings['providers'] : [];
    $aiModels = is_array($aiSettings['models'] ?? null) ? $aiSettings['models'] : [
        ['id' => 'gpt-5.4-mini', 'label' => 'OpenAI - GPT-5.4 Mini', 'provider' => 'openai', 'enabled' => true],
        ['id' => 'gemini-2.5-flash', 'label' => 'Gemini - 2.5 Flash', 'provider' => 'gemini', 'enabled' => true],
        ['id' => 'gemini-2.5-flash-lite', 'label' => 'Gemini - 2.5 Flash-Lite', 'provider' => 'gemini', 'enabled' => true],
    ];
    $aiModelsJson = old('ai_question_models_json', json_encode($aiModels, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $aiDefaultModel = old('ai_question_default_model', $aiSettings['default_model'] ?? ($aiModels[0]['id'] ?? 'gemini-2.5-flash'));
    $aiDiscussionSettings = old('ai_discussion_settings', $profile->ai_discussion_settings ?? ($branding['ai_discussion_settings'] ?? []));
    $aiDiscussionProviders = is_array($aiDiscussionSettings['providers'] ?? null) ? $aiDiscussionSettings['providers'] : [];
    $aiDiscussionEnabled = (bool) old('ai_discussion_enabled', $aiDiscussionSettings['enabled'] ?? false);
    $aiDiscussionCredentialMode = old('ai_discussion_credential_mode', $aiDiscussionSettings['credential_mode'] ?? 'shared');
    $aiDiscussionModel = old('ai_discussion_model', $aiDiscussionSettings['model'] ?? $aiDefaultModel);
    @endphp

    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="settings_tab" id="settings_tab" value="{{ $activeSettingsTab }}">

        @if ($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 text-red-700 text-sm">
            <p class="font-semibold">Pengaturan belum tersimpan</p>
            <p>{{ $errors->first('general') ?: 'Periksa kembali bagian yang ditandai merah, lalu simpan ulang.' }}</p>
        </div>
        @endif

        <div class="bg-white border border-border rounded-2xl shadow-sm p-3 md:p-4">
            <div class="flex flex-wrap gap-2">
                <button type="button" data-settings-tab="identity"
                    class="settings-tab-btn px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeSettingsTab === 'identity' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Informasi Umum
                </button>
                <button type="button" data-settings-tab="visual"
                    class="settings-tab-btn px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeSettingsTab === 'visual' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Logo & Favicon
                </button>
                <button type="button" data-settings-tab="ui"
                    class="settings-tab-btn px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeSettingsTab === 'ui' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Preferensi UI
                </button>
                <button type="button" data-settings-tab="payment"
                    class="settings-tab-btn px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeSettingsTab === 'payment' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Pembayaran
                </button>
                <button type="button" data-settings-tab="smtp"
                    class="settings-tab-btn px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeSettingsTab === 'smtp' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Email SMTP
                </button>
                <button type="button" data-settings-tab="contact"
                    class="settings-tab-btn px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeSettingsTab === 'contact' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Kontak & Login
                </button>
                <button type="button" data-settings-tab="footer"
                    class="settings-tab-btn px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeSettingsTab === 'footer' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Footer
                </button>
                <button type="button" data-settings-tab="ai"
                    class="settings-tab-btn px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeSettingsTab === 'ai' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    AI
                </button>
            </div>
        </div>

        <div data-settings-panel="identity"
            class="settings-tab-panel bg-white border border-border rounded-2xl shadow-sm p-6 space-y-6 {{ $activeSettingsTab !== 'identity' ? 'hidden' : '' }}">
            <div>
                <p class="text-sm font-semibold text-primary mb-1 uppercase tracking-wide">Identitas Bimbel</p>
                <h2 class="text-xl font-semibold text-gray-900">Informasi Umum</h2>
                <p class="text-gray-500 text-sm">Nama bimbel dan kombinasi warna utama yang tampil di seluruh platform.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Nama Bimbel</label>
                    <input type="text" name="nama_bimbel"
                        value="{{ old('nama_bimbel', $profile->nama_bimbel ?? ($branding['name'] ?? '')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Masukkan nama brand" required>
                    @error('nama_bimbel')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Label FAQ</label>
                    <input type="text" name="faq_label"
                        value="{{ old('faq_label', $profile->faq_label ?? ($branding['faq_label'] ?? 'FAQ')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: Informasi" required>
                    <p class="text-xs text-gray-500 mt-1">Mengubah tulisan menu dan halaman bantuan. Contoh: Informasi, Bantuan, atau FAQ.</p>
                    @error('faq_label')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Label Kelas Belajar</label>
                    <input type="text" name="live_session_label"
                        value="{{ old('live_session_label', $profile->live_session_label ?? ($branding['live_session_label'] ?? 'Kelas Belajar')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: Kelas Belajar" required>
                    <p class="text-xs text-gray-500 mt-1">Mengubah tulisan menu dan halaman live session.</p>
                    @error('live_session_label')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Warna Utama</label>
                    <div class="flex items-center gap-3 rounded-xl border border-gray-200 p-3">
                        <input type="color" name="warna_primary"
                            value="{{ old('warna_primary', $profile->warna_primary ?? ($branding['primary_color'] ?? '#1C3259')) }}"
                            class="h-12 w-12 rounded-lg border border-gray-200 cursor-pointer">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">Warna Brand</p>
                            <p class="text-xs text-gray-500">Dipakai untuk tombol utama, link & aksen.</p>
                        </div>
                    </div>
                    @error('warna_primary')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Warna Sekunder</label>
                    <div class="flex items-center gap-3 rounded-xl border border-gray-200 p-3">
                        <input type="color" name="warna_secondary"
                            value="{{ old('warna_secondary', $profile->warna_secondary ?? ($branding['secondary_color'] ?? '#F3F3F3')) }}"
                            class="h-12 w-12 rounded-lg border border-gray-200 cursor-pointer">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">Warna Pendukung</p>
                            <p class="text-xs text-gray-500">Latar belakang card, badge & elemen dekoratif.</p>
                        </div>
                    </div>
                    @error('warna_secondary')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div data-settings-panel="visual"
            class="settings-tab-panel bg-white border border-border rounded-2xl shadow-sm p-6 space-y-6 {{ $activeSettingsTab !== 'visual' ? 'hidden' : '' }}">
            <div>
                <p class="text-sm font-semibold text-primary mb-1 uppercase tracking-wide">Brand Visual</p>
                <h2 class="text-xl font-semibold text-gray-900">Logo & Favicon</h2>
                <p class="text-gray-500 text-sm">Perbarui aset visual utama yang tampil pada navbar, login page, dan tab browser.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
                <div class="space-y-3 h-full">
                    <p class="text-sm font-medium text-gray-900">Logo Utama</p>
                    <label for="logo-input"
                        class="border-2 border-dashed border-gray-200 rounded-2xl p-5 flex flex-col items-center justify-center gap-3 cursor-pointer hover:border-primary hover:bg-primary/5 transition min-h-[220px]">
                        <img id="logo-preview"
                            src="{{ $branding['logo_url'] ?? asset('img/logo/logo-copoit.png') }}"
                            class="h-20 object-contain" alt="Logo Preview">
                        <div class="text-center">
                            <p class="font-semibold text-gray-900">Unggah Logo Baru</p>
                            <p class="text-xs text-gray-500">PNG/JPG/SVG maks 4MB</p>
                        </div>
                        <input id="logo-input" type="file" name="logo" accept="image/png,image/jpeg,image/svg+xml"
                            class="hidden"
                            data-preview-target="logo-preview">
                    </label>
                    @error('logo')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div class="space-y-3 h-full">
                    <p class="text-sm font-medium text-gray-900">Favicon</p>
                    <label for="favicon-input"
                        class="border-2 border-dashed border-gray-200 rounded-2xl p-5 flex flex-col items-center justify-center gap-3 cursor-pointer hover:border-primary hover:bg-primary/5 transition min-h-[220px]">
                        <img id="favicon-preview"
                            src="{{ $branding['favicon_url'] ?? ($branding['logo_url'] ?? asset('img/logo/logo-copoit.png')) }}"
                            class="h-12 w-12 object-contain" alt="Favicon Preview">
                        <div class="text-center">
                            <p class="font-semibold text-gray-900">Unggah Favicon Baru</p>
                            <p class="text-xs text-gray-500">PNG/JPG/ICO maks 2MB</p>
                        </div>
                        <input id="favicon-input" type="file" name="favicon" accept="image/png,image/jpeg,image/x-icon"
                            class="hidden"
                            data-preview-target="favicon-preview">
                    </label>
                    @error('favicon')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div data-settings-panel="ui"
            class="settings-tab-panel bg-white border border-border rounded-2xl shadow-sm p-6 space-y-4 {{ $activeSettingsTab !== 'ui' ? 'hidden' : '' }}">
            <div>
                <p class="text-sm font-semibold text-primary mb-1 uppercase tracking-wide">Preferensi UI</p>
                <h2 class="text-xl font-semibold text-gray-900">Penyesuaian Tampilan</h2>
                <p class="text-gray-500 text-sm">Atur elemen UI yang menggunakan warna utama agar konsisten dengan brand.</p>
            </div>
            @php
            $headerPrimary = old('header_primary_color', $profile->header_primary_color ?? ($clientBranding['header_primary_color'] ?? false));
            $sidebarPrimary = old('sidebar_primary_color', $profile->sidebar_primary_color ?? ($clientBranding['sidebar_primary_color'] ?? false));
            $destinationApiEnabled = old('participant_destination_api_enabled', $profile->participant_destination_api_enabled ?? ($clientBranding['participant_destination_api_enabled'] ?? false));
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex gap-3 border border-gray-200 rounded-2xl p-4 hover:border-primary/60 transition cursor-pointer">
                    <input type="checkbox" name="header_primary_color" value="1"
                        class="mt-1 h-5 w-5 rounded text-primary focus:ring-primary"
                        {{ $headerPrimary ? 'checked' : '' }}>
                    <div>
                        <p class="font-semibold text-gray-900">Header gunakan warna utama</p>
                        <p class="text-xs text-gray-500">Navbar admin & user mengikuti warna brand.</p>
                    </div>
                </label>
                <label class="flex gap-3 border border-gray-200 rounded-2xl p-4 hover:border-primary/60 transition cursor-pointer">
                    <input type="checkbox" name="sidebar_primary_color" value="1"
                        class="mt-1 h-5 w-5 rounded text-primary focus:ring-primary"
                        {{ $sidebarPrimary ? 'checked' : '' }}>
                    <div>
                        <p class="font-semibold text-gray-900">Sidebar gunakan warna utama</p>
                        <p class="text-xs text-gray-500">Menu admin berubah sesuai warna brand.</p>
                    </div>
                </label>
                <label class="flex gap-3 border border-gray-200 rounded-2xl p-4 hover:border-primary/60 transition cursor-pointer">
                    <input type="hidden" name="participant_destination_api_enabled" value="0">
                    <input type="checkbox" name="participant_destination_api_enabled" value="1"
                        class="mt-1 h-5 w-5 rounded text-primary focus:ring-primary"
                        {{ $destinationApiEnabled ? 'checked' : '' }}>
                    <div>
                        <p class="font-semibold text-gray-900">Tampilkan data resmi bersama data manual</p>
                        <p class="text-xs text-gray-500">Register, profil, dan admin user menampilkan gabungan data DB + API resmi SNPMB.</p>
                    </div>
                </label>
            </div>
        </div>

        <div data-settings-panel="payment"
            class="settings-tab-panel bg-white border border-border rounded-2xl shadow-sm p-6 space-y-4 {{ $activeSettingsTab !== 'payment' ? 'hidden' : '' }}">
            <div>
                <p class="text-sm font-semibold text-primary mb-1 uppercase tracking-wide">Pembayaran</p>
                <h2 class="text-xl font-semibold text-gray-900">Mode Pembayaran</h2>
                <p class="text-gray-500 text-sm">Pilih apakah pembayaran diproses otomatis via gateway atau manual.</p>
            </div>
            @php
            $paymentMode = old('payment_mode', $profile->payment_mode ?? ($branding['payment_mode'] ?? 'gateway'));
            $paymentGateway = old('payment_gateway', $profile->payment_gateway ?? ($branding['payment_gateway'] ?? 'xendit'));
            $paymentGatewayMode = old('payment_gateway_mode', $profile->payment_gateway_mode ?? ($branding['payment_gateway_mode'] ?? 'sandbox'));
            $paymentGateways = $paymentGateways ?? config('payment_gateways.gateways', []);
            $paymentUniqueCodeEnabled = old(
                'payment_unique_code_enabled',
                (int) ($profile->payment_unique_code_enabled ?? ($branding['payment_unique_code_enabled'] ?? true))
            );
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex gap-3 border border-gray-200 rounded-2xl p-4 hover:border-primary/60 transition cursor-pointer">
                    <input type="radio" name="payment_mode" value="gateway" class="mt-1 h-5 w-5 text-primary focus:ring-primary"
                        {{ $paymentMode === 'gateway' ? 'checked' : '' }}>
                    <div>
                        <p class="font-semibold text-gray-900">Otomatis (Payment Gateway)</p>
                        <p class="text-xs text-gray-500">Pembayaran langsung diarahkan ke gateway pilihan.</p>
                    </div>
                </label>
                <label class="flex gap-3 border border-gray-200 rounded-2xl p-4 hover:border-primary/60 transition cursor-pointer">
                    <input type="radio" name="payment_mode" value="manual" class="mt-1 h-5 w-5 text-primary focus:ring-primary"
                        {{ $paymentMode === 'manual' ? 'checked' : '' }}>
                    <div>
                        <p class="font-semibold text-gray-900">Manual (Upload Bukti)</p>
                        <p class="text-xs text-gray-500">User mengunggah bukti, admin melakukan ACC di menu pembayaran.</p>
                    </div>
                </label>
            </div>
            @error('payment_mode')
            <p class="text-xs text-red-500">{{ $message }}</p>
            @enderror
            <label class="flex gap-3 border border-gray-200 rounded-2xl p-4 hover:border-primary/60 transition cursor-pointer">
                <input type="hidden" name="payment_unique_code_enabled" value="0">
                <input type="checkbox" name="payment_unique_code_enabled" value="1"
                    class="mt-1 h-5 w-5 rounded text-primary focus:ring-primary"
                    {{ (int) $paymentUniqueCodeEnabled === 1 ? 'checked' : '' }}>
                <div>
                    <p class="font-semibold text-gray-900">Aktifkan kode unik pembayaran</p>
                    <p class="text-xs text-gray-500">Jika aktif, tagihan paket ditambah 3 digit unik untuk membantu pencocokan pembayaran.</p>
                </div>
            </label>
            <div id="payment-manual-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Nama Bank</label>
                    <input type="text" name="payment_bank_name"
                        value="{{ old('payment_bank_name', $profile->payment_bank_name ?? ($branding['payment_bank_name'] ?? '')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: BCA">
                    @error('payment_bank_name')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Nomor Rekening</label>
                    <input type="text" name="payment_account_number"
                        value="{{ old('payment_account_number', $profile->payment_account_number ?? ($branding['payment_account_number'] ?? '')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: 1234567890">
                    @error('payment_account_number')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Nama Pemilik Rekening</label>
                    <input type="text" name="payment_account_holder"
                        value="{{ old('payment_account_holder', $profile->payment_account_holder ?? ($branding['payment_account_holder'] ?? '')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: PT Bimbel Cerdas">
                    @error('payment_account_holder')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Catatan (Opsional)</label>
                    <textarea name="payment_bank_note" rows="5" data-summernote data-height="240"
                        class="summernote-field w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: Kirim bukti max 1x24 jam atau upload gambar QRIS">{{ old('payment_bank_note', $profile->payment_bank_note ?? ($branding['payment_bank_note'] ?? '')) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Bisa isi instruksi pembayaran, link, atau gambar QRIS.</p>
                    @error('payment_bank_note')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div id="payment-gateway-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Gateway</label>
                    <select name="payment_gateway"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5">
                        @foreach($paymentGateways as $gatewayKey => $gatewayMeta)
                            <option value="{{ $gatewayKey }}" {{ $paymentGateway === $gatewayKey ? 'selected' : '' }}>
                                {{ $gatewayMeta['label'] ?? ucfirst(str_replace('_', ' ', $gatewayKey)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('payment_gateway')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Mode</label>
                    <select name="payment_gateway_mode"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5">
                        <option value="sandbox" {{ $paymentGatewayMode === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                        <option value="production" {{ $paymentGatewayMode === 'production' ? 'selected' : '' }}>Production</option>
                    </select>
                    @error('payment_gateway_mode')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2 bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm text-gray-600">
                    <p class="font-semibold text-gray-900 mb-2">Endpoint Otomatis</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div>
                            <p class="text-xs uppercase text-gray-500">Xendit Base URL</p>
                            <p id="xendit-base-url">https://api.xendit.co</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">Midtrans Snap URL</p>
                            <p id="midtrans-snap-url">https://app.sandbox.midtrans.com/snap/v1/transactions</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">Midtrans Status URL</p>
                            <p id="midtrans-status-url">https://api.sandbox.midtrans.com/v2</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">InterActive QRIS Base URL</p>
                            <p id="interactive-qris-base-url">https://qris.interactive.co.id/restapi/qris</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">iPaymu Base URL</p>
                            <p id="ipaymu-base-url">https://sandbox.ipaymu.com/api/v2</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">URL ditentukan otomatis mengikuti mode sandbox/production. InterActive QRIS saat ini adalah API live/production.</p>
                </div>
                <div data-gateway-fields="xendit" class="grid grid-cols-1 md:grid-cols-2 gap-4 md:col-span-2">
                    <div>
                        <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Xendit Secret Key</label>
                        <div class="flex items-center gap-2">
                            <input type="password" name="xendit_secret_key"
                                class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                                placeholder="Kosongkan jika tidak diubah" data-secret-field="xendit_secret_key">
                            <button type="button" class="px-3 py-2 border border-gray-200 rounded-lg text-xs"
                                data-secret-toggle="xendit_secret_key">Show</button>
                        </div>
                        @if (!empty($profile?->getRawOriginal('xendit_secret_key')))
                        <p class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-full px-2.5 py-1 mt-2">
                            <i class="ri-checkbox-circle-line"></i>
                            Secret key sudah tersimpan.
                        </p>
                        @endif
                        @error('xendit_secret_key')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Xendit Webhook Token</label>
                        <div class="flex items-center gap-2">
                            <input type="password" name="xendit_webhook_token"
                                class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                                placeholder="Kosongkan jika tidak diubah" data-secret-field="xendit_webhook_token">
                            <button type="button" class="px-3 py-2 border border-gray-200 rounded-lg text-xs"
                                data-secret-toggle="xendit_webhook_token">Show</button>
                        </div>
                        @if (!empty($profile?->getRawOriginal('xendit_webhook_token')))
                        <p class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-full px-2.5 py-1 mt-2">
                            <i class="ri-checkbox-circle-line"></i>
                            Webhook token sudah tersimpan.
                        </p>
                        @endif
                        @error('xendit_webhook_token')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div data-gateway-fields="midtrans" class="grid grid-cols-1 md:grid-cols-2 gap-4 md:col-span-2">
                    <div>
                        <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Midtrans Server Key</label>
                        <div class="flex items-center gap-2">
                            <input type="password" name="midtrans_server_key"
                                class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                                placeholder="Kosongkan jika tidak diubah" data-secret-field="midtrans_server_key">
                            <button type="button" class="px-3 py-2 border border-gray-200 rounded-lg text-xs"
                                data-secret-toggle="midtrans_server_key">Show</button>
                        </div>
                        @if (!empty($profile?->getRawOriginal('midtrans_server_key')))
                        <p class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-full px-2.5 py-1 mt-2">
                            <i class="ri-checkbox-circle-line"></i>
                            Server key sudah tersimpan.
                        </p>
                        @endif
                        @error('midtrans_server_key')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Midtrans Client Key</label>
                        <div class="flex items-center gap-2">
                            <input type="password" name="midtrans_client_key"
                                class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                                placeholder="Kosongkan jika tidak diubah" data-secret-field="midtrans_client_key">
                            <button type="button" class="px-3 py-2 border border-gray-200 rounded-lg text-xs"
                                data-secret-toggle="midtrans_client_key">Show</button>
                        </div>
                        @if (!empty($profile?->getRawOriginal('midtrans_client_key')))
                        <p class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-full px-2.5 py-1 mt-2">
                            <i class="ri-checkbox-circle-line"></i>
                            Client key sudah tersimpan.
                        </p>
                        @endif
                        @error('midtrans_client_key')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div data-gateway-fields="ipaymu" class="grid grid-cols-1 md:grid-cols-2 gap-4 md:col-span-2">
                    <div>
                        <label class="text-sm font-medium text-gray-900 mb-1 inline-block">iPaymu API Key</label>
                        <div class="flex items-center gap-2">
                            <input type="password" name="ipaymu_api_key"
                                class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                                placeholder="Kosongkan jika tidak diubah" data-secret-field="ipaymu_api_key">
                            <button type="button" class="px-3 py-2 border border-gray-200 rounded-lg text-xs"
                                data-secret-toggle="ipaymu_api_key">Show</button>
                        </div>
                        @if (!empty($profile?->getRawOriginal('ipaymu_api_key')))
                        <p class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-full px-2.5 py-1 mt-2">
                            <i class="ri-checkbox-circle-line"></i>
                            API key sudah tersimpan.
                        </p>
                        @endif
                        @error('ipaymu_api_key')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-900 mb-1 inline-block">VA iPaymu</label>
                        <input type="text" name="ipaymu_va"
                            value="{{ old('ipaymu_va', $profile->ipaymu_va ?? ($branding['ipaymu_va'] ?? '')) }}"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                            placeholder="Contoh: 1179000899">
                        @error('ipaymu_va')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div data-gateway-fields="interactive_qris" class="grid grid-cols-1 md:grid-cols-2 gap-4 md:col-span-2">
                    <div>
                        <label class="text-sm font-medium text-gray-900 mb-1 inline-block">InterActive QRIS API Key</label>
                        <div class="flex items-center gap-2">
                            <input type="password" name="interactive_qris_api_key"
                                class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                                placeholder="Kosongkan jika tidak diubah" data-secret-field="interactive_qris_api_key">
                            <button type="button" class="px-3 py-2 border border-gray-200 rounded-lg text-xs"
                                data-secret-toggle="interactive_qris_api_key">Show</button>
                        </div>
                        @if (!empty($profile?->getRawOriginal('interactive_qris_api_key')))
                        <p class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-full px-2.5 py-1 mt-2">
                            <i class="ri-checkbox-circle-line"></i>
                            API key sudah tersimpan.
                        </p>
                        @endif
                        @error('interactive_qris_api_key')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-900 mb-1 inline-block">mID Merchant</label>
                        <input type="text" name="interactive_qris_mid"
                            value="{{ old('interactive_qris_mid', $profile->interactive_qris_mid ?? ($branding['interactive_qris_mid'] ?? '')) }}"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                            placeholder="Contoh: 123456">
                        @error('interactive_qris_mid')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <label class="md:col-span-2 flex gap-3 border border-gray-200 rounded-2xl p-4 hover:border-primary/60 transition cursor-pointer">
                        <input type="hidden" name="interactive_qris_use_tip" value="0">
                        <input type="checkbox" name="interactive_qris_use_tip" value="1"
                            class="mt-1 h-5 w-5 rounded text-primary focus:ring-primary"
                            {{ old('interactive_qris_use_tip', $profile->interactive_qris_use_tip ?? ($branding['interactive_qris_use_tip'] ?? false)) ? 'checked' : '' }}>
                        <div>
                            <p class="font-semibold text-gray-900">Izinkan tip QRIS</p>
                            <p class="text-xs text-gray-500">Dikirim sebagai parameter <code>useTip=yes</code>. Default mati agar nominal checkout tetap pasti.</p>
                        </div>
                    </label>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Password Admin</label>
                    <input type="password" name="admin_password"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Wajib diisi untuk mengubah kredensial pembayaran">
                    @error('admin_password')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div data-settings-panel="smtp"
            class="settings-tab-panel bg-white border border-border rounded-2xl shadow-sm p-6 space-y-4 {{ $activeSettingsTab !== 'smtp' ? 'hidden' : '' }}">
            <div>
                <p class="text-sm font-semibold text-primary mb-1 uppercase tracking-wide">Email SMTP</p>
                <h2 class="text-xl font-semibold text-gray-900">Notifikasi Pendaftar Baru</h2>
                <p class="text-gray-500 text-sm">Isi email SMTP dan sandi aplikasi. Host/port/enkripsi memakai default sistem.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Email SMTP</label>
                    <input type="email" name="smtp_email"
                        value="{{ old('smtp_email', $profile->smtp_email ?? ($branding['smtp_email'] ?? '')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: notifikasi@domain.com">
                    @error('smtp_email')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Sandi Aplikasi</label>
                    <input type="password" name="smtp_app_password"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Kosongkan jika tidak diubah">
                    @if (!empty($profile?->getRawOriginal('smtp_app_password')))
                    <p class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-full px-2.5 py-1 mt-2">
                        <i class="ri-checkbox-circle-line"></i>
                        Sandi aplikasi sudah tersimpan.
                    </p>
                    @endif
                    @error('smtp_app_password')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Email Tujuan Notifikasi</label>
                    <input type="email" name="smtp_notification_email"
                        value="{{ old('smtp_notification_email', $profile->smtp_notification_email ?? ($branding['smtp_notification_email'] ?? '')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Opsional, default ke Email SMTP">
                    @error('smtp_notification_email')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div data-settings-panel="contact"
            class="settings-tab-panel bg-white border border-border rounded-2xl shadow-sm p-6 space-y-4 {{ $activeSettingsTab !== 'contact' ? 'hidden' : '' }}">
            <div>
                <p class="text-sm font-semibold text-primary mb-1 uppercase tracking-wide">Kontak & Akses</p>
                <h2 class="text-xl font-semibold text-gray-900">Kontak User & Login</h2>
                <p class="text-gray-500 text-sm">Atur tombol WhatsApp dan batas login bersamaan khusus akun user. Admin tidak terkena batasan ini.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Nomor WhatsApp</label>
                    <input type="text" name="contact_whatsapp_number"
                        value="{{ old('contact_whatsapp_number', $profile->contact_whatsapp_number ?? ($branding['contact_whatsapp_number'] ?? '')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: 6281234567890">
                    <p class="text-xs text-gray-500 mt-1">Isi dengan format 62 atau 08. Kosongkan untuk menyembunyikan tombol.</p>
                    @error('contact_whatsapp_number')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Tulisan Button</label>
                    <input type="text" name="contact_whatsapp_button_text"
                        value="{{ old('contact_whatsapp_button_text', $profile->contact_whatsapp_button_text ?? ($branding['contact_whatsapp_button_text'] ?? 'Chat Admin')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: Chat Admin">
                    @error('contact_whatsapp_button_text')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Maksimal Login Bersamaan</label>
                    <input type="number" name="concurrent_login_limit" min="1" max="20"
                        value="{{ old('concurrent_login_limit', $profile->concurrent_login_limit ?? ($branding['concurrent_login_limit'] ?? 1)) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: 1">
                    <p class="text-xs text-gray-500 mt-1">Berlaku hanya untuk akun user. Jika diisi 1, login user baru akan memutus sesi lama. Jika diisi 2, sesi ke-3 akan memutus sesi paling lama.</p>
                    @error('concurrent_login_limit')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div data-settings-panel="footer"
            class="settings-tab-panel bg-white border border-border rounded-2xl shadow-sm p-6 space-y-5 {{ $activeSettingsTab !== 'footer' ? 'hidden' : '' }}">
            @php
            $faqLabel = old('faq_label', $profile->faq_label ?? ($branding['faq_label'] ?? 'FAQ'));
            $defaultFooterLinks = [
                ['label' => $faqLabel, 'url' => '/user/bantuan'],
                ['label' => 'Syarat dan Ketentuan', 'url' => '/terms-and-conditions'],
                ['label' => 'Kebijakan Pembayaran', 'url' => '/payment-policy'],
                ['label' => 'Refund Policy', 'url' => '/refund-policy'],
            ];
            $footerLinks = old('footer_links', $profile->footer_links ?? ($branding['footer_links'] ?? $defaultFooterLinks));
            if (empty($footerLinks)) {
                $footerLinks = $defaultFooterLinks;
            }
            @endphp
            <div>
                <p class="text-sm font-semibold text-primary mb-1 uppercase tracking-wide">Footer User</p>
                <h2 class="text-xl font-semibold text-gray-900">Konten Footer</h2>
                <p class="text-gray-500 text-sm">Footer tampil di halaman user dan bisa diubah sesuai kebutuhan brand.</p>
            </div>

            <label class="flex gap-3 border border-gray-200 rounded-2xl p-4 hover:border-primary/60 transition cursor-pointer">
                <input type="hidden" name="footer_enabled" value="0">
                <input type="checkbox" name="footer_enabled" value="1"
                    class="mt-1 h-5 w-5 rounded text-primary focus:ring-primary"
                    {{ old('footer_enabled', $profile->footer_enabled ?? ($branding['footer_enabled'] ?? true)) ? 'checked' : '' }}>
                <div>
                    <p class="font-semibold text-gray-900">Tampilkan footer di halaman user</p>
                    <p class="text-xs text-gray-500">Matikan jika footer sementara tidak ingin ditampilkan.</p>
                </div>
            </label>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Deskripsi Footer</label>
                    <textarea name="footer_description" rows="3"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Deskripsi singkat platform">{{ old('footer_description', $profile->footer_description ?? ($branding['footer_description'] ?? '')) }}</textarea>
                    @error('footer_description')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Copyright</label>
                    <input type="text" name="footer_copyright"
                        value="{{ old('footer_copyright', $profile->footer_copyright ?? ($branding['footer_copyright'] ?? '')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Kosongkan untuk otomatis mengikuti nama brand dan tahun">
                    @error('footer_copyright')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5 mt-5 space-y-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                    <i class="ri-contacts-book-line text-primary text-lg"></i>
                    Alamat & Kontak Kantor
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Alamat Lengkap</label>
                        <textarea name="footer_address" rows="3"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                            placeholder="Alamat fisik kantor atau bimbel">{{ old('footer_address', $profile->footer_address ?? ($branding['footer_address'] ?? '')) }}</textarea>
                        @error('footer_address')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-900 mb-1 inline-block">No. Telepon</label>
                        <input type="text" name="footer_phone"
                            value="{{ old('footer_phone', $profile->footer_phone ?? ($branding['footer_phone'] ?? '')) }}"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                            placeholder="Contoh: (021) 123456">
                        @error('footer_phone')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-900 mb-1 inline-block">No. WhatsApp</label>
                        <input type="text" name="footer_whatsapp"
                            value="{{ old('footer_whatsapp', $profile->footer_whatsapp ?? ($branding['footer_whatsapp'] ?? '')) }}"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                            placeholder="Contoh: 628123456789">
                        @error('footer_whatsapp')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Email Kontak</label>
                        <input type="email" name="footer_email"
                            value="{{ old('footer_email', $profile->footer_email ?? ($branding['footer_email'] ?? '')) }}"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                            placeholder="Contoh: info@bimbel.com">
                        @error('footer_email')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5 mt-5 space-y-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                    <i class="ri-share-line text-primary text-lg"></i>
                    Media Sosial
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Link Facebook</label>
                        <input type="text" name="footer_facebook"
                            value="{{ old('footer_facebook', $profile->footer_facebook ?? ($branding['footer_facebook'] ?? '')) }}"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                            placeholder="Contoh: https://facebook.com/namahalaman">
                        @error('footer_facebook')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Link Instagram</label>
                        <input type="text" name="footer_instagram"
                            value="{{ old('footer_instagram', $profile->footer_instagram ?? ($branding['footer_instagram'] ?? '')) }}"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                            placeholder="Contoh: https://instagram.com/akunanda">
                        @error('footer_instagram')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Link X/Twitter</label>
                        <input type="text" name="footer_twitter"
                            value="{{ old('footer_twitter', $profile->footer_twitter ?? ($branding['footer_twitter'] ?? '')) }}"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                            placeholder="Contoh: https://twitter.com/akunanda">
                        @error('footer_twitter')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Link YouTube</label>
                        <input type="text" name="footer_youtube"
                            value="{{ old('footer_youtube', $profile->footer_youtube ?? ($branding['footer_youtube'] ?? '')) }}"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                            placeholder="Contoh: https://youtube.com/channel/idchannel">
                        @error('footer_youtube')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div>
                        <p class="font-semibold text-gray-900">Link Footer</p>
                        <p class="text-xs text-gray-500">Bisa pakai path internal seperti /user/bantuan atau URL penuh.</p>
                    </div>
                    <button type="button" id="add-footer-link"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        <i class="ri-add-line"></i>
                        Tambah Link
                    </button>
                </div>
                <div id="footer-links-wrapper" class="space-y-3">
                    @foreach($footerLinks as $index => $link)
                    <div class="footer-link-row grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)_auto] gap-3 rounded-2xl border border-gray-200 p-3">
                        <input type="text" name="footer_links[{{ $index }}][label]"
                            value="{{ $link['label'] ?? '' }}"
                            class="rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                            placeholder="Label">
                        <input type="text" name="footer_links[{{ $index }}][url]"
                            value="{{ $link['url'] ?? '' }}"
                            class="rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                            placeholder="/user/bantuan">
                        <button type="button"
                            class="remove-footer-link inline-flex items-center justify-center rounded-xl border border-gray-200 px-3 py-2.5 text-gray-500 hover:bg-red-50 hover:text-red-600"
                            aria-label="Hapus link footer">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                    @endforeach
                </div>
                @error('footer_links')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div data-settings-panel="ai"
            class="settings-tab-panel bg-white border border-border rounded-2xl shadow-sm p-6 space-y-6 {{ $activeSettingsTab !== 'ai' ? 'hidden' : '' }}">
            <div>
                <p class="text-sm font-semibold text-primary mb-1 uppercase tracking-wide">Pengaturan AI</p>
                <h2 class="text-xl font-semibold text-gray-900">API, Model & Diskusi Pembahasan</h2>
                <p class="text-gray-500 text-sm">Atur API key provider, model generator soal, dan chat AI yang tampil di pembahasan user.</p>
            </div>

            @if($aiGeneratorEnabled)
            <div id="ai-generator-settings-fields" class="space-y-6">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="rounded-2xl border border-gray-200 p-4 space-y-4">
                        <div>
                            <p class="font-semibold text-gray-900">OpenAI</p>
                            <p class="text-xs text-gray-500">Kosongkan API key jika tidak ingin mengubah key yang sudah tersimpan.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">API Key OpenAI</label>
                            <input type="password" name="ai_openai_api_key" autocomplete="new-password"
                                class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                                placeholder="{{ filled($aiProviders['openai']['api_key'] ?? null) ? 'Sudah tersimpan - isi untuk mengganti' : 'sk-...' }}">
                            @error('ai_openai_api_key')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Base URL</label>
                            <input type="url" name="ai_openai_base_url"
                                value="{{ old('ai_openai_base_url', $aiProviders['openai']['base_url'] ?? 'https://api.openai.com/v1') }}"
                                class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5">
                            @error('ai_openai_base_url')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Timeout</label>
                            <input type="number" name="ai_openai_timeout" min="5" max="300"
                                value="{{ old('ai_openai_timeout', $aiProviders['openai']['timeout'] ?? 90) }}"
                                class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5">
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 p-4 space-y-4">
                        <div>
                            <p class="font-semibold text-gray-900">Gemini</p>
                            <p class="text-xs text-gray-500">Cocok untuk mode free tier/testing. Kosongkan API key jika tidak ingin mengubah.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">API Key Gemini</label>
                            <input type="password" name="ai_gemini_api_key" autocomplete="new-password"
                                class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                                placeholder="{{ filled($aiProviders['gemini']['api_key'] ?? null) ? 'Sudah tersimpan - isi untuk mengganti' : 'AIza...' }}">
                            @error('ai_gemini_api_key')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Base URL</label>
                            <input type="url" name="ai_gemini_base_url"
                                value="{{ old('ai_gemini_base_url', $aiProviders['gemini']['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta') }}"
                                class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5">
                            @error('ai_gemini_base_url')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Timeout</label>
                            <input type="number" name="ai_gemini_timeout" min="5" max="300"
                                value="{{ old('ai_gemini_timeout', $aiProviders['gemini']['timeout'] ?? 90) }}"
                                class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-[260px_minmax(0,1fr)]">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Default Model</label>
                        <input type="text" name="ai_question_default_model" value="{{ $aiDefaultModel }}"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                            placeholder="gemini-2.5-flash">
                        @error('ai_question_default_model')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Model JSON</label>
                        <textarea name="ai_question_models_json" rows="10"
                            class="font-mono text-xs w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5">{{ $aiModelsJson }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Format: array object dengan key id, label, provider openai/gemini, enabled true/false.</p>
                        @error('ai_question_models_json')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Admin</label>
                    <input type="password" name="ai_admin_password" autocomplete="current-password"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Wajib diisi kalau mengganti API key AI">
                    @error('admin_password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            @else
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                Fitur generator soal AI tidak aktif untuk role admin saat ini, tetapi diskusi AI pembahasan tetap bisa diatur di bawah.
            </div>
            @endif

            @if($aiDiscussionFeatureEnabled)
            <div class="border-t border-gray-100 pt-6 space-y-5">
                <div>
                    <p class="font-semibold text-gray-900">Diskusi AI di Pembahasan</p>
                    <p class="text-sm text-gray-500">User bisa bertanya ke AI pada tiap soal di halaman pembahasan tryout.</p>
                </div>

                <label class="flex gap-3 border border-gray-200 rounded-2xl p-4 hover:border-primary/60 transition cursor-pointer">
                    <input type="hidden" name="ai_discussion_enabled" value="0">
                    <input type="checkbox" name="ai_discussion_enabled" value="1"
                        class="mt-1 h-5 w-5 rounded text-primary focus:ring-primary"
                        {{ $aiDiscussionEnabled ? 'checked' : '' }}>
                    <div>
                        <p class="font-semibold text-gray-900">Aktifkan diskusi AI per soal</p>
                        <p class="text-xs text-gray-500">Chat hanya tampil kalau pembahasan tryout aktif dan user punya akses paket.</p>
                    </div>
                </label>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mode API</label>
                        <select name="ai_discussion_credential_mode"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5">
                            <option value="shared" {{ $aiDiscussionCredentialMode === 'shared' ? 'selected' : '' }}>Pakai API AI Soal</option>
                            <option value="custom" {{ $aiDiscussionCredentialMode === 'custom' ? 'selected' : '' }}>API khusus pembahasan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Model Chat</label>
                        <input type="text" name="ai_discussion_model" value="{{ $aiDiscussionModel }}"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                            placeholder="gemini-2.5-flash">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Maks. Token Jawaban</label>
                        <input type="number" name="ai_discussion_max_output_tokens" min="200" max="2000"
                            value="{{ old('ai_discussion_max_output_tokens', $aiDiscussionSettings['max_output_tokens'] ?? 700) }}"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="rounded-2xl border border-gray-200 p-4 space-y-4">
                        <div>
                            <p class="font-semibold text-gray-900">OpenAI khusus pembahasan</p>
                            <p class="text-xs text-gray-500">Dipakai hanya jika Mode API memilih API khusus.</p>
                        </div>
                        <input type="password" name="ai_discussion_openai_api_key" autocomplete="new-password"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                            placeholder="{{ filled($aiDiscussionProviders['openai']['api_key'] ?? null) ? 'Sudah tersimpan - isi untuk mengganti' : 'sk-...' }}">
                        <input type="url" name="ai_discussion_openai_base_url"
                            value="{{ old('ai_discussion_openai_base_url', $aiDiscussionProviders['openai']['base_url'] ?? 'https://api.openai.com/v1') }}"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5">
                        <input type="number" name="ai_discussion_openai_timeout" min="5" max="300"
                            value="{{ old('ai_discussion_openai_timeout', $aiDiscussionProviders['openai']['timeout'] ?? 90) }}"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5">
                    </div>

                    <div class="rounded-2xl border border-gray-200 p-4 space-y-4">
                        <div>
                            <p class="font-semibold text-gray-900">Gemini khusus pembahasan</p>
                            <p class="text-xs text-gray-500">Dipakai hanya jika Mode API memilih API khusus.</p>
                        </div>
                        <input type="password" name="ai_discussion_gemini_api_key" autocomplete="new-password"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                            placeholder="{{ filled($aiDiscussionProviders['gemini']['api_key'] ?? null) ? 'Sudah tersimpan - isi untuk mengganti' : 'AIza...' }}">
                        <input type="url" name="ai_discussion_gemini_base_url"
                            value="{{ old('ai_discussion_gemini_base_url', $aiDiscussionProviders['gemini']['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta') }}"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5">
                        <input type="number" name="ai_discussion_gemini_timeout" min="5" max="300"
                            value="{{ old('ai_discussion_gemini_timeout', $aiDiscussionProviders['gemini']['timeout'] ?? 90) }}"
                            class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Instruksi Tambahan Tutor AI</label>
                    <textarea name="ai_discussion_instruction" rows="4"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: jangan langsung beri jawaban akhir, arahkan siswa dengan petunjuk bertahap.">{{ old('ai_discussion_instruction', $aiDiscussionSettings['instruction'] ?? '') }}</textarea>
                </div>

                @if(!$aiGeneratorEnabled)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Admin</label>
                    <input type="password" name="ai_admin_password" autocomplete="current-password"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Wajib diisi kalau mengganti API key AI pembahasan">
                    @error('admin_password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                @endif
            </div>
            @else
            <div class="border-t border-gray-100 pt-6">
                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                    Diskusi AI pembahasan belum diaktifkan oleh super admin. Fitur ini default mati.
                </div>
            </div>
            @endif
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ url()->previous() }}"
                class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50">Batalkan</a>
            <button type="submit"
                class="px-6 py-2.5 rounded-xl bg-primary text-white font-semibold shadow hover:bg-primary/90">Simpan
                Pengaturan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const settingsTabInput = document.getElementById('settings_tab');
        const settingsTabButtons = document.querySelectorAll('[data-settings-tab]');
        const settingsPanels = document.querySelectorAll('[data-settings-panel]');

        const setActiveTab = (tab) => {
            settingsTabButtons.forEach((button) => {
                const isActive = button.getAttribute('data-settings-tab') === tab;
                button.classList.toggle('bg-primary', isActive);
                button.classList.toggle('text-white', isActive);
                button.classList.toggle('bg-gray-100', !isActive);
                button.classList.toggle('text-gray-700', !isActive);
                button.classList.toggle('hover:bg-gray-200', !isActive);
            });

            settingsPanels.forEach((panel) => {
                const isActive = panel.getAttribute('data-settings-panel') === tab;
                panel.classList.toggle('hidden', !isActive);
            });

            if (settingsTabInput) {
                settingsTabInput.value = tab;
            }
        };

        settingsTabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                setActiveTab(button.getAttribute('data-settings-tab'));
            });
        });

        setActiveTab(settingsTabInput?.value || 'identity');

        document.querySelectorAll('input[data-preview-target]').forEach(input => {
            input.addEventListener('change', (event) => {
                const targetId = input.getAttribute('data-preview-target');
                const previewEl = document.getElementById(targetId);
                const file = event.target.files[0];

                if (!file || !previewEl) return;

                const reader = new FileReader();
                reader.onload = (e) => {
                    previewEl.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        });

        const paymentModeInputs = document.querySelectorAll('input[name="payment_mode"]');
        const paymentManualFields = document.getElementById('payment-manual-fields');
        const paymentGatewayFields = document.getElementById('payment-gateway-fields');
        const paymentGatewaySelect = document.querySelector('select[name="payment_gateway"]');
        const paymentGatewayModeSelect = document.querySelector('select[name="payment_gateway_mode"]');
        const xenditBaseUrlEl = document.getElementById('xendit-base-url');
        const midtransSnapUrlEl = document.getElementById('midtrans-snap-url');
        const midtransStatusUrlEl = document.getElementById('midtrans-status-url');
        const interactiveQrisBaseUrlEl = document.getElementById('interactive-qris-base-url');
        const ipaymuBaseUrlEl = document.getElementById('ipaymu-base-url');
        const gatewayBlocks = document.querySelectorAll('[data-gateway-fields]');

        const gatewayEndpoints = {
            sandbox: {
                xenditBase: 'https://api.xendit.co',
                midtransSnap: 'https://app.sandbox.midtrans.com/snap/v1/transactions',
                midtransStatus: 'https://api.sandbox.midtrans.com/v2',
                interactiveQrisBase: 'https://qris.interactive.co.id/restapi/qris',
                ipaymuBase: 'https://sandbox.ipaymu.com/api/v2'
            },
            production: {
                xenditBase: 'https://api.xendit.co',
                midtransSnap: 'https://app.midtrans.com/snap/v1/transactions',
                midtransStatus: 'https://api.midtrans.com/v2',
                interactiveQrisBase: 'https://qris.interactive.co.id/restapi/qris',
                ipaymuBase: 'https://my.ipaymu.com/api/v2'
            }
        };

        const togglePaymentFields = () => {
            const mode = document.querySelector('input[name="payment_mode"]:checked')?.value || 'gateway';
            const gateway = paymentGatewaySelect?.value || 'xendit';
            if (paymentManualFields) {
                paymentManualFields.classList.toggle('hidden', mode !== 'manual');
                paymentManualFields.querySelectorAll('input,select,textarea').forEach((el) => {
                    el.disabled = mode !== 'manual';
                });
            }
            if (paymentGatewayFields) {
                paymentGatewayFields.classList.toggle('hidden', mode !== 'gateway');
                paymentGatewayFields.querySelectorAll('input,select,textarea').forEach((el) => {
                    el.disabled = mode !== 'gateway';
                });
            }

            gatewayBlocks.forEach((block) => {
                const type = block.getAttribute('data-gateway-fields');
                const isActive = gateway === type;
                block.classList.toggle('hidden', !isActive);
                block.querySelectorAll('input').forEach((el) => {
                    el.disabled = !isActive || mode !== 'gateway';
                });
            });

            const env = paymentGatewayModeSelect?.value || 'sandbox';
            const endpoints = gatewayEndpoints[env] || gatewayEndpoints.sandbox;
            if (xenditBaseUrlEl) xenditBaseUrlEl.textContent = endpoints.xenditBase;
            if (midtransSnapUrlEl) midtransSnapUrlEl.textContent = endpoints.midtransSnap;
            if (midtransStatusUrlEl) midtransStatusUrlEl.textContent = endpoints.midtransStatus;
            if (interactiveQrisBaseUrlEl) interactiveQrisBaseUrlEl.textContent = endpoints.interactiveQrisBase;
            if (ipaymuBaseUrlEl) ipaymuBaseUrlEl.textContent = endpoints.ipaymuBase;
        };

        paymentModeInputs.forEach((input) => input.addEventListener('change', togglePaymentFields));
        paymentGatewaySelect?.addEventListener('change', togglePaymentFields);
        paymentGatewayModeSelect?.addEventListener('change', togglePaymentFields);
        togglePaymentFields();

        document.querySelectorAll('[data-secret-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const key = button.getAttribute('data-secret-toggle');
                const input = document.querySelector(`[data-secret-field="${key}"]`);
                if (!input) return;
                const isHidden = input.getAttribute('type') === 'password';
                input.setAttribute('type', isHidden ? 'text' : 'password');
                button.textContent = isHidden ? 'Hide' : 'Show';
            });
        });

        const footerLinksWrapper = document.getElementById('footer-links-wrapper');
        const addFooterLinkButton = document.getElementById('add-footer-link');

        const reindexFooterLinks = () => {
            footerLinksWrapper?.querySelectorAll('.footer-link-row').forEach((row, index) => {
                row.querySelectorAll('input').forEach((input) => {
                    const field = input.name.includes('[url]') ? 'url' : 'label';
                    input.name = `footer_links[${index}][${field}]`;
                });
            });
        };

        const bindFooterRemoveButtons = () => {
            footerLinksWrapper?.querySelectorAll('.remove-footer-link').forEach((button) => {
                button.onclick = () => {
                    button.closest('.footer-link-row')?.remove();
                    reindexFooterLinks();
                };
            });
        };

        addFooterLinkButton?.addEventListener('click', () => {
            if (!footerLinksWrapper) return;
            const count = footerLinksWrapper.querySelectorAll('.footer-link-row').length;
            if (count >= 8) return;

            const row = document.createElement('div');
            row.className = 'footer-link-row grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)_auto] gap-3 rounded-2xl border border-gray-200 p-3';
            row.innerHTML = `
                <input type="text" name="footer_links[${count}][label]"
                    class="rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                    placeholder="Label">
                <input type="text" name="footer_links[${count}][url]"
                    class="rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                    placeholder="/user/bantuan">
                <button type="button"
                    class="remove-footer-link inline-flex items-center justify-center rounded-xl border border-gray-200 px-3 py-2.5 text-gray-500 hover:bg-red-50 hover:text-red-600"
                    aria-label="Hapus link footer">
                    <i class="ri-delete-bin-line"></i>
                </button>
            `;
            footerLinksWrapper.appendChild(row);
            bindFooterRemoveButtons();
        });

        bindFooterRemoveButtons();
    });
</script>
@endpush
