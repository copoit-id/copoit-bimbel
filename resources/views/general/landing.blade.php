@extends('general.layout')

@section('title', 'Persiapan Ujian UTBK SNBT & SNBP Terbaik')

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
                    Bimbel Persiapan UTBK 2026 #1
                </div>
                
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 leading-tight tracking-tight">
                    Siap Tembus <br class="hidden sm:block">
                    <span class="text-gradient">PTN Impian</span> Kamu?
                </h1>
                
                <p class="text-base sm:text-lg text-slate-600 max-w-xl leading-relaxed mx-auto lg:mx-0 font-medium">
                    BimbelHub memandu kamu memahami konsep materi terdalam, strategi memilih jurusan, dan taktik menjawab soal UTBK. Lengkap dengan Tryout IRT nasional, asisten Kak AI, dan bimbingan mentor alumni.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="{{ route('login') }}" 
                       class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-primary px-8 py-4 text-base font-bold text-white transition-all hover:bg-primary-hover shadow-md hover:shadow-lg active:scale-98">
                        Mulai Belajar Sekarang
                        <i class="ri-arrow-right-line text-lg"></i>
                    </a>
                    <a href="https://wa.me/628561078411?text=Halo%20Admin%20saya%20Ingin%20Tanya%20Program%20Bimbel" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="inline-flex items-center justify-center gap-2.5 rounded-xl border border-slate-200 bg-white px-8 py-4 text-base font-bold text-slate-700 transition-all hover:bg-slate-50 hover:border-slate-300 active:scale-98">
                        <i class="ri-whatsapp-line text-lg text-emerald-500"></i>
                        Hubungi Admin
                    </a>
                </div>

                <!-- Avatar Stack featuring Campus Logos as requested -->
                <div class="flex flex-col sm:flex-row items-center gap-4 pt-8 border-t border-slate-100 justify-center lg:justify-start">
                    <div class="flex -space-x-2.5">
                        <img class="h-9 w-9 rounded-full object-contain border-2 border-white bg-white p-0.5 shadow-xs" src="{{ asset('img/logo_kampus.png') }}" alt="UI Logo">
                        <img class="h-9 w-9 rounded-full object-contain border-2 border-white bg-white p-0.5 shadow-xs" src="{{ asset('img/logo_kampus.png') }}" alt="ITB Logo">
                        <img class="h-9 w-9 rounded-full object-contain border-2 border-white bg-white p-0.5 shadow-xs" src="{{ asset('img/logo_kampus.png') }}" alt="UGM Logo">
                        <img class="h-9 w-9 rounded-full object-contain border-2 border-white bg-white p-0.5 shadow-xs" src="{{ asset('img/logo_kampus.png') }}" alt="ITS Logo">
                    </div>
                    <p class="text-xs sm:text-sm font-semibold text-slate-650">
                        Bergabung bersama <span class="text-slate-900 font-extrabold">10.000+ Pejuang UTBK & SNBP</span> tahun ini!
                    </p>
                </div>
            </div>

            <!-- Right Column: Raw Illustration with rounded corners as requested (No background box container, blobs or shadows) -->
            <div class="lg:col-span-6 flex justify-center lg:justify-end">
                <img src="{{ asset('img/hero_study.png') }}" 
                     alt="Siswa Belajar UTBK Online" 
                     class="w-full max-w-[480px] aspect-square object-cover rounded-[32px]">
            </div>
        </div>
    </div>
</section>

