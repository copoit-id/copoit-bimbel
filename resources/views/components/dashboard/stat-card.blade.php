{{--
    Dashboard Stat Card Component
    
    Props:
    - label: string (required)
    - value: string|number (required)
    - icon: string (required) - Remix icon class
    - trend: string|null - 'up'|'down'|null
    - trendValue: string|null - '+41%'|'-12%' etc
    - trendLabel: string|null - 'from last month' etc
    - color: string - 'primary'|'green'|'blue'|'orange'|'red' (default: primary)
--}}

@props([
    'label',
    'value',
    'icon',
    'trend' => null,
    'trendValue' => null,
    'trendLabel' => 'from last period',
    'color' => 'primary',
])

@php
$iconBgColors = [
    'primary' => 'bg-primary/10 text-primary',
    'green' => 'bg-primary/10 text-primary',
    'blue' => 'bg-primary/10 text-primary',
    'orange' => 'bg-primary/10 text-primary',
    'red' => 'bg-primary/10 text-primary',
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

<div class="bg-white rounded-xl p-6 border border-gray-200">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
            <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ $value }}</h3>
            
            @if($trend && $trendValue)
                <div class="flex items-center gap-1 mt-2">
                    <span class="flex items-center text-sm font-medium {{ $trendColors[$trend] ?? 'text-gray-500' }}">
                        <i class="{{ $trendIcons[$trend] ?? 'ri-subtract-line' }} mr-0.5"></i>
                        {{ $trendValue }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $trendLabel }}</span>
                </div>
            @else
                <div class="h-6 mt-2"></div>
            @endif
        </div>
        
        <div class="w-12 h-12 rounded-xl flex items-center justify-center {{ $iconBgColors[$color] ?? $iconBgColors['primary'] }}">
            <i class="{{ $icon }} text-xl"></i>
        </div>
    </div>
</div>
