{{--
    Badge Component
    
    Props:
    - variant: 'primary' | 'secondary' | 'success' | 'warning' | 'danger' | 'info' | 'light' | 'dark' (default: 'primary')
    - size: 'sm' | 'md' | 'lg' (default: 'md')
    - pill: boolean (default: false)
    - dot: boolean (show dot indicator, default: false)
    - icon: string | null (icon class)
--}}

@props([
    'variant' => 'primary',
    'size' => 'md',
    'pill' => false,
    'dot' => false,
    'icon' => null,
])

@php
$baseClasses = 'inline-flex items-center gap-1.5 font-medium';

$variantClasses = [
    'primary' => 'bg-primary/10 text-primary',
    'secondary' => 'bg-gray-100 text-gray-700',
    'success' => 'bg-green-light text-green',
    'warning' => 'bg-yellow-100 text-yellow-700',
    'danger' => 'bg-red-light text-red',
    'info' => 'bg-blue-light text-blue-700',
    'light' => 'bg-gray-50 text-gray-600 border border-gray-200',
    'dark' => 'bg-gray-800 text-white',
];

$sizeClasses = [
    'sm' => 'px-2 py-0.5 text-xs',
    'md' => 'px-2.5 py-1 text-xs',
    'lg' => 'px-3 py-1.5 text-sm',
];

$roundedClasses = $pill ? 'rounded-full' : 'rounded-md';

$dotColors = [
    'primary' => 'bg-primary',
    'secondary' => 'bg-gray-500',
    'success' => 'bg-green',
    'warning' => 'bg-yellow-500',
    'danger' => 'bg-red',
    'info' => 'bg-blue-500',
    'light' => 'bg-gray-400',
    'dark' => 'bg-gray-400',
];

$classes = implode(' ', [
    $baseClasses,
    $variantClasses[$variant] ?? $variantClasses['primary'],
    $sizeClasses[$size] ?? $sizeClasses['md'],
    $roundedClasses,
    $attributes->get('class', ''),
]);

$dotColor = $dotColors[$variant] ?? 'bg-primary';
@endphp

<span class="{{ $classes }}" {{ $attributes->except('class') }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
    @endif
    @if($icon)
        <i class="{{ $icon }} text-xs"></i>
    @endif
    {{ $slot }}
</span>
