<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $clientBranding['name'] ?? config('app.name') }} - Portal Tutor</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
</head>
<body class="min-h-screen bg-slate-50 text-gray-800">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ route('tutor.schedule.index') }}" class="font-bold text-primary">Portal Tutor</a>
            <div class="flex items-center gap-4 text-sm">
                <span class="hidden text-gray-600 sm:inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="font-semibold text-gray-600 hover:text-primary">Keluar</button>
                </form>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
        @if(session('success'))
            <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
