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
        <p class="{{ $sectionLabelClass }} text-sm">Home</p>
        <ul class="font-medium space-y-1">
            <li>
                <a href="{{ route('user.dashboard.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('user.dashboard.index') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-home-9-line text-[20px] {{ request()->routeIs('user.dashboard.index') ? $iconActiveClass : $iconInactiveClass }} font-medium"></i>
                    <span class="ms-3">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('user.package.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('user.package.index') || request()->routeIs('user.package.riwayatPembelian') || request()->routeIs('user.package.riwayatPembelianAktif') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-store-3-line text-[20px] {{ request()->routeIs('user.package.index') || request()->routeIs('user.package.riwayatPembelian') || request()->routeIs('user.package.riwayatPembelianAktif') ? $iconActiveClass : $iconInactiveClass }} font-medium"></i>
                    <span class="ms-3">Paket Pembelian</span>
                </a>
            </li>
            <li>
                <a href="{{ route('user.event.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('user.event.index') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-calendar-event-line text-[20px] {{ request()->routeIs('user.event.index') ? $iconActiveClass : $iconInactiveClass }} font-medium"></i>
                    <span class="ms-3">Event Gratis</span>
                </a>
            </li>
            <li>
                <a href="{{ route('user.help.index') }}"
                    class="flex items-center py-2 px-4  {{ request()->routeIs('user.help.index') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-question-line text-[20px]  {{ request()->routeIs('user.help.index') ? $iconActiveClass : $iconInactiveClass }} font-medium"></i>
                    <span class="ms-3">Bantuan</span>
                </a>
            </li>
            @if($clientBranding['certificate_management_enabled'] ?? true)
            <li>
                <a href="{{ route('user.certificate.validation') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('user.certificate.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-award-line text-[20px] {{ request()->routeIs('user.certificate.*') ? $iconActiveClass : $iconInactiveClass }} font-medium"></i>
                    <span class="ms-3">Validasi Sertifikat</span>
                </a>
            </li>
            @endif
        </ul>
        <p class="{{ $sectionLabelClass }} text-sm mt-6">Paket Saya</p>
        <ul class="font-medium space-y-1">
            @if(isset($sidebarPackages) && $sidebarPackages->count() > 0)
            @foreach($sidebarPackages as $access)
            <li>
                <button type="button"
                    class="flex items-center w-full py-2 px-4 {{ $dropdownButtonClass }} rounded-lg group transition-colors duration-200"
                    aria-controls="dropdown-package-{{ $access->package->package_id }}"
                    data-collapse-toggle="dropdown-package-{{ $access->package->package_id }}">
                    <i class="ri-package-line text-[20px] {{ $dropdownIconClass }} font-medium"></i>
                    <span class="flex-1 ms-3 text-left whitespace-nowrap">{{ Str::limit($access->package->name, 20)
                        }}</span>
                    <svg class="w-3 h-3 transition-transform duration-200 {{ $sidebarPrimary ? 'text-white/80' : 'text-black' }}"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 4 4 4-4" />
                    </svg>
                </button>
                <ul id="dropdown-package-{{ $access->package->package_id }}" class="hidden py-2 space-y-1">
                    @if($access->package->type_package === 'tryout')
                    <li>
                        <a href="{{ route('user.package.tryout', $access->package->package_id) }}"
                            class="flex items-center w-full py-2 px-4 pl-11 rounded-lg transition-colors duration-200 {{ request()->routeIs('user.package.tryout') && request()->route('id_package') == $access->package->package_id ? $dropdownLinkActive : $dropdownLinkInactive }}">
                            <i class="ri-file-list-3-line text-[16px] mr-2"></i>
                            Tryout
                        </a>
                    </li>
                    @elseif($access->package->type_package === 'bimbel')
                    <li>
                        <a href="{{ route('user.package.bimbel', $access->package->package_id) }}"
                            class="flex items-center w-full py-2 px-4 pl-11 rounded-lg transition-colors duration-200 {{ request()->routeIs('user.package.bimbel') && request()->route('id_package') == $access->package->package_id ? $dropdownLinkActive : $dropdownLinkInactive }}">
                            <i class="ri-book-open-line text-[16px] mr-2"></i>
                            Kelas
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('user.package.tryout', $access->package->package_id) }}"
                            class="flex items-center w-full py-2 px-4 pl-11 rounded-lg transition-colors duration-200 {{ request()->routeIs('user.package.tryout') && request()->route('id_package') == $access->package->package_id ? $dropdownLinkActive : $dropdownLinkInactive }}">
                            <i class="ri-file-list-3-line text-[16px] mr-2"></i>
                            Tryout
                        </a>
                    </li>
                    @elseif($access->package->type_package === 'sertifikasi')
                    <li>
                        <a href="{{ route('user.package.tryout', $access->package->package_id) }}"
                            class="flex items-center w-full py-2 px-4 pl-11 rounded-lg transition-colors duration-200 {{ request()->routeIs('user.package.tryout') && request()->route('id_package') == $access->package->package_id ? $dropdownLinkActive : $dropdownLinkInactive }}">
                            <i class="ri-award-line text-[16px] mr-2"></i>
                            Sertifikasi
                        </a>
                    </li>
                    @endif
                </ul>
            </li>
            @endforeach
            @else
            <li>
                <div class="flex items-center py-2 px-4 {{ $emptyTextClass }} rounded-lg">
                    <i class="ri-shopping-bag-line text-[20px] mr-3"></i>
                    <span class="text-sm">Belum ada paket</span>
                </div>
                <div class="mt-2 px-4">
                    <a href="{{ route('user.package.index') }}" class="{{ $emptyCtaClasses }}"
                        @if($emptyCtaStyle) style="{{ $emptyCtaStyle }}" @endif>
                        Beli Paket
                    </a>
                </div>
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
