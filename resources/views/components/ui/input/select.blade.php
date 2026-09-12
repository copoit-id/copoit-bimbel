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
    'multiple' => false,
    'size' => 'md',
    'helper' => null,
    'error' => null,
])

@php
$fieldName = str_ends_with($name, '[]') ? substr($name, 0, -2) : $name;
$isMultiple = $multiple || $attributes->has('multiple');
$hasError = $error || $errors->has($fieldName) || ($isMultiple && $errors->has($fieldName.'.*'));

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

$selectedValue = $value ?? old($fieldName);
$selectedValues = $isMultiple
    ? collect(is_array($selectedValue) ? $selectedValue : [$selectedValue])
        ->filter(static fn ($selected): bool => $selected !== null && $selected !== '')
        ->map(static fn ($selected): string => (string) $selected)
        ->all()
    : [];
$inputName = $isMultiple && !str_ends_with($name, '[]') ? $name.'[]' : $name;
$isSimpleOptions = array_is_list($options);
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $fieldName }}" class="block mb-2 text-sm font-medium {{ $hasError ? 'text-red-600' : 'text-gray-900' }}">
            {{ $label }}
            @if($required)
                <x-form.required-indicator />
            @endif
        </label>
    @endif

    <div class="relative">
        <select
            name="{{ $inputName }}"
            id="{{ $fieldName }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $isMultiple ? 'multiple' : '' }}
            class="{{ $classes }} pr-10"
            {{ $attributes->except(['class', 'name', 'id', 'required', 'disabled', 'multiple']) }}
        >
            @if($placeholder)
                <option value="" {{ !$selectedValue ? 'selected' : '' }} disabled>{{ $placeholder }}</option>
            @endif
            
            @if(trim((string) $slot) !== '')
                {{ $slot }}
            @else
                @foreach($options as $optionValue => $optionLabel)
                    @if($isSimpleOptions)
                        <option value="{{ $optionLabel }}" {{ $isMultiple ? (in_array((string) $optionLabel, $selectedValues, true) ? 'selected' : '') : ($selectedValue == $optionLabel ? 'selected' : '') }}>
                            {{ $optionLabel }}
                        </option>
                    @else
                        <option value="{{ $optionValue }}" {{ $isMultiple ? (in_array((string) $optionValue, $selectedValues, true) ? 'selected' : '') : ($selectedValue == $optionValue ? 'selected' : '') }}>
                            {{ $optionLabel }}
                        </option>
                    @endif
                @endforeach
            @endif
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
