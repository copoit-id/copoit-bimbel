{{--
    Page Description Component
    
    Props:
    - title: string (default: 'Title')
    - description: string | null
    - name_link: string | null
    - url_link: string | null
    - direction: string | null ('items-start', 'items-center', 'items-end')
--}}

@props([
    'title' => 'Title',
    'description' => null,
    'name_link' => null,
    'url_link' => null,
    'direction' => null,
])

@php
    $displayTitle = html_entity_decode((string) $title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $displayDescription = $description === null
        ? null
        : html_entity_decode((string) $description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
@endphp

<div class="flex flex-col {{ $direction ?? 'items-start' }} {{ $attributes->get('class', '') }}" {{ $attributes->except('class') }}>
    <h1 class="text-2xl text-dark font-bold">{{ $displayTitle }}</h1>
    
    @if($displayDescription)
        <p class="font-light text-base text-gray-600 mt-1">{{ $displayDescription }}</p>
    @endif
    
    @if($name_link && $url_link)
        <x-ui.button 
            :href="$url_link" 
            variant="primary" 
            size="md"
            class="mt-4"
        >
            {{ $name_link }}
        </x-ui.button>
    @endif
</div>