<!-- Section 2: Program / Kelas yang Dibuka -->
<section id="program" class="border-b border-slate-100 bg-slate-50/50 py-16 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-primary font-extrabold tracking-widest uppercase text-xs sm:text-sm mb-3 block">Investasi Masa Depan</span>
            <h2 class="text-3xl sm:text-4.5xl font-black text-slate-900 leading-tight">Program Bimbingan Belajar Pilihan</h2>
            <p class="text-sm sm:text-base text-slate-550 mt-4 leading-relaxed font-medium">
                Pilih paket belajar persiapan ujian yang sesuai dengan kriteria target jurusan dan kampus favoritmu.
            </p>
        </div>

        <div class="grid gap-8 md:grid-cols-3 max-w-6xl mx-auto items-stretch">
            <!-- Paket 1: Free -->
            <div class="rounded-3xl border border-slate-200 bg-white p-8 flex flex-col justify-between hover:border-primary/20 transition-all duration-300 hover:shadow-md">
                <div class="space-y-6">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-2">Akses Uji Coba</span>
                        <h3 class="text-xl font-bold text-slate-800">Free Trial</h3>
                        <div class="mt-4 flex items-baseline gap-1">
                            <span class="text-3xl sm:text-4xl font-black text-slate-900">Rp 0</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium mt-3 leading-relaxed">Coba simulasi ujian dan rasakan kemudahan menggunakan platform bimbingan kami.</p>
                    </div>

                    <div class="h-px bg-slate-100"></div>

                    <ul class="space-y-3.5 text-xs sm:text-sm font-semibold text-slate-600">
                        <li class="flex items-center gap-2.5">
                            <i class="ri-checkbox-circle-fill text-primary text-base"></i>
                            Cek Daya Tampung PTN Se-Indonesia
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="ri-checkbox-circle-fill text-primary text-base"></i>
                            1x Latihan Tryout Ujian Mandiri
                        </li>
                        <li class="flex items-center gap-2.5 text-slate-350 line-through">
                            <i class="ri-close-circle-fill text-slate-250 text-base"></i>
                            Rasionalisasi Rapor SNBP Penuh
                        </li>
                        <li class="flex items-center gap-2.5 text-slate-350 line-through">
                            <i class="ri-close-circle-fill text-slate-250 text-base"></i>
                            Grup Tanya Jawab & Mentor Alumni
                        </li>
                    </ul>
                </div>

                <div class="pt-8">
                    <a href="{{ route('login') }}" 
                       class="flex w-full items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200/80 py-3.5 text-center text-xs sm:text-sm font-bold text-slate-700 transition-colors">
                        Daftar Akun Gratis
                    </a>
                </div>
            </div>

            <!-- Paket 2: Silver -->
            <div class="rounded-3xl border border-slate-200 bg-white p-8 flex flex-col justify-between hover:border-primary/20 transition-all duration-300 hover:shadow-md relative">
                <div class="space-y-6">
                    <div>
                        <span class="text-xs font-bold text-primary uppercase tracking-widest block mb-2">Pemetaan Peluang</span>
                        <h3 class="text-xl font-bold text-slate-800">Silver Package</h3>
                        <div class="mt-4 flex flex-col">
                            <span class="text-xs text-slate-400 font-bold line-through decoration-red-500 mb-0.5">Rp 69.000</span>
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl sm:text-4xl font-black text-slate-900">Rp 39.000</span>
                                <span class="text-2xs text-slate-400 font-bold uppercase tracking-wider">Sekali Bayar</span>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 font-medium mt-3 leading-relaxed">Analisis mendalam nilai rapor untuk memperkuat persiapan masuk jalur SNBP.</p>
                    </div>

                    <div class="h-px bg-slate-100"></div>

                    <ul class="space-y-3.5 text-xs sm:text-sm font-semibold text-slate-600">
                        <li class="flex items-center gap-2.5">
                            <i class="ri-checkbox-circle-fill text-primary text-base"></i>
                            Rasionalisasi Rapor SNBP Penuh
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="ri-checkbox-circle-fill text-primary text-base"></i>
                            3x Tryout UTBK Berskala Nasional
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="ri-checkbox-circle-fill text-primary text-base"></i>
                            Pembahasan Lembar Soal & Kunci Jawaban
                        </li>
                        <li class="flex items-center gap-2.5 text-slate-350 line-through">
                            <i class="ri-close-circle-fill text-slate-250 text-base"></i>
                            Pendampingan Konsultasi Jurusan Personal
                        </li>
                    </ul>
                </div>

                <div class="pt-8">
                    <a href="{{ route('login') }}" 
                       class="flex w-full items-center justify-center rounded-xl bg-primary hover:bg-primary-hover py-3.5 text-center text-xs sm:text-sm font-bold text-white transition-colors">
                        Upgrade ke Silver
                    </a>
                </div>
            </div>

            <!-- Paket 3: Gold (Terpopuler) -->
            <div class="rounded-3xl border-2 border-amber-400 bg-gradient-to-b from-white to-amber-50/20 p-8 flex flex-col justify-between hover:shadow-lg transition-all duration-300 relative transform lg:scale-103">
                <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-amber-500 px-4 py-1 text-[10px] font-black uppercase tracking-widest text-white shadow-md">PILIHAN TERBAIK</span>
                
                <div class="space-y-6">
                    <div>
                        <span class="text-xs font-bold text-amber-600 uppercase tracking-widest block mb-2 mt-1">Pendampingan Penuh</span>
                        <h3 class="text-xl font-bold text-slate-800">Gold Package</h3>
                        <div class="mt-4 flex flex-col">
                            <span class="text-xs text-slate-400 font-bold line-through decoration-red-500 mb-0.5">Rp 99.000</span>
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl sm:text-4xl font-black text-slate-900">Rp 49.000</span>
                                <span class="text-2xs text-slate-400 font-bold uppercase tracking-wider">Sekali Bayar</span>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 font-medium mt-3 leading-relaxed">Layanan bimbingan super komprehensif, didampingi secara langsung oleh mentor top PTN.</p>
                    </div>

                    <div class="h-px bg-slate-150"></div>

                    <ul class="space-y-3.5 text-xs sm:text-sm font-semibold text-slate-600">
                        <li class="flex items-center gap-2.5">
                            <i class="ri-checkbox-circle-fill text-amber-500 text-base"></i>
                            Rasionalisasi Peluang Rapor & Skor UTBK
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="ri-checkbox-circle-fill text-amber-500 text-base"></i>
                            Tryout UTBK Nasional <strong class="text-amber-600 font-bold">Unlimited</strong>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="ri-checkbox-circle-fill text-amber-500 text-base"></i>
                            Premium Akses Asisten Pintar Kak AI 24 Jam
                        </li>
                        <li class="flex items-start gap-2.5 bg-amber-500/10 border border-amber-200/50 p-3 rounded-xl">
                            <i class="ri-award-fill text-amber-650 text-xl shrink-0 mt-0.5"></i>
                            <span class="text-xs font-bold text-amber-950 leading-normal">Bimbingan & Konsultasi Jurusan 1-on-1 dengan Mentor Alumni UI/ITB/UGM</span>
                        </li>
                    </ul>
                </div>

                <div class="pt-8">
                    <a href="{{ route('login') }}" 
                       class="flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 py-3.5 text-center text-xs sm:text-sm font-extrabold text-white transition-all shadow-sm hover:shadow-md">
                        Upgrade ke Gold
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section 3: Komunitas Belajar (WhatsApp Group with Live Chat Preview) -->
<section class="py-16 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-primary to-primary-hover p-8 md:p-12 text-white border border-primary/10">
            <!-- Decorative light overlays -->
            <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
            <div class="absolute -left-20 -bottom-20 h-48 w-48 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>

            <div class="relative grid lg:grid-cols-12 gap-8 items-center z-10">
                <!-- Left: Content details -->
                <div class="lg:col-span-7 space-y-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-white font-extrabold uppercase tracking-widest text-[10px] sm:text-xs">
                        <i class="ri-wechat-line text-sm"></i>
                        Support System Pejuang PTN
                    </div>
                    <h3 class="text-2xl sm:text-3xl.5 font-black tracking-tight leading-tight">Komunitas Pejuang PTN {{ $clientBranding['name'] }}</h3>
                    <p class="text-sm sm:text-base text-slate-100/90 font-medium leading-relaxed max-w-2xl">
                        Jangan berjuang sendirian! Bergabunglah di grup WhatsApp diskusi kami untuk berbagi soal, info pendaftaran PTN, konsultasi, serta webinar gratis bersama alumni terkemuka.
                    </p>
                    <div class="pt-4">
                        <a href="https://chat.whatsapp.com/DO0KNXJVyoyAWK31EOoo3H" 
                           target="_blank" 
                           rel="noopener noreferrer" 
                           class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-white hover:bg-slate-50 px-8 py-4 text-base font-extrabold text-primary shadow-md hover:shadow-lg transition-all w-full sm:w-auto active:scale-98">
                            <i class="ri-whatsapp-line text-xl text-emerald-500"></i>
                            Gabung Grup Sekarang
                        </a>
                    </div>
                </div>

                <!-- Right: Realistic WhatsApp Chat Mockup -->
                <div class="lg:col-span-5">
                    <div class="rounded-2xl bg-[#ECE5DD] text-slate-800 p-4 shadow-xl border border-white/10 max-w-md mx-auto relative overflow-hidden">
                        <!-- Chat header mock -->
                        <div class="absolute top-0 left-0 right-0 bg-emerald-600 text-white px-4 py-2 flex items-center justify-between text-xs font-bold">
                            <div class="flex items-center gap-2">
                                <div class="h-6 w-6 rounded-full bg-white/20 flex items-center justify-center text-xs">💬</div>
                                <div>
                                    <p class="truncate font-extrabold">Grup Belajar UTBK {{ $clientBranding['name'] }}</p>
                                    <p class="text-[9px] text-emerald-100 font-medium">10,482 Anggota • Online</p>
                                </div>
                            </div>
                            <i class="ri-more-2-line text-lg"></i>
                        </div>
                        
                        <!-- Chat content spacing -->
                        <div class="space-y-3 pt-8 pb-1 text-[11px] sm:text-xs">
                            <!-- Msg 1 (Admin) -->
                            <div class="flex items-start gap-1.5 max-w-[85%]">
                                <div class="h-6 w-6 rounded-full bg-indigo-500 text-white flex items-center justify-center font-bold text-[9px] shrink-0 mt-0.5">AD</div>
                                <div class="bg-white p-2.5 rounded-lg rounded-tl-none shadow-2xs">
                                    <p class="font-extrabold text-primary text-[10px] mb-0.5">Admin BimbelHub</p>
                                    <p class="leading-normal font-semibold">Halo Pejuang! Hari ini Tryout Akbar SNBT ke-5 sudah dibuka ya. Pastikan gunakan skor IRT terbaru!</p>
                                </div>
                            </div>

                            <!-- Msg 2 (Rian) -->
                            <div class="flex items-start gap-1.5 max-w-[85%] ml-auto justify-end">
                                <div class="bg-[#DCF8C6] p-2.5 rounded-lg rounded-tr-none shadow-2xs text-right">
                                    <p class="font-extrabold text-slate-600 text-[10px] mb-0.5">Rian H. (ITB)</p>
                                    <p class="leading-normal font-semibold text-left">Bismillah, otw ngerjain target hari ini 720+! 💪</p>
                                </div>
                                <div class="h-6 w-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-[9px] shrink-0 mt-0.5">RH</div>
                            </div>

                            <!-- Msg 3 (Nanda) -->
                            <div class="flex items-start gap-1.5 max-w-[85%]">
                                <div class="h-6 w-6 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-[9px] shrink-0 mt-0.5">NP</div>
                                <div class="bg-white p-2.5 rounded-lg rounded-tl-none shadow-2xs">
                                    <p class="font-extrabold text-emerald-600 text-[10px] mb-0.5">Nanda P. (UI)</p>
                                    <p class="leading-normal font-semibold">Bahas soal Penalaran Matematika no 12 nanti malem ya guys.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section 4: Testimoni Siswa (Using Real Student Photo Images as requested) -->
