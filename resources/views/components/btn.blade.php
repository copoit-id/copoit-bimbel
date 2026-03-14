{{--
    Button Component (Legacy - Redirect to ui.Button)
    
    This component is kept for backward compatibility.
    Please use <x-ui.button> for new code.
--}}

@props([
    'title' => null,
    'icon' => null,
    'route' => '#',
    'color' => 'primary',
    'type' => 'button',
])

{{-- Map legacy color to variant --}}
@php
$variantMap = [
    'primary' => 'primary',
    'secondary' => 'secondary',
    'danger' => 'danger',
    'success' => 'success',
    'outline' => 'outline',
];
$variant = $variantMap[$color] ?? 'primary';
@endphp

<x-ui.button
    :variant="$variant"
    size="md"
    :href="$route !== '#' ? $route : null"
    :icon="$icon"
    :type="$type"
    {{ $attributes }}
>
    {{ $title ?? $slot }}
</x-ui.button>
