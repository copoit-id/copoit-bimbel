{{--
    Input Component
    
    Props:
    - name: string (required)
    - label: string | null
    - type: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'date' | 'time' | 'datetime-local' | 'search' (default: 'text')
    - placeholder: string | null
    - value: mixed (default: null)
    - required: boolean (default: false)
    - disabled: boolean (default: false)
    - readonly: boolean (default: false)
    - size: 'sm' | 'md' | 'lg' (default: 'md')
    - icon: string | null (icon class)
    - iconPosition: 'left' | 'right' (default: 'left')
    - helper: string | null (helper text below input)
    - error: string | null (manual error message, overrides validation error)
--}}

@props([
    'name',
    'label' => null,
    'type' => 'text',
    'placeholder' => null,
    'value' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'size' => 'md',
    'icon' => null,
    'iconPosition' => 'left',
    'helper' => null,
    'error' => null,
])

@php
$hasError = $error || $errors->has($name);

$baseClasses = 'block w-full rounded-lg border transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0 disabled:bg-gray-100 disabled:cursor-not-allowed';

$sizeClasses = [
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2.5 text-sm',
    'lg' => 'px-4 py-3 text-base',
];

$stateClasses = $hasError
    ? 'border-red-500 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-200'
    : 'border-gray-300 text-gray-900 placeholder-gray-400 focus:border-primary focus:ring-primary/20';

$paddingClasses = $icon ? ($iconPosition === 'left' ? 'pl-10' : 'pr-10') : '';

$classes = implode(' ', [
    $baseClasses,
    $sizeClasses[$size] ?? $sizeClasses['md'],
    $stateClasses,
    $paddingClasses,
    $attributes->get('class', ''),
]);

$iconSizeClasses = [
    'sm' => 'h-4 w-4',
    'md' => 'h-5 w-5',
    'lg' => 'h-5 w-5',
];
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $name }}" class="block mb-2 text-sm font-medium {{ $hasError ? 'text-red-600' : 'text-gray-900' }}">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if($icon && $iconPosition === 'left')
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="{{ $icon }} {{ $iconSizeClasses[$size] ?? 'h-5 w-5' }} {{ $hasError ? 'text-red-400' : 'text-gray-400' }}"></i>
            </div>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ $value ?? old($name) }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $readonly ? 'readonly' : '' }}
            class="{{ $classes }}"
            {{ $attributes->except(['class', 'name', 'id', 'value', 'placeholder', 'required', 'disabled', 'readonly']) }}
        />

        @if($icon && $iconPosition === 'right')
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <i class="{{ $icon }} {{ $iconSizeClasses[$size] ?? 'h-5 w-5' }} {{ $hasError ? 'text-red-400' : 'text-gray-400' }}"></i>
            </div>
        @endif
    </div>

    @if($hasError)
        <p class="mt-1.5 text-sm text-red-600">{{ $error ?? $errors->first($name) }}</p>
    @elseif($helper)
        <p class="mt-1.5 text-sm text-gray-500">{{ $helper }}</p>
    @endif
</div>
