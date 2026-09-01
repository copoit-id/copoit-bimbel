@extends('general.layout')

@php
    $landingContent = $content ?? [];
    $landingDefaults = \App\Http\Controllers\GeneralPageController::defaultLandingContent();
    $generalVisiblePages = \Illuminate\Support\Facades\Schema::hasTable('general_pages')
        ? \App\Models\GeneralPage::query()
            ->whereIn('page_key', ['statistik-ptn', 'artikel'])
            ->where('is_active', true)
            ->pluck('is_active', 'page_key')
        : collect();
    $showStatisticsNav = (bool) $generalVisiblePages->get('statistik-ptn', false);
    $showArticlesNav = (bool) $generalVisiblePages->get('artikel', false);
    $landingValue = fn (string $key, mixed $default = null) => data_get($landingContent, $key, $default);
    $landingItems = fn (string $key) => data_get($landingContent, $key, data_get($landingDefaults, $key, [])) ?: [];
    $landingAsset = function (?string $path, string $fallback): string {
        $target = $path ?: $fallback;

        if (\Illuminate\Support\Str::startsWith($target, ['http://', 'https://', '//', '/'])) {
            return $target;
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($target)) {
            return \Illuminate\Support\Facades\Storage::url($target);
        }

        return asset($target);
    };
@endphp

