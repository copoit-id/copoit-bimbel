{{--
    Textarea Component
    
    Props:
    - name: string (required)
    - label: string | null
    - placeholder: string | null
    - value: mixed (default: null)
    - rows: int (default: 4)
    - required: boolean (default: false)
    - disabled: boolean (default: false)
    - readonly: boolean (default: false)
    - size: 'sm' | 'md' | 'lg' (default: 'md')
    - resize: 'none' | 'vertical' | 'horizontal' | 'both' (default: 'vertical')
    - helper: string | null
    - error: string | null
--}}

@props([
    'name',
    'label' => null,
    'placeholder' => null,
    'value' => null,
    'rows' => 4,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'size' => 'md',
    'resize' => 'vertical',
    'helper' => null,
    'error' => null,
])

@php
$hasError = $error || $errors->has($name);

$baseClasses = 'block w-full rounded-lg border transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0 disabled:bg-gray-100 disabled:cursor-not-allowed';

$sizeClasses = [
    'sm' => 'px-3 py-2 text-sm',
    'md' => 'px-4 py-3 text-sm',
    'lg' => 'px-4 py-3 text-base',
];

$resizeClasses = [
    'none' => 'resize-none',
    'vertical' => 'resize-y',
    'horizontal' => 'resize-x',
    'both' => 'resize',
];

$stateClasses = $hasError
    ? 'border-red-500 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-200'
    : 'border-gray-300 text-gray-900 placeholder-gray-400 focus:border-primary focus:ring-primary/20';

$classes = implode(' ', [
    $baseClasses,
    $sizeClasses[$size] ?? $sizeClasses['md'],
    $resizeClasses[$resize] ?? 'resize-y',
    $stateClasses,
    $attributes->get('class', ''),
]);
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

    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $readonly ? 'readonly' : '' }}
        class="{{ $classes }}"
        {{ $attributes->except(['class', 'name', 'id', 'rows', 'placeholder', 'required', 'disabled', 'readonly']) }}
    >{{ $value ?? old($name) }}</textarea>

    @if($hasError)
        <p class="mt-1.5 text-sm text-red-600">{{ $error ?? $errors->first($name) }}</p>
    @elseif($helper)
        <p class="mt-1.5 text-sm text-gray-500">{{ $helper }}</p>
    @endif
</div>
