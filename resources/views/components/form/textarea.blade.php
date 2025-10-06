@props(['name', 'label' => '', 'rows' => 4, 'required' => false])

<div>
    @if($label)
    <label for="{{ $name }}" class="block mb-2 text-sm font-medium text-gray-900">{{ $label }}</label>
    @endif
    <textarea name="{{ $name }}" id="{{ $name }}" rows="{{ $rows }}" {{ $required ? 'required' : '' }} {{ $attributes->merge(['class' => 'bg-white border text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5 ' . ($errors->has($name) ? 'border-red-500' : 'border-gray-300 text-gray-900')]) }}
    >{{ old($name) }}</textarea>
    @error($name)
    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
    @enderror
</div>