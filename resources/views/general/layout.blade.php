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
    $showTryoutNav = \Illuminate\Support\Facades\Route::has('user.package.tryout.list');
    $showMaterialNav = \Illuminate\Support\Facades\Route::has('user.material.index');
    $showPackageNav = \Illuminate\Support\Facades\Route::has('user.package.index');
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'General') - {{ $clientBranding['name'] }}</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @stack('styles')
</head>

<body class="min-h-screen bg-slate-50 text-slate-900">
    <header x-data="{ scrolled: false }" @scroll.window="scrolled = (window.pageYOffset > 20)"
            class="fixed left-0 right-0 z-50 px-4 sm:px-6 lg:px-8 flex justify-center transition-all duration-300"
            :class="scrolled ? 'top-2' : 'top-4'">
        <div class="w-full max-w-5xl rounded-full backdrop-blur-md border transition-all duration-300 flex items-center justify-between px-4 py-2.5"
             :class="scrolled ? 'bg-white/95 border-slate-200 shadow-md' : 'bg-white/80 border-white/60 shadow-sm'">
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
                @if($showTryoutNav)
                <a href="{{ route('user.package.tryout.list') }}"
                    class="hover:text-primary transition-colors {{ request()->routeIs('user.package.tryout.list', 'user.tryout.*', 'user.package.tryout.*') ? 'text-primary' : '' }}">
                    Try Out
                </a>
                @endif
                @if($showMaterialNav)
                <a href="{{ route('user.material.index') }}"
                    class="hover:text-primary transition-colors {{ request()->routeIs('user.material.*') ? 'text-primary' : '' }}">
                    Kelas & Materi
                </a>
                @endif
                @if($showPackageNav)
                <a href="{{ route('user.package.index') }}"
                    class="hover:text-primary transition-colors {{ request()->routeIs('user.package.index', 'user.package.detail') ? 'text-primary' : '' }}">
                    Paket
                </a>
                @endif
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
            
            <details class="group relative md:hidden">
                <summary class="flex cursor-pointer list-none items-center rounded-full p-2 text-slate-800 hover:bg-white/50">
                    <i class="ri-menu-line text-xl group-open:hidden"></i>
                    <i class="ri-close-line hidden text-xl group-open:block"></i>
                </summary>
                <div class="absolute right-0 top-full z-50 mt-3 w-56 rounded-2xl border border-white/60 bg-white/90 p-2 text-slate-700 shadow-lg backdrop-blur-md">
                    @if($showLandingNav)
                    <a href="{{ route('landing') }}"
                        class="block rounded-xl px-3 py-2 text-sm font-semibold {{ request()->routeIs('landing', 'general.landing', 'general.index') ? 'bg-primary/10 text-primary' : 'hover:bg-white/60' }}">
                        Home
                    </a>
                    @endif
                    @if($showStatisticsNav)
                    <details class="group/stat relative">
                        <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-3 py-2 text-sm font-semibold {{ request()->routeIs('statistics', 'statistics.snbt', 'general.statistics', 'general.statistics.snbt') ? 'bg-primary/10 text-primary' : 'hover:bg-white/60' }}">
                            Statistik PTN
                            <i class="ri-arrow-down-s-line transition-transform group-open/stat:rotate-180"></i>
                        </summary>
                        <div class="mt-1 space-y-1 pl-3">
                            <a href="{{ route('statistics') }}"
                                class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('statistics', 'general.statistics') ? 'bg-primary/10 text-primary font-semibold' : 'hover:bg-white/60' }}">
                                Statistik SNBP
                            </a>
                            <a href="{{ route('statistics.snbt') }}"
                                class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('statistics.snbt', 'general.statistics.snbt') ? 'bg-primary/10 text-primary font-semibold' : 'hover:bg-white/60' }}">
                                Statistik SNBT
                            </a>
                        </div>
                    </details>
                    @endif
                    @if($showTryoutNav)
                    <a href="{{ route('user.package.tryout.list') }}"
                        class="block rounded-xl px-3 py-2 text-sm font-semibold {{ request()->routeIs('user.package.tryout.list', 'user.tryout.*', 'user.package.tryout.*') ? 'bg-primary/10 text-primary' : 'hover:bg-white/60' }}">
                        Try Out
                    </a>
                    @endif
                    @if($showMaterialNav)
                    <a href="{{ route('user.material.index') }}"
                        class="block rounded-xl px-3 py-2 text-sm font-semibold {{ request()->routeIs('user.material.*') ? 'bg-primary/10 text-primary' : 'hover:bg-white/60' }}">
                        Kelas & Materi
                    </a>
                    @endif
                    @if($showPackageNav)
                    <a href="{{ route('user.package.index') }}"
                        class="block rounded-xl px-3 py-2 text-sm font-semibold {{ request()->routeIs('user.package.index', 'user.package.detail') ? 'bg-primary/10 text-primary' : 'hover:bg-white/60' }}">
                        Paket
                    </a>
                    @endif
                    @if($showArticlesNav)
                    <a href="{{ route('articles.index') }}"
                        class="block rounded-xl px-3 py-2 text-sm font-semibold {{ request()->routeIs('articles.*', 'general.articles.*', 'general.blog.*') ? 'bg-primary/10 text-primary' : 'hover:bg-white/60' }}">
                        Artikel
                    </a>
                    @endif
                </div>
            </details>
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
