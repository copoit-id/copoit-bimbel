{{--
    Breadcrumb Component
    
    Props:
    - items: array (required) - [['label' => '...', 'url' => '...', 'active' => true]]
    - showBack: boolean (default: true)
    - homeUrl: string (default: route('admin.dashboard'))
--}}

@props([
    'items' => [],
    'showBack' => true,
    'homeUrl' => null,
])

@php
$homeUrl = $homeUrl ?? route('admin.dashboard');
@endphp

<div class="flex items-start gap-2 mb-4">
    {{-- Back Button --}}
    @if($showBack)
        <nav class="flex items-start">
            <x-ui.button
                href="{{ url()->previous() }}"
                variant="ghost"
                size="icon"
                class="!p-2.5 border border-gray-200 bg-white hover:bg-primary hover:text-white hover:border-primary"
            >
                <i class="ri-arrow-go-back-line"></i>
            </x-ui.button>
        </nav>
    @endif

    {{-- Breadcrumb --}}
    <nav class="hidden md:flex items-center px-5 py-2.5 border border-gray-200 rounded-lg bg-white" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-2">
            {{-- Home --}}
            <li class="inline-flex items-center">
                <a href="{{ $homeUrl }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-primary transition-colors">
                    <i class="ri-home-line mr-1.5"></i>
                    Home
                </a>
            </li>

            {{-- Items --}}
            @foreach($items as $index => $item)
                <li class="flex items-center">
                    <i class="ri-arrow-right-s-line text-gray-400 mx-1"></i>
                    @if($loop->last || ($item['active'] ?? false))
                        <span class="text-sm font-medium text-primary" aria-current="page">
                            {{ $item['label'] }}
                        </span>
                    @else
                        <a href="{{ $item['url'] ?? '#' }}" class="text-sm font-medium text-gray-500 hover:text-primary transition-colors">
                            {{ $item['label'] }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
</div>
