@php
    $headerPrimary = $clientBranding['header_primary_color'] ?? false;
    $navClasses = $headerPrimary ? 'bg-primary border-b border-primary text-white' : 'bg-white border-b border-gray-200';
    $toggleButtonClasses = $headerPrimary
        ? 'inline-flex items-center p-2 text-sm text-white rounded-lg sm:hidden hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/30'
        : 'inline-flex items-center p-2 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200';
    $brandTitleClass = $headerPrimary ? 'text-white' : 'text-dark';
    $brandSubtitleClass = $headerPrimary ? 'text-white/80' : 'text-gray-500';
    $userNameClass = $headerPrimary ? 'text-white' : 'text-gray-900';
    $userRoleClass = $headerPrimary ? 'text-white/80' : 'text-gray-500';
    $logoutButtonClasses = $headerPrimary
        ? 'flex items-center gap-2 px-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors'
        : 'flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition-colors';
@endphp

<nav class="fixed top-0 z-50 w-full {{ $navClasses }}">
    <div class="px-3 py-3 lg:px-5 lg:pl-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center justify-start rtl:justify-end">
                <button data-drawer-target="logo-sidebar" data-drawer-toggle="logo-sidebar" aria-controls="logo-sidebar"
                    type="button" class="{{ $toggleButtonClasses }}">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path clip-rule="evenodd" fill-rule="evenodd"
                            d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z">
                        </path>
                    </svg>
                </button>
                <a href="#" class="flex ms-2 md:me-12 items-center">
                    <img src="{{ $clientBranding['logo_url'] }}" class="w-12 h-12 object-cover me-1"
                        alt="{{ $clientBranding['name'] }} Logo" />
                    <div class="flex flex-col justify-start">
                        <p class="text-[20px] font-bold {{ $brandTitleClass }}">{{ $clientBranding['name'] }}</p>
                        <p class="font-light text-[13px] mt-[-8px] {{ $brandSubtitleClass }}">Admin Panel</p>
                    </div>
                </a>
            </div>

            <div class="flex items-center gap-4">
                <!-- User Info -->
                <a href="{{ route('admin.profile.index') }}" class="flex items-center gap-3">
                    <div class="text-right">
                        <p class="text-sm font-medium {{ $userNameClass }}">{{ auth()->user()->name ?? 'Admin' }}</p>
                        <p class="text-xs {{ $userRoleClass }}">{{ ucfirst(auth()->user()->role ?? 'admin') }}</p>
                    </div>
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'Admin') }}&background=6366f1&color=fff&size=40"
                        class="w-8 h-8 rounded-full">
                </a>

                <!-- Logout Button -->
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="{{ $logoutButtonClasses }}"
                        onclick="return confirm('Yakin ingin logout?')">
                        <i class="ri-logout-circle-r-line"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
