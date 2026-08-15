@extends('super-admin.layouts.app')

@section('content')
@php
    $aiDiscussion = is_array($clientProfile?->ai_discussion_settings) ? $clientProfile->ai_discussion_settings : [];
    $aiProviders = is_array($aiDiscussion['providers'] ?? null) ? $aiDiscussion['providers'] : [];
    $aiGatewayPayment = is_array($clientProfile?->ai_gateway_payment_settings) ? $clientProfile->ai_gateway_payment_settings : [];
    $aiGatewayTelegram = is_array($clientProfile?->ai_gateway_telegram_settings) ? $clientProfile->ai_gateway_telegram_settings : [];
    $openAiChatModels = collect($aiDiscussionModels)->where('provider', 'openai');
    $geminiChatModels = collect($aiDiscussionModels)->where('provider', 'gemini');
@endphp
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">General Settings</h1>
        <p class="mt-1 text-sm text-gray-500">Atur halaman General mana yang tampil di public dan menu admin.</p>
    </div>

    <form method="POST" action="{{ route('super-admin.general-settings.update') }}" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <input type="hidden" name="settings_tab" value="{{ $activeSettingsTab }}">

        <nav class="mb-6 flex gap-2 overflow-x-auto border-b border-gray-200 pb-3" aria-label="Kategori pengaturan">
            @foreach(['general' => 'Umum', 'ai' => 'Diskusi AI', 'pricing' => 'Tarif Model AI', 'payment' => 'Pembayaran AI', 'notification' => 'Notifikasi Telegram'] as $tab => $label)
                <a href="{{ route('super-admin.general-settings.edit', ['tab' => $tab]) }}"
                    @class([
                        'whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold',
                        'bg-primary text-white' => $activeSettingsTab === $tab,
                        'bg-gray-100 text-gray-600 hover:bg-gray-200' => $activeSettingsTab !== $tab,
                    ])>
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <div @class(['mb-6', 'hidden' => $activeSettingsTab !== 'general'])>
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

        <div @class(['mb-6 grid gap-4 md:grid-cols-2', 'hidden' => $activeSettingsTab !== 'general'])>
            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-gray-200 p-4 hover:border-primary/40">
                <span>
                    <span class="block text-base font-semibold text-gray-900">Booking Jadwal</span>
                    <span class="mt-1 block text-sm text-gray-500">
                        Tampilkan dan aktifkan booking jadwal untuk siswa, tutor, serta pengaturan booking di Admin. Default fitur ini mati.
                    </span>
                </span>
                <span class="flex shrink-0 items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="booking_schedule_enabled" value="1"
                        class="rounded border-gray-300 text-primary focus:ring-primary"
                        @checked(old('booking_schedule_enabled', (bool) ($clientProfile?->booking_schedule_enabled ?? false)))>
                    Tampilkan
                </span>
            </label>

            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-gray-200 p-4 hover:border-primary/40">
                <span>
                    <span class="block text-base font-semibold text-gray-900">Perkembangan Belajar</span>
                    <span class="mt-1 block text-sm text-gray-500">
                        Tampilkan perkembangan belajar untuk siswa dan form pencatatan perkembangan oleh tutor. Default fitur ini mati.
                    </span>
                </span>
                <span class="flex shrink-0 items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="learning_progress_enabled" value="1"
                        class="rounded border-gray-300 text-primary focus:ring-primary"
                        @checked(old('learning_progress_enabled', (bool) ($clientProfile?->learning_progress_enabled ?? false)))>
                    Tampilkan
                </span>
            </label>

            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-gray-200 p-4 hover:border-primary/40">
                <span>
                    <span class="block text-base font-semibold text-gray-900">Konten Tutor</span>
                    <span class="mt-1 block text-sm text-gray-500">
                        Aktifkan agar Admin dapat mengatur mode Gabung atau Isolasi Tutor untuk Tryout, Materi, dan Bank Soal. Default fitur ini mati.
                    </span>
                </span>
                <span class="flex shrink-0 items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="tutor_content_enabled" value="1"
                        class="rounded border-gray-300 text-primary focus:ring-primary"
                        @checked(old('tutor_content_enabled', (bool) ($clientProfile?->tutor_content_enabled ?? false)))>
                    Tampilkan
                </span>
            </label>
        </div>

        <div @class(['mb-6', 'hidden' => $activeSettingsTab !== 'general'])>
            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-gray-200 p-4 hover:border-primary/40">
                <span>
                    <span class="block text-base font-semibold text-gray-900">Kelas Belajar / Live Session</span>
                    <span class="mt-1 block text-sm text-gray-500">
                        Tampilkan menu di header dan sidebar user, serta tab Live pada halaman materi. Saat dimatikan, halaman Live Session juga tidak dapat diakses user.
                    </span>
                </span>
                <span class="flex shrink-0 items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="live_session_enabled" value="1"
                        class="rounded border-gray-300 text-primary focus:ring-primary"
                        @checked(old('live_session_enabled', (bool) ($clientProfile?->live_session_enabled ?? true)))>
                    Tampilkan
                </span>
            </label>
        </div>

        <div @class(['mb-6', 'hidden' => $activeSettingsTab !== 'general'])>
            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-gray-200 p-4 hover:border-primary/40">
                <span>
                    <span class="block text-base font-semibold text-gray-900">Manajemen Sertifikat</span>
                    <span class="mt-1 block text-sm text-gray-500">
                        Izinkan sertifikat dibuat dan diunduh oleh peserta pada tryout yang mengaktifkan Generate Sertifikat Otomatis.
                    </span>
                </span>
                <span class="flex shrink-0 items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="enable_certificate_management" value="1"
                        class="rounded border-gray-300 text-primary focus:ring-primary"
                        @checked(old('enable_certificate_management', (bool) ($clientProfile?->enable_certificate_management ?? false)))>
                    Aktifkan
                </span>
            </label>
        </div>

        <section @class(['mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white', 'hidden' => $activeSettingsTab !== 'ai'])>
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Diskusi AI Pembahasan</h2>
                <p class="mt-1 text-sm text-gray-500">Kelola layanan AI pusat tanpa membuka kredensial atau pengaturan ini ke admin.</p>
            </div>

            <div class="space-y-5 p-5">
            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-gray-200 p-4 transition hover:border-primary/40">
                <span>
                    <span class="block text-base font-semibold text-gray-900">Aktifkan Diskusi AI</span>
                    <span class="mt-1 block text-sm text-gray-500">
                        Tampilkan chat AI per soal di halaman pembahasan user.
                    </span>
                </span>
                <span class="flex shrink-0 items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="ai_discussion_feature_enabled" value="1"
                        class="rounded border-gray-300 text-primary focus:ring-primary"
                        @checked(old('ai_discussion_feature_enabled', (bool) ($clientProfile?->ai_discussion_feature_enabled ?? false)))>
                    Aktifkan
                </span>
            </label>

            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-gray-200 p-4 transition hover:border-primary/40">
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

            <div class="grid gap-4 lg:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Model Chat</label>
                    <select name="ai_discussion_model" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10">
                        @if($openAiChatModels->isNotEmpty())
                            <optgroup label="OpenAI">
                                @foreach($openAiChatModels as $model)
                                    <option value="{{ $model['id'] }}" @disabled(!($model['has_pricing'] ?? false)) @selected(old('ai_discussion_model', $aiDiscussion['model'] ?? 'gemini-3.1-flash-lite') === $model['id'])>
                                        OpenAI — {{ $model['id'] }}{{ ($model['has_pricing'] ?? false) ? '' : ' (atur tarif dulu)' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if($geminiChatModels->isNotEmpty())
                            <optgroup label="Google Gemini">
                                @foreach($geminiChatModels as $model)
                                    <option value="{{ $model['id'] }}" @disabled(!($model['has_pricing'] ?? false)) @selected(old('ai_discussion_model', $aiDiscussion['model'] ?? 'gemini-3.1-flash-lite') === $model['id'])>
                                        Gemini — {{ $model['id'] }}{{ ($model['has_pricing'] ?? false) ? '' : ' (atur tarif dulu)' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if($openAiChatModels->isEmpty() && $geminiChatModels->isEmpty())
                            <option value="" disabled selected>Belum ada model dari API provider.</option>
                        @endif
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Semua model teks yang tersedia untuk API key OpenAI dan Gemini ditampilkan. Model tanpa tarif tetap terlihat, tetapi baru dapat dipilih setelah tarif input dan output diisi pada tab Tarif Model AI.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Token default</label>
                    <input type="number" name="ai_discussion_max_output_tokens" min="200" max="2000" value="{{ old('ai_discussion_max_output_tokens', $aiDiscussion['max_output_tokens'] ?? 700) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10">
                    <p class="mt-1 text-xs text-gray-500">Dipakai sebagai fallback untuk fitur baru. Limit tiap fitur diatur terpisah di bawah.</p>
                </div>
                <div class="self-end rounded-xl border border-blue-100 bg-blue-50 px-3.5 py-2.5 text-sm text-blue-700">Kosongkan API key untuk mempertahankan key yang sudah tersimpan.</div>
            </div>

            @php
                $featureTokenLimits = is_array($aiDiscussion['feature_token_limits'] ?? null) ? $aiDiscussion['feature_token_limits'] : [];
                $featureTokenFields = [
                    'discussion' => ['Tanya jawab', 'Jawaban percakapan per soal', 700, 'ri-message-3-line'],
                    'learning_note' => ['Catatan', 'Materi lengkap dan poin penting', 1200, 'ri-sticky-note-line'],
                    'learning_flashcard' => ['Flashcard', '3–5 kartu konsep', 500, 'ri-stack-line'],
                    'learning_question' => ['Latihan mirip', 'Hingga tiga soal baru beserta pembahasannya', 1800, 'ri-file-list-3-line'],
                ];
            @endphp
            <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-4">
                <div class="mb-3"><p class="font-semibold text-gray-900">Batas output per fitur</p><p class="mt-1 text-xs text-gray-600">Batas ini diterapkan oleh server gateway, bukan oleh browser. Nilai lebih kecil membuat respons lebih ringkas dan hemat kuota paket.</p></div>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach($featureTokenFields as $feature => [$label, $description, $default, $icon])
                        <label class="block rounded-lg border border-white bg-white p-3 shadow-sm">
                            <span class="flex items-center gap-2 text-sm font-semibold text-gray-800"><i class="{{ $icon }} text-primary"></i>{{ $label }}</span>
                            <span class="mt-1 block text-xs text-gray-500">{{ $description }}</span>
                            <span class="mt-3 flex items-center gap-2"><input type="number" name="ai_discussion_feature_token_limits[{{ $feature }}]" min="64" max="2000" value="{{ old('ai_discussion_feature_token_limits.' . $feature, $featureTokenLimits[$feature] ?? $default) }}" class="w-full rounded-lg border-gray-200 text-sm focus:border-primary focus:ring-primary"><span class="text-xs text-gray-500">token</span></span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-4">
                    <div><p class="font-semibold text-gray-900">OpenAI</p><p class="mt-1 text-xs text-gray-500">API untuk model GPT dan kompatibel OpenAI.</p></div>
                    <label class="block"><span class="text-sm font-medium text-gray-700">API Key</span><input type="password" name="ai_discussion_openai_api_key" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10" placeholder="{{ filled($aiProviders['openai']['api_key'] ?? null) ? 'API key sudah tersimpan' : 'sk-...' }}"></label>
                    <label class="block"><span class="text-sm font-medium text-gray-700">Base URL</span><input type="url" name="ai_discussion_openai_base_url" value="{{ old('ai_discussion_openai_base_url', $aiProviders['openai']['base_url'] ?? 'https://api.openai.com/v1') }}" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10"></label>
                    <label class="block"><span class="text-sm font-medium text-gray-700">Timeout (detik)</span><input type="number" name="ai_discussion_openai_timeout" min="5" max="300" value="{{ old('ai_discussion_openai_timeout', $aiProviders['openai']['timeout'] ?? 90) }}" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10"></label>
                </div>
                <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-4">
                    <div><p class="font-semibold text-gray-900">Gemini</p><p class="mt-1 text-xs text-gray-500">API untuk model Gemini Google.</p></div>
                    <label class="block"><span class="text-sm font-medium text-gray-700">API Key</span><input type="password" name="ai_discussion_gemini_api_key" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10" placeholder="{{ filled($aiProviders['gemini']['api_key'] ?? null) ? 'API key sudah tersimpan' : 'AIza...' }}"></label>
                    <label class="block"><span class="text-sm font-medium text-gray-700">Base URL</span><input type="url" name="ai_discussion_gemini_base_url" value="{{ old('ai_discussion_gemini_base_url', $aiProviders['gemini']['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta') }}" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10"></label>
                    <label class="block"><span class="text-sm font-medium text-gray-700">Timeout (detik)</span><input type="number" name="ai_discussion_gemini_timeout" min="5" max="300" value="{{ old('ai_discussion_gemini_timeout', $aiProviders['gemini']['timeout'] ?? 90) }}" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10"></label>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Instruksi Tambahan Tutor AI</label>
                <textarea name="ai_discussion_instruction" rows="3" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary/10" placeholder="Contoh: berikan petunjuk bertahap, jangan langsung jawaban akhir.">{{ old('ai_discussion_instruction', $aiDiscussion['instruction'] ?? '') }}</textarea>
            </div>
            </div>
        </section>

        <section @class(['mb-6 overflow-hidden rounded-xl border border-sky-200 bg-white', 'hidden' => $activeSettingsTab !== 'notification'])>
            <div class="border-b border-sky-100 bg-sky-50/60 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Notifikasi Pembelian AI Learning ke Telegram</h2>
                <p class="mt-1 text-sm text-gray-600">Kirim notifikasi setelah paket AI benar-benar aktif. Invoice pending tidak akan memicu pesan.</p>
            </div>
            <div class="space-y-5 p-5">
                <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-gray-200 p-4 transition hover:border-sky-300">
                    <span><span class="block font-semibold text-gray-900">Aktifkan notifikasi Telegram</span><span class="mt-1 block text-sm text-gray-500">Bot Token dan Chat ID disimpan terenkripsi.</span></span>
                    <span class="flex shrink-0 items-center gap-2 text-sm font-medium text-gray-700"><input type="checkbox" name="ai_gateway_telegram_enabled" value="1" class="rounded border-gray-300 text-primary focus:ring-primary" @checked(old('ai_gateway_telegram_enabled', $aiGatewayTelegram['enabled'] ?? false))> Aktifkan</span>
                </label>

                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    <p class="font-semibold">Cara setup singkat</p>
                    <ol class="mt-2 list-decimal space-y-1 pl-5 text-xs leading-5">
                        <li>Buat bot melalui <strong>@BotFather</strong>, lalu salin Bot Token.</li>
                        <li>Tambahkan bot ke grup/channel tujuan atau kirim <code>/start</code> jika memakai chat pribadi.</li>
                        <li>Isi Chat ID. Untuk channel publik juga bisa memakai format <code>@nama_channel</code>.</li>
                        <li>Simpan pengaturan, kemudian tekan tombol <strong>Kirim Tes</strong>.</li>
                    </ol>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <label class="block"><span class="text-sm font-medium text-gray-700">Bot Token</span><input type="password" name="ai_gateway_telegram_bot_token" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:border-primary focus:bg-white focus:ring-primary" placeholder="{{ filled($aiGatewayTelegram['bot_token'] ?? null) ? 'Token sudah tersimpan — kosongkan jika tidak diganti' : '123456789:AA...' }}"></label>
                    <label class="block"><span class="text-sm font-medium text-gray-700">Chat ID / Channel</span><input type="text" name="ai_gateway_telegram_chat_id" value="{{ old('ai_gateway_telegram_chat_id', $aiGatewayTelegram['chat_id'] ?? '') }}" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:border-primary focus:bg-white focus:ring-primary" placeholder="-1001234567890 atau @nama_channel"></label>
                    <label class="block"><span class="text-sm font-medium text-gray-700">Topic ID (opsional)</span><input type="number" min="1" name="ai_gateway_telegram_message_thread_id" value="{{ old('ai_gateway_telegram_message_thread_id', $aiGatewayTelegram['message_thread_id'] ?? '') }}" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:border-primary focus:bg-white focus:ring-primary" placeholder="Isi jika grup memakai Topics"><span class="mt-1 block text-xs text-gray-500">Biarkan kosong untuk chat, grup, atau channel biasa.</span></label>
                    <div class="rounded-xl border border-gray-200 p-4"><p class="text-sm font-semibold text-gray-900">Jenis transaksi</p><div class="mt-3 space-y-2"><label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="ai_gateway_telegram_notify_free" value="1" class="rounded border-gray-300 text-primary focus:ring-primary" @checked(old('ai_gateway_telegram_notify_free', $aiGatewayTelegram['notify_free'] ?? true))> Klaim paket gratis</label><label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="ai_gateway_telegram_notify_paid" value="1" class="rounded border-gray-300 text-primary focus:ring-primary" @checked(old('ai_gateway_telegram_notify_paid', $aiGatewayTelegram['notify_paid'] ?? true))> Pembayaran paket berbayar berhasil</label></div></div>
                </div>

                <div class="flex flex-col gap-2 rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-amber-800">Simpan pengaturan terlebih dahulu sebelum mengirim pesan tes.</p>
                    <button type="submit" form="ai-gateway-telegram-test-form" class="inline-flex items-center justify-center gap-2 rounded-lg border border-sky-300 bg-white px-4 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-50"><i class="ri-telegram-2-line"></i>Kirim Tes</button>
                </div>
            </div>
        </section>

        <section @class(['mb-6 overflow-hidden rounded-xl border border-sky-100 bg-sky-50/40', 'hidden' => $activeSettingsTab !== 'pricing'])>
            <div class="border-b border-sky-100 px-5 py-4">
                <h2 class="font-semibold text-sky-950">Tarif Model AI</h2>
                <p class="mt-1 text-sm text-sky-800">Daftar model OpenAI dan Gemini diambil dari API provider. Tarif aktif disimpan di database, bukan di ENV. Isi tarif per 1 juta token untuk mengaktifkan model; setiap request akan menyimpan snapshot tarif agar riwayat laba tidak berubah.</p>
            </div>
            <div class="overflow-x-auto bg-white">
                <table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Provider</th><th class="px-4 py-3">Model</th><th class="px-4 py-3">Input / 1 jt token (US$)</th><th class="px-4 py-3">Output / 1 jt token (US$)</th><th class="px-4 py-3">Kurs USD → IDR</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($availableModels as $index => $model)@php($pricing = $aiModelPricings->get($model['provider'] . ':' . $model['id']))<tr><td class="px-4 py-3"><span class="rounded bg-sky-50 px-2 py-1 text-xs font-medium text-sky-700">{{ strtoupper($model['provider']) }}</span><input type="hidden" name="ai_model_pricings[{{ $index }}][provider]" value="{{ $model['provider'] }}"></td><td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900"><span class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700">{{ $model['id'] }}</span><input type="hidden" name="ai_model_pricings[{{ $index }}][model]" value="{{ $model['id'] }}"></td><td class="px-4 py-3"><input type="number" name="ai_model_pricings[{{ $index }}][input_per_million_usd]" min="0" max="10000" step="0.000001" value="{{ old("ai_model_pricings.$index.input_per_million_usd", $pricing?->input_per_million_usd) }}" placeholder="Belum diatur" class="w-40 rounded-lg border-gray-200 text-sm"></td><td class="px-4 py-3"><input type="number" name="ai_model_pricings[{{ $index }}][output_per_million_usd]" min="0" max="10000" step="0.000001" value="{{ old("ai_model_pricings.$index.output_per_million_usd", $pricing?->output_per_million_usd) }}" placeholder="Belum diatur" class="w-40 rounded-lg border-gray-200 text-sm"></td><td class="px-4 py-3"><input type="number" name="ai_model_pricings[{{ $index }}][usd_to_idr]" min="1" max="1000000" step="0.0001" value="{{ old("ai_model_pricings.$index.usd_to_idr", $pricing?->usd_to_idr ?? 16000) }}" class="w-40 rounded-lg border-gray-200 text-sm"></td></tr>@empty<tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Simpan API key OpenAI atau Gemini lalu muat ulang halaman untuk mengambil daftar model.</td></tr>@endforelse</tbody></table>
            </div>
        </section>

        <section @class(['mb-6 overflow-hidden rounded-xl border border-indigo-200 bg-white', 'hidden' => $activeSettingsTab !== 'payment'])>
            <div class="border-b border-indigo-100 bg-indigo-50/60 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Pembayaran AI Gateway</h2>
                <p class="mt-1 text-sm text-gray-600">Khusus untuk pembelian paket AI dari project yang terhubung ke gateway pusat. Konfigurasi ini terpisah dari payment gateway di halaman Admin.</p>
            </div>
            <div class="space-y-5 p-5">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block"><span class="text-sm font-medium text-gray-700">Gateway aktif</span><select name="ai_gateway_payment_gateway" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm"><option value="xendit" @selected(old('ai_gateway_payment_gateway', $aiGatewayPayment['gateway'] ?? 'xendit') === 'xendit')>Xendit</option><option value="midtrans" @selected(old('ai_gateway_payment_gateway', $aiGatewayPayment['gateway'] ?? '') === 'midtrans')>Midtrans</option><option value="ipaymu" @selected(old('ai_gateway_payment_gateway', $aiGatewayPayment['gateway'] ?? '') === 'ipaymu')>iPaymu</option><option value="interactive_qris" @selected(old('ai_gateway_payment_gateway', $aiGatewayPayment['gateway'] ?? '') === 'interactive_qris')>InterActive QRIS</option></select></label>
                    <label class="block"><span class="text-sm font-medium text-gray-700">Mode</span><select name="ai_gateway_payment_gateway_mode" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm"><option value="sandbox" @selected(old('ai_gateway_payment_gateway_mode', $aiGatewayPayment['mode'] ?? 'sandbox') === 'sandbox')>Sandbox</option><option value="production" @selected(old('ai_gateway_payment_gateway_mode', $aiGatewayPayment['mode'] ?? '') === 'production')>Production</option></select></label>
                </div>
                <div class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                    <p>Kosongkan credential yang sudah tersimpan bila tidak ingin menggantinya. Untuk mode production, arahkan webhook provider ke endpoint AI Gateway pusat.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-4">
                        <li>Xendit: <code>{{ route('webhook.ai-gateway.xendit') }}</code></li>
                        <li>Midtrans: <code>{{ route('webhook.ai-gateway.midtrans') }}</code></li>
                        <li>iPaymu: <code>{{ route('webhook.ai-gateway.ipaymu') }}</code></li>
                    </ul>
                </div>
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="space-y-3 rounded-xl border border-gray-200 p-4"><p class="font-semibold text-gray-900">Xendit</p><label class="block text-sm text-gray-700">Secret key<input type="password" name="ai_gateway_xendit_secret_key" autocomplete="new-password" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="{{ filled($aiGatewayPayment['xendit_secret_key'] ?? null) ? 'Sudah tersimpan' : 'xnd_...' }}"></label><label class="block text-sm text-gray-700">Webhook token<input type="password" name="ai_gateway_xendit_webhook_token" autocomplete="new-password" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="{{ filled($aiGatewayPayment['xendit_webhook_token'] ?? null) ? 'Sudah tersimpan' : 'Token callback' }}"></label></div>
                    <div class="space-y-3 rounded-xl border border-gray-200 p-4"><p class="font-semibold text-gray-900">Midtrans</p><label class="block text-sm text-gray-700">Server key<input type="password" name="ai_gateway_midtrans_server_key" autocomplete="new-password" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="{{ filled($aiGatewayPayment['midtrans_server_key'] ?? null) ? 'Sudah tersimpan' : 'SB-Mid-server-...' }}"></label><label class="block text-sm text-gray-700">Client key<input type="password" name="ai_gateway_midtrans_client_key" autocomplete="new-password" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="{{ filled($aiGatewayPayment['midtrans_client_key'] ?? null) ? 'Sudah tersimpan' : 'SB-Mid-client-...' }}"></label></div>
                    <div class="space-y-3 rounded-xl border border-gray-200 p-4"><p class="font-semibold text-gray-900">InterActive QRIS</p><label class="block text-sm text-gray-700">API key<input type="password" name="ai_gateway_interactive_qris_api_key" autocomplete="new-password" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="{{ filled($aiGatewayPayment['interactive_qris_api_key'] ?? null) ? 'Sudah tersimpan' : 'API key' }}"></label><label class="block text-sm text-gray-700">mID<input name="ai_gateway_interactive_qris_mid" value="{{ old('ai_gateway_interactive_qris_mid', $aiGatewayPayment['interactive_qris_mid'] ?? '') }}" class="mt-1 w-full rounded-lg border-gray-200 text-sm"></label><label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="ai_gateway_interactive_qris_use_tip" value="1" @checked(old('ai_gateway_interactive_qris_use_tip', $aiGatewayPayment['interactive_qris_use_tip'] ?? false))> Aktifkan tip</label></div>
                    <div class="space-y-3 rounded-xl border border-gray-200 p-4"><p class="font-semibold text-gray-900">iPaymu</p><label class="block text-sm text-gray-700">API key<input type="password" name="ai_gateway_ipaymu_api_key" autocomplete="new-password" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="{{ filled($aiGatewayPayment['ipaymu_api_key'] ?? null) ? 'Sudah tersimpan' : 'API key' }}"></label><label class="block text-sm text-gray-700">VA<input name="ai_gateway_ipaymu_va" value="{{ old('ai_gateway_ipaymu_va', $aiGatewayPayment['ipaymu_va'] ?? '') }}" class="mt-1 w-full rounded-lg border-gray-200 text-sm"></label></div>
                </div>
            </div>
        </section>

        <div @class(['mb-6 grid gap-4 md:grid-cols-2', 'hidden' => $activeSettingsTab !== 'general'])>
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

            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-gray-200 p-4 hover:border-primary/40">
                <span>
                    <span class="block text-base font-semibold text-gray-900">Chat Tutor–Siswa</span>
                    <span class="mt-1 block text-sm text-gray-500">
                        Tampilkan chat antara tutor dan siswa. Saat nonaktif, menu, tombol, route, API, dan channel realtime chat tidak dapat diakses. Fitur <strong>Diskusi</strong> juga harus aktif pada Plan yang digunakan. Default fitur ini mati.
                    </span>
                </span>
                <span class="flex shrink-0 items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="tutor_chat_enabled" value="1"
                        class="rounded border-gray-300 text-primary focus:ring-primary"
                        @checked(old('tutor_chat_enabled', (bool) ($clientProfile?->tutor_chat_enabled ?? false)))>
                    Tampilkan
                </span>
            </label>
        </div>

        <div @class(['grid gap-4 md:grid-cols-3', 'hidden' => $activeSettingsTab !== 'general'])>
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
    <form id="ai-gateway-telegram-test-form" method="POST" action="{{ route('super-admin.general-settings.telegram.test') }}" class="hidden">@csrf</form>
</div>
@endsection
