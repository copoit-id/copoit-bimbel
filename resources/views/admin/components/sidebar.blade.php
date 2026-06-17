@php
    $sidebarPrimary = $clientBranding['sidebar_primary_color'] ?? false;
    $sidebarWrapperClasses = $sidebarPrimary ? 'bg-primary border-r border-primary text-white' : 'bg-white border-r border-gray-200';
    $sidebarInnerClasses = $sidebarPrimary ? 'bg-primary text-white' : 'bg-white';
    $sectionLabelClass = $sidebarPrimary ? 'text-white/70' : 'text-[#999999]';
    $linkActiveClass = $sidebarPrimary ? 'bg-white/10 text-white' : 'bg-primary text-white';
    $linkInactiveClass = $sidebarPrimary ? 'text-white/80 hover:bg-white/10' : 'text-black hover:bg-gray-100';
    $iconActiveClass = 'text-white';
    $iconInactiveClass = $sidebarPrimary ? 'text-white/80' : 'text-black';
    $authUser = auth()->user();
    $isSuperAdmin = $authUser?->isSuperAdmin() ?? false;
    $canAccessAdminPanel = $authUser?->canAccessAdminPanel() ?? false;
    $permissionSlugs = $authUser?->getEffectivePermissionSlugs() ?? [];
    $adminRouteExists = fn (string $route): bool => \Illuminate\Support\Facades\Route::has($route);
    $canFeatureView = function (string $feature) use ($isSuperAdmin, $canAccessAdminPanel, $permissionSlugs): bool {
        if ($isSuperAdmin) {
            return true;
        }

        if ($feature === 'dashboard') {
            return $canAccessAdminPanel;
        }

        return in_array($feature . '.view', $permissionSlugs, true);
    };
    $canShowDestinationCategories = $canFeatureView('user')
        && $adminRouteExists('admin.participant-destination-categories.index');
    $canShowDiscountMenu = ($clientBranding['discount_menu_enabled'] ?? true)
        && $canAccessAdminPanel
        && $adminRouteExists('admin.discounts.index');
    $canShowAffiliateMenu = ($clientBranding['affiliate_menu_enabled'] ?? false)
        && $canAccessAdminPanel
        && $adminRouteExists('admin.affiliate.index');
    $isMaterialManagementActive = request()->routeIs('admin.material.index')
        || request()->routeIs('admin.material.create')
        || request()->routeIs('admin.material.edit');
    $isCategoryActive = request()->routeIs('admin.material.material-category.*')
        || ($canShowDestinationCategories && request()->routeIs('admin.participant-destination-categories.*'));
    $isMasterActive = request()->routeIs('admin.package.*')
        || request()->routeIs('admin.tryout.*')
        || request()->routeIs('admin.question.*')
        || request()->routeIs('admin.class.*')
        || request()->routeIs('admin.tes-koran.*')
        || $isMaterialManagementActive;
    $isTesKoranActive = request()->routeIs('admin.tes-koran.*');
    $isUserActive = request()->routeIs('admin.user.*')
        || request()->routeIs('admin.akses.*');
    $isReportActive = request()->routeIs('admin.leaderboard.*')
        || request()->routeIs('admin.laporan.*')
        || request()->routeIs('admin.essay-review.*')
        || request()->routeIs('admin.feedback.*');
    $isFinanceActive = request()->routeIs('admin.finance.*') || request()->routeIs('admin.pembayaran.*');
@endphp

