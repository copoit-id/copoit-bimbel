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
<body class="min-h-screen bg-slate-50 text-gray-800" data-app-selects>
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
                            <option value="{{ route(request()->route()?->getName(), ['anak' => $listedChild->id]) }}" @selected((int) ($child?->id) === (int) $listedChild->id)>{{ $listedChild->name }}</option>
                        @endforeach
                    </select>
                @endif
                <form id="parent-logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-red-50 hover:text-red-600" data-logout-confirm data-logout-form="parent-logout-form">
                        <i class="ri-logout-circle-r-line text-lg"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div class="responsive-shell mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[220px_minmax(0,1fr)]">
        <aside class="h-fit rounded-2xl border border-gray-200 bg-white p-3 shadow-sm lg:sticky lg:top-24">
            <nav class="space-y-1 text-sm font-semibold">
                @foreach($parentNavigationItems as $item)
                    <a href="{{ route($item['route'], $child?->id ? ['anak' => $child->id] : []) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 {{ $item['is_active'] ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-primary' }}"><i class="{{ $item['icon'] }} text-lg"></i>{{ $item['label'] }}</a>
                @endforeach
            </nav>
        </aside>
        <main class="min-w-0">@yield('content')</main>
    </div>
    @include('components.flash-alert')
    <x-logout-confirm-modal />
    @vite('resources/js/app.js')
    @stack('scripts')
</body>
</html>
