{{--
    Admin Package Card Component
    
    Props:
    - title: string | null
    - description: string | null
    - stats: array | null (['label' => 'value'])
    - actions: mixed | null (slot for action buttons)
    - href: string | null
--}}

@props([
    'title' => null,
    'description' => null,
    'stats' => [],
    'href' => null,
])

<x-ui.card 
    variant="default" 
    padding="md" 
    :hover="true" 
    :clickable="!!$href"
    :href="$href"
    class="h-full"
>
    <x-ui.card.header>
        <x-slot:title>{{ $title }}</x-slot:title>
        <x-slot:subtitle>{{ $description }}</x-slot:subtitle>
        @if(isset($actions))
            <x-slot:action>
                {{ $actions }}
            </x-slot:action>
        @endif
    </x-ui.card.header>

    @if(!empty($stats))
        <div class="grid grid-cols-2 gap-4 mt-4">
            @foreach($stats as $label => $value)
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">{{ $label }}</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $value }}</p>
                </div>
            @endforeach
        </div>
    @endif

    {{ $slot }}
</x-ui.card>
