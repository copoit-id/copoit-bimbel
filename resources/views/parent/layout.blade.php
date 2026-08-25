<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Orang Tua') - {{ $clientBranding['name'] ?? config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @vite('resources/css/app.css')
    @include('components.branding-styles')
    @include('components.favicon-link')
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-50 text-gray-800">
    @php
        $currentRoute = request()->route()?->getName();
        $selectedChildId = $child?->id;
        $parentLink = fn (string $route) => route($route, $selectedChildId ? ['anak' => $selectedChildId] : []);
    @endphp
    <header class="sticky top-0 z-30 border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('parent.dashboard') }}" class="flex min-w-0 items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary text-xl text-white"><i class="ri-parent-line"></i></span>
                <span class="min-w-0"><span class="block truncate font-bold text-gray-900">Portal Orang Tua</span><span class="block truncate text-xs text-gray-500">{{ $clientBranding['name'] ?? config('app.name') }}</span></span>
            </a>
            <div class="flex items-center gap-3">
                @if($children->isNotEmpty())
                    <label class="sr-only" for="parent-child-selector">Pilih anak</label>
                    <select id="parent-child-selector" onchange="window.location=this.value" class="max-w-44 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 focus:border-primary focus:ring-primary sm:max-w-xs">
                        @foreach($children as $listedChild)
                            <option value="{{ route($currentRoute, ['anak' => $listedChild->id]) }}" @selected((int) $selectedChildId === (int) $listedChild->id)>{{ $listedChild->name }}</option>
                        @endforeach
                    </select>
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 sm:inline-flex">Keluar</button></form>
            </div>
        </div>
    </header>

    <div class="responsive-shell mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[220px_minmax(0,1fr)]">
        <aside class="h-fit rounded-2xl border border-gray-200 bg-white p-3 shadow-sm lg:sticky lg:top-24">
            <nav class="space-y-1 text-sm font-semibold">
                @php($canShowTutorChat = (bool) config('client.branding.tutor_chat_enabled', false) && app(\App\Services\PlanModuleService::class)->allows('discussion'))
                @foreach([
                    ['parent.dashboard', 'ri-home-5-line', 'Ringkasan'],
                    ['parent.attendance', 'ri-calendar-check-line', 'Presensi'],
                    ['parent.packages', 'ri-bank-card-line', 'Paket & Pembayaran'],
                    ['parent.assessments', 'ri-bar-chart-box-line', 'Riwayat Ujian'],
                    ['parent.development', 'ri-line-chart-line', 'Perkembangan'],
                    ...($canShowTutorChat ? [['parent.chat.index', 'ri-chat-3-line', 'Chat Tutor']] : []),
                    ['parent.report', 'ri-file-chart-line', 'Laporan Cetak'],
                ] as [$route, $icon, $label])
                    <a href="{{ $parentLink($route) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 {{ $currentRoute === $route ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-primary' }}"><i class="{{ $icon }} text-lg"></i>{{ $label }}</a>
                @endforeach
            </nav>
        </aside>
        <main class="min-w-0">@yield('content')</main>
    </div>
    @include('components.flash-alert')
    @vite('resources/js/app.js')
    @stack('scripts')
</body>
</html>
