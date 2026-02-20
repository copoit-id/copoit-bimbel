@props(['name', 'label' => '', 'options' => [], 'required' => false, 'value' => null])

<div>
    @if($label)
    <label for="{{ $name }}" class="block mb-2 text-sm font-medium text-gray-900">{{ $label }}</label>
    @endif
    @php($selectedValue = old($name, $value))
    <select name="{{ $name }}" id="{{ $name }}" {{ $required ? 'required' : '' }} {{ $attributes->merge(['class' =>
        'bg-white border text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5 ' .
        ($errors->has($name) ? 'border-red-500' : 'border-gray-300 text-gray-900')]) }}
        >
        <option value="">Pilih {{ strtolower($label) }}</option>
        @foreach($options as $optionValue => $labelOption)
        <option value="{{ $optionValue }}" @selected((string) $selectedValue === (string) $optionValue)>
            {{ $labelOption }}
        </option>
        @endforeach
    </select>
    @error($name)
    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
    @enderror
</div>
