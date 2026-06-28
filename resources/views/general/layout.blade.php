<!DOCTYPE html>
<html lang="id">

@php
    $generalNavPages = \Illuminate\Support\Facades\Schema::hasTable('general_pages')
        ? \App\Models\GeneralPage::query()
            ->whereIn('page_key', ['landing', 'statistik-ptn', 'artikel'])
            ->where('is_active', true)
            ->pluck('is_active', 'page_key')
        : collect();
    $showLandingNav = (bool) $generalNavPages->get('landing', false);
    $showStatisticsNav = (bool) $generalNavPages->get('statistik-ptn', false);
    $showArticlesNav = (bool) $generalNavPages->get('artikel', false);
    $homeRoute = $showLandingNav ? route('landing') : route('login');
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'General') - {{ $clientBranding['name'] }}</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
    @stack('styles')
</head>

<body class="min-h-screen bg-slate-50 text-slate-900">
    <header class="absolute top-4 left-0 right-0 z-50 px-4 sm:px-6 lg:px-8 flex justify-center">
        <div class="w-full max-w-5xl rounded-full bg-white/40 backdrop-blur-md border border-white/60 shadow-sm flex items-center justify-between px-4 py-2.5">
            <a href="{{ $homeRoute }}" class="flex items-center gap-3">
                <img src="{{ $clientBranding['logo_url'] }}" alt="{{ $clientBranding['name'] }} Logo"
                    class="h-9 w-9 rounded-lg object-contain">
                <div class="hidden sm:block">
                    <p class="text-sm font-bold text-slate-900 leading-tight">{{ $clientBranding['name'] }}</p>
                    <p class="text-[10px] text-slate-600 font-medium">Informasi dan artikel</p>
                </div>
            </a>

            <nav class="hidden items-center gap-6 text-sm font-medium text-slate-800 md:flex">
                @if($showLandingNav)
                <a href="{{ route('landing') }}"
                    class="hover:text-primary transition-colors {{ request()->routeIs('landing', 'general.landing', 'general.index') ? 'text-primary' : '' }}">
                    Home
                </a>
                @endif
                @if($showStatisticsNav)
                <div class="group relative">
                    <button type="button"
                        class="hover:text-primary transition-colors flex items-center gap-1 {{ request()->routeIs('statistics', 'statistics.snbt', 'general.statistics', 'general.statistics.snbt') ? 'text-primary' : '' }}">
                        Statistik PTN
                    </button>
                    <div class="invisible absolute left-1/2 -translate-x-1/2 top-full z-50 mt-4 w-44 rounded-xl border border-white/60 bg-white/80 backdrop-blur-md p-2 text-slate-700 opacity-0 shadow-lg transition-all group-hover:visible group-hover:opacity-100 group-hover:mt-2">
                        <a href="{{ route('statistics') }}"
                            class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('statistics', 'general.statistics') ? 'bg-primary/10 text-primary font-semibold' : 'hover:bg-white/50' }}">
                            Statistik SNBP
                        </a>
                        <a href="{{ route('statistics.snbt') }}"
                            class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('statistics.snbt', 'general.statistics.snbt') ? 'bg-primary/10 text-primary font-semibold' : 'hover:bg-white/50' }}">
                            Statistik SNBT
                        </a>
                    </div>
                </div>
                @endif
                <a href="#" class="hover:text-primary transition-colors">Try Out</a>
                <a href="#" class="hover:text-primary transition-colors">Kelas & Materi</a>
                <a href="#program" class="hover:text-primary transition-colors">Paket</a>
                @if($showArticlesNav)
                <a href="{{ route('articles.index') }}"
                    class="hover:text-primary transition-colors {{ request()->routeIs('articles.*', 'general.articles.*', 'general.blog.*') ? 'text-primary' : '' }}">
                    Artikel
                </a>
                @endif
            </nav>

            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ route('user.dashboard.index') }}"
                        class="rounded-full border border-slate-300 bg-transparent px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-white/50 transition-colors">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="rounded-full border border-slate-300 bg-transparent px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-white/50 transition-colors">
                        Login
                    </a>
                @endauth
            </div>
            
            <!-- Mobile Menu Button (Optional, can keep it simple or implement a hamburger) -->
            <button class="md:hidden text-slate-800 p-2">
                <i class="ri-menu-line text-xl"></i>
            </button>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    @if(!request()->routeIs('landing', 'general.landing', 'general.index'))
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <p>&copy; {{ date('Y') }} {{ $clientBranding['name'] }}.</p>
            <div class="flex gap-4">
                @if($showStatisticsNav)
                    <a href="{{ route('statistics') }}" class="hover:text-primary">SNBP</a>
                    <a href="{{ route('statistics.snbt') }}" class="hover:text-primary">SNBT</a>
                @endif
                @if($showArticlesNav)
                    <a href="{{ route('articles.index') }}" class="hover:text-primary">Artikel</a>
                @endif
                <a href="{{ route('login') }}" class="hover:text-primary">Login</a>
            </div>
        </div>
    </footer>
    @endif
</body>

</html>
