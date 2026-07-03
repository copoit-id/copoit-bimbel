@php
    $sidebarPrimary = $clientBranding['sidebar_primary_color'] ?? false;
    $sidebarWrapperClasses = $sidebarPrimary ? 'bg-primary border-r border-primary text-white' : 'bg-white border-r border-gray-200';
    $sidebarInnerClasses = $sidebarPrimary ? 'bg-primary text-white' : 'bg-white';
    $sectionLabelClass = $sidebarPrimary ? 'text-white/70' : 'text-[#999999]';
    $linkActiveClass = $sidebarPrimary ? 'bg-white/10 text-white' : 'bg-primary text-white';
    $linkInactiveClass = $sidebarPrimary ? 'text-white/80 hover:bg-white/10' : 'text-black hover:bg-gray-100';
    $iconActiveClass = 'text-white';
    $iconInactiveClass = $sidebarPrimary ? 'text-white/80' : 'text-black';
    $dropdownButtonClass = $sidebarPrimary ? 'text-white/80 hover:bg-white/10' : 'text-black hover:bg-gray-100';
    $dropdownIconClass = $sidebarPrimary ? 'text-white/80' : 'text-black';
    $dropdownLinkActive = $sidebarPrimary ? 'bg-white/10 text-white' : 'bg-primary/10 text-primary';
    $dropdownLinkInactive = $sidebarPrimary ? 'text-white/80 hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100';
    $emptyTextClass = $sidebarPrimary ? 'text-white/70' : 'text-gray-500';
    $secondaryColor = $clientBranding['secondary_color'] ?? '#F3F3F3';
    $isPackageActive = request()->routeIs('user.package.*') || request()->routeIs('user.tryout.*');
    if ($sidebarPrimary) {
        $emptyCtaClasses = 'block w-full py-2 px-3 text-xs text-center text-primary rounded-lg hover:opacity-90 transition-colors duration-200';
        $emptyCtaStyle = "background-color: {$secondaryColor}; border: none;";
    } else {
        $emptyCtaClasses = 'block w-full py-2 px-3 text-xs text-center text-primary border border-primary rounded-lg hover:bg-primary hover:text-white transition-colors duration-200';
        $emptyCtaStyle = '';
    }
@endphp

<aside id="logo-sidebar"
    class="fixed top-0 left-0 z-40 md:z-30 w-64 h-screen pt-20 transition-transform -translate-x-full sm:translate-x-0 {{ $sidebarWrapperClasses }}"
    aria-label="Sidebar">
    <div class="h-full px-3 pb-4 overflow-y-auto {{ $sidebarInnerClasses }}">
        <p class="{{ $sectionLabelClass }} text-sm">{{ __('Home') }}</p>
        <ul class="font-medium space-y-1">
            <li>
                <a href="{{ route('user.dashboard.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('user.dashboard.index') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-home-9-line text-[20px] {{ request()->routeIs('user.dashboard.index') ? $iconActiveClass : $iconInactiveClass }} font-medium"></i>
                    <span class="ms-3">{{ __('Dashboard') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('user.package.index') }}"
                    class="flex items-center py-2 px-4 {{ $isPackageActive ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-store-3-line text-[20px] {{ $isPackageActive ? $iconActiveClass : $iconInactiveClass }} font-medium"></i>
                    <span class="ms-3">{{ __('Paket Tryout') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('user.practice.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('user.practice.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-pencil-ruler-line text-[20px] {{ request()->routeIs('user.practice.*') ? $iconActiveClass : $iconInactiveClass }} font-medium"></i>
                    <span class="ms-3">{{ __('Latihan Soal') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('user.help.index') }}"
                    class="flex items-center py-2 px-4  {{ request()->routeIs('user.help.index') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-question-line text-[20px]  {{ request()->routeIs('user.help.index') ? $iconActiveClass : $iconInactiveClass }} font-medium"></i>
                    <span class="ms-3">{{ __('Bantuan') }}</span>
                </a>
            </li>
            @if($clientBranding['certificate_management_enabled'] ?? true)
            <li>
                <a href="{{ route('user.certificate.validation') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('user.certificate.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-award-line text-[20px] {{ request()->routeIs('user.certificate.*') ? $iconActiveClass : $iconInactiveClass }} font-medium"></i>
                    <span class="ms-3">{{ __('Validasi Sertifikat') }}</span>
                </a>
            </li>
            @endif
        </ul>
        
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Handle dropdown toggles
    const dropdownToggles = document.querySelectorAll('[data-collapse-toggle]');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.getAttribute('aria-controls');
            const target = document.getElementById(targetId);
            const arrow = this.querySelector('svg');

            if (target) {
                target.classList.toggle('hidden');
                arrow.classList.toggle('rotate-180');
            }
        });
    });
});
</script>
