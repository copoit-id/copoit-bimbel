@props(['items'])

<div class="flex items-start gap-2 mb-4">
    <nav
        class="flex items-start px-5 py-3 text-gray-700 border border-gray-200 rounded-lg bg-white hover:bg-primary group">
        <a href="{{  redirect()->back() }}"
            class="inline-flex items-center text-sm font-medium text-gray-700 group-hover:text-white">
            <i class="ri-arrow-go-back-line"></i>
        </a>
    </nav>
    <nav
        class="hidden md:flex items-start px-5 py-3 text-gray-700 border border-gray-200 rounded-lg bg-white aria-label=">
        <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">

            <!-- Home icon -->
            <li class="inline-flex items-center">
                <a href="{{ route('admin.dashboard') }}"
                    class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary">
                    <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z" />
                    </svg>
                    Home
                </a>
            </li>

            {{ $items }}
        </ol>
    </nav>
</div>