{{--
    Package Card Component
    
    Props:
    - title: string | null
    - price: string | null
    - description: string | null
    - features: array | null (array of feature strings)
    - image: string | null (image url)
    - href: string | null (link url)
--}}

@props([
    'title' => null,
    'price' => null,
    'description' => null,
    'features' => [],
    'image' => null,
    'href' => '#',
])

<x-ui.card variant="default" padding="lg" class="h-full flex flex-col">
    {{-- Image Placeholder --}}
    @if($image)
        <div class="w-full h-20 bg-gray-200 rounded-xl mb-4 overflow-hidden">
            <img src="{{ $image }}" alt="{{ $title }}" class="w-full h-full object-cover">
        </div>
    @else
        <div class="w-full h-20 bg-gray-200 rounded-xl mb-4"></div>
    @endif

    {{-- Content --}}
    <div class="flex-1">
        @if($title)
            <h3 class="text-lg font-bold text-gray-900">{{ $title }}</h3>
        @endif
        
        @if($description)
            <p class="mt-1 text-sm text-gray-500 font-light">{{ $description }}</p>
        @endif
        
        @if($price)
            <p class="mt-2 text-lg font-bold text-gray-900">{{ $price }}</p>
        @endif

        {{-- Features List --}}
        @if(!empty($features))
            <div class="flex flex-col mt-4 gap-2 font-light">
                @foreach($features as $feature)
                    <span class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="ri-checkbox-circle-fill text-green"></i>
                        {{ $feature }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Action --}}
    <x-slot:footer class="mt-4">
        <x-ui.button 
            :href="$href" 
            variant="primary" 
            size="md" 
            full-width
            class="uppercase text-sm font-bold"
        >
            Beli Sekarang
        </x-ui.button>
    </x-slot:footer>
</x-ui.card>
