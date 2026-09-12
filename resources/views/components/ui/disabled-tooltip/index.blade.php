{{-- Wrap a disabled control so its unavailable reason remains available on hover and keyboard focus. --}}
@props([
    'message',
    'position' => 'top',
])

@php
    $positionClasses = $position === 'bottom' ? 'top-full mt-2' : 'bottom-full mb-2';
@endphp

<span {{ $attributes->merge(['class' => 'group relative inline-flex']) }} tabindex="0">
    {{ $slot }}
    <span role="tooltip" class="pointer-events-none absolute left-1/2 z-50 w-64 -translate-x-1/2 translate-y-1 rounded-lg border border-gray-200 bg-gray-900 px-3 py-2 text-center text-xs font-medium leading-5 text-white opacity-0 shadow-lg transition duration-200 group-hover:translate-y-0 group-hover:opacity-100 group-focus:translate-y-0 group-focus:opacity-100 {{ $positionClasses }}">
        {{ $message }}
    </span>
</span>
