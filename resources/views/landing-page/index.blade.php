@extends('landing-page.layout')

@section('title', 'Landing Page - ' . $clientBranding['name'])

@section('content')

<style>
:root {
    --primary-color: {{ $clientBranding['primary_color'] }};
}
.bg-primary-dynamic { background-color: var(--primary-color) !important; }
.text-primary-dynamic { color: var(--primary-color) !important; }
.border-primary-dynamic { border-color: var(--primary-color) !important; }
.hover\:bg-primary-dynamic:hover { background-color: var(--primary-color) !important; opacity: 0.9; }
.bg-primary-50-dynamic { background-color: color-mix(in srgb, var(--primary-color) 8%, white) !important; }
.bg-primary-100-dynamic { background-color: color-mix(in srgb, var(--primary-color) 15%, white) !important; }
</style>

<!-- Hero Section -->
@if($hero)
<section id="hero" class="w-full py-16 lg:py-24 bg-white">
    <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div class="text-center lg:text-left">
                <!-- Badge -->
                <div class="inline-flex items-center bg-primary-50-dynamic text-primary-dynamic rounded-full px-4 py-2 text-sm font-medium mb-6">
                    <i class="ri-verified-badge-line text-sm mr-2"></i>
                    Platform Belajar Terpercaya
                </div>
                
                <h1 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-6 leading-tight">
                    {{ $hero->title }}
                </h1>
                
                <p class="text-xl text-gray-600 mb-6 leading-relaxed">
                    {{ $hero->subtitle }}
                </p>
                
                @if($hero->description)
                <p class="text-gray-600 mb-8 leading-relaxed max-w-lg mx-auto lg:mx-0">
                    {{ $hero->description }}
                </p>
                @endif
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    @if($hero->button_link)
                    <a href="{{ $hero->button_link }}" class="inline-flex items-center justify-center bg-primary-dynamic text-white px-8 py-3 rounded-lg font-medium hover:bg-primary-dynamic transition-colors">
                        {{ $hero->button_text }}
                        <i class="ri-arrow-right-line text-sm ml-2"></i>
                    </a>
                    @endif
                    <a href="#features" class="inline-flex items-center justify-center border border-gray-300 text-gray-700 px-8 py-3 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                        Pelajari Lebih Lanjut
                    </a>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-6 mt-12 pt-8 border-t border-gray-200">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ $hero->stat_1_number }}</div>
                        <div class="text-sm text-gray-600">{{ $hero->stat_1_text }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ $hero->stat_2_number }}</div>
                        <div class="text-sm text-gray-600">{{ $hero->stat_2_text }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ $hero->stat_3_number }}</div>
                        <div class="text-sm text-gray-600">{{ $hero->stat_3_text }}</div>
                    </div>
                </div>
            </div>
            
            @if($hero->image)
            <div class="flex justify-center lg:justify-end">
                <div class="relative">
                    <img src="{{ asset('storage/' . $hero->image) }}" alt="{{ $hero->title }}" 
                         class="w-full max-w-lg h-auto rounded-2xl shadow-lg">
                </div>
            </div>
            @endif
        </div>
    </div>
</section>
@endif

<!-- Features Section -->
@if($features->count() > 0)
<section id="features" class="w-full py-16 lg:py-24 bg-gray-50">
    <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <div class="inline-flex items-center bg-primary-50-dynamic text-primary-dynamic rounded-full px-4 py-2 text-sm font-medium mb-6">
                <i class="ri-star-line text-sm mr-2"></i>
                Keunggulan Kami
            </div>
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-6">
                Mengapa Memilih Kami?
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                Berbagai keunggulan yang membuat kami menjadi pilihan terbaik untuk meraih impian akademik Anda.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($features as $feature)
            <div class="bg-white rounded-2xl p-8 shadow-sm hover:shadow-md transition-shadow border border-gray-100">
                <!-- Icon -->
                <div class="w-12 h-12 bg-primary-100-dynamic rounded-xl flex items-center justify-center mb-6">
                    @if($feature->icon)
                        <i class="{{ $feature->icon }} text-primary-dynamic"></i>
                    @else
                        <i class="ri-star-line text-primary-dynamic"></i>
                    @endif
                </div>
                
                <h3 class="text-xl font-semibold text-gray-900 mb-4">
                    {{ $feature->title }}
                </h3>
                <p class="text-gray-600 leading-relaxed">
                    {{ $feature->description }}
                </p>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Gallery Section -->
