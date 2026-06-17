@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
])

@php
    $id = 'field_' . md5($name);
    $oldKey = str_replace(']', '', str_replace('[', '.', $name));
@endphp

<div>
    <label for="{{ $id }}" class="mb-2 block text-sm font-medium text-gray-700">{{ $label }}</label>
    <input
        type="{{ $type }}"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ old($oldKey, $value) }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20']) }}
    >
</div>
