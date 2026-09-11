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
    <header class="sticky top-0 z-30 bg-slate-50/95 px-3 pt-3 backdrop-blur sm:px-4">
        <div class="responsive-shell mx-auto flex max-w-[1440px] items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 sm:px-5">
            <a href="{{ route('parent.dashboard') }}" class="flex min-w-0 items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary text-xl text-white"><i class="ri-parent-line"></i></span>
                <span class="min-w-0"><span class="block truncate text-sm font-bold text-gray-900">{{ $clientBranding['name'] ?? config('app.name') }}</span><span class="block truncate text-xs text-gray-500">Portal Orang Tua</span></span>
            </a>
            <div class="flex items-center gap-2 sm:gap-3">
                @if($children->isNotEmpty())
                    <label class="sr-only" for="parent-child-selector">Pilih anak</label>
                    <select id="parent-child-selector" onchange="window.location=this.value" class="max-w-36 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-gray-700 focus:border-primary focus:ring-primary sm:max-w-xs">
                        @foreach($children as $listedChild)
                            <option value="{{ route(request()->route()?->getName(), ['anak' => $listedChild->id]) }}" @selected((int) ($child?->id) === (int) $listedChild->id)>{{ $listedChild->name }}</option>
                        @endforeach
                    </select>
                @endif
                <form id="parent-logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600" data-logout-confirm data-logout-form="parent-logout-form" aria-label="Logout">
                        <i class="ri-logout-circle-r-line text-lg"></i>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div class="responsive-shell mx-auto grid max-w-[1440px] gap-4 px-3 pb-6 pt-4 sm:px-4 lg:grid-cols-[248px_minmax(0,1fr)] lg:gap-6">
        <aside class="h-fit rounded-xl border border-slate-200 bg-white p-3 lg:sticky lg:top-24">
            <a href="{{ route('parent.dashboard', $child?->id ? ['anak' => $child->id] : []) }}" class="mb-4 flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50 px-3 py-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">{{ $child ? strtoupper(mb_substr($child->name, 0, 1)) : '?' }}</span>
                <span class="min-w-0"><span class="block truncate text-sm font-semibold text-gray-900">{{ $child?->name ?? 'Belum ada anak' }}</span><span class="block truncate text-xs text-gray-500">Ringkasan belajar</span></span>
            </a>
            <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-[0.12em] text-gray-400">Menu</p>
            <nav class="flex gap-1 overflow-x-auto pb-1 text-sm font-semibold lg:block lg:space-y-1 lg:overflow-visible" aria-label="Navigasi Orang Tua">
                @foreach($parentNavigationItems as $item)
                    <a href="{{ route($item['route'], $child?->id ? ['anak' => $child->id] : []) }}" class="flex shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 transition-colors {{ $item['is_active'] ? 'bg-primary text-white' : 'text-gray-600 hover:bg-primary/5 hover:text-primary' }}"><i class="{{ $item['icon'] }} text-lg"></i><span>{{ $item['label'] }}</span></a>
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
