@php
$user = auth()->user();
$currentRoute = request()->route()->getName();
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$faqLabel = $clientBranding['faq_label'] ?? 'FAQ';
$tesKoranEnabled = $clientBranding['tes_koran_enabled'] ?? true;
$canShowAffiliateMenu = ($clientBranding['affiliate_menu_enabled'] ?? false)
    && \Illuminate\Support\Facades\Route::has('user.affiliate.index');

function isActive($route, $current) {
    return str_starts_with($current, $route);
}
@endphp

<style>
.nav-item-active {
    background-color: {{ $primaryColor }}15 !important;
    color: {{ $primaryColor }} !important;
}
.nav-item-active i {
    color: {{ $primaryColor }} !important;
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
</style>

<nav class="fixed {{ session('admin_login_as') ? 'top-[52px]' : 'top-0' }} left-0 right-0 z-[99998] bg-white/95 backdrop-blur-sm border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="{{ route('user.dashboard.index') }}" class="flex items-center gap-2">
                    @if(!empty($clientBranding['logo_url']))
                    <img src="{{ $clientBranding['logo_url'] }}" alt="Logo" class="h-8 w-auto">
                    @else
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: {{ $primaryColor }}">
                        <i class="ri-book-open-line text-white text-lg"></i>
                    </div>
                    @endif
                    <span class="font-bold text-xl text-gray-800">{{ $clientBranding['name'] ?? 'Belajar' }}</span>
                </a>
            </div>
            
            <!-- Main Navigation -->
            <div class="hidden md:flex items-center gap-1">
                <a href="{{ route('user.dashboard.index') }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ isActive('user.dashboard', $currentRoute) ? 'nav-item-active' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="ri-home-5-line mr-1.5 {{ isActive('user.dashboard', $currentRoute) ? '' : 'text-gray-400' }}"></i>Beranda
                </a>
                
                {{-- Materi with Dropdown - Accessible by Guest & User --}}
                <div class="relative group">
                    <a href="{{ route('user.material.index') }}" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center {{ isActive('user.material', $currentRoute) ? 'nav-item-active' : 'text-gray-600 hover:bg-gray-100' }}">
                        <i class="ri-book-open-line mr-1.5 {{ isActive('user.material', $currentRoute) ? '' : 'text-gray-400' }}"></i>Materi
                        <i class="ri-arrow-down-s-line ml-1 text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="{{ route('user.material.index') }}" class="dropdown-item {{ isActive('user.material.index', $currentRoute) ? 'text-emerald-600' : '' }}">
                            <i class="ri-apps-line"></i>Semua Materi
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('user.material.videos') }}" class="dropdown-item {{ isActive('user.material.videos', $currentRoute) ? 'text-emerald-600' : '' }}">
                            <i class="ri-video-line"></i>Video
                        </a>
                        <a href="{{ route('user.material.documents') }}" class="dropdown-item {{ isActive('user.material.documents', $currentRoute) ? 'text-emerald-600' : '' }}">
                            <i class="ri-file-text-line"></i>Dokumen
                        </a>
                        <a href="{{ route('user.material.live-sessions') }}" class="dropdown-item {{ isActive('user.material.live-sessions', $currentRoute) ? 'text-emerald-600' : '' }}">
                            <i class="ri-live-line"></i>Live Session
                        </a>
                    </div>
                </div>
                
                {{-- Tryout - Accessible by Guest & User --}}
                <a href="{{ route('user.package.tryout.list') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ isActive('user.tryout', $currentRoute) || isActive('user.package.tryout', $currentRoute) ? 'nav-item-active' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="ri-file-list-3-line mr-1.5 {{ isActive('user.tryout', $currentRoute) || isActive('user.package.tryout', $currentRoute) ? '' : 'text-gray-400' }}"></i>Tryout
                </a>

                @if($tesKoranEnabled)
                {{-- Tes Koran - Accessible by Guest & User --}}
                <a href="{{ route('user.tes-koran.index') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ isActive('user.tes-koran', $currentRoute) ? 'nav-item-active' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="ri-file-edit-line mr-1.5 {{ isActive('user.tes-koran', $currentRoute) ? '' : 'text-gray-400' }}"></i>Tes Koran
                </a>
                @endif
                
                {{-- Paket (untuk semua user - berbayar & gratis) --}}
                <a href="{{ route('user.package.index') }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $currentRoute === 'user.package.index' ? 'nav-item-active' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="ri-store-3-line mr-1.5 {{ $currentRoute === 'user.package.index' ? '' : 'text-gray-400' }}"></i>Paket
                </a>

                <a href="{{ route('user.help.index') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ isActive('user.help', $currentRoute) ? 'nav-item-active' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="ri-question-line mr-1.5 {{ isActive('user.help', $currentRoute) ? '' : 'text-gray-400' }}"></i>{{ $faqLabel }}
                </a>
                
                @if($user)
                {{-- Paket Saya with Dropdown (paling kanan) --}}
                <div class="relative group">
                    <a href="{{ route('user.package.my') }}" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center {{ isActive('user.package.my', $currentRoute) || isActive('user.package.show', $currentRoute) ? 'nav-item-active' : 'text-gray-600 hover:bg-gray-100' }}">
                        <i class="ri-road-map-line mr-1.5 {{ isActive('user.package.my', $currentRoute) || isActive('user.package.show', $currentRoute) ? '' : 'text-gray-400' }}"></i>Paket Saya
                        <i class="ri-arrow-down-s-line ml-1 text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="{{ route('user.package.my') }}?tab=packages" class="dropdown-item">
                            <i class="ri-folder-3-line"></i>Daftar Paket
                        </a>
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
            </div>
            
            <!-- Right Side -->
            <div class="flex items-center gap-3">
                @if($user)
                <!-- User Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center gap-2 p-1.5 pr-3 rounded-full hover:bg-gray-100 transition-colors">
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
                        <a href="{{ route('user.profile.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="ri-user-line mr-2"></i>Profil
                        </a>
                        <a href="{{ route('user.package.riwayatPembelian') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="ri-history-line mr-2"></i>Riwayat
                        </a>
                        @if($canShowAffiliateMenu)
                        <a href="{{ route('user.affiliate.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="ri-share-forward-line mr-2"></i>Affiliate
                        </a>
                        @endif
                        <a href="{{ route('user.help.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="ri-question-line mr-2"></i>{{ $faqLabel }}
                        </a>
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
                <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg text-sm font-medium text-white hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
                    Masuk
                </a>
                <a href="{{ route('register') }}" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors">
                    Daftar
                </a>
                @endif
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Menu (fixed bottom) -->
<div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 z-50">
    <div class="flex justify-around py-2">
        <a href="{{ route('user.dashboard.index') }}" class="flex flex-col items-center p-2 {{ isActive('user.dashboard', $currentRoute) ? '' : 'text-gray-400' }}" style="{{ isActive('user.dashboard', $currentRoute) ? 'color: ' . $primaryColor : '' }}">
            <i class="ri-home-5-line text-xl"></i>
            <span class="text-xs mt-0.5">Beranda</span>
        </a>
        
        {{-- Materi - Accessible by Guest & User --}}
        <a href="{{ route('user.material.index') }}" class="flex flex-col items-center p-2 {{ isActive('user.material', $currentRoute) ? '' : 'text-gray-400' }}" style="{{ isActive('user.material', $currentRoute) ? 'color: ' . $primaryColor : '' }}">
            <i class="ri-book-open-line text-xl"></i>
            <span class="text-xs mt-0.5">Materi</span>
        </a>
        
        {{-- Tryout - Accessible by Guest & User --}}
        <a href="{{ route('user.package.tryout.list') }}" class="flex flex-col items-center p-2 {{ isActive('user.package.tryout', $currentRoute) ? '' : 'text-gray-400' }}" style="{{ isActive('user.package.tryout', $currentRoute) ? 'color: ' . $primaryColor : '' }}">
            <i class="ri-file-list-3-line text-xl"></i>
            <span class="text-xs mt-0.5">Tryout</span>
        </a>

        @if($tesKoranEnabled)
        <a href="{{ route('user.tes-koran.index') }}" class="flex flex-col items-center p-2 {{ isActive('user.tes-koran', $currentRoute) ? '' : 'text-gray-400' }}" style="{{ isActive('user.tes-koran', $currentRoute) ? 'color: ' . $primaryColor : '' }}">
            <i class="ri-file-edit-line text-xl"></i>
            <span class="text-xs mt-0.5">Koran</span>
        </a>
        @endif
        
        <a href="{{ route('user.package.index') }}" class="flex flex-col items-center p-2 {{ $currentRoute === 'user.package.index' ? '' : 'text-gray-400' }}" style="{{ $currentRoute === 'user.package.index' ? 'color: ' . $primaryColor : '' }}">
            <i class="ri-store-3-line text-xl"></i>
            <span class="text-xs mt-0.5">Paket</span>
        </a>

        <a href="{{ route('user.help.index') }}" class="flex flex-col items-center p-2 {{ isActive('user.help', $currentRoute) ? '' : 'text-gray-400' }}" style="{{ isActive('user.help', $currentRoute) ? 'color: ' . $primaryColor : '' }}">
            <i class="ri-question-line text-xl"></i>
            <span class="text-xs mt-0.5">{{ $faqLabel }}</span>
        </a>
        
        @if($user)
        <a href="{{ route('user.package.my') }}" class="flex flex-col items-center p-2 {{ isActive('user.package.my', $currentRoute) ? '' : 'text-gray-400' }}" style="{{ isActive('user.package.my', $currentRoute) ? 'color: ' . $primaryColor : '' }}">
            <i class="ri-road-map-line text-xl"></i>
            <span class="text-xs mt-0.5">Saya</span>
        </a>
        @else
        <a href="{{ route('login') }}" class="flex flex-col items-center p-2 text-gray-400">
            <i class="ri-login-box-line text-xl"></i>
            <span class="text-xs mt-0.5">Masuk</span>
        </a>
        @endif
    </div>
</div>
