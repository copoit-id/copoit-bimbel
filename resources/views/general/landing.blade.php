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
        /* Custom floating and pulse animations for premium feel */
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
    </style>
@endpush

@section('content')
<!-- Section 1: Hero / Pengenalan Platform -->
<section class="relative overflow-hidden border-b border-slate-100 bg-grid-pattern bg-white py-16 sm:py-24">
    <!-- Decorative background glow blobs -->
    <div class="absolute -top-40 -right-40 h-96 w-96 rounded-full bg-primary/5 blur-3xl pointer-events-none"></div>
    <div class="absolute top-1/2 left-10 h-72 w-72 rounded-full bg-indigo-500/5 blur-3xl pointer-events-none"></div>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid lg:grid-cols-12 gap-12 lg:gap-16 items-center">
            <!-- Left Column: Content -->
            <div class="lg:col-span-6 space-y-6 sm:space-y-8 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary/8 text-primary font-bold text-xs sm:text-sm border border-primary/15">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-primary"></span>
                    </span>
                    {{ $landingValue('hero.badge', 'Bimbel Persiapan UTBK 2026 #1') }}
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 leading-tight tracking-tight">
                    {!! $landingValue('hero.title_html', 'Siap Tembus <br class="hidden sm:block"><span class="text-gradient">PTN Impian</span> Kamu?') !!}
                </h1>

                <p class="text-base sm:text-lg text-slate-600 max-w-xl leading-relaxed mx-auto lg:mx-0 font-medium">
                    {{ $landingValue('hero.description', 'BimbelHub memandu kamu memahami konsep materi terdalam, strategi memilih jurusan, dan taktik menjawab soal UTBK. Lengkap dengan Tryout IRT nasional, asisten Kak AI, dan bimbingan mentor alumni.') }}
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="{{ $landingValue('hero.primary_cta.href', route('login')) }}"
                       class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-primary px-8 py-4 text-base font-bold text-white transition-all hover:bg-primary-hover shadow-md hover:shadow-lg active:scale-98">
                        {{ $landingValue('hero.primary_cta.label', 'Mulai Belajar Sekarang') }}
                        <i class="ri-arrow-right-line text-lg"></i>
                    </a>
                    <a href="{{ $landingValue('hero.secondary_cta.href', 'https://wa.me/628561078411?text=Halo%20Admin%20saya%20Ingin%20Tanya%20Program%20Bimbel') }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center justify-center gap-2.5 rounded-xl border border-slate-200 bg-white px-8 py-4 text-base font-bold text-slate-700 transition-all hover:bg-slate-50 hover:border-slate-300 active:scale-98">
                        <i class="ri-whatsapp-line text-lg text-emerald-500"></i>
                        {{ $landingValue('hero.secondary_cta.label', 'Hubungi Admin') }}
                    </a>
                </div>

                <!-- Logo grid featuring campus logos as requested -->
                @php
                    $heroLogoStack = $landingItems('hero.logo_stack');
                    $heroLogoColumns = max(1, min(count($heroLogoStack), 6));
                @endphp
                <div class="flex flex-col sm:flex-row items-center gap-4 pt-8 border-t border-slate-100 justify-center lg:justify-start">
                    @if(count($heroLogoStack) > 0)
                        <div class="grid gap-2" style="grid-template-columns: repeat({{ $heroLogoColumns }}, minmax(0, 2.25rem));">
                            @foreach($heroLogoStack as $logo)
                                <img class="h-9 w-9 rounded-full object-contain border-2 border-white bg-white p-0.5 shadow-xs" src="{{ $landingAsset($logo['src'] ?? null, 'img/logo_kampus.png') }}" alt="{{ $logo['alt'] ?? 'Campus Logo' }}">
                            @endforeach
                        </div>
                    @endif
                    <p class="text-xs sm:text-sm font-semibold text-slate-650">
                        {!! $landingValue('hero.social_proof_html', 'Bergabung bersama <span class="text-slate-900 font-extrabold">10.000+ Pejuang UTBK & SNBP</span> tahun ini!') !!}
                    </p>
                </div>
            </div>

            <!-- Right Column: Raw Illustration with rounded corners as requested (No background box container, blobs or shadows) -->
            <div class="lg:col-span-6 flex justify-center lg:justify-end">
                <img src="{{ $landingAsset($landingValue('hero.image'), 'img/hero_study.png') }}"
                     alt="{{ $landingValue('hero.image_alt', 'Siswa Belajar UTBK Online') }}"
                     class="w-full max-w-[480px] aspect-square object-cover rounded-[32px]">
            </div>
        </div>
    </div>
