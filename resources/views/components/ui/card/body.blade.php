{{--
    Card Body Component
    
    Props:
    - class: string (additional classes)
--}}

@props([])

<div class="{{ $attributes->get('class', '') }}">
    {{ $slot }}
</div>
