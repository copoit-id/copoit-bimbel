{{--
    Container Component
    
    Props:
    - size: 'sm' | 'md' | 'lg' | 'xl' | 'full' (default: 'xl')
    - padding: 'none' | 'sm' | 'md' | 'lg' (default: 'md')
    - centered: boolean (default: true)
--}}

@props([
    'size' => 'xl',
    'padding' => 'md',
    'centered' => true,
])

@php
$sizeClasses = [
    'sm' => 'max-w-3xl',
    'md' => 'max-w-4xl',
    'lg' => 'max-w-5xl',
    'xl' => 'max-w-7xl',
    'full' => 'max-w-full',
];

$paddingClasses = [
    'none' => 'px-0',
    'sm' => 'px-4',
    'md' => 'px-4 sm:px-6',
    'lg' => 'px-4 sm:px-6 lg:px-8',
];

$classes = implode(' ', [
    'w-full',
    $sizeClasses[$size] ?? $sizeClasses['xl'],
    $paddingClasses[$padding] ?? $paddingClasses['md'],
    $centered ? 'mx-auto' : '',
    $attributes->get('class', ''),
]);
@endphp

<div class="{{ $classes }}" {{ $attributes->except('class') }}>
    {{ $slot }}
</div>
