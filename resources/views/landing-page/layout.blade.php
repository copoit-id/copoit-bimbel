<!DOCTYPE html>
<html class="light" lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'Landing Page')</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Remix Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.8.0/fonts/remixicon.css" rel="stylesheet">
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
    @stack('styles')
</head>

<body class="bg-[#f6f7f8] font-display text-[#111418] overflow-x-hidden flex flex-col min-h-screen">
    <!-- Navbar -->
    <nav class="sticky top-0 z-50 w-full bg-white border-b border-[#f0f2f4]">
        <div class="px-4 sm:px-6 lg:px-8 flex justify-center">
            <div class="flex flex-1 items-center justify-between max-w-6xl py-3">
                <div class="flex items-center gap-2 text-[#111418]">
                    <img class="w-12 h-12 object-cover" src="{{ $clientBranding['logo_url'] }}" alt="{{ $clientBranding['name'] }} Logo">
                    <div class="flex flex-col justify-start">
                        <p class="text-[20px] font-bold">{{ $clientBranding['name'] }}</p>
                        <p class="text-[13px] font-light mt-[-6px] text-[#617589]">Learning Platform</p>
                    </div>
                </div>
                
                @php
                    $dashboardRoute = auth()->check()
                        ? (auth()->user()->role === 'admin' ? route('admin.dashboard') : route('user.dashboard.index'))
                        : null;
                    $dashboardLabel = auth()->check()
                        ? (auth()->user()->role === 'admin' ? 'Dashboard Admin' : 'Dashboard')
                        : null;
                @endphp

                <!-- Desktop Menu -->
                <div class="hidden md:flex flex-1 justify-end gap-8 items-center">
                    <div class="flex items-center gap-6 lg:gap-9">
                        <a class="text-base font-medium leading-normal hover:text-primary transition-colors" href="#hero">Beranda</a>
                        <a class="text-base font-medium leading-normal hover:text-primary transition-colors" href="#features">Program</a>
                        <a class="text-base font-medium leading-normal hover:text-primary transition-colors" href="#testimonials">Testimoni</a>
                        <a class="text-base font-medium leading-normal hover:text-primary transition-colors" href="#gallery">Gallery</a>
                    </div>
                    @guest
                    <a href="{{ route('login') }}" class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-base font-bold leading-normal tracking-[0.015em] hover:bg-blue-600 transition-colors">
                        <span class="truncate">Login</span>
                    </a>
                    @else
                        <div class="relative">
                            <button id="profile-menu-button" type="button" class="flex items-center gap-2 rounded-full border border-[#e5e7eb] bg-white px-3 py-2 text-base font-medium text-[#111418] hover:border-primary transition-colors" aria-haspopup="true" aria-expanded="false">
                                <span class="flex size-8 items-center justify-center rounded-full bg-primary text-white text-sm font-bold">
                                    {{ strtoupper(substr(trim(auth()->user()->name), 0, 1)) }}
                                </span>
                                <span class="hidden lg:inline max-w-[140px] truncate">{{ auth()->user()->name }}</span>
                                <i class="ri-arrow-down-s-line text-lg"></i>
                            </button>
                            <div id="profile-menu" class="absolute right-0 mt-2 w-48 rounded-lg border border-[#e5e7eb] bg-white shadow-lg hidden">
                                <a href="{{ $dashboardRoute }}" class="block px-4 py-2 text-sm text-[#111418] hover:bg-[#f6f7f8]">
                                    {{ $dashboardLabel }}
                                </a>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-[#111418] hover:bg-[#f6f7f8]">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endguest
                </div>
                
                <!-- Mobile Menu Icon -->
                <div class="md:hidden flex items-center">
                    <button onclick="toggleMobileMenu()" class="text-xl text-[#111418]" aria-label="Toggle menu">
                        <i class="ri-menu-line"></i>
                    </button>
                </div>
            </div>
            
            <!-- Mobile menu -->
            <div class="md:hidden">
                <div id="mobile-menu-overlay" class="fixed inset-0 bg-black/40 opacity-0 pointer-events-none transition-opacity"></div>
                <div id="mobile-menu" class="fixed top-0 right-0 h-full w-72 bg-white shadow-xl translate-x-full transition-transform">
                    <div class="flex items-center justify-between px-4 py-4 border-b border-[#f0f2f4]">
                        <span class="text-sm font-semibold text-[#111418]">Menu</span>
                        <button onclick="toggleMobileMenu()" class="text-xl text-[#111418]" aria-label="Close menu">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                    <div class="px-4 py-4 space-y-2">
                        <a href="#hero" class="text-[#111418] hover:text-primary block px-3 py-2 text-lg font-medium transition-colors">Beranda</a>
                        <a href="#features" class="text-[#111418] hover:text-primary block px-3 py-2 text-lg font-medium transition-colors">Program</a>
                        <a href="#testimonials" class="text-[#111418] hover:text-primary block px-3 py-2 text-lg font-medium transition-colors">Testimoni</a>
                        <a href="#gallery" class="text-[#111418] hover:text-primary block px-3 py-2 text-lg font-medium transition-colors">Gallery</a>
                    </div>
                    <div class="px-4 pb-6">
                        @guest
                            <a href="{{ route('login') }}" class="flex w-full items-center justify-center rounded-lg h-10 px-4 bg-primary text-white text-base font-bold tracking-[0.015em] hover:bg-blue-600 transition-colors">
                                Login
                            </a>
                        @else
                            <div class="flex items-center gap-3 px-3 py-2 mb-3 rounded-lg bg-[#f6f7f8]">
                                <span class="flex size-9 items-center justify-center rounded-full bg-primary text-white text-sm font-bold">
                                    {{ strtoupper(substr(trim(auth()->user()->name), 0, 1)) }}
                                </span>
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-[#111418] truncate">{{ auth()->user()->name }}</div>
                                    <div class="text-xs text-[#617589] truncate">{{ auth()->user()->email }}</div>
                                </div>
                            </div>
                            <a href="{{ $dashboardRoute }}" class="flex w-full items-center justify-center rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold tracking-[0.015em] hover:bg-blue-600 transition-colors">
                                {{ $dashboardLabel }}
                            </a>
                            <form action="{{ route('logout') }}" method="POST" class="mt-2">
                                @csrf
                                <button type="submit" class="flex w-full items-center justify-center rounded-lg h-10 px-4 border border-[#e5e7eb] text-sm font-semibold text-[#111418] hover:bg-[#f6f7f8] transition-colors">
                                    Logout
                                </button>
                            </form>
                        @endguest
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow flex flex-col items-center w-full">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="w-full bg-[#f0f2f4] border-t border-[#dbe0e6]">
        <div class="px-4 sm:px-6 lg:px-8 flex justify-center py-12">
            <div class="flex flex-col md:flex-row justify-between max-w-6xl flex-1 gap-10">
                <!-- Brand -->
                <div class="flex flex-col gap-4 max-w-[300px]">
                    <div class="flex items-center gap-2 text-[#111418]">
                        <img class="h-6 w-auto" src="{{ $clientBranding['logo_url'] }}" alt="{{ $clientBranding['name'] }} Logo">
                        <h3 class="text-lg font-bold">{{ $clientBranding['name'] }}</h3>
                    </div>
                    <p class="text-[#617589] text-sm leading-relaxed">Platform belajar terbaik untuk membantu siswa meraih prestasi akademik dengan cara yang menyenangkan.</p>
                </div>
                
                <!-- Links 1 -->
                <div class="flex flex-col gap-3">
                    <h4 class="text-[#111418] font-bold text-sm uppercase tracking-wider">Program</h4>
                    <a class="text-[#617589] text-sm hover:text-primary transition-colors" href="#features">Keunggulan</a>
                    <a class="text-[#617589] text-sm hover:text-primary transition-colors" href="#gallery">Gallery</a>
                    <a class="text-[#617589] text-sm hover:text-primary transition-colors" href="#testimonials">Testimoni</a>
                </div>
                
                <!-- Links 2 -->
                <div class="flex flex-col gap-3">
                    <h4 class="text-[#111418] font-bold text-sm uppercase tracking-wider">Perusahaan</h4>
                    <a class="text-[#617589] text-sm hover:text-primary transition-colors" href="#">Tentang Kami</a>
                    <a class="text-[#617589] text-sm hover:text-primary transition-colors" href="#">Karir</a>
                    <a class="text-[#617589] text-sm hover:text-primary transition-colors" href="#">Blog</a>
                    <a class="text-[#617589] text-sm hover:text-primary transition-colors" href="#">Kebijakan Privasi</a>
                </div>
                
                <!-- Contact -->
                <div class="flex flex-col gap-3">
                    <h4 class="text-[#111418] font-bold text-sm uppercase tracking-wider">Hubungi Kami</h4>
                    <div class="flex items-center gap-2 text-[#617589] text-sm">
                        <i class="ri-mail-line text-base"></i>
                        <span>info@bimbel.com</span>
                    </div>
                    <div class="flex items-center gap-2 text-[#617589] text-sm">
                        <i class="ri-phone-line text-base"></i>
                        <span>+62 812-3456-7890</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="w-full bg-[#e5e7eb] py-4 text-center">
            <p class="text-[#617589] text-xs">© {{ date('Y') }} {{ $clientBranding['name'] }}. All rights reserved.</p>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>
