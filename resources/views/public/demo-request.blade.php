<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengajuan Demo - {{ $clientBranding['name'] ?? 'BimbelHub' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
</head>
<body class="min-h-screen bg-slate-50 text-slate-900" data-app-selects>
    <main class="flex min-h-screen items-center justify-center px-4 py-8 sm:px-6">
    <section class="w-full max-w-2xl">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-primary px-6 py-8 text-white sm:px-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-white/80">Akses Admin Demo</p>
                <h1 class="mt-2 text-2xl font-bold sm:text-3xl">Ajukan demo untuk bimbel Anda</h1>
                <p class="mt-3 max-w-xl text-sm leading-6 text-white/85">
                    Isi data berikut. Pengajuan akan ditinjau terlebih dahulu sebelum akses demo diberikan.
                </p>
            </div>

            <div class="p-6 sm:p-8">
                @if (session('success'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('demo-requests.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Nama <span class="text-red-600">*</span></label>
                        <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email <span class="text-red-600">*</span></label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>
                        <div>
                            <label for="phone" class="mb-2 block text-sm font-semibold text-slate-700">WhatsApp <span class="text-red-600">*</span></label>
                            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required inputmode="tel" autocomplete="tel"
                                placeholder="081234567890"
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        </div>
                    </div>
                    <div>
                        <label for="origin_institution" class="mb-2 block text-sm font-semibold text-slate-700">Asal Bimbel <span class="text-red-600">*</span></label>
                        <input id="origin_institution" name="origin_institution" value="{{ old('origin_institution') }}" required
                            placeholder="Contoh: Bimbel Cakrawala"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                    </div>
                    <div>
                        <label for="request_note" class="mb-2 block text-sm font-semibold text-slate-700">Keterangan (opsional)</label>
                        <textarea id="request_note" name="request_note" rows="5"
                            placeholder="Ceritakan kebutuhan demo Anda secara singkat..."
                            class="w-full resize-y rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">{{ old('request_note') }}</textarea>
                    </div>
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 font-semibold text-white transition hover:bg-primary/90">
                        <i class="ri-send-plane-line"></i>
                        Kirim Pengajuan Demo
                    </button>
                </form>
            </div>
        </div>
    </section>
    </main>

    @vite('resources/js/app.js')
</body>
</html>