<section class="border-y border-slate-100 bg-slate-50/50 py-16 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-primary font-extrabold tracking-widest uppercase text-xs sm:text-sm block mb-3">Kisah Sukses Pejuang</span>
            <h2 class="text-3xl sm:text-4.5xl font-black text-slate-900 leading-tight">Apa Kata Alumni Kami?</h2>
            <p class="text-sm sm:text-base text-slate-550 mt-4 leading-relaxed font-medium">
                Mereka telah membuktikan keakuratan data dan bimbingan kami, kini berhasil lolos ke prodi impian.
            </p>
        </div>

        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4 items-stretch">
            <!-- Testi 1 -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 flex flex-col justify-between hover:border-primary/25 hover:shadow-md transition-all duration-300">
                <div class="space-y-4">
                    <div class="flex items-center gap-1 text-amber-400">
                        <i class="ri-star-fill text-sm"></i>
                        <i class="ri-star-fill text-sm"></i>
                        <i class="ri-star-fill text-sm"></i>
                        <i class="ri-star-fill text-sm"></i>
                        <i class="ri-star-fill text-sm"></i>
                    </div>
                    <p class="text-xs sm:text-sm font-semibold text-slate-600 leading-relaxed italic">
                        "Tryout IRT di sini bener-bener mirip dengan ujian UTBK aslinya. Ranking nasionalnya bikin aku termotivasi untuk terus mengejar ketertinggalan materi."
                    </p>
                </div>
                <div class="flex items-center gap-3 pt-5 border-t border-slate-100 mt-6">
                    <!-- Real student photo instead of text initials -->
                    <img src="{{ asset('img/student_rian.png') }}" alt="Rian H." class="h-10 w-10 rounded-full object-cover shrink-0 border border-slate-200">
                    <div class="min-w-0">
                        <div class="flex items-center gap-1">
                            <h4 class="text-xs sm:text-sm font-bold text-slate-800 leading-none truncate">Rian H.</h4>
                            <i class="ri-checkbox-circle-fill text-emerald-500 text-xs" title="Alumni Terverifikasi"></i>
                        </div>
                        <p class="text-[10px] text-slate-400 font-bold mt-1">Lolos Teknik Sipil ITB</p>
                    </div>
                </div>
            </div>

            <!-- Testi 2 -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 flex flex-col justify-between hover:border-primary/25 hover:shadow-md transition-all duration-300">
                <div class="space-y-4">
                    <div class="flex items-center gap-1 text-amber-400">
                        <i class="ri-star-fill text-sm"></i>
                        <i class="ri-star-fill text-sm"></i>
                        <i class="ri-star-fill text-sm"></i>
                        <i class="ri-star-fill text-sm"></i>
                        <i class="ri-star-fill text-sm"></i>
                    </div>
                    <p class="text-xs sm:text-sm font-semibold text-slate-600 leading-relaxed italic">
                        "Fitur Kak AI ngebantu aku banget saat ngerjain soal fisika malam-malam. Penjelasan langkah demi langkahnya mudah dipahami dan cepat responnya!"
                    </p>
                </div>
                <div class="flex items-center gap-3 pt-5 border-t border-slate-100 mt-6">
                    <!-- Real student photo instead of text initials -->
                    <img src="{{ asset('img/student_nanda.png') }}" alt="Nanda P." class="h-10 w-10 rounded-full object-cover shrink-0 border border-slate-200">
                    <div class="min-w-0">
                        <div class="flex items-center gap-1">
                            <h4 class="text-xs sm:text-sm font-bold text-slate-800 leading-none truncate">Nanda P.</h4>
                            <i class="ri-checkbox-circle-fill text-emerald-500 text-xs" title="Alumni Terverifikasi"></i>
                        </div>
                        <p class="text-[10px] text-slate-400 font-bold mt-1">Lolos Farmasi UI</p>
                    </div>
                </div>
            </div>

            <!-- Testi 3 -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 flex flex-col justify-between hover:border-primary/25 hover:shadow-md transition-all duration-300">
                <div class="space-y-4">
                    <div class="flex items-center gap-1 text-amber-400">
                        <i class="ri-star-fill text-sm"></i>
                        <i class="ri-star-fill text-sm"></i>
                        <i class="ri-star-fill text-sm"></i>
                        <i class="ri-star-fill text-sm"></i>
                        <i class="ri-star-fill text-sm"></i>
                    </div>
                    <p class="text-xs sm:text-sm font-semibold text-slate-600 leading-relaxed italic">
                        "Terima kasih program bimbingan rapot SNBP-nya. Penjelasan mentor tentang strategi memilih prodi di UI dan UGM bikin aku mantap melangkah."
                    </p>
                </div>
                <div class="flex items-center gap-3 pt-5 border-t border-slate-100 mt-6">
                    <!-- Real student photo instead of text initials -->
                    <img src="{{ asset('img/student_farah.png') }}" alt="Farah D." class="h-10 w-10 rounded-full object-cover shrink-0 border border-slate-200">
                    <div class="min-w-0">
                        <div class="flex items-center gap-1">
                            <h4 class="text-xs sm:text-sm font-bold text-slate-800 leading-none truncate">Farah D.</h4>
                            <i class="ri-checkbox-circle-fill text-emerald-500 text-xs" title="Alumni Terverifikasi"></i>
                        </div>
                        <p class="text-[10px] text-slate-400 font-bold mt-1">Lolos Psikologi UGM</p>
                    </div>
                </div>
            </div>

            <!-- Testi 4 -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 flex flex-col justify-between hover:border-primary/25 hover:shadow-md transition-all duration-300">
                <div class="space-y-4">
                    <div class="flex items-center gap-1 text-amber-400">
                        <i class="ri-star-fill text-sm"></i>
                        <i class="ri-star-fill text-sm"></i>
                        <i class="ri-star-fill text-sm"></i>
                        <i class="ri-star-fill text-sm"></i>
                        <i class="ri-star-fill text-sm"></i>
                    </div>
                    <p class="text-xs sm:text-sm font-semibold text-slate-600 leading-relaxed italic">
                        "Sebagai siswa dari luar Jawa, akses materi UTBK premium di sini terjangkau dan sangat berkualitas dibandingkan bimbel tatap muka biasa."
                    </p>
                </div>
                <div class="flex items-center gap-3 pt-5 border-t border-slate-100 mt-6">
                    <!-- Real student photo instead of text initials -->
                    <img src="{{ asset('img/student_alvin.png') }}" alt="Alvin K." class="h-10 w-10 rounded-full object-cover shrink-0 border border-slate-200">
                    <div class="min-w-0">
                        <div class="flex items-center gap-1">
                            <h4 class="text-xs sm:text-sm font-bold text-slate-800 leading-none truncate">Alvin K.</h4>
                            <i class="ri-checkbox-circle-fill text-emerald-500 text-xs" title="Alumni Terverifikasi"></i>
                        </div>
                        <p class="text-[10px] text-slate-400 font-bold mt-1">Lolos Matematika ITS</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section 5: Pencapaian & Logo Lembaga / Sekolah Kerjasama (Fokus Detail Informasi) -->
