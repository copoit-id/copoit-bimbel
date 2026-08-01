@extends('general.layout')

@section('title', 'AI Learning Tools')

@push('styles')
    <style>
        @keyframes ai-promo-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .ai-promo-float { animation: ai-promo-float 5s ease-in-out infinite; }
        .ai-promo-float-delay { animation: ai-promo-float 6.5s ease-in-out 1s infinite; }

        @media (prefers-reduced-motion: reduce) {
            .ai-promo-float, .ai-promo-float-delay { animation: none; }
        }
    </style>
@endpush

@section('content')
@php
    $tryAiHref = route('user.ai-learning.index', ['tool' => 'note']);
    $featureCards = [
        ['ri-sticky-note-line', 'Catatan cerdas', 'Rangkum konsep, rumus, dan langkah penyelesaian dalam struktur yang lebih mudah dipelajari.', 'from-blue-500 to-indigo-600'],
        ['ri-stack-line', 'Flashcard aktif', 'Ubah materi panjang menjadi kartu tanya-jawab agar latihan recall lebih konsisten.', 'from-violet-500 to-fuchsia-600'],
        ['ri-file-add-line', 'Soal serupa', 'Buat latihan baru dari topik yang sedang kamu pelajari, sesuai tingkat kesulitan pilihanmu.', 'from-amber-400 to-orange-500'],
        ['ri-compass-3-line', 'Rekomendasi belajar', 'Temukan fokus dan langkah berikutnya supaya waktu belajarmu lebih terarah.', 'from-emerald-400 to-teal-600'],
    ];
@endphp