@if($gallery->count() > 0)
<section id="gallery" class="w-full py-16 lg:py-24 bg-white">
    <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <div class="inline-flex items-center bg-primary-50-dynamic text-primary-dynamic rounded-full px-4 py-2 text-sm font-medium mb-6">
                <i class="ri-image-2-line text-sm mr-2"></i>
                Portfolio Kami
            </div>
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-6">
                Dokumentasi Kegiatan
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                Lihat berbagai kegiatan pembelajaran, fasilitas modern, dan momen kebahagiaan siswa kami.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($gallery as $item)
            <div class="group relative overflow-hidden rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow">
                <!-- Image container -->
                <div class="relative overflow-hidden rounded-2xl h-64">
                    <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->title }}" 
                         class="w-full h-full object-cover">
                    
                    <!-- Overlay -->
                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-all duration-300 flex items-center justify-center">
                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center">
                            <i class="ri-eye-line text-gray-900"></i>
                        </div>
                    </div>
                    
                    <!-- Category badge -->
                    @if($item->category)
                    <div class="absolute top-4 left-4 bg-primary-dynamic text-white px-3 py-1 rounded-full text-xs font-medium">
                        {{ $item->category }}
                    </div>
                    @endif
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                        {{ $item->title }}
                    </h3>
                    @if($item->description)
                    <p class="text-gray-600 text-sm leading-relaxed">
                        {{ $item->description }}
                    </p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Testimonials Section -->
@if($testimonials->count() > 0)
<section id="testimonials" class="w-full py-16 lg:py-24 bg-gray-50">
    <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <div class="inline-flex items-center bg-primary-50-dynamic text-primary-dynamic rounded-full px-4 py-2 text-sm font-medium mb-6">
                <i class="ri-double-quotes-l text-sm mr-2"></i>
                Testimoni
            </div>
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-6">
                Apa Kata Mereka
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                Dengar langsung pengalaman dan testimoni dari siswa-siswa yang telah merasakan manfaatnya.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($testimonials as $testimonial)
            <div class="bg-white rounded-2xl p-8 shadow-sm hover:shadow-md transition-shadow border border-gray-100">
                <!-- Rating stars -->
                <div class="flex items-center mb-6">
                    <div class="flex space-x-1">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= $testimonial->rating)
                                <i class="ri-star-fill text-yellow-400 text-sm"></i>
                            @else
                                <i class="ri-star-fill text-gray-300 text-sm"></i>
                            @endif
                        @endfor
                    </div>
                    <span class="ml-3 text-sm font-medium text-gray-600">
                        {{ $testimonial->rating }}.0
                    </span>
                </div>
                
                <!-- Testimonial content -->
                <blockquote class="text-gray-700 leading-relaxed mb-8">
                    "{{ $testimonial->content }}"
                </blockquote>
                
                <!-- Author info -->
                <div class="flex items-center">
                    <div class="relative">
                        @if($testimonial->photo)
                            <img src="{{ asset('storage/' . $testimonial->photo) }}" alt="{{ $testimonial->name }}" 
                                 class="w-12 h-12 rounded-full object-cover">
                        @else
                            <div class="w-12 h-12 bg-primary-100-dynamic rounded-full flex items-center justify-center">
                                <i class="ri-user-line text-primary-dynamic"></i>
                            </div>
                        @endif
                    </div>
                    <div class="ml-4">
                        <h4 class="font-semibold text-gray-900">
                            {{ $testimonial->name }}
                        </h4>
                        @if($testimonial->position)
                        <p class="text-sm text-gray-600">
                            {{ $testimonial->position }}
                        </p>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        <!-- CTA Section -->
        @if($cta)
        <div class="mt-16 text-center">
            <div class="bg-primary-dynamic rounded-2xl p-8 shadow-sm text-white max-w-4xl mx-auto">
                <div class="flex items-center justify-center mb-6">
                    <div class="flex -space-x-2">
                        @for($i = 1; $i <= 4; $i++)
                        <div class="w-10 h-10 bg-white/20 rounded-full border-2 border-white flex items-center justify-center">
                            <i class="ri-user-3-line text-white text-xs"></i>
                        </div>
                        @endfor
                        <div class="w-10 h-10 bg-white/20 rounded-full border-2 border-white flex items-center justify-center">
                            <span class="text-xs text-white font-medium">+</span>
                        </div>
                    </div>
                </div>
                
                <h3 class="text-2xl lg:text-3xl font-bold mb-4">
                    {{ $cta->title }}
                </h3>
                <p class="text-white/90 mb-8 text-lg leading-relaxed">
                    {{ $cta->description }}
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    @guest
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center bg-white text-gray-900 px-8 py-4 rounded-lg text-lg font-medium hover:bg-gray-100 transition-colors shadow-lg">
                        <i class="ri-user-add-line text-lg mr-3"></i>
                        {{ $cta->primary_button_text }}
                    </a>
                    
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center border-2 border-white/30 text-white px-8 py-4 rounded-lg text-lg font-medium hover:bg-white/10 transition-colors">
                        <i class="ri-login-box-line text-lg mr-3"></i>
                        {{ $cta->secondary_button_text }}
                    </a>
                    @else
                        @if(auth()->user()->role === 'admin')
                        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center bg-white text-gray-900 px-8 py-4 rounded-lg text-lg font-medium hover:bg-gray-100 transition-colors shadow-lg">
                            <i class="ri-dashboard-line text-lg mr-3"></i>
                            Dashboard Admin
                        </a>
                        @else
                        <a href="{{ route('user.dashboard.index') }}" class="inline-flex items-center justify-center bg-white text-gray-900 px-8 py-4 rounded-lg text-lg font-medium hover:bg-gray-100 transition-colors shadow-lg">
                            <i class="ri-dashboard-line text-lg mr-3"></i>
                            Dashboard
                        </a>
                        @endif
                    @endguest
                </div>
            </div>
        </div>
        @endif
    </div>