</section>

<!-- Section 2: Program / Kelas yang Dibuka -->
<section id="program" class="border-b border-slate-100 bg-slate-50/50 py-16 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-primary font-extrabold tracking-widest uppercase text-xs sm:text-sm mb-3 block">{{ $landingValue('program.eyebrow', 'Investasi Masa Depan') }}</span>
            <h2 class="text-3xl sm:text-4.5xl font-black text-slate-900 leading-tight">{{ $landingValue('program.title', 'Program Bimbingan Belajar Pilihan') }}</h2>
            <p class="text-sm sm:text-base text-slate-550 mt-4 leading-relaxed font-medium">
                {{ $landingValue('program.description', 'Pilih paket belajar persiapan ujian yang sesuai dengan kriteria target jurusan dan kampus favoritmu.') }}
            </p>
        </div>

        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3 max-w-6xl mx-auto items-stretch">
            @forelse($landingPackages as $package)
                @php
                    $programFeatures = json_decode($package->features ?? '[]', true);
                    $programFeatures = is_array($programFeatures) ? array_values(array_filter($programFeatures)) : [];
                    $programThumbnail = $package->image
                        ? \Illuminate\Support\Facades\Storage::url($package->image)
                        : null;
                    $isVideoThumbnail = $package->image
                        && in_array(strtolower(pathinfo($package->image, PATHINFO_EXTENSION)), ['mp4', 'webm', 'mov', 'm4v'], true);
                    $priceLabel = match ($package->type_price) {
                        'paid' => 'Rp ' . number_format($package->price, 0, ',', '.'),
                        'free_conditional' => 'Gratis*',
                        default => 'Gratis',
                    };
                    $ctaLabel = match ($package->type_price) {
                        'paid' => 'Lihat Paket',
                        'free_conditional' => 'Lihat Persyaratan',
                        default => 'Ambil Paket',
                    };
                @endphp
                <article class="relative flex flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white transition-all duration-300 hover:-translate-y-1 hover:border-primary/20 hover:shadow-lg">
                    <div class="relative h-48 overflow-hidden bg-gradient-to-br from-primary/10 to-slate-100">
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
                        <span class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-primary shadow-sm backdrop-blur">
                            {{ str_replace('_', ' ', $package->type_package) }}
                        </span>
                    </div>

                    <div class="flex flex-1 flex-col justify-between p-7">
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-xl font-bold text-slate-800">{{ $package->name }}</h3>
                                <div class="mt-4 flex items-baseline gap-2">
                                    <span class="text-3xl font-black text-slate-900">{{ $priceLabel }}</span>
                                    @if($package->type_price === 'paid')
                                        <span class="text-2xs font-bold uppercase tracking-wider text-slate-400">Sekali Bayar</span>
                                    @endif
                                </div>
                                <p class="mt-3 text-xs font-medium leading-relaxed text-slate-500">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($package->description ?: 'Paket pembelajaran lengkap untuk mendukung target belajarmu.'), 150) }}
                                </p>
                            </div>

                            @if($programFeatures !== [])
                                <div class="h-px bg-slate-100"></div>

                                <ul class="space-y-3 text-xs font-semibold text-slate-600 sm:text-sm">
                                    @foreach(array_slice($programFeatures, 0, 5) as $feature)
                                        <li class="flex items-start gap-2.5">
                                            <i class="ri-checkbox-circle-fill mt-0.5 shrink-0 text-base text-primary"></i>
                                            <span>{{ is_array($feature) ? data_get($feature, 'label', '') : $feature }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <a href="{{ route('user.package.detail', $package->package_id) }}"
                           class="mt-8 flex w-full items-center justify-center rounded-xl bg-primary py-3.5 text-center text-xs font-bold text-white transition-all hover:bg-primary-hover sm:text-sm">
                            {{ $ctaLabel }}
                            <i class="ri-arrow-right-line ml-2"></i>
                        </a>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm font-medium text-slate-500">
                    Program pilihan sedang disiapkan.
                </div>
            @endforelse
        </div>
    </div>
</section>

<!-- Section 3: Komunitas Belajar -->
<section class="py-16 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-primary to-primary-hover p-8 md:p-12 text-white border border-primary/10">
            <!-- Decorative light overlays -->
            <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
            <div class="absolute -left-20 -bottom-20 h-48 w-48 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>

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

<!-- Section 4: Testimoni Siswa (Using Real Student Photo Images as requested) -->
<section class="border-y border-slate-100 bg-slate-50/50 py-16 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-primary font-extrabold tracking-widest uppercase text-xs sm:text-sm block mb-3">{{ $landingValue('testimonials.eyebrow', 'Kisah Sukses Pejuang') }}</span>
            <h2 class="text-3xl sm:text-4.5xl font-black text-slate-900 leading-tight">{{ $landingValue('testimonials.title', 'Apa Kata Alumni Kami?') }}</h2>
            <p class="text-sm sm:text-base text-slate-550 mt-4 leading-relaxed font-medium">
                {{ $landingValue('testimonials.description', 'Mereka telah membuktikan keakuratan data dan bimbingan kami, kini berhasil lolos ke prodi impian.') }}
            </p>
        </div>

        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4 items-stretch">
            @foreach($landingItems('testimonials.items') as $testimonial)
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 flex flex-col justify-between hover:border-primary/25 hover:shadow-md transition-all duration-300">
                    <div class="space-y-4">
                        <div class="flex items-center gap-1 text-amber-400">
                            @for($star = 0; $star < 5; $star++)
                                <i class="ri-star-fill text-sm"></i>
                            @endfor
                        </div>
                        <p class="text-xs sm:text-sm font-semibold text-slate-600 leading-relaxed italic">
                            "{{ data_get($testimonial, 'quote') }}"
                        </p>
                    </div>
                    <div class="flex items-center gap-3 pt-5 border-t border-slate-100 mt-6">
                        <img src="{{ $landingAsset(data_get($testimonial, 'image'), 'img/student_rian.png') }}" alt="{{ data_get($testimonial, 'name') }}" class="h-10 w-10 rounded-full object-cover shrink-0 border border-slate-200">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1">
                                <h4 class="text-xs sm:text-sm font-bold text-slate-800 leading-none truncate">{{ data_get($testimonial, 'name') }}</h4>
                                <i class="ri-checkbox-circle-fill text-emerald-500 text-xs" title="Alumni Terverifikasi"></i>
                            </div>
                            <p class="text-[10px] text-slate-400 font-bold mt-1">{{ data_get($testimonial, 'result') }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Section 5: Pencapaian & Logo Lembaga / Sekolah Kerjasama (Fokus Detail Informasi) -->
<section class="bg-white py-16 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-20">
        <!-- Part 1: Achievements Details -->
        <div class="space-y-8">
            <div class="text-center max-w-3xl mx-auto">
                <span class="text-primary font-extrabold tracking-widest uppercase text-xs sm:text-sm mb-3 block">{{ $landingValue('achievements.eyebrow', 'Pencapaian Terbaik Kami') }}</span>
                <h3 class="text-2xl sm:text-3.5xl font-black text-slate-900 leading-tight">{{ $landingValue('achievements.title', 'Bukti Nyata Kualitas Pendampingan BimbelHub') }}</h3>
            </div>

            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($landingItems('achievements.items') as $achievementIndex => $achievement)
                    @php
                        $achievementIcons = ['ri-trophy-line', 'ri-group-line', 'ri-book-open-line'];
                    @endphp
                    <div class="rounded-3xl border-2 border-slate-200 bg-slate-50/30 p-8 flex flex-col justify-between hover:border-primary/40 transition-all duration-300 hover:shadow-xs">
                        <div class="space-y-4">
                            <div class="h-12 w-12 rounded-2xl bg-primary/8 flex items-center justify-center text-primary border border-primary/20">
                                <i class="{{ $achievementIcons[$achievementIndex % count($achievementIcons)] }} text-2xl"></i>
                            </div>
                            <h4 class="text-3xl sm:text-4.5xl font-black text-primary leading-none">{{ data_get($achievement, 'value') }}</h4>
                            <p class="text-sm font-bold text-slate-850">{{ data_get($achievement, 'label') }}</p>
                            <p class="text-xs text-slate-500 leading-relaxed font-medium">{{ data_get($achievement, 'description') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if(count($landingItems('partners.items')) > 0)
        <!-- Part 2: Cooperating Institutions & Schools (Logo Lembaga yang Bekerjasama) -->
        <div class="space-y-12 border-t border-slate-100 pt-16">
            <div class="text-center">
                <span class="text-xs sm:text-sm font-bold uppercase tracking-widest text-slate-400">{{ $landingValue('partners.eyebrow', 'Lembaga & Sekolah Mitra Kerja Sama') }}</span>
                <p class="text-xs text-slate-400 font-medium mt-1">{{ $landingValue('partners.description', 'Kami bekerjasama secara resmi dengan sekolah mitra dalam menyelenggarakan tryout nasional & sosialisasi PTN') }}</p>
                <div class="mt-3.5 h-0.5 w-12 bg-primary/30 mx-auto rounded"></div>
            </div>

            <!-- Grid of Cooperating Schools (Lembaga Bekerjasama) -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-6">
                @foreach($landingItems('partners.items') as $partner)
                    <div class="flex flex-col items-center justify-center text-center p-4 rounded-2xl border border-slate-100 bg-white hover:border-primary/25 hover:shadow-2xs transition-all duration-300 group">
                        <img src="{{ $landingAsset(data_get($partner, 'logo'), 'img/logo_kampus.png') }}" alt="{{ data_get($partner, 'alt', 'Logo ' . data_get($partner, 'name', 'Mitra')) }}" class="h-12 w-12 object-contain rounded-xl mb-3 filter grayscale group-hover:grayscale-0 transition-all duration-300">
                        <p class="text-xs font-bold text-slate-800 leading-tight">{{ data_get($partner, 'name') }}</p>
                        <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">{{ data_get($partner, 'location') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>

<!-- Section 6: FAQ (Alpine.js Accordion) -->
<section class="border-t border-slate-100 bg-slate-50/50 py-16 sm:py-24">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-12">
        <div class="text-center space-y-3">
            <span class="text-primary font-extrabold tracking-widest uppercase text-xs sm:text-sm block">{{ $landingValue('faq.eyebrow', 'Pertanyaan Umum') }}</span>
            <h2 class="text-2xl sm:text-3.5xl font-black text-slate-900 leading-tight">{{ $landingValue('faq.title', 'FAQ (Frequently Asked Questions)') }}</h2>
        </div>

        <div x-data="{ activeIndex: null }" class="space-y-4">
            @foreach($landingItems('faq.items') as $faqIndex => $faq)
                <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden transition-all duration-200">
                    <button @click="activeIndex = (activeIndex === {{ $faqIndex }} ? null : {{ $faqIndex }})"
                            class="w-full text-left px-6 py-5 flex items-center justify-between gap-4 font-bold text-slate-800 text-sm sm:text-base focus:outline-none select-none">
                        <span>{{ data_get($faq, 'question') }}</span>
                        <i :class="activeIndex === {{ $faqIndex }} ? 'ri-subtract-line text-primary' : 'ri-add-line text-slate-400'" class="text-xl transition-transform"></i>
                    </button>
                    <div x-show="activeIndex === {{ $faqIndex }}"
                         x-collapse
                         class="px-6 pb-6 pt-2 border-t border-slate-150 text-xs sm:text-sm font-semibold text-slate-600 leading-relaxed"
                         style="display: none;">
                        {{ data_get($faq, 'answer') }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Section 7: Kontak & Footer Khusus Landing Page -->
<footer class="bg-primary text-white border-t border-primary-hover pt-16 pb-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 grid gap-12 md:grid-cols-12 pb-12 border-b border-white/10">
        <!-- Logo & Description -->
        <div class="md:col-span-6 space-y-6">
            <div class="flex items-center gap-3">
                <img src="{{ $clientBranding['logo_url'] }}" alt="{{ $clientBranding['name'] }} Logo"
                     class="h-12 w-12 rounded-xl object-contain bg-white p-1 shadow-md">
                <div>
                    <h3 class="text-lg sm:text-xl font-bold leading-none">{{ $clientBranding['name'] }}</h3>
                    <p class="text-xs text-white/70 font-medium mt-1">{{ $landingValue('footer.tagline', 'Platform Sukses Tembus PTN Impian') }}</p>
                </div>
            </div>
            <p class="text-sm text-slate-100/80 font-medium leading-relaxed max-w-md">
                {{ $landingValue('footer.description', 'Penyedia layanan bimbingan belajar, tryout IRT online nasional, pendampingan konsultasi jurusan, serta rasionalisasi rapor seleksi SNBP/SNBT terpercaya di Indonesia.') }}
            </p>
        </div>

        <!-- Services Menu Link -->
        <div class="md:col-span-3 space-y-4">
            <h4 class="font-bold text-sm sm:text-base tracking-wide uppercase text-white/95">{{ $landingValue('footer.navigation_title', 'Navigasi') }}</h4>
            <ul class="space-y-3 text-xs sm:text-sm font-semibold text-slate-200/90 list-disc pl-5">
                <li><a href="{{ route('landing') }}" class="hover:text-amber-300 transition-colors">{{ $landingValue('footer.nav_landing_label', 'Home Landing') }}</a></li>
                @if($showStatisticsNav)
                    <li><a href="{{ route('statistics') }}" class="hover:text-amber-300 transition-colors">{{ $landingValue('footer.nav_statistics_snbp_label', 'Statistik PTN SNBP') }}</a></li>
                    <li><a href="{{ route('statistics.snbt') }}" class="hover:text-amber-300 transition-colors">{{ $landingValue('footer.nav_statistics_snbt_label', 'Statistik PTN SNBT') }}</a></li>
                @endif
                @if($showArticlesNav)
                    <li><a href="{{ route('articles.index') }}" class="hover:text-amber-300 transition-colors">{{ $landingValue('footer.nav_articles_label', 'Insight & Artikel') }}</a></li>
                @endif
                <li><a href="{{ route('login') }}" class="hover:text-amber-300 transition-colors">{{ $landingValue('footer.nav_login_label', 'Daftar / Login Akun') }}</a></li>
            </ul>
        </div>

        <!-- Contact Links -->
        <div class="md:col-span-3 space-y-4">
            <h4 class="font-bold text-sm sm:text-base tracking-wide uppercase text-white/95">{{ $landingValue('footer.contact_title', 'Hubungi Kami') }}</h4>
            <ul class="space-y-3 text-xs sm:text-sm font-semibold text-slate-200/90">
                <li>
                    <a href="{{ $landingValue('footer.instagram_href', 'https://instagram.com/naufalacademy') }}" target="_blank" rel="noopener noreferrer"
                       class="flex items-center gap-2.5 hover:text-amber-300 transition-colors">
                        <i class="ri-instagram-line text-base"></i>
                        {{ $landingValue('footer.instagram_label', '@naufalacademy') }}
                    </a>
                </li>
                <li>
                    <a href="{{ $landingValue('footer.whatsapp_href', 'https://wa.me/628561078411?text=Halo%2520Admin%2520saya%2520Ingin%2520Bertanya') }}" target="_blank" rel="noopener noreferrer"
                       class="flex items-center gap-2.5 hover:text-amber-300 transition-colors">
                        <i class="ri-whatsapp-line text-base"></i>
                        {{ $landingValue('footer.whatsapp_label', '+62 856-1078-411') }}
                    </a>
                </li>
                <li>
                    <a href="{{ $landingValue('footer.email_href', 'mailto:team.naufalacademy@gmail.com') }}"
                       class="flex items-center gap-2.5 hover:text-amber-300 transition-colors">
                        <i class="ri-mail-line text-base"></i>
                        {{ $landingValue('footer.email_label', 'team.naufalacademy@gmail.com') }}
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 text-xs sm:text-sm font-semibold text-slate-300 text-center">
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
@endsection
