@php
    $headerPrimary = $clientBranding['header_primary_color'] ?? false;
    $navClasses = $headerPrimary ? 'bg-primary border-b border-primary text-white' : 'bg-white border-b border-gray-200';
    $toggleButtonClasses = $headerPrimary
        ? 'inline-flex items-center p-2 text-sm text-white rounded-lg sm:hidden hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/30'
        : 'inline-flex items-center p-2 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200';
    $brandTitleClass = $headerPrimary ? 'text-white' : 'text-dark';
    $brandSubtitleClass = $headerPrimary ? 'text-white/80' : 'text-gray-500';
    $menuTextClass = $headerPrimary ? 'text-white' : 'text-gray-900';
    $menuSubTextClass = $headerPrimary ? 'text-white/80' : 'text-gray-500';
    $dropdownBgClass = $headerPrimary ? 'bg-white text-gray-900' : 'bg-white';
@endphp

<nav class="fixed top-0 z-40 w-full {{ $navClasses }}">
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
                <a href="/" class="flex ms-2 md:me-12 items-center">
                    <img src="{{ $clientBranding['logo_url'] }}" class="w-12 h-12 object-cover me-1"
                        alt="{{ $clientBranding['name'] }} Logo" />
                    <div class="flex flex-col justify-start">
                        <p class="text-[20px] font-bold {{ $brandTitleClass }}">{{ $clientBranding['name'] }}</p>
                        <p class="font-light text-[13px] mt-[-8px] {{ $brandSubtitleClass }}">Learning Platform</p>
                    </div>
                </a>
            </div>
            <div class="flex items-center">
                <div class="flex items-center ms-3">
                    <div>
                        @php
                            $profileButtonClasses = $headerPrimary
                                ? 'flex text-sm bg-white/20 rounded-full focus:ring-4 focus:ring-white/30'
                                : 'flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300';
                        @endphp
                        <button type="button" class="{{ $profileButtonClasses }}"
                            aria-expanded="false" data-dropdown-toggle="dropdown-user">
                            <span class="sr-only">Open user menu</span>
                            <img class="w-8 h-8 rounded-full"
                                src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="user photo">
                        </button>
                    </div>
                    <div class="z-50 hidden my-4 text-base list-none {{ $dropdownBgClass }} divide-y divide-gray-100 rounded-sm shadow-sm "
                        id="dropdown-user">
                        <div class="px-4 py-3" role="none">
                            <p class="text-sm {{ $menuTextClass }}" role="none">
                                {{ Auth::user()->name }}
                            </p>
                            <p class="text-sm font-medium {{ $menuSubTextClass }} truncate" role="none">
                                {{ Auth::user()->email }}
                            </p>
                        </div>
                        @php
                            $dropdownLinkClasses = $headerPrimary
                                ? 'block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left'
                                : 'block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left';
                        @endphp
                        <ul class="py-1" role="none">
                            <li>
                                <a href="{{ route('user.profile.index') }}" class="{{ $dropdownLinkClasses }}">
                                    Profile
                                </a>
                            </li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="{{ $dropdownLinkClasses }}">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
