<aside id="parent-sidebar" data-persistent-sidebar class="fixed left-0 top-0 z-40 h-screen w-64 -translate-x-full pt-20 transition-transform sm:translate-x-0" aria-label="Sidebar Orang Tua">
    <div class="h-full overflow-y-auto px-3 pb-4">
        <a href="{{ route('parent.dashboard') }}" data-sidebar-brand><span data-sidebar-brand-mark><img src="{{ $clientBranding['logo_url'] }}" class="h-full w-full object-contain p-1" alt="{{ $clientBranding['name'] }}"></span><span class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ $clientBranding['name'] }}</p><small class="block truncate text-xs">{{ trim((string) app('view')->getSection('title')) ?: 'Portal Orang Tua' }}</small></span></a>
        <div data-sidebar-divider class="border-t"></div>
        <div class="flex items-center justify-between gap-2"><p data-sidebar-section-label class="text-xs font-medium uppercase tracking-[0.12em] text-gray-500">Menu</p><button type="button" data-persistent-sidebar-toggle aria-expanded="true" class="hidden h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 sm:inline-flex"><span class="sr-only" data-persistent-sidebar-toggle-label>Ringkas sidebar</span><i class="ri-arrow-left-s-line text-xl" data-persistent-sidebar-toggle-icon></i></button></div>
        @if($children->isNotEmpty())
            <form method="POST" action="{{ route('parent.select-child') }}" class="mb-3 mt-1 sm:hidden">@csrf<label for="parent-mobile-child" class="mb-1.5 block text-xs font-medium text-gray-500">Anak dipantau</label><select id="parent-mobile-child" name="child_id" onchange="this.form.submit()" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm">@foreach($children as $listedChild)<option value="{{ $listedChild->id }}" @selected((int) ($child?->id) === (int) $listedChild->id)>{{ $listedChild->name }}</option>@endforeach</select></form>
        @endif
        <ul class="space-y-1 font-medium">
            @foreach($parentNavigationItems as $navigation)
                @if($navigation['type'] === 'link')
                    <li><a href="{{ route($navigation['route']) }}" class="flex items-center rounded-lg px-4 py-2 {{ $navigation['is_active'] ? 'bg-primary text-white' : 'text-gray-600' }} group"><i class="{{ $navigation['icon'] }} text-[20px]"></i><span class="ms-3">{{ $navigation['label'] }}</span></a></li>
                @else
                    <li><details id="parent-menu-{{ $navigation['id'] }}" class="group" @if($navigation['is_active']) open @endif><summary class="flex cursor-pointer list-none items-center justify-between rounded-lg px-4 py-2 {{ $navigation['is_active'] ? 'bg-primary text-white' : 'text-gray-600' }}"><span class="flex items-center"><i class="{{ $navigation['icon'] }} text-[20px]"></i><span class="ms-3">{{ $navigation['label'] }}</span></span><i class="ri-arrow-down-s-line text-[18px] transition-transform group-open:rotate-180"></i></summary><ul class="mt-1 ms-2 space-y-1">@foreach($navigation['items'] as $item)<li><a href="{{ route($item['route']) }}" class="flex items-center rounded-lg py-2 pl-3 pr-4 {{ $item['is_active'] ? 'bg-primary text-white' : 'text-gray-600' }}"><i class="{{ $item['icon'] }} text-base"></i><span class="ms-2">{{ $item['label'] }}</span></a></li>@endforeach</ul></details></li>
                @endif
            @endforeach
        </ul>
    </div>
</aside>
