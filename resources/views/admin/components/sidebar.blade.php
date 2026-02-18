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
        <p class="{{ $sectionLabelClass }} text-sm">{{ __('Menu') }}</p>
        <ul class="space-y-1 font-medium">
            <li>
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.dashboard') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-home-line text-[20px] {{ request()->routeIs('admin.dashboard') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">{{ __('Dashboard') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.tryout.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.tryout.*')  || request()->routeIs('admin.question.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-draft-line text-[20px] {{ request()->routeIs('admin.tryout.*') || request()->routeIs('admin.question.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">{{ __('Manajemen Tryout') }}</span>
                </a>
            </li>
            @if($clientBranding['class_management_enabled'] ?? false)
            <li>
                <a href="{{ route('admin.class.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.class.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-video-line text-[20px] {{ request()->routeIs('admin.class.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">{{ __('Manajemen Kelas') }}</span>
                </a>
            </li>
            @endif
            <li>
                <a href="{{ route('admin.question-bank.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.question-bank.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-folder-3-line text-[20px] {{ request()->routeIs('admin.question-bank.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">{{ __('Bank Soal') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.user.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.user.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-user-3-line text-[20px] {{ request()->routeIs('admin.user.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">{{ __('Manajemen Users') }}</span>
                </a>
            </li>
            {{-- <li>
                <a href="{{ route('admin.akses.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.akses.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-key-line text-[20px] {{ request()->routeIs('admin.akses.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">{{ __('Akses User') }}</span>
                </a>
            </li> --}}
            <li>
                <a href="{{ route('admin.leaderboard.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.leaderboard.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-bar-chart-line text-[20px] {{ request()->routeIs('admin.leaderboard.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">{{ __('Leaderboard') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.laporan.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.laporan.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-file-chart-line text-[20px] {{ request()->routeIs('admin.laporan.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">{{ __('Laporan Tryout') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.essay-review.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.essay-review.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-pencil-line text-[20px] {{ request()->routeIs('admin.essay-review.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">{{ __('Koreksi Essay') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.faq.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.faq.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-question-line text-[20px] {{ request()->routeIs('admin.faq.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">{{ __('FAQ') }}</span>
                </a>
            </li>
            @if($clientBranding['certificate_management_enabled'] ?? true)
            <li>
                <a href="{{ route('admin.certificate.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.certificate.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-award-line text-[20px] {{ request()->routeIs('admin.certificate.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">{{ __('Manajemen Sertifikat') }}</span>
                </a>
            </li>
            @endif
            <li>
                <a href="{{ route('admin.settings.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.settings.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-settings-3-line text-[20px] {{ request()->routeIs('admin.settings.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">{{ __('Pengaturan') }}</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
