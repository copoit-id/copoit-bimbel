{{--
    Button Component
    
    Props:
    - variant: 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger' (default: 'primary')
    - size: 'sm' | 'md' | 'lg' | 'icon' (default: 'md')
    - type: 'button' | 'submit' | 'reset' (default: 'button')
    - href: string | null (if provided, renders as anchor tag)
    - icon: string | null (icon class, e.g., 'ri-user-line')
    - iconPosition: 'left' | 'right' (default: 'left')
    - disabled: boolean (default: false)
    - loading: boolean (default: false)
    - fullWidth: boolean (default: false)
    - class: string (additional classes)
--}}

@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'iconPosition' => 'left',
    'disabled' => false,
    'loading' => false,
    'fullWidth' => false,
])

@php
$baseClasses = 'inline-flex items-center justify-center gap-2 font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

$variantClasses = [
    'primary' => 'bg-primary text-white hover:bg-primary/90 focus:ring-primary shadow-sm',
    'secondary' => 'bg-secondary text-gray-700 hover:bg-gray-200 focus:ring-gray-400',
    'outline' => 'border-2 border-primary text-primary hover:bg-primary hover:text-white focus:ring-primary',
    'ghost' => 'text-primary hover:bg-primary/10 focus:ring-primary',
    'danger' => 'bg-red text-white hover:bg-red/90 focus:ring-red shadow-sm',
    'success' => 'bg-green text-white hover:bg-green/90 focus:ring-green shadow-sm',
];

$sizeClasses = [
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
    'icon' => 'p-2',
];

$classes = implode(' ', [
    $baseClasses,
    $variantClasses[$variant] ?? $variantClasses['primary'],
    $sizeClasses[$size] ?? $sizeClasses['md'],
    $fullWidth ? 'w-full' : '',
    $attributes->get('class', ''),
]);
@endphp

@if($href && !$disabled)
    <a href="{{ $href }}" class="{{ $classes }}" {{ $attributes->except(['class', 'href']) }}>
        @if($loading)
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @elseif($icon && $iconPosition === 'left')
            <i class="{{ $icon }}"></i>
        @endif
        
        {{ $slot }}
        
        @if($icon && $iconPosition === 'right' && !$loading)
            <i class="{{ $icon }}"></i>
        @endif
    </a>
@else
    <button 
        type="{{ $type }}" 
        class="{{ $classes }}" 
        {{ $disabled || $loading ? 'disabled' : '' }}
        {{ $attributes->except(['class', 'type']) }}
    >
        @if($loading)
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @elseif($icon && $iconPosition === 'left')
            <i class="{{ $icon }}"></i>
        @endif
        
        {{ $slot }}
        
        @if($icon && $iconPosition === 'right' && !$loading)
            <i class="{{ $icon }}"></i>
        @endif
    </button>
@endif
