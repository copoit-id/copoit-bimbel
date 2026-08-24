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

<div class="flex flex-col {{ $direction ?? 'items-start' }} {{ $attributes->get('class', '') }}" {{ $attributes->except('class') }}>
    <h1 class="text-2xl text-dark font-bold">{{ $title }}</h1>
    
    @if($description)
        <p class="font-light text-base text-gray-600 mt-1">{{ $description }}</p>
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
