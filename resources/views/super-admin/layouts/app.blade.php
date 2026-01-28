<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Super Admin - {{ $clientBranding['name'] ?? 'Copoit Academy' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
</head>
<body class="bg-gray-50">
    @include('super-admin.components.navbar')
    @include('super-admin.components.sidebar')

    <main class="pt-20 pl-0 sm:pl-64">
        <div class="px-6 py-6">
            @yield('content')
        </div>
    </main>

    @include('components.flash-alert')

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    @vite('resources/js/app.js')
    @stack('scripts')
</body>
</html>
