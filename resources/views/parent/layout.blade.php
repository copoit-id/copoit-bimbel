<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, proxy-revalidate">
    <title>@yield('title', 'Portal Orang Tua') - {{ $clientBranding['name'] ?? config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @vite('resources/css/app.css')
    <x-ui.persistent-sidebar />
    @include('components.branding-styles')
    @include('components.favicon-link')
    @stack('styles')
</head>
<body data-app-selects>
    @include('parent.components.navbar')
    @include('parent.components.sidebar')
    <x-ui.persistent-sidebar-reopen />
    @include('components.flash-alert')
    <x-logout-confirm-modal />

    <main data-persistent-sidebar-content="margin" class="responsive-shell mt-2 p-3 sm:ml-64 sm:p-4 md:mt-3 md:p-6">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    @vite('resources/js/app.js')
    @stack('scripts')
</body>
</html>
