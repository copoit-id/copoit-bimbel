@props([
    'name',
    'label' => null,
    'checked' => false,
    'value' => '1',
])

<label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700">
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="checkbox"
        value="{{ $value }}"
        @checked($checked)
        class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
        {{ $attributes->except(['class', 'name', 'id', 'type', 'value']) }}
    >
    <span>{{ $label ?? $slot }}</span>
</label>
