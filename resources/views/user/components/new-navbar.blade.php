@php
$user = auth()->user();
$currentRoute = request()->route()->getName();
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';

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
</style>

<nav class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-sm border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="{{ route('user.dashboard.index') }}" class="flex items-center gap-2">
                    @if(!empty($clientBranding['logo']))
                    <img src="{{ $clientBranding['logo'] }}" alt="Logo" class="h-8 w-auto">
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
                
                @if($user)
                <a href="{{ route('user.material.index') }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ isActive('user.material', $currentRoute) ? 'nav-item-active' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="ri-book-open-line mr-1.5 {{ isActive('user.material', $currentRoute) ? '' : 'text-gray-400' }}"></i>Materi
                </a>
                
                <a href="{{ route('user.package.tryout.list') }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ isActive('user.tryout', $currentRoute) || isActive('user.package.tryout', $currentRoute) ? 'nav-item-active' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="ri-file-list-3-line mr-1.5 {{ isActive('user.tryout', $currentRoute) || isActive('user.package.tryout', $currentRoute) ? '' : 'text-gray-400' }}"></i>Tryout
                </a>
                
                {{-- Paket Saya (khusus user login) --}}
                <a href="{{ route('user.package.my') }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ isActive('user.package.my', $currentRoute) || isActive('user.package.show', $currentRoute) ? 'nav-item-active' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="ri-road-map-line mr-1.5 {{ isActive('user.package.my', $currentRoute) || isActive('user.package.show', $currentRoute) ? '' : 'text-gray-400' }}"></i>Paket Saya
                </a>
                @endif
                
                {{-- Paket (untuk semua user - berbayar & gratis) --}}
                <a href="{{ route('user.package.index') }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $currentRoute === 'user.package.index' ? 'nav-item-active' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="ri-store-3-line mr-1.5 {{ $currentRoute === 'user.package.index' ? '' : 'text-gray-400' }}"></i>Paket
                </a>
            </div>
            
            <!-- Right Side -->
            <div class="flex items-center gap-3">
                @if($user)
                <!-- Notification -->
                <button class="relative p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="ri-notification-3-line text-xl"></i>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full" style="background-color: {{ $primaryColor }}"></span>
                </button>
                
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
        
        @if($user)
        <a href="{{ route('user.material.index') }}" class="flex flex-col items-center p-2 {{ isActive('user.material', $currentRoute) ? '' : 'text-gray-400' }}" style="{{ isActive('user.material', $currentRoute) ? 'color: ' . $primaryColor : '' }}">
            <i class="ri-book-open-line text-xl"></i>
            <span class="text-xs mt-0.5">Materi</span>
        </a>
        
        <a href="{{ route('user.package.my') }}" class="flex flex-col items-center p-2 {{ isActive('user.package.my', $currentRoute) ? '' : 'text-gray-400' }}" style="{{ isActive('user.package.my', $currentRoute) ? 'color: ' . $primaryColor : '' }}">
            <i class="ri-road-map-line text-xl"></i>
            <span class="text-xs mt-0.5">Saya</span>
        </a>
        @endif
        
        <a href="{{ route('user.package.index') }}" class="flex flex-col items-center p-2 {{ $currentRoute === 'user.package.index' ? '' : 'text-gray-400' }}" style="{{ $currentRoute === 'user.package.index' ? 'color: ' . $primaryColor : '' }}">
            <i class="ri-store-3-line text-xl"></i>
            <span class="text-xs mt-0.5">Paket</span>
        </a>
        
        @if(!$user)
        <a href="{{ route('login') }}" class="flex flex-col items-center p-2 text-gray-400">
            <i class="ri-login-box-line text-xl"></i>
            <span class="text-xs mt-0.5">Masuk</span>
        </a>
        @endif
    </div>
</div>
