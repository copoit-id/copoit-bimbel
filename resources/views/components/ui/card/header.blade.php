{{--
    Card Header Component
    
    Props:
    - title: string | null
    - subtitle: string | null
    - action: mixed | null (slot for action buttons)
    - border: boolean (default: true)
--}}

@props([
    'title' => null,
    'subtitle' => null,
    'border' => true,
])

<div class="flex items-center justify-between {{ $border ? 'border-b border-gray-100 pb-4 mb-4' : '' }} {{ $attributes->get('class', '') }}">
    <div>
        @if($title)
            <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
        @endif
        @if($subtitle)
            <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
        @endif
        @if(!$title && !$subtitle)
            {{ $slot }}
        @endif
    </div>
    @if(isset($action))
        <div class="flex items-center gap-2">
            {{ $action }}
        </div>
    @endif
</div>