<section class="bg-white py-16 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-20">
        <!-- Part 1: Achievements Details -->
        <div class="space-y-8">
            <div class="text-center max-w-3xl mx-auto">
                <span class="text-primary font-extrabold tracking-widest uppercase text-xs sm:text-sm mb-3 block">Pencapaian Terbaik Kami</span>
                <h3 class="text-2xl sm:text-3.5xl font-black text-slate-900 leading-tight">Bukti Nyata Kualitas Pendampingan BimbelHub</h3>
            </div>
            
            <div class="grid gap-8 sm:grid-cols-3">
                <!-- Achievement 1 -->
                <div class="rounded-3xl border-2 border-slate-200 bg-slate-50/30 p-8 flex flex-col justify-between hover:border-primary/40 transition-all duration-300 hover:shadow-xs">
                    <div class="space-y-4">
                        <!-- Replaced Emoji with High-quality Remixicon -->
                        <div class="h-12 w-12 rounded-2xl bg-primary/8 flex items-center justify-center text-primary border border-primary/20">
                            <i class="ri-trophy-line text-2xl"></i>
                        </div>
                        <h4 class="text-3xl sm:text-4.5xl font-black text-primary leading-none">92,4%</h4>
                        <p class="text-sm font-bold text-slate-850">Tingkat Kelolosan Ujian</p>
                        <p class="text-xs text-slate-500 leading-relaxed font-medium">9.240 dari total 10.000 siswa bimbingan kami berhasil lolos ke program studi & PTN pilihan ke-1 dan ke-2.</p>
                    </div>
                </div>

                <!-- Achievement 2 -->
                <div class="rounded-3xl border-2 border-slate-200 bg-slate-50/30 p-8 flex flex-col justify-between hover:border-primary/40 transition-all duration-300 hover:shadow-xs">
                    <div class="space-y-4">
                        <!-- Replaced Emoji with High-quality Remixicon -->
                        <div class="h-12 w-12 rounded-2xl bg-primary/8 flex items-center justify-center text-primary border border-primary/20">
                            <i class="ri-group-line text-2xl"></i>
                        </div>
                        <h4 class="text-3xl sm:text-4.5xl font-black text-primary leading-none">10.000+</h4>
                        <p class="text-sm font-bold text-slate-850">Pejuang PTN Aktif</p>
                        <p class="text-xs text-slate-500 leading-relaxed font-medium">Siswa terdaftar aktif berasal dari sekolah-sekolah unggulan mitra kami di seluruh wilayah Indonesia.</p>
                    </div>
                </div>

                <!-- Achievement 3 -->
                <div class="rounded-3xl border-2 border-slate-200 bg-slate-50/30 p-8 flex flex-col justify-between hover:border-primary/40 transition-all duration-300 hover:shadow-xs">
                    <div class="space-y-4">
                        <!-- Replaced Emoji with High-quality Remixicon -->
                        <div class="h-12 w-12 rounded-2xl bg-primary/8 flex items-center justify-center text-primary border border-primary/20">
                            <i class="ri-book-open-line text-2xl"></i>
                        </div>
                        <h4 class="text-3xl sm:text-4.5xl font-black text-primary leading-none">50.000+</h4>
                        <p class="text-sm font-bold text-slate-850">Bank Soal & Pembahasan</p>
                        <p class="text-xs text-slate-500 leading-relaxed font-medium">Koleksi bank soal terlengkap dari subtest TPS, Literasi Bahasa, dan Penalaran Matematika terupdate.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Part 2: Cooperating Institutions & Schools (Logo Lembaga yang Bekerjasama) -->
        <div class="space-y-12 border-t border-slate-100 pt-16">
            <div class="text-center">
                <span class="text-xs sm:text-sm font-bold uppercase tracking-widest text-slate-400">Lembaga & Sekolah Mitra Kerja Sama</span>
                <p class="text-xs text-slate-400 font-medium mt-1">Kami bekerjasama secara resmi dengan sekolah mitra dalam menyelenggarakan tryout nasional & sosialisasi PTN</p>
                <div class="mt-3.5 h-0.5 w-12 bg-primary/30 mx-auto rounded"></div>
            </div>
            
            <!-- Grid of Cooperating Schools (Lembaga Bekerjasama) -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-6">
                <!-- SMAN 8 Jakarta -->
                <div class="flex flex-col items-center justify-center text-center p-4 rounded-2xl border border-slate-100 bg-white hover:border-primary/25 hover:shadow-2xs transition-all duration-300 group">
                    <img src="{{ asset('img/logo_kampus.png') }}" alt="Logo SMAN 8 Jakarta" class="h-12 w-12 object-contain rounded-xl mb-3 filter grayscale group-hover:grayscale-0 transition-all duration-300">
                    <p class="text-xs font-bold text-slate-800 leading-tight">SMAN 8 Jakarta</p>
                    <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">DKI Jakarta</p>
                </div>

                <!-- SMAN 3 Bandung -->
                <div class="flex flex-col items-center justify-center text-center p-4 rounded-2xl border border-slate-100 bg-white hover:border-primary/25 hover:shadow-2xs transition-all duration-300 group">
                    <img src="{{ asset('img/logo_kampus.png') }}" alt="Logo SMAN 3 Bandung" class="h-12 w-12 object-contain rounded-xl mb-3 filter grayscale group-hover:grayscale-0 transition-all duration-300">
                    <p class="text-xs font-bold text-slate-800 leading-tight">SMAN 3 Bandung</p>
                    <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">Jawa Barat</p>
                </div>

                <!-- SMAN 1 Yogyakarta -->
                <div class="flex flex-col items-center justify-center text-center p-4 rounded-2xl border border-slate-100 bg-white hover:border-primary/25 hover:shadow-2xs transition-all duration-300 group">
                    <img src="{{ asset('img/logo_kampus.png') }}" alt="Logo SMAN 1 Yogyakarta" class="h-12 w-12 object-contain rounded-xl mb-3 filter grayscale group-hover:grayscale-0 transition-all duration-300">
                    <p class="text-xs font-bold text-slate-800 leading-tight">SMAN 1 Yogya</p>
                    <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">DI Yogyakarta</p>
                </div>

                <!-- SMAN 5 Surabaya -->
                <div class="flex flex-col items-center justify-center text-center p-4 rounded-2xl border border-slate-100 bg-white hover:border-primary/25 hover:shadow-2xs transition-all duration-300 group">
                    <img src="{{ asset('img/logo_kampus.png') }}" alt="Logo SMAN 5 Surabaya" class="h-12 w-12 object-contain rounded-xl mb-3 filter grayscale group-hover:grayscale-0 transition-all duration-300">
                    <p class="text-xs font-bold text-slate-800 leading-tight">SMAN 5 Surabaya</p>
                    <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">Jawa Timur</p>
                </div>

                <!-- SMA Labschool -->
                <div class="flex flex-col items-center justify-center text-center p-4 rounded-2xl border border-slate-100 bg-white hover:border-primary/25 hover:shadow-2xs transition-all duration-300 group">
                    <img src="{{ asset('img/logo_kampus.png') }}" alt="Logo SMA Labschool" class="h-12 w-12 object-contain rounded-xl mb-3 filter grayscale group-hover:grayscale-0 transition-all duration-300">
                    <p class="text-xs font-bold text-slate-800 leading-tight">SMA Labschool</p>
                    <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">DKI Jakarta</p>
                </div>

                <!-- SMA Kristen Yusuf -->
                <div class="flex flex-col items-center justify-center text-center p-4 rounded-2xl border border-slate-100 bg-white hover:border-primary/25 hover:shadow-2xs transition-all duration-300 group">
                    <img src="{{ asset('img/logo_kampus.png') }}" alt="Logo SMA Kristen Yusuf" class="h-12 w-12 object-contain rounded-xl mb-3 filter grayscale group-hover:grayscale-0 transition-all duration-300">
                    <p class="text-xs font-bold text-slate-800 leading-tight">SMA K. Yusuf</p>
                    <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">DKI Jakarta</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section 6: FAQ (Alpine.js Accordion) -->
