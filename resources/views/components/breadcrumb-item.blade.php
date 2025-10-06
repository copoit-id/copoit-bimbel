@props(['href' => '#', 'title', 'isLast' => false])

<li @if($isLast) aria-current="page" @endif>
    <div class="flex items-center">
        <svg class="rtl:rotate-180 block w-3 h-3 mx-1 text-gray-400 " aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="m1 9 4-4-4-4" />
        </svg>
        @if($isLast)
        <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2">{{ $title }}</span>
        @else
        <a href="{{ $href }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-primary md:ms-2">
            {{ $title }}
        </a>
        @endif
    </div>
</li>