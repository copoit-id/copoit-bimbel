@php
    $sidebarPrimary = $clientBranding['sidebar_primary_color'] ?? false;
    $sidebarWrapperClasses = $sidebarPrimary ? 'bg-primary border-r border-primary text-white' : 'bg-white border-r border-gray-200';
    $sidebarInnerClasses = $sidebarPrimary ? 'bg-primary text-white' : 'bg-white';
    $sectionLabelClass = $sidebarPrimary ? 'text-white/70' : 'text-[#999999]';
    $linkActiveClass = $sidebarPrimary ? 'bg-white/10 text-white' : 'bg-primary text-white';
    $linkInactiveClass = $sidebarPrimary ? 'text-white/80 hover:bg-white/10' : 'text-black hover:bg-gray-100';
    $iconActiveClass = 'text-white';
    $iconInactiveClass = $sidebarPrimary ? 'text-white/80' : 'text-black';
@endphp

<aside id="logo-sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform -translate-x-full sm:translate-x-0 {{ $sidebarWrapperClasses }}"
    aria-label="Sidebar">
    <div class="h-full px-3 pb-4 overflow-y-auto {{ $sidebarInnerClasses }}">
        <p class="{{ $sectionLabelClass }} text-sm">Menu</p>
        <ul class="space-y-1 font-medium">
            <li>
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.dashboard') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-home-line text-[20px] {{ request()->routeIs('admin.dashboard') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.package.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.package.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-store-3-line text-[20px] {{ request()->routeIs('admin.package.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Manajemen Paket</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.tryout.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.tryout.*')  || request()->routeIs('admin.question.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-draft-line text-[20px] {{ request()->routeIs('admin.tryout.*') || request()->routeIs('admin.question.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Manajemen Tryout</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.class.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.class.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-video-line text-[20px] {{ request()->routeIs('admin.class.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Manajemen Kelas</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.user.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.user.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-user-3-line text-[20px] {{ request()->routeIs('admin.user.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Manajemen Users</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.akses.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.akses.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-key-line text-[20px] {{ request()->routeIs('admin.akses.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Akses User</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.pembayaran.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.pembayaran.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-money-dollar-circle-line text-[20px] {{ request()->routeIs('admin.pembayaran.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Pembayaran</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.leaderboard.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.leaderboard.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-bar-chart-line text-[20px] {{ request()->routeIs('admin.leaderboard.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Leaderboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.laporan.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.laporan.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-file-chart-line text-[20px] {{ request()->routeIs('admin.laporan.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Laporan User</span>
                </a>
            </li>
            @if($clientBranding['certificate_management_enabled'] ?? true)
            <li>
                <a href="{{ route('admin.certificate.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.certificate.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-award-line text-[20px] {{ request()->routeIs('admin.certificate.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Manajemen Sertifikat</span>
                </a>
            </li>
            @endif
        </ul>
    </div>
</aside>
