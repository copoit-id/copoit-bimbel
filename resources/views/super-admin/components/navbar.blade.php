<nav class="fixed top-0 z-50 w-full bg-white border-b border-gray-200">
    <div class="px-4 py-3 lg:px-6 lg:pl-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button data-drawer-target="superadmin-sidebar" data-drawer-toggle="superadmin-sidebar" aria-controls="superadmin-sidebar"
                    class="sm:hidden inline-flex items-center p-2 text-sm text-gray-500 rounded-lg hover:bg-gray-100">
                    <span class="sr-only">Open sidebar</span>
                    <i class="ri-menu-line text-lg"></i>
                </button>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary font-bold">SA</div>
                    <span class="text-lg font-semibold text-gray-900">Super Admin</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="px-4 py-2 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50">
                        <i class="ri-logout-circle-line mr-1"></i>Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
