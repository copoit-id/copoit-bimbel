{{--
    Breadcrumb Item Component (Legacy - use Breadcrumb component instead)
    
    Props:
    - href: string (default: '#')
    - title: string (required)
    - isLast: boolean (default: false)
--}}

@props(['href' => '#', 'title', 'isLast' => false])

<li @if($isLast) aria-current="page" @endif>
    <div class="flex items-center">
        <i class="ri-arrow-right-s-line text-gray-400 mx-1"></i>
        @if($isLast)
            <span class="text-sm font-medium text-primary">{{ $title }}</span>
        @else
            <a href="{{ $href }}" class="text-sm font-medium text-gray-500 hover:text-primary transition-colors">
                {{ $title }}
            </a>
        @endif
    </div>
</li>