@section('title', $landingValue('meta.title', 'Persiapan Ujian UTBK SNBT & SNBP Terbaik'))

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        /* High-energy academic landing visual, powered by CMS content. */
        @keyframes float-slow {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(0.5deg); }
        }
        @keyframes float-delayed {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(6px) rotate(-0.5deg); }
        }
        .animate-float-slow {
            animation: float-slow 7s ease-in-out infinite;
        }
        .animate-float-delayed {
            animation: float-delayed 9s ease-in-out infinite;
        }
        /* Grid background pattern */
        .bg-grid-pattern {
            background-size: 24px 24px;
            background-image: linear-gradient(to right, rgba(148, 163, 184, 0.05) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(148, 163, 184, 0.05) 1px, transparent 1px);
        }
        /* Custom text gradient */
        .text-gradient {
            background: linear-gradient(135deg, var(--client-color-primary, #1C3259) 20%, #4F46E5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .text-gradient-amber {
            background: linear-gradient(135deg, #D97706 0%, #F59E0B 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .landing-header .site-nav,
        .landing-header .site-nav > a,
        .landing-header .site-nav > div > button {
            color: #fff !important;
        }
        .landing-header .site-nav > a,
        .landing-header .site-nav > div > button {
            background: transparent !important;
        }
        .landing-header .site-nav > a:hover,
        .landing-header .site-nav > div > button:hover {
            background: rgba(255,255,255,.14) !important;
        }
        .landing-header .landing-login {
            border-color: transparent !important;
            background: #fbbf24 !important;
            color: #171717 !important;
            font-weight: 800;
        }
        .landing-hero-layer-one {
            clip-path: polygon(0 0, 100% 0, 100% 28%, 72% 48%, 40% 27%, 0 48%);
        }
        .landing-hero-layer-two {
            clip-path: polygon(0 12%, 34% 53%, 72% 37%, 100% 57%, 100% 100%, 0 100%);
        }
    </style>
@endpush

@section('content')
<!-- Section 1: Hero / Pengenalan Platform -->
<section class="landing-hero relative isolate min-h-[41rem] overflow-hidden bg-[#FF6B00] pb-28 pt-32 text-white sm:min-h-[45rem] sm:pb-36 sm:pt-40 lg:min-h-[49rem] lg:pt-48">
    <div class="landing-hero-layer-one pointer-events-none absolute inset-0 bg-gradient-to-br from-[#ffb067] via-[#FF7A1A] to-[#D94F00]"></div>
    <div class="landing-hero-layer-two pointer-events-none absolute inset-0 bg-[#E85D00]/90"></div>
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-64 bg-gradient-to-t from-[#B83B00]/45 to-transparent"></div>
    <svg class="pointer-events-none absolute inset-x-0 -bottom-px h-44 w-full sm:h-56" viewBox="0 0 1440 250" preserveAspectRatio="none" aria-hidden="true">
        <path fill="white" d="M0,112 C166,236 380,245 550,180 C730,109 791,162 944,203 C1112,248 1282,198 1440,115 L1440,250 L0,250 Z"></path>
        <path fill="#F26A21" d="M510,215 C649,150 740,154 865,199 C1005,249 1151,230 1300,175 C1195,248 1010,264 861,218 C725,177 635,180 535,235 Z"></path>
    </svg>

    <div class="relative z-10 mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
        <p class="text-sm font-bold tracking-wide text-white/90 sm:text-base">{{ $landingValue('hero.badge', 'Bimbingan Belajar untuk Target Terbaikmu') }}</p>
        <h1 class="mt-5 text-5xl font-black leading-none tracking-tight text-white sm:text-7xl lg:mt-8 lg:text-[6.5rem]">{{ $clientBranding['name'] }}</h1>
        <p class="mx-auto mt-5 max-w-3xl text-base font-semibold leading-relaxed text-white sm:mt-7 sm:text-2xl">
            {{ $landingValue('hero.description', 'Bimbingan belajar yang membantu kamu belajar terarah dan mencapai kampus impian.') }}
        </p>

        <div class="mx-auto mt-9 grid max-w-5xl gap-3 sm:grid-cols-3 sm:gap-5 lg:mt-12">
            <a href="{{ $landingValue('hero.secondary_cta.href', 'https://wa.me/628561078411?text=Halo%20Admin%20saya%20Ingin%20Tanya%20Program%20Bimbel') }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-14 items-center justify-center rounded-full bg-white px-5 text-center text-xs font-black uppercase tracking-[.13em] text-slate-950 shadow-xl shadow-[#B83B00]/20 transition hover:-translate-y-0.5 hover:bg-amber-50 sm:text-sm">Hubungi Kami</a>
            <a href="#program" class="inline-flex min-h-14 items-center justify-center rounded-full bg-white px-5 text-center text-xs font-black uppercase tracking-[.13em] text-slate-950 shadow-xl shadow-[#B83B00]/20 transition hover:-translate-y-0.5 hover:bg-amber-50 sm:text-sm">Lihat Program Belajar</a>
            <a href="{{ $landingValue('hero.primary_cta.href', route('login')) }}" class="inline-flex min-h-14 items-center justify-center rounded-full bg-white px-5 text-center text-xs font-black uppercase tracking-[.13em] text-slate-950 shadow-xl shadow-[#B83B00]/20 transition hover:-translate-y-0.5 hover:bg-amber-50 sm:text-sm">{{ $landingValue('hero.primary_cta.label', 'Mulai Belajar') }}</a>
        </div>
    </div>

    <div class="pointer-events-none absolute bottom-7 left-[29%] z-10 hidden -rotate-12 rounded-full border-4 border-white/85 bg-amber-300 p-4 text-[#FF6B00] shadow-xl lg:block"><i class="ri-book-open-fill text-4xl"></i></div>
    <div class="pointer-events-none absolute bottom-20 right-[13%] z-10 hidden rotate-12 rounded-full border-4 border-white/85 bg-white p-4 text-[#FF6B00] shadow-xl lg:block"><i class="ri-graduation-cap-fill text-4xl"></i></div>
</section>

<!-- About and achievements: the same CMS content is surfaced in a GO-inspired editorial sequence. -->
<section id="tentang" class="bg-white py-14 sm:py-20">
    <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
        <p class="text-sm font-extrabold uppercase tracking-[.18em] text-[#FF6B00]">Tentang {{ $clientBranding['name'] }}</p>
        <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">{{ $landingValue('community.title', 'Belajar lebih terarah, target makin dekat') }}</h2>
        <p class="mx-auto mt-5 max-w-3xl text-sm leading-7 text-slate-600 sm:text-base">
            {{ $landingValue('community.description', 'Temukan pendampingan belajar, tryout, dan komunitas yang membantu kamu konsisten bertumbuh sampai target tercapai.') }}
        </p>
        <a href="#program" class="mt-7 inline-flex items-center gap-2 text-sm font-extrabold text-[#FF6B00] hover:underline">Lihat program belajar <i class="ri-arrow-right-line"></i></a>
    </div>
</section>

<section class="bg-[#FF6B00] py-12 text-white sm:py-16">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 md:grid-cols-3 lg:px-8">
        @foreach(array_slice($landingItems('achievements.items'), 0, 3) as $achievement)
            <article class="border-l-2 border-amber-300/80 pl-5">
                <p class="text-3xl font-black text-amber-200">{{ data_get($achievement, 'value') }}</p>
                <h3 class="mt-2 text-lg font-extrabold">{{ data_get($achievement, 'label') }}</h3>
                <p class="mt-2 text-sm leading-6 text-white/85">{{ data_get($achievement, 'description') }}</p>
            </article>
        @endforeach
    </div>
</section>

<section id="artikel" class="relative overflow-hidden bg-white py-16 sm:py-24">
    <div class="pointer-events-none absolute left-0 top-12 h-44 w-44 -translate-x-1/2 rounded-full bg-[#FF6B00]/5"></div>
    <div class="pointer-events-none absolute bottom-8 right-0 h-56 w-56 translate-x-1/2 rounded-full bg-amber-300/25"></div>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative mb-9 flex flex-col gap-3 text-center sm:mb-12">
            <p class="text-sm font-extrabold uppercase tracking-[.18em] text-[#FF6B00]">Info & Insight</p>
            <h2 class="text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">Mading {{ $clientBranding['name'] }}</h2>
            <p class="mx-auto max-w-2xl text-sm font-medium leading-relaxed text-slate-600">Cerita, strategi belajar, dan informasi terbaru untuk menemani perjalananmu mencapai target.</p>
        </div>
        @if($landingArticles->isNotEmpty())
            <div class="relative flex flex-wrap justify-center gap-5">
                @foreach($landingArticles as $article)
                    <a href="{{ route('articles.show', $article->slug) }}" class="group flex w-full max-w-[17rem] flex-col overflow-hidden rounded-[1.25rem] border border-slate-200 bg-white p-2.5 shadow-[0_8px_22px_-15px_rgba(15,23,42,.52)] transition duration-300 hover:-translate-y-1 hover:border-[#FF6B00]/30 hover:shadow-lg sm:w-[calc(50%-0.625rem)] lg:w-[calc(25%-0.9375rem)]">
                        <div class="relative aspect-[16/10] overflow-hidden rounded-[.9rem] bg-[#FF6B00]/10">
                            <img src="{{ $article->cover_url }}" alt="{{ $article->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            <span class="absolute left-3 top-3 rounded-full bg-white/95 px-2 py-1 text-[8px] font-black uppercase tracking-[.12em] text-[#FF6B00]">Insight</span>
                        </div>
                        <div class="flex flex-1 flex-col px-1.5 pb-2 pt-4">
                            <p class="text-[9px] font-extrabold uppercase tracking-[.12em] text-[#FF6B00]">{{ $article->published_date_label }} · {{ $article->reading_minutes }} menit</p>
                            <h3 class="mt-2 line-clamp-3 text-sm font-black leading-snug text-slate-800">{{ $article->title }}</h3>
                            <span class="mt-4 text-xs font-extrabold text-[#FF6B00]">Baca artikel <i class="ri-arrow-right-line"></i></span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="relative rounded-[1.5rem] border border-dashed border-[#FF6B00]/30 bg-[#FF6B00]/5 px-6 py-12 text-center text-sm font-semibold text-slate-600">Artikel terbaru sedang disiapkan.</div>
        @endif
        @if($showArticlesNav)<div class="relative mt-9 text-center"><a href="{{ route('articles.index') }}" class="inline-flex items-center gap-2 rounded-full border-2 border-[#FF6B00] px-5 py-2.5 text-sm font-extrabold text-[#FF6B00] transition hover:bg-[#FF6B00] hover:text-white">Lihat semua artikel <i class="ri-arrow-right-line"></i></a></div>@endif
    </div>
</section>

<!-- Section 2: Daftar Paket, positioned directly after Info & Insight. -->
<section id="program" class="border-b border-slate-100 bg-white py-12 sm:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-11">
            <span class="text-[#FF6B00] font-extrabold tracking-widest uppercase text-xs sm:text-sm mb-3 block">{{ $landingValue('program.eyebrow', 'Daftar Paket') }}</span>
            <h2 class="text-3xl sm:text-4.5xl font-black text-slate-900 leading-tight">{{ $landingValue('program.title', 'Paket Belajar Pilihan') }}</h2>
            <p class="text-sm sm:text-base text-slate-550 mt-4 leading-relaxed font-medium">
                {{ $landingValue('program.description', 'Pilih paket belajar persiapan ujian yang sesuai dengan kriteria target jurusan dan kampus favoritmu.') }}
            </p>
        </div>

        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3 max-w-6xl mx-auto items-stretch">
            @forelse($landingPackages as $package)
                @php
                    $programThumbnail = $package->image
                        ? \Illuminate\Support\Facades\Storage::url($package->image)
                        : null;
                    $isVideoThumbnail = $package->image
                        && in_array(strtolower(pathinfo($package->image, PATHINFO_EXTENSION)), ['mp4', 'webm', 'mov', 'm4v'], true);
                    $ctaLabel = match ($package->type_price) {
                        'paid' => 'Lihat Paket',
                        'free_conditional' => 'Lihat Persyaratan',
                        default => 'Ambil Paket',
                    };
                @endphp
                <article class="relative mx-auto flex min-h-[30rem] w-full max-w-[24rem] flex-col rounded-[5%] border border-[#FF6B00]/10 bg-white p-3 shadow-[0_8px_26px_-16px_rgba(196,75,0,.52)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_18px_32px_-18px_rgba(196,75,0,.58)]">
                    <div class="relative h-52 overflow-hidden rounded-[4%] bg-gradient-to-br from-[#FF6B00]/10 to-amber-100">
                        @if($programThumbnail)
                            @if($isVideoThumbnail)
                                <video src="{{ $programThumbnail }}" class="h-full w-full object-cover" muted playsinline preload="metadata"></video>
                            @else
                                <img src="{{ $programThumbnail }}" alt="Thumbnail {{ $package->name }}" class="h-full w-full object-cover" loading="lazy">
                            @endif
                        @else
                            <div class="flex h-full items-center justify-center">
                                <i class="ri-book-open-line text-6xl text-primary/30"></i>
                            </div>
                        @endif
                        <span class="absolute left-3 top-3 rounded-full bg-white/95 px-2.5 py-1 text-[9px] font-black uppercase tracking-widest text-[#FF6B00] shadow-sm">
                            {{ str_replace('_', ' ', $package->type_package) }}
                        </span>
                    </div>

                    <div class="flex flex-1 flex-col justify-between px-3 pb-2 pt-4 text-center">
                        <div class="space-y-2">
                            <div>
                                <h3 class="text-base font-extrabold text-[#FF6B00]">{{ $package->name }}</h3>
                                <p class="mt-2 text-xs font-medium leading-relaxed text-slate-700">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($package->description ?: 'Paket pembelajaran lengkap untuk mendukung target belajarmu.'), 155) }}
                                </p>
                            </div>

                        </div>

                        @if($package->type_price === 'free_conditional')
                            <button type="button"
                                data-landing-modal-open="landing-conditional-package-{{ $package->package_id }}"
                                class="mt-5 inline-flex items-center justify-center text-xs font-extrabold text-[#FF6B00] transition hover:underline">
                                {{ $ctaLabel }}
                                <i class="ri-file-list-3-line ml-2"></i>
                            </button>
                        @else
                            <a href="{{ route('user.package.detail', $package->package_id) }}"
                               class="mt-5 inline-flex items-center justify-center text-xs font-extrabold text-[#FF6B00] transition hover:underline">
                                {{ $ctaLabel }}
                                <i class="ri-arrow-right-line ml-2"></i>
                            </a>
                        @endif
                    </div>
                </article>

                @if($package->type_price === 'free_conditional')
                    <div id="landing-conditional-package-{{ $package->package_id }}"
                        class="fixed inset-0 z-50 hidden items-center justify-center bg-blue-950/25 px-4 py-8 backdrop-blur-[2px]"
                        data-landing-modal>
                        <div class="absolute inset-0" data-landing-modal-close="landing-conditional-package-{{ $package->package_id }}"></div>
                        <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
                            <button type="button"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600"
                                data-landing-modal-close="landing-conditional-package-{{ $package->package_id }}">
                                <i class="ri-close-line text-xl"></i>
                            </button>
                            <div class="space-y-4 p-6">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-primary">Syarat Paket Gratis</p>
                                    <h3 class="mt-1 text-xl font-semibold text-gray-900">{{ $package->name }}</h3>
                                    <p class="text-sm text-gray-500">Lengkapi bukti syarat untuk mengajukan akses gratis bersyarat.</p>
                                </div>

                                <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-4 text-sm text-gray-700">
                                    <p class="mb-1 font-semibold text-blue-900">Detail Syarat</p>
                                    <p class="whitespace-pre-line">{{ $package->conditional_requirement ?: 'Syarat belum ditentukan. Silakan hubungi admin.' }}</p>
                                </div>

                                <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST"
                                    enctype="multipart/form-data" class="space-y-4">
                                    @csrf
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-gray-700">Upload Bukti Syarat</label>
                                        <input type="file" name="requirement_proofs[]" required multiple
                                            accept=".jpg,.jpeg,.png,.pdf,.mp4,.webm"
                                            class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                                        <p class="mt-2 text-xs text-gray-500">Bisa pilih lebih dari satu file. Format: JPG, PNG, PDF, MP4, WEBM. Maks 2MB per file.</p>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-gray-700">Catatan untuk Admin <span class="font-normal text-gray-400">(opsional)</span></label>
                                        <textarea name="requirement_user_notes" rows="3" maxlength="1000"
                                            class="w-full resize-none rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                                            placeholder="Contoh: Bukti ini dari akun Instagram saya, nama akun @..."></textarea>
                                        <p class="mt-2 text-xs text-gray-500">Catatan ini akan terlihat oleh admin saat review pengajuan.</p>
                                    </div>

                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button"
                                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                                            data-landing-modal-close="landing-conditional-package-{{ $package->package_id }}">Batal</button>
                                        <button type="submit"
                                            class="rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-white hover:bg-primary/90">Kirim Bukti</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm font-medium text-slate-500">
                    Program pilihan sedang disiapkan.
                </div>
            @endforelse
        </div>
    </div>
</section>

<!-- Section 3: Komunitas Belajar -->
<section class="bg-white py-16 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-[2rem] bg-[#FF6B00] p-8 text-white shadow-[0_16px_32px_-20px_rgba(196,75,0,.6)] md:p-12">
            <!-- Decorative light overlays -->
            <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-amber-300/25 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-20 -bottom-20 h-48 w-48 rounded-full bg-white/10 blur-2xl"></div>
            <div class="pointer-events-none absolute -right-16 bottom-0 h-28 w-[55%] -rotate-6 bg-[#D94F00]/60"></div>

            <div class="relative grid lg:grid-cols-12 gap-8 items-center z-10">
                <!-- Left: Content details -->
                <div class="lg:col-span-8 space-y-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-white font-extrabold uppercase tracking-widest text-[10px] sm:text-xs">
                        <i class="ri-wechat-line text-sm"></i>
                        {{ $landingValue('community.badge', 'Support System Pejuang PTN') }}
                    </div>
                    <h3 class="text-2xl sm:text-3xl.5 font-black tracking-tight leading-tight">{{ $landingValue('community.title', 'Komunitas Pejuang PTN ' . $clientBranding['name']) }}</h3>
                    <p class="text-sm sm:text-base text-slate-100/90 font-medium leading-relaxed max-w-2xl">
                        {{ $landingValue('community.description', 'Jangan berjuang sendirian! Bergabunglah di grup WhatsApp diskusi kami untuk berbagi soal, info pendaftaran PTN, konsultasi, serta webinar gratis bersama alumni terkemuka.') }}
                    </p>
                </div>

                <!-- Right: CTA -->
                <div class="lg:col-span-4 flex lg:justify-end">
                    <a href="{{ $landingValue('community.cta.href', 'https://chat.whatsapp.com/DO0KNXJVyoyAWK31EOoo3H') }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex w-full sm:w-auto items-center justify-center gap-2.5 rounded-xl bg-white hover:bg-slate-50 px-8 py-4 text-base font-extrabold text-primary shadow-md hover:shadow-lg transition-all active:scale-98">
                        <i class="ri-whatsapp-line text-xl text-emerald-500"></i>
                        {{ $landingValue('community.cta.label', 'Gabung Grup Sekarang') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section 4: Testimoni Siswa -->
<section class="border-y border-slate-100 bg-white py-16 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-[#FF6B00] font-extrabold tracking-widest uppercase text-xs sm:text-sm block mb-3">{{ $landingValue('testimonials.eyebrow', 'Kisah Sukses Pejuang') }}</span>
            <h2 class="text-3xl sm:text-4.5xl font-black text-slate-900 leading-tight">{{ $landingValue('testimonials.title', 'Apa Kata Mereka Tentang ' . $clientBranding['name'] . '?') }}</h2>
            <p class="text-sm sm:text-base text-slate-550 mt-4 leading-relaxed font-medium">
                {{ $landingValue('testimonials.description', 'Mereka telah membuktikan keakuratan data dan bimbingan kami, kini berhasil lolos ke prodi impian.') }}
            </p>
        </div>

        <div class="grid items-stretch gap-7 md:grid-cols-2 lg:grid-cols-3">
            @foreach($landingItems('testimonials.items') as $testimonial)
                <article class="group relative mx-auto flex w-full max-w-[25rem] flex-col overflow-hidden rounded-[1.8rem] bg-gradient-to-b from-[#FF862D] to-[#D94F00] p-4 text-white shadow-[0_18px_35px_-20px_rgba(196,75,0,.65)] transition-all duration-300 hover:-translate-y-2 hover:shadow-[0_24px_42px_-18px_rgba(196,75,0,.6)]">
                    <div class="relative aspect-[16/10] overflow-hidden rounded-[1.25rem] border border-white/25 bg-[#C94A00]">
                        <img src="{{ $landingAsset(data_get($testimonial, 'image'), 'img/student_rian.png') }}" alt="{{ data_get($testimonial, 'name') }}" class="h-full w-full object-cover object-top transition duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/45 via-transparent to-transparent"></div>
                        <span class="absolute left-3 top-3 rounded-full bg-white/95 px-2.5 py-1 text-[9px] font-black uppercase tracking-[.12em] text-[#D94F00]">Cerita pejuang</span>
                        <span class="absolute inset-0 m-auto flex h-12 w-12 items-center justify-center rounded-full border-2 border-white/85 bg-slate-950/45 pl-0.5 text-xl text-white backdrop-blur-sm transition group-hover:scale-110"><i class="ri-play-fill"></i></span>
                    </div>
                    <div class="flex flex-1 flex-col px-3 pb-3 pt-5">
                        <h3 class="text-xl font-black leading-tight">{{ data_get($testimonial, 'name') }}</h3>
                        <p class="mt-1 text-sm font-bold text-amber-200">{{ data_get($testimonial, 'result') }}</p>
                        <div class="mt-5 flex items-start gap-2">
                            <i class="ri-double-quotes-l text-3xl leading-none text-amber-300"></i>
                            <p class="pt-1 text-sm font-medium leading-relaxed text-white/95">{{ data_get($testimonial, 'quote') }}</p>
                        </div>
                        <div class="mt-auto flex items-center justify-between border-t border-white/20 pt-5">
                            <span class="text-[10px] font-extrabold uppercase tracking-[.12em] text-white/70">Alumni terverifikasi</span>
                            <i class="ri-checkbox-circle-fill text-lg text-amber-300" title="Alumni Terverifikasi"></i>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

@if(count($landingItems('facilities.items')) > 0)
<!-- Section 5: Fasilitas -->
<section class="relative isolate overflow-hidden bg-[#FF6B00] py-16 sm:py-20">
    <div class="landing-hero-layer-one pointer-events-none absolute inset-0 bg-gradient-to-br from-[#ffb067] via-[#FF7A1A] to-[#D94F00]"></div>
    <div class="landing-hero-layer-two pointer-events-none absolute inset-0 bg-[#E85D00]/90"></div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="space-y-12">
            <div class="mx-auto max-w-3xl text-center">
                <span class="text-xs font-extrabold uppercase tracking-[.18em] text-[#FFE1B8] sm:text-sm">{{ $landingValue('facilities.eyebrow', 'Fasilitas') }}</span>
                <h2 class="mt-4 text-2xl font-black leading-tight text-white sm:text-3xl">{{ $landingValue('facilities.title', 'Fasilitas Unggulan BimbelHub Untuk Mencapai Target Akademikmu') }}</h2>
                <p class="mt-4 text-sm font-semibold leading-relaxed text-white/85">{{ $landingValue('facilities.description', 'Dukungan belajar terintegrasi agar kamu bisa belajar lebih terarah dan konsisten.') }}</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($landingItems('facilities.items') as $facilityIndex => $facility)
                    <article class="group relative overflow-hidden rounded-xl border border-white/80 bg-white p-5 text-center shadow-[0_16px_28px_-20px_rgba(120,65,0,.55)] transition duration-300 hover:-translate-y-2 hover:shadow-[0_22px_34px_-18px_rgba(120,65,0,.5)]">
                        <span class="absolute right-4 top-3 text-[10px] font-black tracking-[.16em] text-[#FF6B00]/30">0{{ $facilityIndex + 1 }}</span>
                        <div class="mx-auto flex h-[3.75rem] w-[3.75rem] items-center justify-center rounded-lg bg-[#FF6B00] text-white shadow-[0_10px_18px_-12px_rgba(196,75,0,.75)] transition duration-300 group-hover:-rotate-3 group-hover:bg-[#D94F00]"><i class="{{ data_get($facility, 'icon', 'ri-book-open-line') }} text-2xl"></i></div>
                        <h3 class="mt-5 text-sm font-extrabold text-slate-900">{{ data_get($facility, 'title') }}</h3>
                        <div class="mx-auto mt-3 h-0.5 w-8 rounded-full bg-[#FFB11A] transition group-hover:w-12"></div>
                        <p class="mt-3 text-xs font-medium leading-relaxed text-slate-600">{{ data_get($facility, 'description') }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

<!-- Section 6: FAQ -->
<section class="relative overflow-hidden bg-white py-16 sm:py-24">
    <div class="pointer-events-none absolute bottom-0 left-0 h-4 w-full bg-[#FF6B00]"></div>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @php
            $faqItems = $landingItems('faq.items');
            $faqVisualImage = $landingValue('faq.visual_image');
        @endphp
        <div class="grid items-center gap-10 lg:grid-cols-12 lg:gap-14">
            <div class="lg:col-span-7">
                <div class="mb-8">
                    <p class="text-sm font-extrabold uppercase tracking-[.18em] text-[#FF6B00]">{{ $landingValue('faq.eyebrow', 'Pertanyaan Umum') }}</p>
                    <h2 class="mt-3 text-3xl font-black leading-tight text-slate-900 sm:text-4xl">{{ $landingValue('faq.title', 'Tanya Jawab') }}</h2>
                </div>
                <div x-data="{ activeIndex: null, showAll: false }" class="overflow-hidden rounded-[1.5rem] bg-white shadow-[0_20px_38px_-24px_rgba(15,23,42,.38)]">
                    <div class="divide-y-4 divide-white">
                        @foreach($faqItems as $faqIndex => $faq)
                            <div x-show="showAll || {{ $faqIndex }} < 3" x-collapse class="bg-[#FF6B00]">
                                <button @click="activeIndex = (activeIndex === {{ $faqIndex }} ? null : {{ $faqIndex }})" class="flex w-full items-center justify-between gap-5 px-6 py-6 text-left text-sm font-extrabold leading-relaxed text-white transition hover:bg-[#e65f00] sm:px-8 sm:text-lg">
                                    <span>{{ data_get($faq, 'question') }}</span>
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#FFF1DF] text-2xl font-medium text-[#FF6B00]">
                                        <i :class="activeIndex === {{ $faqIndex }} ? 'ri-subtract-line' : 'ri-add-line'"></i>
                                    </span>
                                </button>
                                <div x-show="activeIndex === {{ $faqIndex }}" x-collapse class="border-t border-white/30 px-6 pb-6 pt-1 text-sm font-medium leading-relaxed text-white/95 sm:px-8">
                                    {{ data_get($faq, 'answer') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if(count($faqItems) > 3)
                        <button type="button" @click="showAll = !showAll" class="flex w-full items-center gap-2 bg-white px-6 py-6 text-left text-sm font-extrabold text-[#FF6B00] transition hover:bg-[#FFF1DF] sm:px-8">
                            <span x-text="showAll ? 'Sembunyikan pertanyaan' : 'Lihat semua pertanyaan'"></span><i class="ri-arrow-right-line text-lg"></i>
                        </button>
                    @endif
                </div>
            </div>
            <div class="relative mx-auto flex min-h-[20rem] w-full max-w-sm items-end justify-center lg:col-span-5 lg:min-h-[28rem] lg:max-w-none">
                @if($faqVisualImage)
                    <img src="{{ $landingAsset($faqVisualImage, '') }}" alt="{{ $landingValue('faq.visual_alt', 'Ilustrasi tim siap membantu') }}" class="relative z-10 h-full max-h-[30rem] w-full object-contain object-bottom">
                @else
                    <div class="flex aspect-[4/5] w-full max-w-xs flex-col items-center justify-center rounded-[2rem] border-2 border-dashed border-[#FF6B00]/25 bg-[#FFF1DF] px-8 text-center">
                        <i class="ri-image-add-line text-5xl text-[#FF6B00]/55"></i>
                        <p class="mt-4 text-sm font-bold text-slate-600">Tambahkan ilustrasi PNG transparan melalui CMS</p>
                        <p class="mt-1 text-xs font-medium leading-relaxed text-slate-400">Gunakan gambar orang yang mengarah ke daftar pertanyaan.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<!-- Section 7: Kontak & Footer Khusus Landing Page -->
<footer class="relative isolate overflow-hidden bg-[#D94F00] pb-8 pt-16 text-white">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-2 bg-[#FFB11A]"></div>
    <div class="landing-hero-layer-one pointer-events-none absolute inset-0 bg-gradient-to-br from-[#FF862D] via-[#E85D00] to-[#9C3200]"></div>
    <div class="landing-hero-layer-two pointer-events-none absolute inset-0 bg-[#B83B00]/70"></div>
    <div class="relative mx-auto grid max-w-7xl gap-12 border-b border-white/15 px-4 pb-12 sm:px-6 md:grid-cols-12 lg:px-8">
        <!-- Logo & Description -->
        <div class="md:col-span-6 space-y-6">
            <div class="flex items-center gap-3">
                <img src="{{ $clientBranding['logo_url'] }}" alt="{{ $clientBranding['name'] }} Logo"
                     class="client-brand-logo h-12 w-12 rounded-xl object-contain bg-white p-1 shadow-md">
                <div>
                    <h3 class="text-lg font-black leading-none sm:text-xl">{{ $clientBranding['name'] }}</h3>
                    <p class="mt-1 text-xs font-semibold text-[#FFD28C]">{{ $landingValue('footer.tagline', 'Platform Sukses Tembus PTN Impian') }}</p>
                </div>
            </div>
            <p class="max-w-md text-sm font-medium leading-relaxed text-white/75">
                {{ $landingValue('footer.description', 'Penyedia layanan bimbingan belajar, tryout IRT online nasional, pendampingan konsultasi jurusan, serta rasionalisasi rapor seleksi SNBP/SNBT terpercaya di Indonesia.') }}
            </p>
        </div>

        <!-- Services Menu Link -->
        <div class="md:col-span-3 space-y-4">
            <h4 class="text-sm font-black uppercase tracking-[.14em] text-[#FFD28C] sm:text-base">{{ $landingValue('footer.navigation_title', 'Navigasi') }}</h4>
            <ul class="space-y-3 text-xs font-semibold text-white/80 sm:text-sm">
                <li><a href="{{ route('landing') }}" class="inline-flex items-center gap-2 transition-colors hover:text-[#FFB11A]"><i class="ri-arrow-right-s-line text-[#FFB11A]"></i>{{ $landingValue('footer.nav_landing_label', 'Home Landing') }}</a></li>
                @if($showStatisticsNav)
                    <li><a href="{{ route('statistics') }}" class="inline-flex items-center gap-2 transition-colors hover:text-[#FFB11A]"><i class="ri-arrow-right-s-line text-[#FFB11A]"></i>{{ $landingValue('footer.nav_statistics_snbp_label', 'Statistik PTN SNBP') }}</a></li>
                    <li><a href="{{ route('statistics.snbt') }}" class="inline-flex items-center gap-2 transition-colors hover:text-[#FFB11A]"><i class="ri-arrow-right-s-line text-[#FFB11A]"></i>{{ $landingValue('footer.nav_statistics_snbt_label', 'Statistik PTN SNBT') }}</a></li>
                @endif
                @if($showArticlesNav)
                    <li><a href="{{ route('articles.index') }}" class="inline-flex items-center gap-2 transition-colors hover:text-[#FFB11A]"><i class="ri-arrow-right-s-line text-[#FFB11A]"></i>{{ $landingValue('footer.nav_articles_label', 'Insight & Artikel') }}</a></li>
                @endif
                <li><a href="{{ route('login') }}" class="inline-flex items-center gap-2 transition-colors hover:text-[#FFB11A]"><i class="ri-arrow-right-s-line text-[#FFB11A]"></i>{{ $landingValue('footer.nav_login_label', 'Daftar / Login Akun') }}</a></li>
            </ul>
        </div>

        <!-- Contact Links -->
        <div class="md:col-span-3 space-y-4">
            <h4 class="text-sm font-black uppercase tracking-[.14em] text-[#FFD28C] sm:text-base">{{ $landingValue('footer.contact_title', 'Hubungi Kami') }}</h4>
            <ul class="space-y-3 text-xs font-semibold text-white/80 sm:text-sm">
                <li>
                    <a href="{{ $landingValue('footer.instagram_href', 'https://instagram.com/naufalacademy') }}" target="_blank" rel="noopener noreferrer"
                       class="flex items-center gap-2.5 transition-colors hover:text-[#FFB11A]">
                        <i class="ri-instagram-line flex h-7 w-7 items-center justify-center rounded-full bg-white/10 text-base text-[#FFB11A]"></i>
                        {{ $landingValue('footer.instagram_label', '@naufalacademy') }}
                    </a>
                </li>
                <li>
                    <a href="{{ $landingValue('footer.whatsapp_href', 'https://wa.me/628561078411?text=Halo%2520Admin%2520saya%2520Ingin%2520Bertanya') }}" target="_blank" rel="noopener noreferrer"
                       class="flex items-center gap-2.5 transition-colors hover:text-[#FFB11A]">
                        <i class="ri-whatsapp-line flex h-7 w-7 items-center justify-center rounded-full bg-white/10 text-base text-[#FFB11A]"></i>
                        {{ $landingValue('footer.whatsapp_label', '+62 856-1078-411') }}
                    </a>
                </li>
                <li>
                    <a href="{{ $landingValue('footer.email_href', 'mailto:team.naufalacademy@gmail.com') }}"
                       class="flex items-center gap-2.5 transition-colors hover:text-[#FFB11A]">
                        <i class="ri-mail-line flex h-7 w-7 items-center justify-center rounded-full bg-white/10 text-base text-[#FFB11A]"></i>
                        {{ $landingValue('footer.email_label', 'team.naufalacademy@gmail.com') }}
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="relative mx-auto flex max-w-7xl flex-col gap-4 px-4 pt-8 text-center text-xs font-semibold text-white/60 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:text-sm lg:px-8">
        <p>&copy; {{ date('Y') }} {{ $clientBranding['name'] }}. {{ $landingValue('footer.copyright_suffix', 'Hak cipta dilindungi undang-undang.') }}</p>
        <div class="flex gap-4 justify-center">
            <a href="{{ $landingValue('footer.terms_href', '#') }}" class="hover:text-white">{{ $landingValue('footer.terms_label', 'Syarat & Ketentuan') }}</a>
            <a href="{{ $landingValue('footer.privacy_href', '#') }}" class="hover:text-white">{{ $landingValue('footer.privacy_label', 'Kebijakan Privasi') }}</a>
        </div>
    </div>
</footer>

<!-- Floating WhatsApp Button (Exactly like user dashboard) with database branding and default fallback -->
@php
    if (empty($clientBranding['contact_whatsapp_number'])) {
        $clientBranding['contact_whatsapp_number'] = '628561078411';
    }
@endphp
@include('user.components.floating-whatsapp')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const openModal = (id) => {
            const modal = document.getElementById(id);
            if (!modal) return;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.documentElement.classList.add('overflow-hidden');
        };

        const closeModal = (id) => {
            const modal = document.getElementById(id);
            if (!modal) return;

            modal.classList.add('hidden');
            modal.classList.remove('flex');

            if (!document.querySelector('[data-landing-modal]:not(.hidden)')) {
                document.documentElement.classList.remove('overflow-hidden');
            }
        };

        document.querySelectorAll('[data-landing-modal-open]').forEach((button) => {
            button.addEventListener('click', () => openModal(button.dataset.landingModalOpen));
        });

        document.querySelectorAll('[data-landing-modal-close]').forEach((button) => {
            button.addEventListener('click', () => closeModal(button.dataset.landingModalClose));
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;

            document.querySelectorAll('[data-landing-modal]:not(.hidden)').forEach((modal) => {
                closeModal(modal.id);
            });
        });
    });
</script>
@endsection
