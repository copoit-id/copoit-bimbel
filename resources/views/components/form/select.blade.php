{{--
    Form Select Component (Legacy - Redirect to ui.Input.select)
    
    This component is kept for backward compatibility.
    Please use <x-ui.input.select> for new code.
--}}

@props([
    'name',
    'label' => '',
    'options' => [],
    'required' => false,
    'value' => null,
])

<x-ui.input.select
    :name="$name"
    :label="$label"
    :options="$options"
    :required="$required"
    :value="$value"
    size="md"
    {{ $attributes }}
/>
