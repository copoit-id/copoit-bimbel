@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$headerPrimary = $clientBranding['header_primary_color'] ?? false;
$liveSessionLabel = $clientBranding['live_session_label'] ?? 'Kelas Belajar';
$bimbelNavLabel = $clientBranding['bimbel_nav_label'] ?? 'Bimbel';
$materialNavLabel = $clientBranding['material_nav_label'] ?? 'Kelas & Materi';
$packageNavLabel = $clientBranding['package_nav_label'] ?? 'Paket Belajar';
$tryoutNavLabel = $clientBranding['tryout_nav_label'] ?? 'Ujian & Try Out';
$canShowBimbel = $canShowPackage || $canShowSchedule || $canShowBooking || $canShowLearningProgress || $canShowMaterial || $canShowTryout || $canShowAiLearning;
$bimbelUrl = match (true) {
    $canShowPackage => route('user.package.index'),
    $canShowSchedule => route('user.class-schedule.index'),
    $canShowBooking => route('user.booking.index'),
    $canShowLearningProgress => route('user.development.index'),
    $canShowMaterial => route('user.material.index'),
    $canShowTryout => route('user.package.tryout.list'),
    default => $canShowAiLearning ? route('user.ai-learning.index') : route('landing'),
};
$homeUrl = $canShowDashboard ? route('user.dashboard.index') : route('landing');
$bimbelActive = isActive('user.material', $currentRoute)
    || isActive('user.tryout', $currentRoute)
    || isActive('user.package.tryout', $currentRoute)
    || isActive('user.tes-koran', $currentRoute)
    || isActive('user.tes-kecermatan', $currentRoute)
    || isActive('user.booking', $currentRoute)
    || isActive('user.development', $currentRoute)
    || isActive('user.class-schedule', $currentRoute)
    || $currentRoute === 'user.package.index'
    || isActive('user.ai-gateway', $currentRoute);

function isActive($route, $current) {
    return str_starts_with((string) $current, $route);
}
@endphp