</section>
@endif

@endsection

@push('scripts')
<script>
    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const navHeight = document.getElementById('navbar') ? document.getElementById('navbar').offsetHeight : 0;
                const targetPosition = target.offsetTop - navHeight - 20;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
                
                closeMobileMenu();
            }
        });
    });
    
    // Mobile menu toggle
    function toggleMobileMenu() {
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileOverlay = document.getElementById('mobile-menu-overlay');
        if (!mobileMenu || !mobileOverlay) {
            return;
        }

        if (mobileMenu.classList.contains('translate-x-full')) {
            openMobileMenu();
        } else {
            closeMobileMenu();
        }
    }

    function openMobileMenu() {
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileOverlay = document.getElementById('mobile-menu-overlay');
        if (!mobileMenu || !mobileOverlay) {
            return;
        }
        mobileMenu.classList.remove('translate-x-full');
        mobileOverlay.classList.remove('opacity-0', 'pointer-events-none');
        document.body.classList.add('overflow-hidden');
    }

    function closeMobileMenu() {
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileOverlay = document.getElementById('mobile-menu-overlay');
        if (!mobileMenu || !mobileOverlay) {
            return;
        }
        mobileMenu.classList.add('translate-x-full');
        mobileOverlay.classList.add('opacity-0', 'pointer-events-none');
        document.body.classList.remove('overflow-hidden');
    }

    const mobileOverlay = document.getElementById('mobile-menu-overlay');
    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', closeMobileMenu);
    }

    // Profile dropdown
    const profileButton = document.getElementById('profile-menu-button');
    const profileMenu = document.getElementById('profile-menu');
    if (profileButton && profileMenu) {
        profileButton.addEventListener('click', function(event) {
            event.stopPropagation();
            profileMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', function() {
            if (!profileMenu.classList.contains('hidden')) {
                profileMenu.classList.add('hidden');
            }
        });
    }
    
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('navbar');
        if (navbar) {
            if (window.scrollY > 100) {
                navbar.classList.add('shadow-lg');
                navbar.classList.remove('shadow-sm');
            } else {
                navbar.classList.remove('shadow-lg');
                navbar.classList.add('shadow-sm');
            }
        }
    });
    
    // Back to top function
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
</script>
@endpush
