{{--
    Form Textarea Component (Legacy - Redirect to ui.Input.textarea)
    
    This component is kept for backward compatibility.
    Please use <x-ui.input.textarea> for new code.
--}}

@props([
    'name',
    'label' => '',
    'required' => false,
    'value' => null,
    'rows' => 4,
])

<x-ui.input.textarea
    :name="$name"
    :label="$label"
    :required="$required"
    :value="$value"
    :rows="$rows"
    size="md"
    {{ $attributes }}
/>
