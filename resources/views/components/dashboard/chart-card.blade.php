{{--
    Dashboard Chart Card Component
    
    Props:
    - title: string (required)
    - subtitle: string|null
    - value: string|number|null
    - trend: string|null - 'up'|'down'
    - trendValue: string|null
    - periodSelector: boolean (default: true)
    - selectedPeriod: string (default: '30d') - '7d'|'30d'|'90d'|'1y'
    - chartId: string (required) - unique ID for canvas
    - chartType: string (default: 'bar') - 'bar'|'line'
    - height: string (default: '250px')
--}}

@props([
    'title',
    'subtitle' => null,
    'value' => null,
    'trend' => null,
    'trendValue' => null,
    'periodSelector' => true,
    'selectedPeriod' => '30d',
    'chartId',
    'chartType' => 'bar',
    'height' => '250px',
])

@php
$periods = [
    '7d' => 'Last 7 days',
    '30d' => 'Last 30 days',
    '90d' => 'Last 3 months',
    '1y' => 'Last year',
];

$trendColors = [
    'up' => 'text-green',
    'down' => 'text-red',
    'neutral' => 'text-gray-500',
];

$trendIcons = [
    'up' => 'ri-arrow-up-line',
    'down' => 'ri-arrow-down-line',
    'neutral' => 'ri-subtract-line',
];
@endphp

<div class="bg-white rounded-xl border border-gray-200 p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
            @if($subtitle)
                <p class="text-sm text-gray-500 mt-0.5">{{ $subtitle }}</p>
            @endif
            
            @if($value)
                <div class="flex items-center gap-2 mt-2">
                    <span class="text-2xl font-bold text-gray-900">{{ $value }}</span>
                    @if($trend && $trendValue)
                        <span class="flex items-center text-sm font-medium {{ $trendColors[$trend] }}">
                            <i class="{{ $trendIcons[$trend] }} mr-0.5"></i>
                            {{ $trendValue }}
                        </span>
                    @endif
                </div>
            @endif
        </div>
        
        @if($periodSelector)
            <div class="flex items-center gap-2">
                <div class="relative" x-data="{ open: false, selected: '{{ $selectedPeriod }}' }">
                    <button 
                        @click="open = !open"
                        class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200 transition-colors"
                    >
                        <i class="ri-calendar-line"></i>
                        <span x-text="{ '7d': 'Last 7 days', '30d': 'Last 30 days', '90d': 'Last 3 months', '1y': 'Last year' }[selected]"></span>
                        <i class="ri-arrow-down-s-line ml-1"></i>
                    </button>
                    
                    <div 
                        x-show="open"
                        @click.away="open = false"
                        x-transition
                        class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-10"
                    >
                        @foreach($periods as $key => $label)
                            <button 
                                @click="selected = '{{ $key }}'; open = false; $dispatch('period-changed', { period: '{{ $key }}', chartId: '{{ $chartId }}' })"
                                class="w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-50 transition-colors {{ $key === $selectedPeriod ? 'bg-gray-50 font-medium' : '' }}"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
    
    {{-- Chart Container --}}
    <div style="height: {{ $height }};">
        <canvas id="{{ $chartId }}"></canvas>
    </div>
</div>
