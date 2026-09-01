<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $clientBranding['name'] ?? config('app.name') }} - @yield('title', 'Portal Tutor')</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
    <x-website-translation-head />
</head>
<body class="min-h-screen bg-slate-50 text-gray-800" data-app-selects>
    @php
        $planModules = app(\App\Services\PlanModuleService::class);
        $canShowTutorProfile = $planModules->allows('profile');
        $canShowBooking = ($clientBranding['booking_schedule_enabled'] ?? false)
            && $planModules->allows('booking')
            && \Illuminate\Support\Facades\Route::has('tutor.booking.index');
        $canShowLearningProgress = ($clientBranding['learning_progress_enabled'] ?? false)
            && $planModules->allows('booking')
            && \Illuminate\Support\Facades\Route::has('tutor.development.index');
        $canShowAttendance = $planModules->allows('attendance')
            && \Illuminate\Support\Facades\Route::has('tutor.attendance.index');
        $activeLinkClass = 'bg-primary text-white shadow-sm';
        $inactiveLinkClass = 'text-slate-600 hover:bg-slate-100 hover:text-primary';
    @endphp

    <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 border-r border-gray-200 bg-white lg:flex lg:flex-col">
        <div class="border-b border-gray-100 px-5 py-5">
            <a href="{{ route('tutor.dashboard') }}" class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-lg text-white"><i class="ri-presentation-line"></i></span>
                <span><span class="block font-bold text-gray-900">Portal Tutor</span><span class="block text-xs text-gray-500">{{ $clientBranding['name'] ?? config('app.name') }}</span></span>
            </a>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5 text-sm font-semibold">
            <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Menu Tutor</p>
            <a href="{{ route('tutor.dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 {{ request()->routeIs('tutor.dashboard') ? $activeLinkClass : $inactiveLinkClass }}"><i class="ri-dashboard-line text-lg"></i>Dashboard</a>
            <a href="{{ route('tutor.schedule.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 {{ request()->routeIs('tutor.schedule.*') ? $activeLinkClass : $inactiveLinkClass }}"><i class="ri-calendar-2-line text-lg"></i>Jadwal Mengajar</a>
            @if($canShowBooking)<a href="{{ route('tutor.booking.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 {{ request()->routeIs('tutor.booking.*') ? $activeLinkClass : $inactiveLinkClass }}"><i class="ri-calendar-schedule-line text-lg"></i>Booking</a>@endif
            @if($canShowAttendance)<a href="{{ route('tutor.attendance.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 {{ request()->routeIs('tutor.attendance.*') ? $activeLinkClass : $inactiveLinkClass }}"><i class="ri-checkbox-circle-line text-lg"></i>Absensi</a>@endif
            @if($canShowLearningProgress)<a href="{{ route('tutor.development.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 {{ request()->routeIs('tutor.development.*') ? $activeLinkClass : $inactiveLinkClass }}"><i class="ri-line-chart-line text-lg"></i>Perkembangan</a>@endif
            @if($clientBranding['tutor_chat_enabled'] ?? false)<a href="{{ route('tutor.chat.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 {{ request()->routeIs('tutor.chat.*') ? $activeLinkClass : $inactiveLinkClass }}"><i class="ri-chat-3-line text-lg"></i>Chat Siswa</a>@endif
            @if($canShowTutorProfile)<a href="{{ route('tutor.profile.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 {{ request()->routeIs('tutor.profile.*') ? $activeLinkClass : $inactiveLinkClass }}"><i class="ri-user-settings-line text-lg"></i>Profil Saya</a>@endif
        </nav>

        <div class="border-t border-gray-100 p-3">
            <div class="mb-3 flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-2.5"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 font-bold text-primary">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}</span><div class="min-w-0"><p class="truncate text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p><p class="text-xs text-gray-500">Tutor</p></div></div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 hover:bg-red-50 hover:text-red-600"><i class="ri-logout-box-r-line text-lg"></i>Keluar</button></form>
        </div>
    </aside>

    <header class="sticky top-0 z-30 border-b border-gray-200 bg-white lg:hidden">
        <div class="flex items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('tutor.dashboard') }}" class="shrink-0 font-bold text-primary">Portal Tutor</a>
            <button type="button" class="rounded-lg p-2 text-gray-600 hover:bg-gray-100" onclick="document.getElementById('tutor-mobile-nav').classList.toggle('hidden')" aria-label="Buka navigasi tutor">
                <i class="ri-menu-line text-xl"></i>
            </button>
        </div>
        <nav id="tutor-mobile-nav" class="hidden border-t border-gray-100 px-4 py-2 text-sm font-semibold">
            <a href="{{ route('tutor.dashboard') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('tutor.dashboard') ? $activeLinkClass : $inactiveLinkClass }}">Dashboard</a>
            <a href="{{ route('tutor.schedule.index') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('tutor.schedule.*') ? $activeLinkClass : $inactiveLinkClass }}">Jadwal Mengajar</a>
            @if($canShowBooking)<a href="{{ route('tutor.booking.index') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('tutor.booking.*') ? $activeLinkClass : $inactiveLinkClass }}">Booking</a>@endif
            @if($canShowAttendance)<a href="{{ route('tutor.attendance.index') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('tutor.attendance.*') ? $activeLinkClass : $inactiveLinkClass }}">Absensi</a>@endif
            @if($canShowLearningProgress)<a href="{{ route('tutor.development.index') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('tutor.development.*') ? $activeLinkClass : $inactiveLinkClass }}">Perkembangan</a>@endif
            @if($clientBranding['tutor_chat_enabled'] ?? false)<a href="{{ route('tutor.chat.index') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('tutor.chat.*') ? $activeLinkClass : $inactiveLinkClass }}">Chat Siswa</a>@endif
            @if($canShowTutorProfile)<a href="{{ route('tutor.profile.edit') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('tutor.profile.*') ? $activeLinkClass : $inactiveLinkClass }}">Profil Saya</a>@endif
        </nav>
    </header>

    <main class="responsive-shell min-h-screen px-4 py-6 sm:px-6 lg:ml-64 lg:px-8">
        @if(session('success'))<div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>@endif
        @yield('content')
    </main>
    @vite('resources/js/app.js')
    @stack('scripts')
    <x-website-translator />
</body>
</html>
