@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <p class="text-sm text-gray-500">Pengaturan</p>
            <h1 class="text-2xl font-semibold text-gray-900">Branding & Identitas</h1>
            <p class="text-gray-500">Perbarui tampilan umum platform bimbel sesuai kebutuhan klien.</p>
        </div>
        <div class="hidden md:flex items-center gap-3 bg-white border border-border rounded-2xl px-4 py-2">
            <img src="{{ $branding['logo_url'] ?? asset('img/logo/logo-copoit.png') }}" class="client-brand-logo w-10 h-10 rounded-full object-cover"
                alt="Logo Preview">
            <div>
                <p class="text-xs text-gray-500">Saat ini</p>
                <p class="font-semibold text-gray-900">{{ $branding['name'] ?? config('app.name') }}</p>
            </div>
        </div>
    </div>

    @php
    $settingErrorKeys = $errors->keys();
    $activeSettingsTab = old('settings_tab', session('active_tab', 'identity'));
    if ($errors->isNotEmpty() && !old('settings_tab') && !session('active_tab')) {
    if (collect($settingErrorKeys)->intersect(['logo', 'logo_display_mode', 'favicon'])->isNotEmpty()) {
    $activeSettingsTab = 'visual';
    } elseif (collect($settingErrorKeys)->intersect(['faq_label', 'live_session_label', 'bimbel_nav_label', 'material_nav_label', 'package_nav_label', 'tryout_nav_label'])->isNotEmpty()) {
    $activeSettingsTab = 'wording';
    } elseif (collect($settingErrorKeys)->intersect(['tutor_content_visibility'])->isNotEmpty()) {
    $activeSettingsTab = 'tutor-content';
    } elseif (collect($settingErrorKeys)->intersect(['header_primary_color', 'sidebar_primary_color'])->isNotEmpty()) {
    $activeSettingsTab = 'ui';
    } elseif (collect($settingErrorKeys)->intersect(['website_translation_enabled', 'website_translation_locales'])->isNotEmpty()) {
    $activeSettingsTab = 'language';
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
    'footer_contacts',
    'footer_socials',
    'footer_phone',
    'footer_email',
    'footer_whatsapp',
    'footer_facebook',
    'footer_instagram',
    'footer_twitter',
    'footer_youtube'
    ])->isNotEmpty()) {
    $activeSettingsTab = 'footer';
    }
    }

    $activeWordingTab = old('wording_tab', session('active_wording_tab', 'general'));
    if ($errors->isNotEmpty() && collect($settingErrorKeys)->intersect(['bimbel_nav_label', 'material_nav_label', 'package_nav_label', 'tryout_nav_label'])->isNotEmpty()) {
    $activeWordingTab = 'navbar';
    }

    $isDemoAdmin = auth()->user()?->isDemoAdmin() ?? false;
    $tutorContentEnabled = (bool) ($profile->tutor_content_enabled ?? ($branding['tutor_content_enabled'] ?? false));
    @endphp

    <form id="settings-form" action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="settings_tab" id="settings_tab" value="{{ $activeSettingsTab }}">
        <input type="hidden" name="wording_tab" id="wording_tab" value="{{ $activeWordingTab }}">

        @if ($isDemoAdmin)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <p class="font-semibold">Mode lihat akun demo</p>
            <p class="mt-1">Seluruh pengaturan dikunci dan tidak dapat disimpan oleh akun demo.</p>
        </div>
        @endif

        @if ($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 text-red-700 text-sm">
            <p class="font-semibold">{{ $errors->has('admin_password') ? 'Password Admin salah' : 'Pengaturan belum tersimpan' }}</p>
            <p>{{ $errors->first('admin_password') ?: ($errors->first('general') ?: 'Periksa kembali bagian yang ditandai merah, lalu simpan ulang.') }}</p>
        </div>
        @endif

        <div class="bg-white border border-border rounded-2xl shadow-sm p-3 md:p-4">
            <div class="flex flex-wrap gap-2">
                <button type="button" data-settings-tab="identity"
                    class="settings-tab-btn px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeSettingsTab === 'identity' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Informasi Umum
                </button>
                <button type="button" data-settings-tab="wording"
                    class="settings-tab-btn px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeSettingsTab === 'wording' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Wording & Label
                </button>
                @if($tutorContentEnabled)<button type="button" data-settings-tab="tutor-content"
                    class="settings-tab-btn px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeSettingsTab === 'tutor-content' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Konten Tutor
                </button>@endif
                <button type="button" data-settings-tab="visual"
                    class="settings-tab-btn px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeSettingsTab === 'visual' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Logo & Favicon
                </button>
                <button type="button" data-settings-tab="ui"
                    class="settings-tab-btn px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeSettingsTab === 'ui' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Preferensi UI
                </button>
                <button type="button" data-settings-tab="language"
                    class="settings-tab-btn px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeSettingsTab === 'language' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Bahasa
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
            </div>
        </div>

        <fieldset @disabled($isDemoAdmin)>
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

        <div data-settings-panel="wording"
            class="settings-tab-panel bg-white border border-border rounded-2xl shadow-sm p-6 space-y-6 {{ $activeSettingsTab !== 'wording' ? 'hidden' : '' }}">
            <div>
                <p class="text-sm font-semibold text-primary mb-1 uppercase tracking-wide">Wording Platform</p>
                <h2 class="text-xl font-semibold text-gray-900">Label Menu & Fitur</h2>
                <p class="text-gray-500 text-sm">Sesuaikan istilah yang tampil kepada pengguna tanpa mengubah fungsi sistem.</p>
            </div>

            <div class="flex flex-wrap gap-2 border-b border-gray-100 pb-4">
                <button type="button" data-wording-tab="general"
                    class="wording-tab-btn px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeWordingTab === 'general' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Umum
                </button>
                <button type="button" data-wording-tab="navbar"
                    class="wording-tab-btn px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeWordingTab === 'navbar' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Navbar
                </button>
            </div>

            <div data-wording-panel="general" class="wording-tab-panel grid grid-cols-1 md:grid-cols-2 gap-6 {{ $activeWordingTab !== 'general' ? 'hidden' : '' }}">
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
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Label FAQ</label>
                    <input type="text" name="faq_label"
                        value="{{ old('faq_label', $profile->faq_label ?? ($branding['faq_label'] ?? 'FAQ')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: Informasi" required>
                    <p class="text-xs text-gray-500 mt-1">Mengubah tulisan menu dan halaman bantuan.</p>
                    @error('faq_label')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div data-wording-panel="navbar" class="wording-tab-panel grid grid-cols-1 md:grid-cols-2 gap-6 {{ $activeWordingTab !== 'navbar' ? 'hidden' : '' }}">
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Label Navbar Bimbel</label>
                    <input type="text" name="bimbel_nav_label"
                        value="{{ old('bimbel_nav_label', $profile->bimbel_nav_label ?? ($branding['bimbel_nav_label'] ?? 'Bimbel')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: Belajar" required>
                    <p class="text-xs text-gray-500 mt-1">Mengubah label menu Bimbel pada navbar user, desktop, dan mobile.</p>
                    @error('bimbel_nav_label')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Label Menu Materi</label>
                    <input type="text" name="material_nav_label"
                        value="{{ old('material_nav_label', $profile->material_nav_label ?? ($branding['material_nav_label'] ?? 'Kelas & Materi')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: Kelas & Materi" required>
                    <p class="text-xs text-gray-500 mt-1">Label submenu materi di menu {{ $branding['bimbel_nav_label'] ?? 'Bimbel' }}.</p>
                    @error('material_nav_label')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Label Menu Paket</label>
                    <input type="text" name="package_nav_label"
                        value="{{ old('package_nav_label', $profile->package_nav_label ?? ($branding['package_nav_label'] ?? 'Paket Belajar')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: Paket Belajar" required>
                    <p class="text-xs text-gray-500 mt-1">Label submenu paket di menu {{ $branding['bimbel_nav_label'] ?? 'Bimbel' }}.</p>
                    @error('package_nav_label')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Label Menu Try Out</label>
                    <input type="text" name="tryout_nav_label"
                        value="{{ old('tryout_nav_label', $profile->tryout_nav_label ?? ($branding['tryout_nav_label'] ?? 'Ujian & Try Out')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: Ujian & Try Out" required>
                    <p class="text-xs text-gray-500 mt-1">Label submenu try out di menu {{ $branding['bimbel_nav_label'] ?? 'Bimbel' }}.</p>
                    @error('tryout_nav_label')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        @if($tutorContentEnabled)<div data-settings-panel="tutor-content"
            class="settings-tab-panel bg-white border border-border rounded-2xl shadow-sm p-6 space-y-5 {{ $activeSettingsTab !== 'tutor-content' ? 'hidden' : '' }}">
            <div>
                <p class="text-sm font-semibold text-primary mb-1 uppercase tracking-wide">Konten Tutor</p>
                <h2 class="text-xl font-semibold text-gray-900">Visibilitas Konten Operasional</h2>
                <p class="text-gray-500 text-sm">Pengaturan ini berlaku untuk Tryout, Materi, Bank Soal, dan soal yang dibuat dari menu tersebut. Paket selalu dikelola Admin.</p>
            </div>

            @php
                $tutorContentVisibility = old(
                    'tutor_content_visibility',
                    $profile->tutor_content_visibility ?? 'shared'
                );
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label class="flex gap-3 border rounded-2xl p-4 cursor-pointer transition {{ $tutorContentVisibility === 'shared' ? 'border-primary bg-primary/5' : 'border-gray-200 hover:border-primary/60' }}">
                    <input type="radio" name="tutor_content_visibility" value="shared"
                        class="mt-1 h-5 w-5 text-primary focus:ring-primary"
                        {{ $tutorContentVisibility === 'shared' ? 'checked' : '' }}>
                    <div>
                        <p class="font-semibold text-gray-900">Gabung</p>
                        <p class="mt-1 text-xs leading-5 text-gray-500">Pengelola yang memiliki izin dapat melihat dan memakai konten bersama. Cocok untuk bank soal dan tryout pusat.</p>
                    </div>
                </label>
                <label class="flex gap-3 border rounded-2xl p-4 cursor-pointer transition {{ $tutorContentVisibility === 'isolated' ? 'border-primary bg-primary/5' : 'border-gray-200 hover:border-primary/60' }}">
                    <input type="radio" name="tutor_content_visibility" value="isolated"
                        class="mt-1 h-5 w-5 text-primary focus:ring-primary"
                        {{ $tutorContentVisibility === 'isolated' ? 'checked' : '' }}>
                    <div>
                        <p class="font-semibold text-gray-900">Isolasi Penuh</p>
                        <p class="mt-1 text-xs leading-5 text-gray-500">Setiap akun hanya melihat dan mengelola konten miliknya. Super Admin tetap dapat melihat seluruh konten.</p>
                    </div>
                </label>
                <label class="flex gap-3 border rounded-2xl p-4 cursor-pointer transition {{ $tutorContentVisibility === 'tutor_isolated' ? 'border-primary bg-primary/5' : 'border-gray-200 hover:border-primary/60' }}">
                    <input type="radio" name="tutor_content_visibility" value="tutor_isolated"
                        class="mt-1 h-5 w-5 text-primary focus:ring-primary"
                        {{ $tutorContentVisibility === 'tutor_isolated' ? 'checked' : '' }}>
                    <div>
                        <p class="font-semibold text-gray-900">Isolasi Tutor</p>
                        <p class="mt-1 text-xs leading-5 text-gray-500">Tutor melihat konten sendiri dan Admin, tetapi tidak dapat menghapus konten Admin. Konten Tutor lain tetap terpisah.</p>
                    </div>
                </label>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Data lama yang belum memiliki pembuat hanya tetap terlihat oleh Admin saat mode isolasi aktif. Konten baru otomatis menjadi milik akun yang membuatnya.
            </div>

            @error('tutor_content_visibility')
                <p class="text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>@endif

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
                            class="client-brand-logo h-20 w-20 object-contain" alt="Logo Preview">
                        <div class="text-center">
                            <p class="font-semibold text-gray-900">Unggah Logo Baru</p>
                            <p class="text-xs text-gray-500">Rasio bebas; tinggi ideal 160 px (contoh 512 × 160 px). PNG/JPG/SVG maks 4MB</p>
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
                            <p class="text-xs text-gray-500">Ukuran ideal: 512 × 512 px (rasio 1:1). PNG/JPG/ICO maks 2MB</p>
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

            @php
                $logoDisplayMode = old(
                    'logo_display_mode',
                    $profile->logo_display_mode ?? ($branding['logo_display_mode'] ?? 'square')
                );
            @endphp
            <div>
                <p class="text-sm font-medium text-gray-900">Bentuk Tampilan Logo</p>
                <p class="mt-1 text-xs text-gray-500">Pilih rasio asli untuk logo memanjang; pilih kotak untuk tampilan ikon yang seragam.</p>
                <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <label class="flex cursor-pointer gap-3 rounded-2xl border p-4 transition {{ $logoDisplayMode === 'original' ? 'border-primary bg-primary/5' : 'border-gray-200 hover:border-primary/60' }}">
                        <input type="radio" name="logo_display_mode" value="original" data-logo-display-mode
                            class="mt-1 h-5 w-5 text-primary focus:ring-primary"
                            {{ $logoDisplayMode === 'original' ? 'checked' : '' }}>
                        <span>
                            <span class="block font-semibold text-gray-900">Rasio asli</span>
                            <span class="mt-1 block text-xs leading-5 text-gray-500">Lebar mengikuti proporsi file logo. Cocok untuk logo horizontal atau vertikal.</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer gap-3 rounded-2xl border p-4 transition {{ $logoDisplayMode === 'square' ? 'border-primary bg-primary/5' : 'border-gray-200 hover:border-primary/60' }}">
                        <input type="radio" name="logo_display_mode" value="square" data-logo-display-mode
                            class="mt-1 h-5 w-5 text-primary focus:ring-primary"
                            {{ $logoDisplayMode === 'square' ? 'checked' : '' }}>
                        <span>
                            <span class="block font-semibold text-gray-900">Kotak</span>
                            <span class="mt-1 block text-xs leading-5 text-gray-500">Logo ditempatkan dalam area persegi yang konsisten di seluruh platform.</span>
                        </span>
                    </label>
                </div>
                @error('logo_display_mode')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
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

        <div data-settings-panel="language"
            class="settings-tab-panel bg-white border border-border rounded-2xl shadow-sm p-6 space-y-5 {{ $activeSettingsTab !== 'language' ? 'hidden' : '' }}">
            <div>
                <p class="text-sm font-semibold text-primary mb-1 uppercase tracking-wide">Bahasa</p>
                <h2 class="text-xl font-semibold text-gray-900">Terjemahan Otomatis Halaman</h2>
                <p class="text-gray-500 text-sm">Sediakan pilihan bahasa pada seluruh halaman tanpa menerjemahkan setiap teks secara manual.</p>
            </div>
            @php
                $websiteTranslationEnabled = old(
                    'website_translation_enabled',
                    $profile->website_translation_enabled ?? ($branding['website_translation_enabled'] ?? false)
                );
                $websiteTranslationLocales = old(
                    'website_translation_locales',
                    $profile->website_translation_locales ?? ($branding['website_translation_locales'] ?? ['en', 'zh-CN', 'ja', 'ar', 'ko'])
                );
                $websiteTranslationLocales = is_array($websiteTranslationLocales) ? $websiteTranslationLocales : [];
            @endphp
            <label class="flex gap-3 border border-gray-200 rounded-2xl p-4 hover:border-primary/60 transition cursor-pointer">
                <input type="checkbox" name="website_translation_enabled" value="1"
                    class="mt-1 h-5 w-5 rounded text-primary focus:ring-primary"
                    @checked((bool) $websiteTranslationEnabled)>
                <div>
                    <p class="font-semibold text-gray-900">Aktifkan pilihan bahasa</p>
                    <p class="text-xs text-gray-500">User dapat memilih bahasa dari tombol di sudut kanan bawah pada setiap halaman.</p>
                </div>
            </label>
            <div>
                <p class="text-sm font-medium text-gray-900">Bahasa yang tersedia</p>
                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex gap-3 border border-gray-200 rounded-2xl p-4 hover:border-primary/60 transition cursor-pointer">
                        <input type="checkbox" name="website_translation_locales[]" value="en"
                            class="mt-1 h-5 w-5 rounded text-primary focus:ring-primary"
                            @checked(in_array('en', $websiteTranslationLocales, true))>
                        <div>
                            <p class="font-semibold text-gray-900">English</p>
                            <p class="text-xs text-gray-500">Terjemahan otomatis dari Bahasa Indonesia ke English.</p>
                        </div>
                    </label>
                    <label class="flex gap-3 border border-gray-200 rounded-2xl p-4 hover:border-primary/60 transition cursor-pointer">
                        <input type="checkbox" name="website_translation_locales[]" value="zh-CN"
                            class="mt-1 h-5 w-5 rounded text-primary focus:ring-primary"
                            @checked(in_array('zh-CN', $websiteTranslationLocales, true))>
                        <div>
                            <p class="font-semibold text-gray-900">Mandarin (Simplified)</p>
                            <p class="text-xs text-gray-500">Terjemahan otomatis ke 中文（简体）.</p>
                        </div>
                    </label>
                    <label class="flex gap-3 border border-gray-200 rounded-2xl p-4 hover:border-primary/60 transition cursor-pointer">
                        <input type="checkbox" name="website_translation_locales[]" value="ja"
                            class="mt-1 h-5 w-5 rounded text-primary focus:ring-primary"
                            @checked(in_array('ja', $websiteTranslationLocales, true))>
                        <div>
                            <p class="font-semibold text-gray-900">Japanese</p>
                            <p class="text-xs text-gray-500">Terjemahan otomatis ke 日本語.</p>
                        </div>
                    </label>
                    <label class="flex gap-3 border border-gray-200 rounded-2xl p-4 hover:border-primary/60 transition cursor-pointer">
                        <input type="checkbox" name="website_translation_locales[]" value="ar"
                            class="mt-1 h-5 w-5 rounded text-primary focus:ring-primary"
                            @checked(in_array('ar', $websiteTranslationLocales, true))>
                        <div>
                            <p class="font-semibold text-gray-900">Arabic</p>
                            <p class="text-xs text-gray-500">Terjemahan otomatis ke العربية.</p>
                        </div>
                    </label>
                    <label class="flex gap-3 border border-gray-200 rounded-2xl p-4 hover:border-primary/60 transition cursor-pointer">
                        <input type="checkbox" name="website_translation_locales[]" value="ko"
                            class="mt-1 h-5 w-5 rounded text-primary focus:ring-primary"
                            @checked(in_array('ko', $websiteTranslationLocales, true))>
                        <div>
                            <p class="font-semibold text-gray-900">Korean</p>
                            <p class="text-xs text-gray-500">Terjemahan otomatis ke 한국어.</p>
                        </div>
                    </label>
                </div>
                <p class="mt-3 text-xs text-gray-500">Form, editor, dan rumus matematika tidak diubah agar input dan fungsi tryout tetap aman.</p>
            </div>
            @error('website_translation_locales')
                <p class="text-xs text-red-500">{{ $message }}</p>
            @enderror
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
                            <p id="ipaymu-base-url">https://sandbox.ipaymu.com</p>
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
                        placeholder="Wajib diisi untuk mengubah kredensial pembayaran atau SMTP">
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
            <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-4"><button form="smtp-test-form" type="submit" class="inline-flex items-center gap-2 rounded-xl border border-primary px-4 py-2.5 text-sm font-semibold text-primary hover:bg-primary hover:text-white"><i class="ri-send-plane-line"></i>Kirim Email Tes</button><p class="text-xs text-gray-500">Simpan SMTP terlebih dahulu, lalu cek inbox tujuan.</p></div>
        </div>
        <form id="smtp-test-form" method="POST" action="{{ route('admin.settings.smtp.test') }}">@csrf</form>

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
            $legacyFooterContacts = array_values(array_filter([
                ['type' => 'phone', 'label' => 'Telepon', 'value' => $profile->footer_phone ?? ($branding['footer_phone'] ?? null)],
                ['type' => 'whatsapp', 'label' => 'WhatsApp', 'value' => $profile->footer_whatsapp ?? ($branding['footer_whatsapp'] ?? null)],
                ['type' => 'email', 'label' => 'Email', 'value' => $profile->footer_email ?? ($branding['footer_email'] ?? null)],
            ], fn ($contact) => filled($contact['value'])));
            $legacyFooterSocials = array_values(array_filter([
                ['platform' => 'facebook', 'label' => 'Facebook', 'url' => $profile->footer_facebook ?? ($branding['footer_facebook'] ?? null)],
                ['platform' => 'instagram', 'label' => 'Instagram', 'url' => $profile->footer_instagram ?? ($branding['footer_instagram'] ?? null)],
                ['platform' => 'twitter', 'label' => 'X/Twitter', 'url' => $profile->footer_twitter ?? ($branding['footer_twitter'] ?? null)],
                ['platform' => 'youtube', 'label' => 'YouTube', 'url' => $profile->footer_youtube ?? ($branding['footer_youtube'] ?? null)],
            ], fn ($social) => filled($social['url'])));
            $footerContacts = old('footer_contacts', is_array($profile->footer_contacts ?? null)
                ? $profile->footer_contacts
                : $legacyFooterContacts);
            $footerSocials = old('footer_socials', is_array($profile->footer_socials ?? null)
                ? $profile->footer_socials
                : $legacyFooterSocials);
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
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Alamat Lengkap</label>
                    <textarea name="footer_address" rows="3"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Alamat fisik kantor atau bimbel">{{ old('footer_address', $profile->footer_address ?? ($branding['footer_address'] ?? '')) }}</textarea>
                    @error('footer_address')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-2xl border border-gray-200 p-4">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-900"><i class="ri-contacts-book-line text-lg text-primary"></i> Kontak</h3>
                            <p class="mt-1 text-xs text-gray-500">Tambahkan telepon, WhatsApp, email, atau informasi kontak lainnya.</p>
                        </div>
                        <button type="button" id="add-footer-contact" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"><i class="ri-add-line"></i> Tambah Kontak</button>
                    </div>
                    <div id="footer-contacts-wrapper" class="space-y-3">
                        @foreach($footerContacts as $index => $contact)
                        <div class="footer-contact-row grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-gray-50/70 p-4 md:grid-cols-12">
                            <div class="md:col-span-3">
                                <label class="mb-1.5 block text-xs font-medium text-gray-600">Jenis kontak</label>
                                <select name="footer_contacts[{{ $index }}][type]" data-footer-field="type" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/30">
                                    @foreach(['phone' => 'Telepon', 'whatsapp' => 'WhatsApp', 'email' => 'Email', 'text' => 'Teks lain'] as $type => $label)
                                    <option value="{{ $type }}" @selected(($contact['type'] ?? 'text') === $type)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-3">
                                <label class="mb-1.5 block text-xs font-medium text-gray-600">Label</label>
                                <input type="text" name="footer_contacts[{{ $index }}][label]" data-footer-field="label" value="{{ $contact['label'] ?? '' }}" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/30" placeholder="Admin pendaftaran">
                            </div>
                            <div class="md:col-span-5">
                                <label class="mb-1.5 block text-xs font-medium text-gray-600">Kontak</label>
                                <input type="text" name="footer_contacts[{{ $index }}][value]" data-footer-field="value" value="{{ $contact['value'] ?? '' }}" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/30" placeholder="Nomor, email, atau keterangan">
                            </div>
                            <div class="flex items-end md:col-span-1">
                                <button type="button" class="remove-footer-contact inline-flex h-11 w-full items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-red-50 hover:text-red-600" aria-label="Hapus kontak"><i class="ri-delete-bin-line"></i></button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @error('footer_contacts')<p class="mt-2 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div class="rounded-2xl border border-gray-200 p-4">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-900"><i class="ri-share-line text-lg text-primary"></i> Media Sosial</h3>
                            <p class="mt-1 text-xs text-gray-500">Pilih platform yang ingin ditampilkan sebagai ikon di footer.</p>
                        </div>
                        <button type="button" id="add-footer-social" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"><i class="ri-add-line"></i> Tambah Medsos</button>
                    </div>
                    <div id="footer-socials-wrapper" class="space-y-3">
                        @foreach($footerSocials as $index => $social)
                        <div class="footer-social-row grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-gray-50/70 p-4 md:grid-cols-12">
                            <div class="md:col-span-3">
                                <label class="mb-1.5 block text-xs font-medium text-gray-600">Platform</label>
                                <select name="footer_socials[{{ $index }}][platform]" data-footer-field="platform" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/30">
                                    @foreach(['facebook' => 'Facebook', 'instagram' => 'Instagram', 'twitter' => 'X/Twitter', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'linkedin' => 'LinkedIn', 'website' => 'Website', 'custom' => 'Lainnya'] as $platform => $label)
                                    <option value="{{ $platform }}" @selected(($social['platform'] ?? 'custom') === $platform)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-3">
                                <label class="mb-1.5 block text-xs font-medium text-gray-600">Label</label>
                                <input type="text" name="footer_socials[{{ $index }}][label]" data-footer-field="label" value="{{ $social['label'] ?? '' }}" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/30" placeholder="Nama akun (opsional)">
                            </div>
                            <div class="md:col-span-5">
                                <label class="mb-1.5 block text-xs font-medium text-gray-600">Tautan profil</label>
                                <input type="url" name="footer_socials[{{ $index }}][url]" data-footer-field="url" value="{{ $social['url'] ?? '' }}" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/30" placeholder="https://...">
                            </div>
                            <div class="flex items-end md:col-span-1">
                                <button type="button" class="remove-footer-social inline-flex h-11 w-full items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-red-50 hover:text-red-600" aria-label="Hapus media sosial"><i class="ri-delete-bin-line"></i></button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @error('footer_socials')<p class="mt-2 text-xs text-red-500">{{ $message }}</p>@enderror
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

        {{--
            Konfigurasi AI dikelola penuh oleh Super Admin. Panel lama sengaja
            tidak dirender untuk admin agar API key, model, dan limit tidak
            dapat lagi diubah dari halaman ini.
        --}}
        {{-- <div data-settings-panel="ai"
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

            @if($aiDiscussionConfigurable)
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

                @php
                    $aiDiscussionFeatureLimits = is_array($aiDiscussionSettings['feature_token_limits'] ?? null) ? $aiDiscussionSettings['feature_token_limits'] : [];
                    $aiDiscussionFeatures = [
                        'discussion' => ['Tanya jawab', 700],
                        'learning_note' => ['Catatan', 1200],
                        'learning_flashcard' => ['Flashcard', 500],
                        'learning_question' => ['Latihan mirip', 1800],
                    ];
                @endphp
                <div class="rounded-2xl border border-primary/10 bg-primary/5 p-4">
                    <p class="font-semibold text-gray-900">Batas output per fitur</p>
                    <p class="mt-1 text-xs text-gray-500">Diterapkan server-side untuk setiap request AI.</p>
                    <div class="mt-3 grid grid-cols-2 gap-3 lg:grid-cols-4">
                        @foreach($aiDiscussionFeatures as $feature => [$label, $default])
                            <label class="rounded-xl border border-gray-200 bg-white p-3">
                                <span class="block text-sm font-semibold text-gray-800">{{ $label }}</span>
                                <input type="number" name="ai_discussion_feature_token_limits[{{ $feature }}]" min="64" max="2000" value="{{ old('ai_discussion_feature_token_limits.' . $feature, $aiDiscussionFeatureLimits[$feature] ?? $default) }}" class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                <span class="mt-1 block text-xs text-gray-500">token output</span>
                            </label>
                        @endforeach
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
                    Pengaturan Diskusi AI Pembahasan dikelola oleh super admin.
                </div>
            </div>
            @endif
        </div> --}}

        <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
            <a href="{{ url()->previous() }}"
                class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50">Batalkan</a>
            <button type="submit"
                class="px-6 py-2.5 rounded-xl bg-primary text-white font-semibold hover:bg-primary/90">Simpan
                Pengaturan</button>
        </div>
        </fieldset>
    </form>

    <div id="settings-password-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4"><div class="w-full max-w-md rounded-2xl bg-white p-6"><h2 class="text-lg font-semibold">Konfirmasi Password Admin</h2><p class="mt-1 text-sm text-gray-500">Masukkan password untuk menyimpan perubahan.</p><input id="settings-modal-password" type="password" class="mt-4 w-full rounded-xl border border-gray-200 px-4 py-2.5" placeholder="Password Admin"><div class="mt-5 flex justify-end gap-3"><button type="button" data-close-settings-modal class="rounded-xl border px-4 py-2">Batal</button><button id="settings-confirm-save" type="button" class="rounded-xl bg-primary px-4 py-2 text-white">Simpan</button></div></div></div>
    <div id="smtp-test-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4"><div class="w-full max-w-md rounded-2xl bg-white p-6"><h2 class="text-lg font-semibold">Kirim Email Tes</h2><p class="mt-1 text-sm text-gray-500">Email ini hanya dipakai untuk pengujian dan tidak disimpan.</p><input form="smtp-test-form" name="recipient" type="email" class="mt-4 w-full rounded-xl border border-gray-200 px-4 py-2.5" placeholder="email@penerima.com" required><div class="mt-5 flex justify-end gap-3"><button type="button" data-close-smtp-modal class="rounded-xl border px-4 py-2">Batal</button><button form="smtp-test-form" type="submit" class="rounded-xl bg-primary px-4 py-2 text-white">Kirim</button></div></div></div>
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

        const wordingTabInput = document.getElementById('wording_tab');
        const wordingTabButtons = document.querySelectorAll('[data-wording-tab]');
        const wordingPanels = document.querySelectorAll('[data-wording-panel]');

        const setActiveWordingTab = (tab) => {
            wordingTabButtons.forEach((button) => {
                const isActive = button.getAttribute('data-wording-tab') === tab;
                button.classList.toggle('bg-primary', isActive);
                button.classList.toggle('text-white', isActive);
                button.classList.toggle('bg-gray-100', !isActive);
                button.classList.toggle('text-gray-700', !isActive);
                button.classList.toggle('hover:bg-gray-200', !isActive);
            });

            wordingPanels.forEach((panel) => {
                const isActive = panel.getAttribute('data-wording-panel') === tab;
                panel.classList.toggle('hidden', !isActive);
            });

            if (wordingTabInput) {
                wordingTabInput.value = tab;
            }
        };

        wordingTabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                setActiveWordingTab(button.getAttribute('data-wording-tab'));
            });
        });

        setActiveWordingTab(wordingTabInput?.value || 'general');

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
                ipaymuBase: 'https://sandbox.ipaymu.com'
            },
            production: {
                xenditBase: 'https://api.xendit.co',
                midtransSnap: 'https://app.midtrans.com/snap/v1/transactions',
                midtransStatus: 'https://api.midtrans.com/v2',
                interactiveQrisBase: 'https://qris.interactive.co.id/restapi/qris',
                ipaymuBase: 'https://my.ipaymu.com'
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

        const configureDynamicFooterItems = ({ wrapperId, addButtonId, rowClass, removeClass, fieldName, rowTemplate }) => {
            const wrapper = document.getElementById(wrapperId);
            const addButton = document.getElementById(addButtonId);

            const reindex = () => {
                wrapper?.querySelectorAll(`.${rowClass}`).forEach((row, index) => {
                    row.querySelectorAll('[data-footer-field]').forEach((field) => {
                        field.name = `${fieldName}[${index}][${field.dataset.footerField}]`;
                    });
                });
            };

            const bindRemoveButtons = () => {
                wrapper?.querySelectorAll(`.${removeClass}`).forEach((button) => {
                    button.onclick = () => {
                        button.closest(`.${rowClass}`)?.remove();
                        reindex();
                    };
                });
            };

            addButton?.addEventListener('click', () => {
                if (!wrapper || wrapper.querySelectorAll(`.${rowClass}`).length >= 12) return;
                wrapper.insertAdjacentHTML('beforeend', rowTemplate());
                bindRemoveButtons();
                reindex();
            });

            bindRemoveButtons();
        };

        configureDynamicFooterItems({
            wrapperId: 'footer-contacts-wrapper',
            addButtonId: 'add-footer-contact',
            rowClass: 'footer-contact-row',
            removeClass: 'remove-footer-contact',
            fieldName: 'footer_contacts',
            rowTemplate: () => `
                <div class="footer-contact-row grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-gray-50/70 p-4 md:grid-cols-12">
                    <div class="md:col-span-3"><label class="mb-1.5 block text-xs font-medium text-gray-600">Jenis kontak</label><select data-footer-field="type" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/30"><option value="phone">Telepon</option><option value="whatsapp">WhatsApp</option><option value="email">Email</option><option value="text">Teks lain</option></select></div>
                    <div class="md:col-span-3"><label class="mb-1.5 block text-xs font-medium text-gray-600">Label</label><input type="text" data-footer-field="label" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/30" placeholder="Admin pendaftaran"></div>
                    <div class="md:col-span-5"><label class="mb-1.5 block text-xs font-medium text-gray-600">Kontak</label><input type="text" data-footer-field="value" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/30" placeholder="Nomor, email, atau keterangan"></div>
                    <div class="flex items-end md:col-span-1"><button type="button" class="remove-footer-contact inline-flex h-11 w-full items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-red-50 hover:text-red-600" aria-label="Hapus kontak"><i class="ri-delete-bin-line"></i></button></div>
                </div>`,
        });

        configureDynamicFooterItems({
            wrapperId: 'footer-socials-wrapper',
            addButtonId: 'add-footer-social',
            rowClass: 'footer-social-row',
            removeClass: 'remove-footer-social',
            fieldName: 'footer_socials',
            rowTemplate: () => `
                <div class="footer-social-row grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-gray-50/70 p-4 md:grid-cols-12">
                    <div class="md:col-span-3"><label class="mb-1.5 block text-xs font-medium text-gray-600">Platform</label><select data-footer-field="platform" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/30"><option value="facebook">Facebook</option><option value="instagram">Instagram</option><option value="twitter">X/Twitter</option><option value="youtube">YouTube</option><option value="tiktok">TikTok</option><option value="linkedin">LinkedIn</option><option value="website">Website</option><option value="custom">Lainnya</option></select></div>
                    <div class="md:col-span-3"><label class="mb-1.5 block text-xs font-medium text-gray-600">Label</label><input type="text" data-footer-field="label" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/30" placeholder="Nama akun (opsional)"></div>
                    <div class="md:col-span-5"><label class="mb-1.5 block text-xs font-medium text-gray-600">Tautan profil</label><input type="url" data-footer-field="url" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/30" placeholder="https://..."></div>
                    <div class="flex items-end md:col-span-1"><button type="button" class="remove-footer-social inline-flex h-11 w-full items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-red-50 hover:text-red-600" aria-label="Hapus media sosial"><i class="ri-delete-bin-line"></i></button></div>
                </div>`,
        });

        bindFooterRemoveButtons();

        const settingsForm = document.getElementById('settings-form'); const passwordModal = document.getElementById('settings-password-modal');
        settingsForm?.addEventListener('submit', (event) => { if (settingsForm.dataset.confirmed) return; event.preventDefault(); passwordModal.classList.remove('hidden'); passwordModal.classList.add('flex'); });
        document.getElementById('settings-confirm-save')?.addEventListener('click', () => { const password = document.getElementById('settings-modal-password').value; if (!password) return; const input = document.createElement('input'); input.type = 'hidden'; input.name = 'admin_password'; input.value = password; settingsForm.append(input); settingsForm.dataset.confirmed = '1'; settingsForm.submit(); });
        document.querySelector('[data-close-settings-modal]')?.addEventListener('click', () => passwordModal.classList.add('hidden'));
        const smtpModal = document.getElementById('smtp-test-modal'); document.querySelector('[form="smtp-test-form"]')?.addEventListener('click', (event) => { if (event.currentTarget.tagName !== 'BUTTON') return; event.preventDefault(); smtpModal.classList.remove('hidden'); smtpModal.classList.add('flex'); });
        document.querySelector('[data-close-smtp-modal]')?.addEventListener('click', () => smtpModal.classList.add('hidden'));
    });
</script>
@endpush
