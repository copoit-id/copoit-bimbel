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
                    Landing Page
                </a>
                @endif
                @if($showStatisticsNav)
                <a href="{{ route('statistics') }}"
                    class="rounded-lg px-3 py-2 {{ request()->routeIs('statistics', 'general.statistics') ? 'bg-primary text-white' : 'hover:bg-slate-100' }}">
                    Statistik PTN
                </a>
                @endif
                @if($showArticlesNav)
                <a href="{{ route('articles.index') }}"
                    class="rounded-lg px-3 py-2 {{ request()->routeIs('articles.*', 'general.articles.*', 'general.blog.*') ? 'bg-primary text-white' : 'hover:bg-slate-100' }}">
                    Artikel
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

        <nav class="flex gap-1 overflow-x-auto border-t border-slate-100 px-4 py-2 text-sm font-medium text-slate-600 md:hidden">
            @if($showLandingNav)
            <a href="{{ route('landing') }}"
                class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs('landing', 'general.landing', 'general.index') ? 'bg-primary text-white' : 'bg-white' }}">
                Landing Page
            </a>
            @endif
            @if($showStatisticsNav)
            <a href="{{ route('statistics') }}"
                class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs('statistics', 'general.statistics') ? 'bg-primary text-white' : 'bg-white' }}">
                Statistik PTN
            </a>
            @endif
            @if($showArticlesNav)
            <a href="{{ route('articles.index') }}"
                class="whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs('articles.*', 'general.articles.*', 'general.blog.*') ? 'bg-primary text-white' : 'bg-white' }}">
                Artikel
            </a>
            @endif
        </nav>
    </header>

    <main>
        @yield('content')
    </main>

    @if(!request()->routeIs('landing', 'general.landing', 'general.index'))
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <p>&copy; {{ date('Y') }} {{ $clientBranding['name'] }}.</p>
            <div class="flex gap-4">
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