<section class="relative isolate overflow-hidden bg-slate-950 pb-20 pt-16 text-white sm:pb-28 sm:pt-24">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_15%_20%,rgba(79,70,229,.5),transparent_27%),radial-gradient(circle_at_85%_15%,rgba(16,185,129,.26),transparent_22%),linear-gradient(135deg,#0f172a_0%,#172554_50%,#0f172a_100%)]"></div>
    <div class="absolute -left-28 top-24 -z-10 h-80 w-80 rounded-full bg-indigo-500/20 blur-3xl"></div>
    <div class="absolute -right-20 bottom-0 -z-10 h-96 w-96 rounded-full bg-cyan-400/10 blur-3xl"></div>

    <div class="mx-auto grid max-w-7xl items-center gap-14 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
        <div class="max-w-2xl">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3.5 py-2 text-xs font-bold text-indigo-100 backdrop-blur">
                <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-300 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-300"></span></span>
                AI Learning Tools · Teman belajarmu setiap hari
            </div>
            <h1 class="mt-6 text-4xl font-black leading-[1.08] tracking-tight sm:text-5xl lg:text-6xl">Saat materi terasa rumit, <span class="bg-gradient-to-r from-cyan-200 via-indigo-200 to-violet-200 bg-clip-text text-transparent">ubah cara belajarnya.</span></h1>
            <p class="mt-6 max-w-xl text-base font-medium leading-8 text-slate-300 sm:text-lg">AI Learning Tools membantu kamu mengolah soal dan materi sendiri menjadi bahan belajar yang lebih aktif: pahami, ingat, latih, lalu tentukan langkah berikutnya.</p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ $tryAiHref }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-6 py-4 text-base font-extrabold text-slate-900 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:bg-cyan-50">
                    <i class="ri-sparkling-2-line text-xl text-indigo-600"></i>
                    Coba AI Learning Tools
                    <i class="ri-arrow-right-line"></i>
                </a>
                <a href="#cara-kerja" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/20 bg-white/5 px-6 py-4 text-base font-bold text-white transition hover:bg-white/10">
                    Lihat cara kerjanya <i class="ri-arrow-down-line"></i>
                </a>
            </div>
            <p class="mt-4 flex items-center gap-2 text-xs font-medium text-slate-400"><i class="ri-login-circle-line text-base text-cyan-300"></i> Belum punya akun? Kamu akan diminta login terlebih dahulu, lalu langsung masuk ke AI Learning Tools.</p>
        </div>

        <div class="relative mx-auto w-full max-w-xl lg:max-w-none">
            <div class="ai-promo-float absolute -left-3 top-16 hidden rounded-2xl border border-white/15 bg-slate-900/90 p-3 shadow-2xl backdrop-blur sm:block">
                <div class="flex items-center gap-2"><span class="grid h-8 w-8 place-items-center rounded-xl bg-amber-400/15 text-amber-300"><i class="ri-lightbulb-flash-line"></i></span><div><p class="text-[10px] font-bold text-slate-400">Fokus hari ini</p><p class="text-xs font-extrabold">Latihan soal serupa</p></div></div>
            </div>
            <div class="ai-promo-float-delay absolute -right-2 bottom-9 hidden rounded-2xl border border-white/15 bg-slate-900/90 p-3 shadow-2xl backdrop-blur sm:block">
                <p class="text-[10px] font-bold text-slate-400">Materi diolah</p><p class="mt-1 text-sm font-black text-emerald-300">4 format belajar <i class="ri-checkbox-circle-fill"></i></p>
            </div>

            <div class="overflow-hidden rounded-[2rem] border border-white/15 bg-white/95 p-3 shadow-2xl shadow-indigo-950/50 sm:p-5">
                <div class="rounded-[1.4rem] bg-slate-50 p-4 text-slate-900 sm:p-5">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                        <div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-600 text-xl text-white shadow-lg shadow-indigo-200"><i class="ri-sparkling-2-line"></i></span><div><p class="text-sm font-extrabold">AI Learning Tools</p><p class="text-[10px] font-medium text-slate-400">Ruang belajarmu yang personal</p></div></div>
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-extrabold text-emerald-700">Siap belajar</span>
                    </div>
                    <div class="mt-5 grid grid-cols-[auto_1fr] gap-3">
                        <div class="flex flex-col items-center"><span class="grid h-9 w-9 place-items-center rounded-full bg-indigo-600 text-sm font-black text-white">1</span><span class="my-1 h-12 w-px bg-indigo-200"></span><span class="grid h-9 w-9 place-items-center rounded-full bg-slate-200 text-sm font-black text-slate-500">2</span></div>
                        <div class="space-y-4"><div><p class="text-[10px] font-bold uppercase tracking-wide text-indigo-600">Masukkan soal atau materi</p><div class="mt-2 rounded-xl border border-indigo-100 bg-white p-3 text-xs font-semibold leading-5 text-slate-600">"Jelaskan cara menyelesaikan persamaan linear satu variabel dengan cara yang mudah dipahami."</div></div><div><p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Pilih hasil yang kamu butuhkan</p><div class="mt-2 flex flex-wrap gap-2"><span class="rounded-lg bg-blue-50 px-2.5 py-1.5 text-[10px] font-bold text-blue-700"><i class="ri-sticky-note-line mr-1"></i>Catatan</span><span class="rounded-lg bg-violet-50 px-2.5 py-1.5 text-[10px] font-bold text-violet-700"><i class="ri-stack-line mr-1"></i>Flashcard</span><span class="rounded-lg bg-amber-50 px-2.5 py-1.5 text-[10px] font-bold text-amber-700"><i class="ri-file-add-line mr-1"></i>Soal</span></div></div></div>
                    </div>
                    <div class="mt-5 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 p-4 text-white"><div class="flex items-center justify-between"><div><p class="text-[10px] font-bold uppercase tracking-wide text-indigo-100">Hasilmu siap</p><p class="mt-1 text-sm font-extrabold">Belajar jadi lebih terstruktur</p></div><i class="ri-arrow-right-up-line text-2xl"></i></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-white py-20 sm:py-28">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center"><span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-extrabold uppercase tracking-wider text-indigo-700"><i class="ri-magic-line"></i> Satu materi, banyak kemungkinan</span><h2 class="mt-5 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">Pilih cara belajar yang paling kamu butuhkan saat ini.</h2><p class="mt-5 text-base font-medium leading-7 text-slate-500">Tidak perlu mulai dari nol. Tempel materi atau soal yang kamu punya, lalu jadikan bahan belajar yang benar-benar bisa kamu gunakan.</p></div>
        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($featureCards as $feature)
                <article class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-indigo-200 hover:shadow-xl hover:shadow-indigo-100/60">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br {{ $feature[3] }} text-2xl text-white shadow-lg"><i class="{{ $feature[0] }}"></i></span>
                    <h3 class="mt-6 text-lg font-extrabold text-slate-900">{{ $feature[1] }}</h3>
                    <p class="mt-3 text-sm font-medium leading-6 text-slate-500">{{ $feature[2] }}</p>
                    <a href="{{ $tryAiHref }}" class="mt-5 inline-flex items-center gap-1 text-sm font-bold text-indigo-600 transition group-hover:gap-2">Coba fitur ini <i class="ri-arrow-right-line"></i></a>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section id="cara-kerja" class="relative overflow-hidden border-y border-slate-200 bg-slate-50 py-20 sm:py-28">
    <div class="absolute right-0 top-0 h-72 w-72 rounded-full bg-indigo-100/70 blur-3xl"></div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid items-start gap-12 lg:grid-cols-[.8fr_1.2fr]"><div><span class="text-xs font-extrabold uppercase tracking-[.16em] text-indigo-600">Alur sederhana</span><h2 class="mt-4 text-3xl font-black leading-tight tracking-tight text-slate-900 sm:text-4xl">Dari bingung jadi punya pegangan, dalam tiga langkah.</h2><p class="mt-5 max-w-md text-base font-medium leading-7 text-slate-500">Gunakan AI sebagai teman untuk mengolah materi—kamu tetap memegang kendali atas apa yang ingin dipahami dan dilatih.</p><a href="{{ $tryAiHref }}" class="mt-7 inline-flex items-center gap-2 rounded-2xl bg-primary px-6 py-3.5 text-sm font-extrabold text-white shadow-lg shadow-primary/20 transition hover:-translate-y-0.5 hover:bg-primary-hover">Mulai dari materi kamu <i class="ri-arrow-right-line text-lg"></i></a></div>
            <div class="space-y-4">
                @foreach([
                    ['01', 'Masukkan materi atau soal', 'Tempel teks, rumus, konsep, atau soal yang ingin kamu pahami.', 'ri-file-text-line'],
                    ['02', 'Pilih alat yang sesuai', 'Pilih catatan, flashcard, soal serupa, atau rekomendasi belajar.', 'ri-cursor-line'],
                    ['03', 'Belajar dan lanjutkan progresmu', 'Gunakan hasilnya untuk memahami konsep, menguji ingatan, dan menentukan latihan berikutnya.', 'ri-rocket-2-line'],
                ] as $step)
                    <div class="flex gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:items-center sm:p-6"><span class="text-xl font-black text-indigo-200">{{ $step[0] }}</span><span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-indigo-50 text-xl text-indigo-600"><i class="{{ $step[3] }}"></i></span><div><h3 class="font-extrabold text-slate-900">{{ $step[1] }}</h3><p class="mt-1 text-sm font-medium leading-6 text-slate-500">{{ $step[2] }}</p></div></div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<section class="bg-white py-20 sm:py-28"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8"><div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-indigo-700 via-indigo-600 to-violet-700 px-6 py-12 text-center text-white shadow-2xl sm:px-12 sm:py-16"><div class="absolute left-1/2 top-0 h-72 w-72 -translate-x-1/2 rounded-full bg-white/10 blur-3xl"></div><div class="relative"><span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 text-3xl"><i class="ri-brain-line"></i></span><h2 class="mt-6 text-3xl font-black tracking-tight sm:text-4xl">Siap membuat belajarmu lebih aktif?</h2><p class="mx-auto mt-4 max-w-xl text-base font-medium leading-7 text-indigo-100">Masuk untuk mencoba AI Learning Tools dan mulai dari materi yang sedang kamu pelajari hari ini.</p><a href="{{ $tryAiHref }}" class="mt-8 inline-flex items-center gap-2 rounded-2xl bg-white px-7 py-4 text-base font-extrabold text-indigo-700 shadow-xl transition hover:-translate-y-0.5 hover:bg-indigo-50">Coba AI Learning Tools <i class="ri-arrow-right-line text-lg"></i></a><p class="mt-4 text-xs font-medium text-indigo-200">Login diperlukan untuk menyimpan hasil belajarmu.</p></div></div></div></section>
@endsection
