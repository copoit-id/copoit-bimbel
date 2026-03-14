{{--
    Page Header Component
    
    Props:
    - title: string | null
    - description: string | null
    - breadcrumb: array | null (array of ['label' => '...', 'url' => '...'])
    - actions: mixed | null (slot for action buttons)
    - border: boolean (default: true)
--}}

@props([
    'title' => null,
    'description' => null,
    'breadcrumb' => null,
    'border' => true,
])

<div class="mb-6 {{ $border ? 'pb-6 border-b border-gray-200' : '' }} {{ $attributes->get('class', '') }}">
    {{-- Breadcrumb --}}
    @if($breadcrumb)
        <nav class="flex mb-3" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                @foreach($breadcrumb as $index => $item)
                    <li class="flex items-center">
                        @if($index > 0)
                            <i class="ri-arrow-right-s-line text-gray-400 mx-1"></i>
                        @endif
                        @if($loop->last || empty($item['url']))
                            <span class="text-sm text-gray-500">{{ $item['label'] }}</span>
                        @else
                            <a href="{{ $item['url'] }}" class="text-sm text-primary hover:text-primary/80">
                                {{ $item['label'] }}
                            </a>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            @if($title)
                <h1 class="text-2xl font-bold text-gray-900">{{ $title }}</h1>
            @endif
            @if($description)
                <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
            @endif
        </div>
        
        @if(isset($actions))
            <div class="flex items-center gap-3">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
