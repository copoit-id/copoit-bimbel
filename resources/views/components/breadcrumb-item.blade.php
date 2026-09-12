{{--
    Breadcrumb Item Component (Legacy - use Breadcrumb component instead)
    
    Props:
    - href: string (default: '#')
    - title: string (required)
    - isLast: boolean (default: false)
--}}

@props(['href' => '#', 'title', 'isLast' => false])

@php
    $displayTitle = html_entity_decode((string) $title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
@endphp

<li @if($isLast) aria-current="page" @endif>
    <div class="flex items-center">
        <i class="ri-arrow-right-s-line text-gray-400 mx-1"></i>
        @if($isLast)
            <span class="text-sm font-medium text-primary">{{ $displayTitle }}</span>
        @else
            <a href="{{ $href }}" class="text-sm font-medium text-gray-500 hover:text-primary transition-colors">
                {{ $displayTitle }}
            </a>
        @endif
    </div>
</li>
