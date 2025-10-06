<aside id="logo-sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0"
    aria-label="Sidebar">
    <div class="h-full px-3 pb-4 overflow-y-auto bg-white">
        <p class="text-[#999999] text-sm">Menu</p>
        <ul class="space-y-1 font-medium">
            <li>
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.dashboard') ? 'text-white bg-primary' : 'text-black hover:bg-gray-100' }} rounded-lg group">
                    <i
                        class="ri-home-line text-[20px] {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-black' }}"></i>
                    <span class="ms-3">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.package.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.package.*') ? 'text-white bg-primary' : 'text-black hover:bg-gray-100' }} rounded-lg group">
                    <i
                        class="ri-store-3-line text-[20px] {{ request()->routeIs('admin.package.*') ? 'text-white' : 'text-black' }}"></i>
                    <span class="ms-3">Manajemen Paket</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.tryout.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.tryout.*')  || request()->routeIs('admin.question.*') ? 'text-white bg-primary' : 'text-black hover:bg-gray-100' }} rounded-lg group">
                    <i
                        class="ri-draft-line text-[20px] {{ request()->routeIs('admin.tryout.*') || request()->routeIs('admin.question.*') ? 'text-white' : 'text-black' }}"></i>
                    <span class="ms-3">Manajemen Tryout</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.class.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.class.*') ? 'text-white bg-primary' : 'text-black hover:bg-gray-100' }} rounded-lg group">
                    <i
                        class="ri-video-line text-[20px] {{ request()->routeIs('admin.class.*') ? 'text-white' : 'text-black' }}"></i>
                    <span class="ms-3">Manajemen Kelas</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.user.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.user.*') ? 'text-white bg-primary' : 'text-black hover:bg-gray-100' }} rounded-lg group">
                    <i
                        class="ri-user-3-line text-[20px] {{ request()->routeIs('admin.user.*') ? 'text-white' : 'text-black' }}"></i>
                    <span class="ms-3">Manajemen Users</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.akses.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.akses.*') ? 'text-white bg-primary' : 'text-black hover:bg-gray-100' }} rounded-lg group">
                    <i
                        class="ri-key-line text-[20px] {{ request()->routeIs('admin.akses.*') ? 'text-white' : 'text-black' }}"></i>
                    <span class="ms-3">Akses User</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.pembayaran.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.pembayaran.*') ? 'text-white bg-primary' : 'text-black hover:bg-gray-100' }} rounded-lg group">
                    <i
                        class="ri-money-dollar-circle-line text-[20px] {{ request()->routeIs('admin.pembayaran.*') ? 'text-white' : 'text-black' }}"></i>
                    <span class="ms-3">Pembayaran</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.leaderboard.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.leaderboard.*') ? 'text-white bg-primary' : 'text-black hover:bg-gray-100' }} rounded-lg group">
                    <i
                        class="ri-bar-chart-line text-[20px] {{ request()->routeIs('admin.leaderboard.*') ? 'text-white' : 'text-black' }}"></i>
                    <span class="ms-3">Leaderboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.laporan.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.laporan.*') ? 'text-white bg-primary' : 'text-black hover:bg-gray-100' }} rounded-lg group">
                    <i
                        class="ri-file-chart-line text-[20px] {{ request()->routeIs('admin.laporan.*') ? 'text-white' : 'text-black' }}"></i>
                    <span class="ms-3">Laporan User</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.certificate.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('admin.certificate.*') ? 'text-white bg-primary' : 'text-black hover:bg-gray-100' }} rounded-lg group">
                    <i
                        class="ri-award-line text-[20px] {{ request()->routeIs('admin.certificate.*') ? 'text-white' : 'text-black' }}"></i>
                    <span class="ms-3">Manajemen Sertifikat</span>
                </a>
            </li>
        </ul>
    </div>
</aside>