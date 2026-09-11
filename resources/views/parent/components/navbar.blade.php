@php($pageTitle = trim((string) app('view')->getSection('title')) ?: 'Portal Orang Tua')

<nav data-persistent-navbar class="sticky top-0 z-50 w-full border-b border-slate-200 bg-white/95 text-gray-900 backdrop-blur">
    <div class="px-2 py-2 sm:px-3 sm:py-3 lg:px-5 lg:pl-3">
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 flex-1 items-center">
                <button data-drawer-target="parent-sidebar" data-drawer-toggle="parent-sidebar" aria-controls="parent-sidebar" type="button" class="inline-flex items-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 sm:hidden">
                    <span class="sr-only">Buka sidebar</span><i class="ri-menu-line text-2xl"></i>
                </button>
                <a href="{{ route('parent.dashboard') }}" class="ml-1 flex min-w-0 items-center gap-2 rounded-xl px-1 py-1 sm:hidden">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white p-1 ring-1 ring-gray-200"><img src="{{ $clientBranding['logo_url'] }}" class="h-full w-full object-contain" alt="{{ $clientBranding['name'] }}"></span>
                    <span class="truncate text-sm font-bold">{{ $clientBranding['name'] }}</span>
                </a>
                <div class="hidden min-w-0 items-center gap-2 text-sm text-gray-500 sm:flex"><span class="truncate">{{ $clientBranding['name'] }}</span><i class="ri-arrow-right-s-line text-gray-300"></i><span class="truncate font-medium text-gray-900">{{ $pageTitle }}</span></div>
            </div>
            <div class="flex shrink-0 items-center gap-2 sm:gap-4">
                @if($children->isNotEmpty())
                    <form method="POST" action="{{ route('parent.select-child') }}" class="hidden sm:block">@csrf<label class="sr-only" for="parent-child-selector">Pilih anak</label><select id="parent-child-selector" name="child_id" onchange="this.form.submit()" class="max-w-48 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 focus:border-primary focus:bg-white focus:ring-primary/20">@foreach($children as $listedChild)<option value="{{ $listedChild->id }}" @selected((int) ($child?->id) === (int) $listedChild->id)>{{ $listedChild->name }}</option>@endforeach</select></form>
                @endif
                <div class="hidden max-w-[150px] text-right sm:block"><p class="truncate text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p><p class="truncate text-xs text-gray-500">Orang Tua</p></div>
                <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=6366f1&color=fff&size=40" class="h-8 w-8 rounded-full" alt="">
                <form id="parent-logout-form" method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="flex items-center gap-2 rounded-lg px-2 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-100 sm:px-3" data-logout-confirm data-logout-form="parent-logout-form"><i class="ri-logout-circle-r-line"></i><span class="hidden sm:inline">Logout</span></button></form>
            </div>
        </div>
    </div>
</nav>
