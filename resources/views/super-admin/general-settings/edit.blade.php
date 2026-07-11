@extends('super-admin.layouts.app')

@section('content')
@php
    $aiDiscussion = is_array($clientProfile?->ai_discussion_settings) ? $clientProfile->ai_discussion_settings : [];
    $aiProviders = is_array($aiDiscussion['providers'] ?? null) ? $aiDiscussion['providers'] : [];
@endphp
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">General Settings</h1>
        <p class="mt-1 text-sm text-gray-500">Atur halaman General mana yang tampil di public dan menu admin.</p>
    </div>

    <form method="POST" action="{{ route('super-admin.general-settings.update') }}" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        <div class="mb-6">
            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-gray-200 p-4 hover:border-primary/40">
                <span>
                    <span class="block text-base font-semibold text-gray-900">Asisten Admin</span>
                    <span class="mt-1 block text-sm text-gray-500">
                        Tampilkan chat asisten floating di pojok kanan bawah halaman admin. Default fitur ini mati.
                    </span>
                </span>
                <span class="flex shrink-0 items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="admin_assistant_enabled" value="1"
                        class="rounded border-gray-300 text-primary focus:ring-primary"
                        @checked(old('admin_assistant_enabled', (bool) ($clientProfile?->admin_assistant_enabled ?? false)))>
                    Tampilkan
                </span>
            </label>
        </div>

        <div class="mb-6 space-y-4 rounded-xl border border-gray-200 p-5">
            <label class="flex cursor-pointer items-start justify-between gap-4">
                <span>
                    <span class="block text-base font-semibold text-gray-900">Diskusi AI Pembahasan</span>
                    <span class="mt-1 block text-sm text-gray-500">
                        Aktifkan chat AI per soal di halaman pembahasan user. Konfigurasi ini dikelola oleh super admin dan cocok untuk koneksi satu arah melalui gateway.
                    </span>
                </span>
                <span class="flex shrink-0 items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="ai_discussion_feature_enabled" value="1"
                        class="rounded border-gray-300 text-primary focus:ring-primary"
                        @checked(old('ai_discussion_feature_enabled', (bool) ($clientProfile?->ai_discussion_feature_enabled ?? false)))>
                    Aktifkan
                </span>
            </label>

            <label class="flex cursor-pointer items-start justify-between gap-4 border-t border-gray-100 pt-4">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Izinkan admin mengatur Diskusi AI</span>
                    <span class="mt-1 block text-sm text-gray-500">Default mati. Saat nonaktif, bagian pengaturan AI pembahasan tidak tampil di halaman admin.</span>
                </span>
                <span class="flex shrink-0 items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="ai_discussion_admin_configurable" value="1"
                        class="rounded border-gray-300 text-primary focus:ring-primary"
                        @checked(old('ai_discussion_admin_configurable', (bool) ($clientProfile?->ai_discussion_admin_configurable ?? false)))>
                    Izinkan
                </span>
            </label>

            <div class="grid gap-4 border-t border-gray-100 pt-4 lg:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Model Chat</label>
                    <input type="text" name="ai_discussion_model" value="{{ old('ai_discussion_model', $aiDiscussion['model'] ?? 'gemini-2.5-flash') }}" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary" placeholder="gemini-2.5-flash">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Maks. Token Jawaban</label>
                    <input type="number" name="ai_discussion_max_output_tokens" min="200" max="2000" value="{{ old('ai_discussion_max_output_tokens', $aiDiscussion['max_output_tokens'] ?? 700) }}" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary">
                </div>
                <p class="self-end text-sm text-gray-500">Kosongkan API key untuk mempertahankan key yang sudah tersimpan.</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="space-y-3 rounded-lg bg-gray-50 p-4">
                    <p class="font-semibold text-gray-900">OpenAI</p>
                    <input type="password" name="ai_discussion_openai_api_key" autocomplete="new-password" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary" placeholder="{{ filled($aiProviders['openai']['api_key'] ?? null) ? 'API key sudah tersimpan' : 'sk-...' }}">
                    <input type="url" name="ai_discussion_openai_base_url" value="{{ old('ai_discussion_openai_base_url', $aiProviders['openai']['base_url'] ?? 'https://api.openai.com/v1') }}" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary">
                    <input type="number" name="ai_discussion_openai_timeout" min="5" max="300" value="{{ old('ai_discussion_openai_timeout', $aiProviders['openai']['timeout'] ?? 90) }}" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary" placeholder="Timeout (detik)">
                </div>
                <div class="space-y-3 rounded-lg bg-gray-50 p-4">
                    <p class="font-semibold text-gray-900">Gemini</p>
                    <input type="password" name="ai_discussion_gemini_api_key" autocomplete="new-password" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary" placeholder="{{ filled($aiProviders['gemini']['api_key'] ?? null) ? 'API key sudah tersimpan' : 'AIza...' }}">
                    <input type="url" name="ai_discussion_gemini_base_url" value="{{ old('ai_discussion_gemini_base_url', $aiProviders['gemini']['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta') }}" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary">
                    <input type="number" name="ai_discussion_gemini_timeout" min="5" max="300" value="{{ old('ai_discussion_gemini_timeout', $aiProviders['gemini']['timeout'] ?? 90) }}" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary" placeholder="Timeout (detik)">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Instruksi Tambahan Tutor AI</label>
                <textarea name="ai_discussion_instruction" rows="3" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary" placeholder="Contoh: berikan petunjuk bertahap, jangan langsung jawaban akhir.">{{ old('ai_discussion_instruction', $aiDiscussion['instruction'] ?? '') }}</textarea>
            </div>
        </div>

        <div class="mb-6 grid gap-4 md:grid-cols-2">
            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-gray-200 p-4 hover:border-primary/40">
                <span>
                    <span class="block text-base font-semibold text-gray-900">Menu Jadwal, Absensi & Rombel</span>
                    <span class="mt-1 block text-sm text-gray-500">
                        Tampilkan menu jadwal kelas, absensi, rombel, dan assign akses via rombel. Default fitur ini mati.
                    </span>
                </span>
                <span class="flex shrink-0 items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="class_schedule_menu_enabled" value="1"
                        class="rounded border-gray-300 text-primary focus:ring-primary"
                        @checked(old('class_schedule_menu_enabled', (bool) ($clientProfile?->class_schedule_menu_enabled ?? false)))>
                    Tampilkan
                </span>
            </label>

            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-gray-200 p-4 hover:border-primary/40">
                <span>
                    <span class="block text-base font-semibold text-gray-900">Menu Tagihan Rutin</span>
                    <span class="mt-1 block text-sm text-gray-500">
                        Tampilkan menu tagihan rutin di sidebar keuangan admin. Default fitur ini mati.
                    </span>
                </span>
                <span class="flex shrink-0 items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="recurring_bill_menu_enabled" value="1"
                        class="rounded border-gray-300 text-primary focus:ring-primary"
                        @checked(old('recurring_bill_menu_enabled', (bool) ($clientProfile?->recurring_bill_menu_enabled ?? false)))>
                    Tampilkan
                </span>
            </label>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            @foreach([
                'landing' => ['label' => 'Landing Page', 'description' => 'Jika off, route / diarahkan ke login.'],
                'artikel' => ['label' => 'Artikel', 'description' => 'Jika off, artikel tidak tampil di public dan menu admin.'],
                'statistik-ptn' => ['label' => 'Statistik PTN', 'description' => 'Jika off, halaman statistik dan endpoint datanya tidak aktif.'],
            ] as $pageKey => $item)
                <label class="flex min-h-32 cursor-pointer flex-col justify-between rounded-xl border border-gray-200 p-4 hover:border-primary/40">
                    <span>
                        <span class="block text-base font-semibold text-gray-900">{{ $item['label'] }}</span>
                        <span class="mt-1 block text-sm text-gray-500">{{ $item['description'] }}</span>
                    </span>
                    <span class="mt-4 flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="checkbox" name="public_visibility[{{ $pageKey }}]" value="1"
                            class="rounded border-gray-300 text-primary focus:ring-primary"
                            @checked(old("public_visibility.{$pageKey}", data_get($pages, "{$pageKey}.is_active", false)))>
                        Tampilkan
                    </span>
                </label>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary/90">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>
@endsection
