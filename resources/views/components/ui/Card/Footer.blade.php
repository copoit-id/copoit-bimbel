{{--
    Card Footer Component
    
    Props:
    - align: 'left' | 'center' | 'right' | 'between' (default: 'left')
    - border: boolean (default: true)
--}}

@props([
    'align' => 'left',
    'border' => true,
])

@php
$alignClasses = [
    'left' => 'justify-start',
    'center' => 'justify-center',
    'right' => 'justify-end',
    'between' => 'justify-between',
];
@endphp

<div class="flex items-center gap-3 {{ $alignClasses[$align] ?? 'justify-start' }} {{ $border ? 'border-t border-gray-100 pt-4 mt-4' : '' }} {{ $attributes->get('class', '') }}">
    {{ $slot }}
</div>