<section class="border-t border-slate-100 bg-slate-50/50 py-16 sm:py-24">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-12">
        <div class="text-center space-y-3">
            <span class="text-primary font-extrabold tracking-widest uppercase text-xs sm:text-sm block">Pertanyaan Umum</span>
            <h2 class="text-2xl sm:text-3.5xl font-black text-slate-900 leading-tight">FAQ (Frequently Asked Questions)</h2>
        </div>

        <div x-data="{ activeIndex: null }" class="space-y-4">
            <!-- FAQ 1 -->
            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden transition-all duration-200">
                <button @click="activeIndex = (activeIndex === 1 ? null : 1)" 
                        class="w-full text-left px-6 py-5 flex items-center justify-between gap-4 font-bold text-slate-800 text-sm sm:text-base focus:outline-none select-none">
                    <span>Apakah saya bisa menggunakan platform ini secara gratis?</span>
                    <i :class="activeIndex === 1 ? 'ri-subtract-line text-primary' : 'ri-add-line text-slate-400'" class="text-xl transition-transform"></i>
                </button>
                <div x-show="activeIndex === 1" 
                     x-collapse 
                     class="px-6 pb-6 pt-2 border-t border-slate-150 text-xs sm:text-sm font-semibold text-slate-600 leading-relaxed" 
                     style="display: none;">
                    Ya, tentu saja! Kamu bisa menggunakan akun gratis untuk melihat data statistik program studi PTN se-Indonesia serta menguji coba 1x sistem simulasi tryout awal yang kami miliki.
                </div>
            </div>

            <!-- FAQ 2 -->
            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden transition-all duration-200">
                <button @click="activeIndex = (activeIndex === 2 ? null : 2)" 
                        class="w-full text-left px-6 py-5 flex items-center justify-between gap-4 font-bold text-slate-800 text-sm sm:text-base focus:outline-none select-none">
                    <span>Bagaimana sistem penilaian di simulasi Tryout UTBK?</span>
                    <i :class="activeIndex === 2 ? 'ri-subtract-line text-primary' : 'ri-add-line text-slate-400'" class="text-xl transition-transform"></i>
                </button>
                <div x-show="activeIndex === 2" 
                     x-collapse 
                     class="px-6 pb-6 pt-2 border-t border-slate-150 text-xs sm:text-sm font-semibold text-slate-600 leading-relaxed" 
                     style="display: none;">
                    Sistem penilaian tryout kami menggunakan algoritma Item Response Theory (IRT) yang disesuaikan dengan aturan penilaian resmi dari panitia pelaksana seleksi SNPMB BP3 Kemendikbud. Bobot nilai setiap soal dihitung berdasarkan tingkat kesulitan riil soal tersebut.
                </div>
            </div>

            <!-- FAQ 3 -->
            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden transition-all duration-200">
                <button @click="activeIndex = (activeIndex === 3 ? null : 3)" 
                        class="w-full text-left px-6 py-5 flex items-center justify-between gap-4 font-bold text-slate-800 text-sm sm:text-base focus:outline-none select-none">
                    <span>Apa itu fitur Rasionalisasi Rapor SNBP?</span>
                    <i :class="activeIndex === 3 ? 'ri-subtract-line text-primary' : 'ri-add-line text-slate-400'" class="text-xl transition-transform"></i>
                </button>
                <div x-show="activeIndex === 3" 
                     x-collapse 
                     class="px-6 pb-6 pt-2 border-t border-slate-150 text-xs sm:text-sm font-semibold text-slate-600 leading-relaxed" 
                     style="display: none;">
                    Rasionalisasi Rapor SNBP adalah fitur analisis kelayakan nilai rapor semester 1 sampai 5. Nilai rapor kamu akan dikalkulasikan dengan bobot mata pelajaran pendukung prodi yang dituju, dipetakan secara statistik, lalu dibandingkan dengan jutaan histori pendaftar lain di PTN pilihan Anda.
                </div>
            </div>

            <!-- FAQ 4 -->
            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden transition-all duration-200">
                <button @click="activeIndex = (activeIndex === 4 ? null : 4)" 
                        class="w-full text-left px-6 py-5 flex items-center justify-between gap-4 font-bold text-slate-800 text-sm sm:text-base focus:outline-none select-none">
                    <span>Apakah pembayaran paket berlaku langganan bulanan?</span>
                    <i :class="activeIndex === 4 ? 'ri-subtract-line text-primary' : 'ri-add-line text-slate-400'" class="text-xl transition-transform"></i>
                </button>
                <div x-show="activeIndex === 4" 
                     x-collapse 
                     class="px-6 pb-6 pt-2 border-t border-slate-150 text-xs sm:text-sm font-semibold text-slate-600 leading-relaxed" 
                     style="display: none;">
                    Tidak. Pembayaran paket bimbingan belajar (Silver maupun Gold) bersifat sekali bayar (*One-Time Payment*) di awal dan langsung aktif untuk masa kepesertaan penuh selama satu tahun penuh hingga seleksi ujian mandiri selesai.
                </div>
            </div>

            <!-- FAQ 5 -->
            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden transition-all duration-200">
                <button @click="activeIndex = (activeIndex === 5 ? null : 5)" 
                        class="w-full text-left px-6 py-5 flex items-center justify-between gap-4 font-bold text-slate-800 text-sm sm:text-base focus:outline-none select-none">
                    <span>Bagaimana asisten cerdas Kak AI membantu saya?</span>
                    <i :class="activeIndex === 5 ? 'ri-subtract-line text-primary' : 'ri-add-line text-slate-400'" class="text-xl transition-transform"></i>
                </button>
                <div x-show="activeIndex === 5" 
                     x-collapse 
                     class="px-6 pb-6 pt-2 border-t border-slate-150 text-xs sm:text-sm font-semibold text-slate-600 leading-relaxed" 
                     style="display: none;">
                    Kak AI terintegrasi dengan model AI canggih. Kamu cukup mengetik pertanyaan atau mengunggah gambar/foto soal latihan yang sulit, dan Kak AI akan memberikan panduan penjelasan langkah-demi-langkah, rumus pelengkap, serta tips cepat mengerjakannya.
                </div>
            </div>
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
                    <p class="text-xs text-white/70 font-medium mt-1">Platform Sukses Tembus PTN Impian</p>
                </div>
            </div>
            <p class="text-sm text-slate-100/80 font-medium leading-relaxed max-w-md">
                Penyedia layanan bimbingan belajar, tryout IRT online nasional, pendampingan konsultasi jurusan, serta rasionalisasi rapor seleksi SNBP/SNBT terpercaya di Indonesia.
            </p>
        </div>

        <!-- Services Menu Link -->
        <div class="md:col-span-3 space-y-4">
            <h4 class="font-bold text-sm sm:text-base tracking-wide uppercase text-white/95">Navigasi</h4>
            <ul class="space-y-3 text-xs sm:text-sm font-semibold text-slate-200/90 list-disc pl-5">
                <li><a href="{{ route('landing') }}" class="hover:text-amber-300 transition-colors">Home Landing</a></li>
                <li><a href="{{ route('statistics') }}" class="hover:text-amber-300 transition-colors">Statistik PTN</a></li>
                <li><a href="{{ route('articles.index') }}" class="hover:text-amber-300 transition-colors">Insight & Artikel</a></li>
                <li><a href="{{ route('login') }}" class="hover:text-amber-300 transition-colors">Daftar / Login Akun</a></li>
            </ul>
        </div>

        <!-- Contact Links -->
        <div class="md:col-span-3 space-y-4">
            <h4 class="font-bold text-sm sm:text-base tracking-wide uppercase text-white/95">Hubungi Kami</h4>
            <ul class="space-y-3 text-xs sm:text-sm font-semibold text-slate-200/90">
                <li>
                    <a href="https://instagram.com/naufalacademy" target="_blank" rel="noopener noreferrer" 
                       class="flex items-center gap-2.5 hover:text-amber-300 transition-colors">
                        <i class="ri-instagram-line text-base"></i>
                        @naufalacademy
                    </a>
                </li>
                <li>
                    <a href="https://wa.me/628561078411?text=Halo%2520Admin%2520saya%2520Ingin%2520Bertanya" target="_blank" rel="noopener noreferrer"
                       class="flex items-center gap-2.5 hover:text-amber-300 transition-colors">
                        <i class="ri-whatsapp-line text-base"></i>
                        +62 856-1078-411
                    </a>
                </li>
                <li>
                    <a href="mailto:team.naufalacademy@gmail.com" 
                       class="flex items-center gap-2.5 hover:text-amber-300 transition-colors">
                        <i class="ri-mail-line text-base"></i>
                        team.naufalacademy@gmail.com
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 text-xs sm:text-sm font-semibold text-slate-300 text-center">
        <p>&copy; {{ date('Y') }} {{ $clientBranding['name'] }}. Hak cipta dilindungi undang-undang.</p>
        <div class="flex gap-4 justify-center">
            <a href="#" class="hover:text-white">Syarat & Ketentuan</a>
            <a href="#" class="hover:text-white">Kebijakan Privasi</a>
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
