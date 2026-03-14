{{--
    Card Component
    
    Props:
    - variant: 'default' | 'bordered' | 'elevated' | 'flat' (default: 'default')
    - size: 'sm' | 'md' | 'lg' | 'xl' (default: 'md')
    - padding: 'none' | 'sm' | 'md' | 'lg' | 'xl' (default: 'md')
    - hover: boolean (default: false)
    - clickable: boolean (default: false)
    - href: string | null (renders as anchor if provided)
--}}

@props([
    'variant' => 'default',
    'size' => 'md',
    'padding' => 'md',
    'hover' => false,
    'clickable' => false,
    'href' => null,
])

@php
$baseClasses = 'bg-white rounded-lg overflow-hidden';

$variantClasses = [
    'default' => 'border border-gray-200 shadow-sm',
    'bordered' => 'border-2 border-gray-200',
    'elevated' => 'shadow-lg border-0',
    'flat' => 'border-0 shadow-none',
];

$sizeClasses = [
    'sm' => '',
    'md' => '',
    'lg' => '',
    'xl' => '',
];

$paddingClasses = [
    'none' => '',
    'sm' => 'p-3',
    'md' => 'p-4',
    'lg' => 'p-6',
    'xl' => 'p-8',
];

$hoverClasses = $hover ? 'transition-all duration-200 hover:shadow-md hover:-translate-y-0.5' : '';
$clickableClasses = $clickable || $href ? 'cursor-pointer' : '';

$classes = implode(' ', [
    $baseClasses,
    $variantClasses[$variant] ?? $variantClasses['default'],
    $sizeClasses[$size] ?? $sizeClasses['md'],
    $paddingClasses[$padding] ?? $paddingClasses['md'],
    $hoverClasses,
    $clickableClasses,
    $attributes->get('class', ''),
]);
@endphp

@if($href)
    <a href="{{ $href }}" class="{{ $classes }} block" {{ $attributes->except(['class', 'href']) }}>
        {{ $slot }}
    </a>
@else
    <div class="{{ $classes }}" {{ $attributes->except('class') }}>
        {{ $slot }}
    </div>
@endif
