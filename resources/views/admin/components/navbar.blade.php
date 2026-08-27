@php
    $portalLabel = auth()->user()?->isTutor() ? 'Tutor' : 'Admin';
    $profileUrl = auth()->user()?->isTutor()
        ? route('user.profile.index')
        : route('admin.profile.index');
    $canShowProfile = app(\App\Services\PlanModuleService::class)->allows('profile');
    $headerPrimary = $clientBranding['header_primary_color'] ?? false;
    $logoDisplayMode = ($clientBranding['logo_display_mode'] ?? 'square') === 'original'
        ? 'original'
        : 'square';
    $isOriginalLogo = $logoDisplayMode === 'original';
    $navClasses = $headerPrimary
        ? 'border-b border-primary bg-primary text-white'
        : 'border-b border-slate-200 bg-white/95 text-gray-900 backdrop-blur';
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
    $brandLinkClasses = $isOriginalLogo
        ? 'flex min-w-0 items-center gap-3 rounded-xl px-1 py-1 transition-colors hover:bg-black/5 focus:outline-none focus:ring-2'
        : 'flex min-w-0 items-center gap-2 rounded-xl px-1 py-1 transition-colors hover:bg-black/5 focus:outline-none focus:ring-2';
    $logoContainerClasses = $isOriginalLogo
        ? 'flex h-9 shrink-0 items-center sm:h-11'
        : 'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white p-1 shadow-sm sm:h-11 sm:w-11';
    $logoClasses = $isOriginalLogo
        ? 'client-brand-logo h-full w-auto max-w-[10rem] object-contain'
        : 'client-brand-logo h-full w-full object-contain';
@endphp

<nav class="fixed {{ !empty($isQuestionPickerMode) ? 'top-14' : 'top-0' }} z-50 w-full {{ $navClasses }}">
    <div class="px-2 py-2 sm:px-3 sm:py-3 lg:px-5 lg:pl-3">
        <div class="flex items-center justify-between">
            <div class="flex min-w-0 flex-1 items-center justify-start rtl:justify-end">
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
                <a href="{{ auth()->user()?->isTutor() ? route('tutor.dashboard') : route('admin.dashboard') }}" class="{{ $brandLinkClasses }} {{ $headerPrimary ? 'focus:ring-white/50' : 'focus:ring-primary/30' }}">
                    <span class="{{ $logoContainerClasses }}">
                        <img src="{{ $clientBranding['logo_url'] }}" class="{{ $logoClasses }}"
                        alt="{{ $clientBranding['name'] }} Logo" />
                    </span>
                    <div class="flex min-w-0 flex-col justify-start">
                        <p class="text-sm sm:text-[20px] font-bold {{ $brandTitleClass }} truncate">{{ $clientBranding['name'] }}</p>
                        <p class="hidden sm:block text-[12px] font-medium {{ $brandSubtitleClass }}">{{ $portalLabel }} Panel</p>
                    </div>
                </a>
            </div>

            <div class="flex shrink-0 items-center gap-2 sm:gap-4">
                @if(auth()->user()?->role === 'admin_demo' && auth()->user()?->admin_expires_at)
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full border border-gray-200 bg-white/80 text-sm text-gray-700"
                    data-admin-demo-expiry="{{ auth()->user()->admin_expires_at->setTimezone('Asia/Jakarta')->toIso8601String() }}">
                    <i class="ri-timer-line text-gray-800"></i>
                    <span class="font-semibold text-gray-800">Sisa Akses Demo:</span>
                    <span id="admin-demo-countdown" class="tabular-nums text-gray-700">--:--:--</span>
                </div>
                @endif
                <!-- User Info -->
                @if($canShowProfile)
                <a href="{{ $profileUrl }}" class="flex items-center gap-2 sm:gap-3">
                @else
                <div class="flex items-center gap-2 sm:gap-3">
                @endif
                    <div class="hidden sm:block text-right max-w-[140px]">
                        <p class="text-sm font-medium {{ $userNameClass }} truncate">{{ auth()->user()->name ?? $portalLabel }}</p>
                        <p class="text-xs {{ $userRoleClass }} truncate">{{ ucfirst(auth()->user()->role ?? strtolower($portalLabel)) }}</p>
                    </div>
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? $portalLabel) }}&background=6366f1&color=fff&size=40"
                        class="w-8 h-8 rounded-full">
                @if($canShowProfile)
                </a>
                @else
                </div>
                @endif

                <!-- Logout Button -->
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="{{ $logoutButtonClasses }} px-2 sm:px-3"
                        onclick="return confirm('Yakin ingin logout?')">
                        <i class="ri-logout-circle-r-line"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

@if(auth()->user()?->role === 'admin_demo' && auth()->user()?->admin_expires_at)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('[data-admin-demo-expiry]');
    const label = document.getElementById('admin-demo-countdown');
    if (!container || !label) return;

    const expiryIso = container.getAttribute('data-admin-demo-expiry');
    const expiry = new Date(expiryIso).getTime();

    const format = (ms) => {
        const totalSeconds = Math.max(0, Math.floor(ms / 1000));
        const days = Math.floor(totalSeconds / 86400);
        const hours = Math.floor((totalSeconds % 86400) / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        if (days > 0) return `${days}h ${hours}j ${minutes}m`;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    };

    const tick = () => {
        const now = Date.now();
        const remaining = expiry - now;
        label.textContent = format(remaining);
    };

    tick();
    setInterval(tick, 1000);
});
</script>
@endpush
@endif
