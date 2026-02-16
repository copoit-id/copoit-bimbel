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
    $activeSettingsTab = old('settings_tab', 'identity');
    if ($errors->isNotEmpty() && !old('settings_tab')) {
    if (collect($settingErrorKeys)->intersect(['logo', 'favicon'])->isNotEmpty()) {
    $activeSettingsTab = 'visual';
    } elseif (collect($settingErrorKeys)->intersect(['header_primary_color', 'sidebar_primary_color'])->isNotEmpty()) {
    $activeSettingsTab = 'ui';
    } elseif (collect($settingErrorKeys)->intersect([
    'payment_mode',
    'payment_bank_name',
    'payment_account_number',
    'payment_account_holder',
    'payment_bank_note'
    ])->isNotEmpty()) {
    $activeSettingsTab = 'payment';
    } elseif (collect($settingErrorKeys)->intersect([
    'smtp_email',
    'smtp_app_password',
    'smtp_notification_email'
    ])->isNotEmpty()) {
    $activeSettingsTab = 'smtp';
    }
    }
    @endphp

    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="settings_tab" id="settings_tab" value="{{ $activeSettingsTab }}">

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
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex gap-3 border border-gray-200 rounded-2xl p-4 hover:border-primary/60 transition cursor-pointer">
                    <input type="radio" name="payment_mode" value="gateway" class="mt-1 h-5 w-5 text-primary focus:ring-primary"
                        {{ $paymentMode === 'gateway' ? 'checked' : '' }}>
                    <div>
                        <p class="font-semibold text-gray-900">Otomatis (Payment Gateway)</p>
                        <p class="text-xs text-gray-500">Pembayaran langsung diarahkan ke Xendit/Midtrans.</p>
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
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
                <div>
                    <label class="text-sm font-medium text-gray-900 mb-1 inline-block">Catatan (Opsional)</label>
                    <input type="text" name="payment_bank_note"
                        value="{{ old('payment_bank_note', $profile->payment_bank_note ?? ($branding['payment_bank_note'] ?? '')) }}"
                        class="w-full rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/30 focus:border-primary px-4 py-2.5"
                        placeholder="Contoh: Kirim bukti max 1x24 jam">
                    @error('payment_bank_note')
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
    });
</script>
@endpush
