<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - {{ $clientBranding['name'] ?? config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
    <x-website-translation-head />
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-2xl items-center px-4 py-12 sm:px-6">
        <section class="w-full rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm sm:p-12">
            <img src="{{ $clientBranding['logo_url'] ?? asset('img/logo/logo-copoit.png') }}"
                alt="{{ $clientBranding['name'] ?? config('app.name') }}"
                class="client-brand-logo mx-auto h-14 w-14 max-w-48 object-contain">

            <div class="mx-auto mt-8 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                <i class="@yield('icon') text-3xl" aria-hidden="true"></i>
            </div>

            <p class="mt-6 text-sm font-bold uppercase tracking-[0.2em] text-primary">@yield('code')</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">@yield('heading')</h1>
            <p class="mx-auto mt-3 max-w-lg text-sm leading-6 text-slate-600 sm:text-base">@yield('message')</p>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                @yield('actions')
            </div>
        </section>
    </main>
    <x-website-translator />
</body>
</html>
