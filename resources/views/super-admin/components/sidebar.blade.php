@php
    $linkActiveClass = 'bg-primary text-white';
    $linkInactiveClass = 'text-gray-700 hover:bg-gray-100';
@endphp

<aside id="superadmin-sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform -translate-x-full sm:translate-x-0 bg-white border-r border-gray-200"
    aria-label="Sidebar">
    <div class="h-full px-3 pb-4 overflow-y-auto">
        <p class="text-[#999999] text-sm">Menu</p>
        <ul class="space-y-1 font-medium mt-2">
            <li>
                <a href="{{ route('super-admin.roles.index') }}"
                    class="flex items-center py-2 px-4 {{ request()->routeIs('super-admin.roles.*') ? $linkActiveClass : $linkInactiveClass }} rounded-lg group">
                    <i class="ri-shield-keyhole-line text-[20px]"></i>
                    <span class="ms-3">Role & Akses</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