<aside id="logo-sidebar" x-ignore
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform -translate-x-full sm:translate-x-0 {{ $sidebarWrapperClasses }}"
    aria-label="Sidebar">
    <div class="h-full px-3 pb-4 overflow-y-auto {{ $sidebarInnerClasses }}">
        <p class="{{ $sectionLabelClass }} text-sm">Menu</p>
        <ul class="space-y-1 font-medium">
            @if($canFeatureView('dashboard'))
            <li>
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.dashboard') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-home-line text-[20px] {{ request()->routeIs('admin.dashboard') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Dashboard</span>
                </a>
            </li>
            @endif
            <li>
                <details id="menu-master" class="group" {{ $isMasterActive ? 'open' : '' }}>
                    <summary class="flex items-center justify-between py-2 px-4 cursor-pointer {{ $isMasterActive ? $linkActiveClass : $linkInactiveClass }} rounded-lg group" style="list-style: none;">
                        <span class="flex items-center">
                            <i class="ri-stack-line text-[20px] {{ $isMasterActive ? $iconActiveClass : $iconInactiveClass }}"></i>
                            <span class="ms-3">Manajemen Master</span>
                        </span>
                        <i class="ri-arrow-down-s-line text-[18px] transition-transform group-open:rotate-180 {{ $isMasterActive ? $iconActiveClass : $iconInactiveClass }}"></i>
                    </summary>
                    <ul class="mt-1 ms-2 space-y-1">
                        @if($canFeatureView('package'))
                        <li>
                            <a href="{{ route('admin.package.index') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ request()->routeIs('admin.package.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Manajemen Paket</span>
                            </a>
                        </li>
                        @endif
                        @if($canFeatureView('tryout') || $canFeatureView('question'))
                        <li>
                            <a href="{{ route('admin.tryout.index') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ request()->routeIs('admin.tryout.*')  || request()->routeIs('admin.question.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Manajemen Tryout</span>
                            </a>
                        </li>
                        @endif
                        @if($canFeatureView('class'))
                        <li>
                            <a href="{{ route('admin.class.index') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ request()->routeIs('admin.class.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Manajemen Kelas</span>
                            </a>
                        </li>
                        @endif
                        <li>
                            <a href="{{ route('admin.material.index') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ $isMaterialManagementActive ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Manajemen Materi</span>
                            </a>
                        </li>
                        @if($canFeatureView('tes_koran'))
                        <li>
                            <a href="{{ route('admin.tes-koran.index') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ $isTesKoranActive ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Tes Koran</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </details>
            </li>
            @if($canFeatureView('question_bank'))
            <li>
                <a href="{{ route('admin.question-bank.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.question-bank.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-folder-3-line text-[20px] {{ request()->routeIs('admin.question-bank.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Bank Soal</span>
                </a>
            </li>
            @endif
            <li>
                <details id="menu-category" class="group" {{ $isCategoryActive ? 'open' : '' }}>
                    <summary class="flex items-center justify-between py-2 px-4 cursor-pointer {{ $isCategoryActive ? $linkActiveClass : $linkInactiveClass }} rounded-lg group" style="list-style: none;">
                        <span class="flex items-center">
                            <i class="ri-folder-settings-line text-[20px] {{ $isCategoryActive ? $iconActiveClass : $iconInactiveClass }}"></i>
                            <span class="ms-3">Kategori</span>
                        </span>
                        <i class="ri-arrow-down-s-line text-[18px] transition-transform group-open:rotate-180 {{ $isCategoryActive ? $iconActiveClass : $iconInactiveClass }}"></i>
                    </summary>
                    <ul class="mt-1 ms-2 space-y-1">
                        <li>
                            <a href="{{ route('admin.material.material-category.index') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ request()->routeIs('admin.material.material-category.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Kategori Materi</span>
                            </a>
                        </li>
                        @if($canShowDestinationCategories)
                        <li>
                            <a href="{{ route('admin.participant-destination-categories.index') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ request()->routeIs('admin.participant-destination-categories.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Tujuan / Instansi</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </details>
            </li>
            <li>
                <details id="menu-user" class="group" {{ $isUserActive ? 'open' : '' }}>
                    <summary class="flex items-center justify-between py-2 px-4 cursor-pointer {{ $isUserActive ? $linkActiveClass : $linkInactiveClass }} rounded-lg group" style="list-style: none;">
                        <span class="flex items-center">
                            <i class="ri-user-2-line text-[20px] {{ $isUserActive ? $iconActiveClass : $iconInactiveClass }}"></i>
                            <span class="ms-3">User</span>
                        </span>
                        <i class="ri-arrow-down-s-line text-[18px] transition-transform group-open:rotate-180 {{ $isUserActive ? $iconActiveClass : $iconInactiveClass }}"></i>
                    </summary>
                    <ul class="mt-1 ms-2 space-y-1">
                        @if($canFeatureView('user'))
                        <li>
                            <a href="{{ route('admin.user.index') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ request()->routeIs('admin.user.*') && !request()->routeIs('admin.user.login-as-page') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Manajemen Users</span>
                            </a>
                        </li>
                        @endif
                        @if($canFeatureView('akses'))
                        <li>
                            <a href="{{ route('admin.akses.index') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ request()->routeIs('admin.akses.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Akses User</span>
                            </a>
                        </li>
                        @endif
                        @if($canFeatureView('user'))
                        <li>
                            <a href="{{ route('admin.user.login-as-page') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ request()->routeIs('admin.user.login-as-page') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Login As User</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </details>
            </li>
            <li>
                <details id="menu-report" class="group" {{ $isReportActive ? 'open' : '' }}>
                    <summary class="flex items-center justify-between py-2 px-4 cursor-pointer {{ $isReportActive ? $linkActiveClass : $linkInactiveClass }} rounded-lg group" style="list-style: none;">
                        <span class="flex items-center">
                            <i class="ri-file-chart-line text-[20px] {{ $isReportActive ? $iconActiveClass : $iconInactiveClass }}"></i>
                            <span class="ms-3">Laporan Tryout</span>
                        </span>
                        <i class="ri-arrow-down-s-line text-[18px] transition-transform group-open:rotate-180 {{ $isReportActive ? $iconActiveClass : $iconInactiveClass }}"></i>
                    </summary>
                    <ul class="mt-1 ms-2 space-y-1">
                        @if($canFeatureView('leaderboard'))
                        <li>
                            <a href="{{ route('admin.leaderboard.index') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ request()->routeIs('admin.leaderboard.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Leaderboard</span>
                            </a>
                        </li>
                        @endif
                        @if($canFeatureView('laporan'))
                        <li>
                            <a href="{{ route('admin.laporan.index') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ request()->routeIs('admin.laporan.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Laporan Tryout</span>
                            </a>
                        </li>
                        @endif
                        @if($canFeatureView('essay_review'))
                        <li>
                            <a href="{{ route('admin.essay-review.index') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ request()->routeIs('admin.essay-review.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Koreksi Essay</span>
                            </a>
                        </li>
                        @endif
                        @if($canFeatureView('feedback'))
                        <li>
                            <a href="{{ route('admin.feedback.index') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ request()->routeIs('admin.feedback.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Feedback Tryout</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </details>
            </li>
            <li>
                <details id="menu-finance" class="group" {{ $isFinanceActive ? 'open' : '' }}>
                    <summary class="flex items-center justify-between py-2 px-4 cursor-pointer {{ $isFinanceActive ? $linkActiveClass : $linkInactiveClass }} rounded-lg group" style="list-style: none;">
                        <span class="flex items-center">
                            <i class="ri-wallet-3-line text-[20px] {{ $isFinanceActive ? $iconActiveClass : $iconInactiveClass }}"></i>
                            <span class="ms-3">Keuangan</span>
                        </span>
                        <i class="ri-arrow-down-s-line text-[18px] transition-transform group-open:rotate-180 {{ $isFinanceActive ? $iconActiveClass : $iconInactiveClass }}"></i>
                    </summary>
                    <ul class="mt-1 ms-2 space-y-1">
                        <li>
                            <a href="{{ route('admin.finance.income.index') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ request()->routeIs('admin.finance.income.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Pemasukan</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.finance.expenses.index') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ request()->routeIs('admin.finance.expenses.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Pengeluaran</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.pembayaran.index') }}"
                                class="flex items-center py-2 pl-12 pr-4 {{ request()->routeIs('admin.pembayaran.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                                <span>Pembayaran</span>
                            </a>
                        </li>
                    </ul>
                </details>
            </li>
            @if($canShowAffiliateMenu)
            <li>
                <a href="{{ route('admin.affiliate.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.affiliate.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-share-forward-line text-[20px] {{ request()->routeIs('admin.affiliate.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Affiliate</span>
                </a>
            </li>
            @endif
            @if($canShowDiscountMenu)
            <li>
                <a href="{{ route('admin.discounts.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.discounts.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-coupon-3-line text-[20px] {{ request()->routeIs('admin.discounts.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Diskon</span>
                </a>
            </li>
            @endif
            @if($canFeatureView('faq'))
            <li>
                <a href="{{ route('admin.faq.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.faq.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-question-line text-[20px] {{ request()->routeIs('admin.faq.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">FAQ</span>
                </a>
            </li>
            @endif
            <li>
                <a href="{{ route('admin.activity.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.activity.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-history-line text-[20px] {{ request()->routeIs('admin.activity.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Activity</span>
                </a>
            </li>
            @if($clientBranding['certificate_management_enabled'] ?? true)
            @if($canFeatureView('certificate'))
            <li>
                <a href="{{ route('admin.certificate.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.certificate.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-award-line text-[20px] {{ request()->routeIs('admin.certificate.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Manajemen Sertifikat</span>
                </a>
            </li>
            @endif
            @endif
            <li>
                <a href="{{ route('admin.update-notifications.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.update-notifications.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-notification-3-line text-[20px] {{ request()->routeIs('admin.update-notifications.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Notifikasi Update</span>
                </a>
            </li>
            @if($canFeatureView('settings'))
            <li>
                <a href="{{ route('admin.settings.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.settings.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i
                        class="ri-settings-3-line text-[20px] {{ request()->routeIs('admin.settings.*') ? $iconActiveClass : $iconInactiveClass }}"></i>
                    <span class="ms-3">Pengaturan</span>
                </a>
            </li>
            @endif
        </ul>
    </div>
</aside>