<style>
[x-cloak] {
    display: none !important;
}
.nav-item-active {
    background-color: {{ $primaryColor }}15 !important;
    color: {{ $primaryColor }} !important;
}
.nav-item-active i {
    color: {{ $primaryColor }} !important;
}
.user-navbar-primary .nav-item-active {
    background-color: rgba(255, 255, 255, 0.16) !important;
    color: #ffffff !important;
}
.user-navbar-primary .nav-item-active i,
.user-navbar-primary .user-nav-link i {
    color: rgba(255, 255, 255, 0.85) !important;
}
.user-navbar-primary .nav-item-active i {
    color: #ffffff !important;
}
.user-navbar-primary .user-nav-link {
    color: rgba(255, 255, 255, 0.88) !important;
}
.user-navbar-primary .user-nav-link:hover,
.user-navbar-primary .user-account-trigger:hover {
    background-color: rgba(255, 255, 255, 0.12) !important;
}
.user-navbar-primary .user-account-trigger,
.user-navbar-primary .user-account-trigger span,
.user-navbar-primary .user-account-trigger i,
.user-navbar-primary .user-register-link {
    color: #ffffff !important;
}
.dropdown-menu {
    display: block;
    position: absolute;
    top: 100%;
    left: 0;
    margin-top: 0.5rem;
    min-width: 200px;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 10px 40px -10px rgba(0,0,0,0.15);
    border: 1px solid #e5e7eb;
    z-index: 50;
    padding: 0.5rem;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
    transition-delay: 0.1s;
}
.group:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
    transition-delay: 0s;
}
/* Delay saat mouse leave - dropdown tetap terlihat selama 500ms */
.group:not(:hover) .dropdown-menu {
    transition-delay: 0.3s;
}
.dropdown-item {
    display: flex;
    align-items: center;
    padding: 0.625rem 0.875rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    color: #4b5563;
    transition: all 0.15s;
}
.dropdown-item:hover {
    background-color: #f3f4f6;
    color: {{ $primaryColor }};
}
.dropdown-item i {
    margin-right: 0.625rem;
    font-size: 1.1rem;
    color: #9ca3af;
}
.dropdown-item:hover i {
    color: {{ $primaryColor }};
}
.dropdown-divider {
    height: 1px;
    background-color: #e5e7eb;
    margin: 0.375rem 0;
}
.dropdown-submenu { position: relative; }
.dropdown-submenu-menu {
    position: absolute;
    top: -0.5rem;
    left: calc(100% + 0.35rem);
    width: 13rem;
    padding: 0.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    background: white;
    box-shadow: 0 10px 40px -10px rgba(0,0,0,0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateX(-0.4rem);
    transition: opacity 0.15s ease, transform 0.15s ease, visibility 0.15s;
}
.dropdown-submenu:hover .dropdown-submenu-menu {
    opacity: 1;
    visibility: visible;
    transform: translateX(0);
}
</style>

<nav class="fixed {{ session('admin_login_as') ? 'top-[52px]' : 'top-0' }} left-0 right-0 z-[99998] {{ $headerPrimary ? 'user-navbar-primary bg-primary border-b border-primary text-white' : 'bg-white/95 border-b border-gray-100 backdrop-blur-sm' }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="{{ $homeUrl }}" class="flex items-center gap-2 rounded-lg px-1 py-1 {{ $headerPrimary ? 'hover:bg-white/10' : 'hover:bg-gray-50' }}">
                    @if(!empty($clientBranding['logo_url']))
                    <img src="{{ $clientBranding['logo_url'] }}" alt="Logo" class="client-brand-logo h-8 w-8">
                    @else
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: {{ $primaryColor }}">
                        <i class="ri-book-open-line text-white text-lg"></i>
                    </div>
                    @endif
                    <span class="font-bold text-xl {{ $headerPrimary ? 'text-white' : 'text-gray-800' }}">{{ $clientBranding['name'] ?? 'Belajar' }}</span>
                </a>
            </div>
            
            <!-- Main Navigation -->
            <div class="hidden md:flex items-center gap-1">
                @if($canShowDashboard)
                <a href="{{ route('user.dashboard.index') }}" 
                   class="user-nav-link px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ isActive('user.dashboard', $currentRoute) ? 'nav-item-active' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="ri-home-5-line mr-1.5 {{ isActive('user.dashboard', $currentRoute) ? '' : 'text-gray-400' }}"></i>Dashboard
                </a>
                @endif
                
                @if($canShowBimbel)
                <div class="relative group">
                    <a href="{{ $bimbelUrl }}" class="user-nav-link flex items-center rounded-lg px-4 py-2 text-sm font-medium transition-colors {{ $bimbelActive ? 'nav-item-active' : 'text-gray-600 hover:bg-gray-100' }}"><i class="ri-graduation-cap-line mr-1.5 {{ $bimbelActive ? '' : 'text-gray-400' }}"></i>{{ $bimbelNavLabel }}<i class="ri-arrow-down-s-line ml-1 text-gray-400"></i></a>
                    <div class="dropdown-menu min-w-52">
                        @if($canShowMaterial)
                        <div class="dropdown-submenu"><a href="{{ route('user.material.index') }}" class="dropdown-item justify-between {{ isActive('user.material', $currentRoute) ? 'font-bold text-primary' : '' }}"><span><i class="ri-book-open-line"></i>{{ $materialNavLabel }}</span><i class="ri-arrow-right-s-line !mr-0"></i></a><div class="dropdown-submenu-menu"><a href="{{ route('user.material.index') }}" class="dropdown-item">Semua Materi</a><a href="{{ route('user.material.videos') }}" class="dropdown-item">Video</a><a href="{{ route('user.material.documents') }}" class="dropdown-item">Dokumen</a>@if($liveSessionAvailable)<a href="{{ route('user.material.live-sessions') }}" class="dropdown-item">{{ $liveSessionLabel }}</a>@endif</div></div>
                        @endif
                        @if($canShowTryout)
                        <div class="dropdown-submenu"><a href="{{ route('user.package.tryout.list') }}" class="dropdown-item justify-between {{ isActive('user.tryout', $currentRoute) || isActive('user.package.tryout', $currentRoute) || isActive('user.tes-koran', $currentRoute) || isActive('user.tes-kecermatan', $currentRoute) ? 'font-bold text-primary' : '' }}"><span><i class="ri-file-list-3-line"></i>{{ $tryoutNavLabel }}</span><i class="ri-arrow-right-s-line !mr-0"></i></a><div class="dropdown-submenu-menu"><a href="{{ route('user.package.tryout.list') }}" class="dropdown-item">Daftar Tryout</a>
                            @if($tesKoranEnabled)<a href="{{ route('user.tes-koran.index') }}" class="dropdown-item">Tes Koran</a>@endif
                            @if($canShowTesKoran && \Illuminate\Support\Facades\Route::has('user.tes-kecermatan.index'))<a href="{{ route('user.tes-kecermatan.index') }}" class="dropdown-item">Tes Kecermatan</a>@endif
                        </div></div>
                        @endif
                        @if($canShowPackage || $canShowAiLearning)
                        <div class="dropdown-submenu"><a href="{{ $canShowPackage ? route('user.package.index') : route('user.ai-gateway.index') }}" class="dropdown-item justify-between {{ $currentRoute === 'user.package.index' || isActive('user.ai-gateway', $currentRoute) ? 'font-bold text-primary' : '' }}"><span><i class="ri-store-3-line"></i>{{ $packageNavLabel }}</span><i class="ri-arrow-right-s-line !mr-0"></i></a><div class="dropdown-submenu-menu">@if($canShowPackage)<a href="{{ route('user.package.index') }}" class="dropdown-item">Semua Paket</a>@endif @if($user && $canShowAiLearning)<a href="{{ route('user.ai-gateway.index') }}" class="dropdown-item">Paket AI</a>@endif</div></div>
                        @endif
                        @if($canShowSchedule)
                        <a href="{{ route('user.class-schedule.index') }}" class="dropdown-item {{ isActive('user.class-schedule', $currentRoute) ? 'font-bold text-primary' : '' }}"><i class="ri-calendar-2-line"></i>Jadwal Kelas</a>
                        @endif
                        @if($user && $canShowBooking)
                        <a href="{{ route('user.booking.index') }}" class="dropdown-item {{ isActive('user.booking', $currentRoute) ? 'font-bold text-primary' : '' }}"><i class="ri-calendar-schedule-line"></i>Booking Jadwal</a>
                        @endif
                        @if($user && $canShowLearningProgress)
                        <a href="{{ route('user.development.index') }}" class="dropdown-item {{ isActive('user.development', $currentRoute) ? 'font-bold text-primary' : '' }}"><i class="ri-line-chart-line"></i>Perkembangan</a>
                        @endif
                    </div>
                </div>
                @endif

                @if($user && $canShowAiLearning)
                <a href="{{ route('user.ai-learning.index') }}"
                   class="user-nav-link px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ isActive('user.ai-learning', $currentRoute) ? 'nav-item-active' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="ri-sparkling-2-line mr-1.5 {{ isActive('user.ai-learning', $currentRoute) ? '' : 'text-gray-400' }}"></i>AI Learning
                </a>
                @endif

                @if($user && $canShowPackage)
                {{-- Paket Saya with Dropdown (paling kanan) --}}
                <div class="relative group">
                    <a href="{{ route('user.package.my') }}" 
                       class="user-nav-link px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center {{ isActive('user.package.my', $currentRoute) || isActive('user.package.show', $currentRoute) ? 'nav-item-active' : 'text-gray-600 hover:bg-gray-100' }}">
                        <i class="ri-road-map-line mr-1.5 {{ isActive('user.package.my', $currentRoute) || isActive('user.package.show', $currentRoute) ? '' : 'text-gray-400' }}"></i>Paket Saya
                        <i class="ri-arrow-down-s-line ml-1 text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="{{ route('user.package.my') }}?tab=packages" class="dropdown-item">
                            <i class="ri-folder-3-line"></i>Daftar Paket
                        </a>
                        @if($canShowSchedule)
                        <a href="{{ route('user.class-schedule.index') }}" class="dropdown-item">
                            <i class="ri-calendar-2-line"></i>Jadwal Kelas
                        </a>
                        @endif
                        @if($canShowBooking)
                        <a href="{{ route('user.booking.index') }}" class="dropdown-item">
                            <i class="ri-calendar-schedule-line"></i>Booking Jadwal
                        </a>
                        @endif
                        @if($canShowLearningProgress)
                        <a href="{{ route('user.development.index') }}" class="dropdown-item">
                            <i class="ri-line-chart-line"></i>Perkembangan
                        </a>
                        @endif
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('user.package.my') }}?tab=videos" class="dropdown-item">
                            <i class="ri-video-line"></i>Video Saya
                        </a>
                        <a href="{{ route('user.package.my') }}?tab=documents" class="dropdown-item">
                            <i class="ri-file-text-line"></i>Dokumen Saya
                        </a>
                        <a href="{{ route('user.package.my') }}?tab=tryouts" class="dropdown-item">
                            <i class="ri-file-list-3-line"></i>Tryout Saya
                        </a>
                        @if($tesKoranEnabled)
                        <a href="{{ route('user.package.my') }}?tab=tes-koran" class="dropdown-item">
                            <i class="ri-file-edit-line"></i>Tes Koran Saya
                        </a>
                        @endif
                    </div>
                </div>
                @endif
                @if($canShowTutorChat)
                    @include('user.components.tutor-chat-drawer', [
                        'chatContacts' => $tutorChatContacts,
                        'unreadCount' => $tutorChatUnreadCount,
                    ])
                @endif
            </div>
            
            <!-- Right Side -->
            <div class="flex items-center gap-3">
                @if($user)
                <!-- User Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="user-account-trigger flex items-center gap-2 p-1.5 pr-3 rounded-full hover:bg-gray-100 transition-colors">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-semibold text-sm" style="background-color: {{ $primaryColor }}">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                        <span class="hidden sm:block text-sm font-medium text-gray-700 max-w-24 truncate">{{ $user->name }}</span>
                        <i class="ri-arrow-down-s-line text-gray-400"></i>
                    </button>
                    
                    <div x-show="open" @click.away="open = false" 
                         class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-50"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         style="display: none;">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $user->email }}</p>
                        </div>
                        @if($user && ($user->isAdmin() || $user->isSuperAdmin()))
                        <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm font-semibold text-red-650 hover:bg-red-50 border-b border-gray-100">
                            <i class="ri-shield-user-line mr-2"></i>Dashboard {{ $user->isTutor() ? 'Tutor' : 'Admin' }}
                        </a>
                        @endif
                        @if($canShowProfile)
                        <a href="{{ route('user.profile.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="ri-user-line mr-2"></i>Profil
                        </a>
                        @endif
                        @if($canShowPackage)
                        <a href="{{ route('user.package.riwayatPembelian') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="ri-history-line mr-2"></i>Riwayat
                        </a>
                        @endif
                        @if($canShowBooking)
                        <a href="{{ route('user.booking.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="ri-calendar-schedule-line mr-2"></i>Booking Jadwal
                        </a>
                        @endif
                        @if($canShowLearningProgress)
                        <a href="{{ route('user.development.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="ri-line-chart-line mr-2"></i>Perkembangan
                        </a>
                        @endif
                        @if($canShowAffiliateMenu)
                        <a href="{{ route('user.affiliate.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="ri-share-forward-line mr-2"></i>Affiliate
                        </a>
                        @endif
                        <form action="{{ route('logout') }}" method="POST" class="border-t border-gray-100 mt-1 pt-1">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="ri-logout-box-line mr-2"></i>Keluar
                            </button>
                        </form>
                    </div>
                </div>
                @else
                <!-- Login Button for Guest -->
                <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg text-sm font-medium {{ $headerPrimary ? 'bg-white text-primary hover:bg-white/90' : 'text-white hover:opacity-90' }} transition-opacity" @if(! $headerPrimary) style="background-color: {{ $primaryColor }}" @endif>
                    Masuk
                </a>
                <a href="{{ route('register') }}" class="user-register-link px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors">
                    Daftar
                </a>
                @endif
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Menu (fixed bottom) -->
<div class="md:hidden fixed bottom-0 left-0 right-0 z-50" x-data="{ bimbelOpen: false }">
    @if($canShowBimbel)
    <div
        x-cloak
        x-show="bimbelOpen"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="translate-y-2 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-2 opacity-0"
        class="absolute bottom-full left-3 right-3 mb-2 overflow-hidden rounded-2xl border border-gray-100 bg-white p-2 shadow-xl">
        <div class="flex items-center justify-between px-3 py-2">
            <span class="text-sm font-semibold text-gray-800">Menu {{ $bimbelNavLabel }}</span>
            <button type="button" @click="bimbelOpen = false" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100" aria-label="Tutup menu {{ $bimbelNavLabel }}">
                <i class="ri-close-line text-lg"></i>
            </button>
        </div>
        <div class="grid grid-cols-2 gap-1">
            @if($canShowMaterial)
            <a href="{{ route('user.material.index') }}" class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">
                <i class="ri-book-open-line text-lg text-gray-400"></i>{{ $materialNavLabel }}
            </a>
            @endif
            @if($canShowTryout)
            <a href="{{ route('user.package.tryout.list') }}" class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">
                <i class="ri-file-list-3-line text-lg text-gray-400"></i>{{ $tryoutNavLabel }}
            </a>
            @endif
            @if($canShowPackage)
            <a href="{{ route('user.package.index') }}" class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">
                <i class="ri-store-3-line text-lg text-gray-400"></i>{{ $packageNavLabel }}
            </a>
            @endif
            @if($canShowSchedule)
            <a href="{{ route('user.class-schedule.index') }}" class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">
                <i class="ri-calendar-2-line text-lg text-gray-400"></i>Jadwal Kelas
            </a>
            @endif
            @if($user && $canShowBooking)
            <a href="{{ route('user.booking.index') }}" class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">
                <i class="ri-calendar-schedule-line text-lg text-gray-400"></i>Booking
            </a>
            @endif
            @if($user && $canShowLearningProgress)
            <a href="{{ route('user.development.index') }}" class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">
                <i class="ri-line-chart-line text-lg text-gray-400"></i>Perkembangan
            </a>
            @endif
        </div>
    </div>
    @endif

    <div class="bg-white border-t border-gray-100">
    <div class="flex justify-around py-2">
        @if($canShowDashboard)
        <a href="{{ route('user.dashboard.index') }}" class="flex flex-col items-center p-2 {{ isActive('user.dashboard', $currentRoute) ? '' : 'text-gray-400' }}" style="{{ isActive('user.dashboard', $currentRoute) ? 'color: ' . $primaryColor : '' }}">
            <i class="ri-home-5-line text-xl"></i>
            <span class="text-xs mt-0.5">Dashboard</span>
        </a>
        @endif
        
        @if($canShowBimbel)
        <button type="button" @click="bimbelOpen = !bimbelOpen" :aria-expanded="bimbelOpen.toString()" class="flex flex-col items-center p-2 {{ $bimbelActive ? '' : 'text-gray-400' }}" style="{{ $bimbelActive ? 'color: ' . $primaryColor : '' }}">
            <i class="ri-graduation-cap-line text-xl"></i>
            <span class="text-xs mt-0.5">{{ $bimbelNavLabel }}</span>
        </button>
        @endif
        @if($user && $canShowAiLearning)
        <a href="{{ route('user.ai-learning.index') }}" class="flex flex-col items-center p-2 {{ isActive('user.ai-learning', $currentRoute) ? '' : 'text-gray-400' }}" style="{{ isActive('user.ai-learning', $currentRoute) ? 'color: ' . $primaryColor : '' }}">
            <i class="ri-sparkling-2-line text-xl"></i>
            <span class="text-xs mt-0.5">AI Learning</span>
        </a>
        @endif
        @if($user && $canShowPackage)
        <a href="{{ route('user.package.my') }}" class="flex flex-col items-center p-2 {{ isActive('user.package.my', $currentRoute) || isActive('user.package.show', $currentRoute) ? '' : 'text-gray-400' }}" style="{{ isActive('user.package.my', $currentRoute) || isActive('user.package.show', $currentRoute) ? 'color: ' . $primaryColor : '' }}">
            <i class="ri-road-map-line text-xl"></i>
            <span class="text-xs mt-0.5">Paket Saya</span>
        </a>
        @endif
        @if(!$user)
        <a href="{{ route('login') }}" class="flex flex-col items-center p-2 text-gray-400">
            <i class="ri-login-box-line text-xl"></i>
            <span class="text-xs mt-0.5">Masuk</span>
        </a>
        @endif
    </div>
    </div>
</div>
