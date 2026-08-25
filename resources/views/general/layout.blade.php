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
    $pageTitle = trim($__env->yieldContent('title', ''));
    $documentTitle = $pageTitle !== ''
        ? $pageTitle . ' - ' . $clientBranding['name']
        : $clientBranding['name'];
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $documentTitle }}</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
    <x-website-translation-head />
    @stack('styles')
</head>

<body class="min-h-screen bg-slate-50 text-slate-900">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ $homeRoute }}" class="flex min-w-0 items-center gap-3">
                <img src="{{ $clientBranding['logo_url'] }}" alt="{{ $clientBranding['name'] }} Logo"
                    class="h-10 w-10 rounded-lg object-contain">
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-slate-950 sm:text-base">{{ $clientBranding['name'] }}</p>
                    <p class="hidden text-xs text-slate-500 sm:block">Informasi dan artikel</p>
                </div>
            </a>

            <nav class="hidden items-center gap-1 text-sm font-medium text-slate-600 md:flex">
                @if($showLandingNav)
                <a href="{{ route('landing') }}"
                    class="rounded-lg px-3 py-2 {{ request()->routeIs('landing', 'general.landing', 'general.index') ? 'bg-primary text-white' : 'hover:bg-slate-100' }}">
                    Home
                </a>
                @endif
                @if($showStatisticsNav)
                <div class="group relative">
                    <button type="button"
                        class="rounded-lg px-3 py-2 {{ request()->routeIs('statistics', 'statistics.snbt', 'general.statistics', 'general.statistics.snbt') ? 'bg-primary text-white' : 'hover:bg-slate-100' }}">
                        Statistik PTN
                        <i class="ri-arrow-down-s-line align-middle"></i>
                    </button>
                    <div class="invisible absolute left-0 top-full z-50 mt-2 w-44 rounded-xl border border-slate-200 bg-white p-1 text-slate-700 opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100">
                        <a href="{{ route('statistics') }}"
                            class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('statistics', 'general.statistics') ? 'bg-primary/10 text-primary font-semibold' : 'hover:bg-slate-50' }}">
                            Statistik SNBP
                        </a>
                        <a href="{{ route('statistics.snbt') }}"
                            class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('statistics.snbt', 'general.statistics.snbt') ? 'bg-primary/10 text-primary font-semibold' : 'hover:bg-slate-50' }}">
                            Statistik SNBT
                        </a>
                    </div>
                </div>
                @endif
                @if($showArticlesNav)
                <a href="{{ route('articles.index') }}"
                    class="rounded-lg px-3 py-2 {{ request()->routeIs('articles.*', 'general.articles.*', 'general.blog.*') ? 'bg-primary text-white' : 'hover:bg-slate-100' }}">
                    Artikel
                </a>
                @endif
                @if($showTryoutNav)
                <a href="{{ route('user.package.tryout.list', ['layout' => 'landing']) }}"
                    class="rounded-lg px-3 py-2 hover:bg-slate-100 {{ request()->routeIs('user.package.tryout.list', 'user.tryout.*', 'user.package.tryout.*') ? 'bg-primary text-white' : '' }}">
                    Try Out
                </a>
                @endif
                @if($showMaterialNav)
                <a href="{{ route('user.material.index', ['layout' => 'landing']) }}"
                    class="rounded-lg px-3 py-2 hover:bg-slate-100 {{ request()->routeIs('user.material.*') ? 'bg-primary text-white' : '' }}">
                    Kelas & Materi
                </a>
                @endif
                @if($showPackageNav)
                <a href="{{ route('user.package.index', ['layout' => 'landing']) }}"
                    class="rounded-lg px-3 py-2 hover:bg-slate-100 {{ request()->routeIs('user.package.index', 'user.package.detail') ? 'bg-primary text-white' : '' }}">
                    Paket
                </a>
                @endif
            </nav>

            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ route('user.dashboard.index') }}"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        Login
                    </a>
                @endauth
            </div>
        </div>

        <nav class="flex flex-wrap gap-1 border-t border-slate-100 px-4 py-2 text-sm font-medium text-slate-600 md:hidden">
            @if($showLandingNav)
            <a href="{{ route('landing') }}"
                class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs('landing', 'general.landing', 'general.index') ? 'bg-primary text-white' : 'bg-white' }}">
                Home
            </a>
            @endif
            @if($showStatisticsNav)
            <details class="group relative shrink-0">
                <summary class="flex cursor-pointer list-none items-center gap-1 whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs('statistics', 'statistics.snbt', 'general.statistics', 'general.statistics.snbt') ? 'bg-primary text-white' : 'bg-white' }}">
                    Statistik PTN
                    <i class="ri-arrow-down-s-line transition-transform group-open:rotate-180"></i>
                </summary>
                <div class="absolute left-0 top-full z-50 mt-2 w-44 rounded-xl border border-slate-200 bg-white p-1 text-slate-700 shadow-lg">
                    <a href="{{ route('statistics') }}"
                        class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('statistics', 'general.statistics') ? 'bg-primary/10 text-primary font-semibold' : 'hover:bg-slate-50' }}">
                        Statistik SNBP
                    </a>
                    <a href="{{ route('statistics.snbt') }}"
                        class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('statistics.snbt', 'general.statistics.snbt') ? 'bg-primary/10 text-primary font-semibold' : 'hover:bg-slate-50' }}">
                        Statistik SNBT
                    </a>
                </div>
            </details>
            @endif
            @if($showArticlesNav)
            <a href="{{ route('articles.index') }}"
                class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs('articles.*', 'general.articles.*', 'general.blog.*') ? 'bg-primary text-white' : 'bg-white' }}">
                Artikel
            </a>
            @endif
            @if($showTryoutNav)
            <a href="{{ route('user.package.tryout.list', ['layout' => 'landing']) }}"
                class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs('user.package.tryout.list', 'user.tryout.*', 'user.package.tryout.*') ? 'bg-primary text-white' : 'bg-white' }}">
                Try Out
            </a>
            @endif
            @if($showMaterialNav)
            <a href="{{ route('user.material.index', ['layout' => 'landing']) }}"
                class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs('user.material.*') ? 'bg-primary text-white' : 'bg-white' }}">
                Kelas & Materi
            </a>
            @endif
            @if($showPackageNav)
            <a href="{{ route('user.package.index', ['layout' => 'landing']) }}"
                class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs('user.package.index', 'user.package.detail') ? 'bg-primary text-white' : 'bg-white' }}">
                Paket
            </a>
            @endif
        </nav>
    </header>

    <main class="responsive-shell">
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
    <x-website-translator />
</body>

</html>
