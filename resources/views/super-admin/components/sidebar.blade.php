@php
    $linkActiveClass = 'bg-primary text-white';
    $linkInactiveClass = 'text-gray-700 hover:bg-gray-100';
@endphp

<aside id="superadmin-sidebar" data-persistent-sidebar
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform -translate-x-full sm:translate-x-0 bg-white border-r border-gray-200"
    aria-label="Sidebar">
    <div class="h-full px-3 pb-4 overflow-y-auto">
        <a href="{{ route('super-admin.admins.index') }}" data-sidebar-brand>
            <span data-sidebar-brand-mark class="font-bold">SA</span>
            <span class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">{{ $clientBranding['name'] ?? 'Copoit Academy' }}</p>
                <small class="block truncate text-xs">{{ trim((string) app('view')->getSection('title')) ?: 'Pengelolaan Sistem' }}</small>
            </span>
        </a>
        <div data-sidebar-divider class="border-t"></div>
        <div class="flex items-center justify-between gap-2">
            <p data-sidebar-section-label class="text-[#999999] text-xs font-medium uppercase tracking-[0.12em]">Menu</p>
            <button type="button" data-persistent-sidebar-toggle aria-expanded="true"
                class="hidden sm:inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">
                <span class="sr-only" data-persistent-sidebar-toggle-label>Tutup sidebar</span>
                <i class="ri-arrow-left-s-line text-xl" data-persistent-sidebar-toggle-icon aria-hidden="true"></i>
            </button>
        </div>
        <ul class="space-y-1 font-medium mt-2">
            <li>
                <a href="{{ route('super-admin.admins.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('super-admin.admins.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-user-settings-line text-[20px]"></i>
                    <span class="ms-3">Kelola Admin Demo</span>
                </a>
            </li>
            <li>
                <a href="{{ route('super-admin.activity.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('super-admin.activity.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-history-line text-[20px]"></i>
                    <span class="ms-3">Activity</span>
                </a>
            </li>
            <li>
                <a href="{{ route('super-admin.roles.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('super-admin.roles.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-shield-keyhole-line text-[20px]"></i>
                    <span class="ms-3">Role & Akses</span>
                </a>
            </li>
            <li>
                <a href="{{ route('super-admin.general-settings.edit') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('super-admin.general-settings.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-global-line text-[20px]"></i>
                    <span class="ms-3">General Settings</span>
                </a>
            </li>
            <li>
                <a href="{{ route('super-admin.ai-usage.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('super-admin.ai-usage.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-cpu-line text-[20px]"></i>
                    <span class="ms-3">AI Usage</span>
                </a>
            </li>
            <li>
                <a href="{{ route('super-admin.ai-gateway-usage.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('super-admin.ai-gateway-usage.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-radar-line text-[20px]"></i>
                    <span class="ms-3">Gateway Monitoring</span>
                </a>
            </li>
            <li>
                <a href="{{ route('super-admin.ai-gateway-payments.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('super-admin.ai-gateway-payments.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-bank-card-line text-[20px]"></i>
                    <span class="ms-3">Pembayaran AI</span>
                </a>
            </li>
            <li><a href="{{ route('super-admin.ai-gateway-plans.index') }}" class="flex items-center py-2 px-4 {{ request()->routeIs('super-admin.ai-gateway-plans.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg"><i class="ri-price-tag-3-line text-[20px]"></i><span class="ms-3">Paket AI Gateway</span></a></li>
            <li>
                <a href="{{ route('super-admin.plans.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('super-admin.plans.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-vip-crown-line text-[20px]"></i>
                    <span class="ms-3">Manajemen Plan</span>
                </a>
            </li>
            <li>
                <a href="{{ route('super-admin.plan-management.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('super-admin.plan-management.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-dashboard-3-line text-[20px]"></i>
                    <span class="ms-3">Plan & Quota</span>
                </a>
            </li>
            <li>
                <a href="{{ route('super-admin.load-test.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('super-admin.load-test.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-speed-up-line text-[20px]"></i>
                    <span class="ms-3">Load Test Tryout</span>
                </a>
            </li>
            <li>
                <a href="{{ route('super-admin.data-reset.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('super-admin.data-reset.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-delete-bin-6-line text-[20px]"></i>
                    <span class="ms-3">Reset Data</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
