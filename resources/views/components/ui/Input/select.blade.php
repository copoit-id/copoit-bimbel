{{--
    Select Component
    
    Props:
    - name: string (required)
    - label: string | null
    - options: array (required) - ['value' => 'label'] or simple array
    - value: mixed (default: null)
    - placeholder: string | null
    - required: boolean (default: false)
    - disabled: boolean (default: false)
    - size: 'sm' | 'md' | 'lg' (default: 'md')
    - helper: string | null
    - error: string | null
--}}

@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'size' => 'md',
    'helper' => null,
    'error' => null,
])

@php
$hasError = $error || $errors->has($name);

$baseClasses = 'block w-full rounded-lg border appearance-none bg-white transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0 disabled:bg-gray-100 disabled:cursor-not-allowed';

$sizeClasses = [
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2.5 text-sm',
    'lg' => 'px-4 py-3 text-base',
];

$stateClasses = $hasError
    ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-200'
    : 'border-gray-300 text-gray-900 focus:border-primary focus:ring-primary/20';

$classes = implode(' ', [
    $baseClasses,
    $sizeClasses[$size] ?? $sizeClasses['md'],
    $stateClasses,
    $attributes->get('class', ''),
]);

$selectedValue = $value ?? old($name);
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
        <select
            name="{{ $name }}"
            id="{{ $name }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            class="{{ $classes }} pr-10"
            {{ $attributes->except(['class', 'name', 'id', 'required', 'disabled']) }}
        >
            @if($placeholder)
                <option value="" {{ !$selectedValue ? 'selected' : '' }} disabled>{{ $placeholder }}</option>
            @endif
            
            @foreach($options as $optionValue => $optionLabel)
                @if(is_int($optionValue) && is_string($optionLabel))
                    {{-- Simple array --}}
                    <option value="{{ $optionLabel }}" {{ $selectedValue == $optionLabel ? 'selected' : '' }}>
                        {{ $optionLabel }}
                    </option>
                @else
                    {{-- Associative array --}}
                    <option value="{{ $optionValue }}" {{ $selectedValue == $optionValue ? 'selected' : '' }}>
                        {{ $optionLabel }}
                    </option>
                @endif
            @endforeach
        </select>

        {{-- Dropdown Icon --}}
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
            <i class="ri-arrow-down-s-line text-gray-400"></i>
        </div>
    </div>

    @if($hasError)
        <p class="mt-1.5 text-sm text-red-600">{{ $error ?? $errors->first($name) }}</p>
    @elseif($helper)
        <p class="mt-1.5 text-sm text-gray-500">{{ $helper }}</p>
    @endif
</div>
