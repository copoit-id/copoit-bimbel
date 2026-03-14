{{--
    Form Input Component (Legacy - Redirect to ui.Input)
    
    This component is kept for backward compatibility.
    Please use <x-ui.input> for new code.
--}}

@props([
    'name',
    'label' => '',
    'type' => 'text',
    'required' => false,
    'value' => null,
])

<x-ui.input
    :name="$name"
    :label="$label"
    :type="$type"
    :required="$required"
    :value="$value"
    size="md"
    {{ $attributes }}
/>
