@props([
    'message' => null,
    'position' => 'top',
])

@php
    $positionClasses = match($position) {
        'bottom' => 'top-full mt-2',
        default => 'bottom-full mb-2',
    };
@endphp

<span {{ $attributes->merge(['class' => 'relative group inline-flex items-center text-gray-400 hover:text-primary cursor-help ml-1.5']) }}>
    <i class="ri-information-line text-sm"></i>
    <span class="pointer-events-none absolute left-1/2 z-50 w-64 -translate-x-1/2 translate-y-1 rounded-md border border-gray-200 bg-white p-2.5 text-xs font-normal text-gray-600 opacity-0 shadow-lg transition duration-200 group-hover:opacity-100 group-hover:translate-y-0 {{ $positionClasses }}">
        {{ $message ?? $slot }}
    </span>
</span>
