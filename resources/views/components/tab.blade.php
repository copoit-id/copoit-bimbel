{{--
    Tabs Component
    
    Props:
    - tabs: array (required) - [['id' => '...', 'label' => '...', 'active' => true, 'href' => '...']]
    - variant: 'default' | 'pills' | 'underline' (default: 'default')
--}}

@props([
    'tabs' => [],
    'variant' => 'default',
])

@php
$containerClasses = [
    'default' => 'flex justify-start gap-2',
    'pills' => 'flex justify-start gap-2',
    'underline' => 'flex justify-start border-b border-gray-200',
];

$tabClasses = [
    'default' => [
        'active' => 'px-4 py-1.5 bg-primary text-white rounded-xl transition-colors',
        'inactive' => 'px-4 py-1.5 border border-primary text-primary rounded-xl hover:bg-primary/10 transition-colors',
    ],
    'pills' => [
        'active' => 'px-4 py-2 bg-primary text-white rounded-full transition-colors',
        'inactive' => 'px-4 py-2 text-gray-600 rounded-full hover:bg-gray-100 transition-colors',
    ],
    'underline' => [
        'active' => 'px-4 py-2 text-primary border-b-2 border-primary font-medium transition-colors',
        'inactive' => 'px-4 py-2 text-gray-500 hover:text-gray-700 hover:border-gray-300 border-b-2 border-transparent transition-colors',
    ],
];

$containerClass = $containerClasses[$variant] ?? $containerClasses['default'];
@endphp

<div class="{{ $containerClass }} {{ $attributes->get('class', '') }}" {{ $attributes->except('class') }}>
    @foreach($tabs as $tab)
        @php
        $isActive = $tab['active'] ?? false;
        $tabClass = $isActive ? $tabClasses[$variant]['active'] : $tabClasses[$variant]['inactive'];
        @endphp
        
        @if(!empty($tab['href']))
            <a href="{{ $tab['href'] }}" class="{{ $tabClass }}">
                @if(!empty($tab['icon']))
                    <i class="{{ $tab['icon'] }} mr-1.5"></i>
                @endif
                {{ $tab['label'] }}
            </a>
        @else
            <button 
                type="button" 
                class="{{ $tabClass }}"
                {{ !empty($tab['onclick']) ? 'onclick=' . $tab['onclick'] : '' }}
            >
                @if(!empty($tab['icon']))
                    <i class="{{ $tab['icon'] }} mr-1.5"></i>
                @endif
                {{ $tab['label'] }}
            </button>
        @endif
    @endforeach
</div>

{{-- Legacy slot support --}}
@if(!empty($slot->toHtml()))
    <div class="flex justify-start gap-2 mt-4">
        {{ $slot }}
    </div>
@endif
