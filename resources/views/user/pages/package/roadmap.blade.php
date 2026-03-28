@extends('user.layout.new-user')

@section('title', $package->name . ' - Roadmap')

@section('content')
@php
$user = auth()->user();
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$materials = $package->materials;
$tryouts = $package->tryouts;

// Combine and sort by order
$allItems = collect()
    ->merge($materials->map(function($m) { return ['type' => 'material', 'item' => $m]; }))
    ->merge($tryouts->map(function($t) { return ['type' => 'tryout', 'item' => $t]; }))
    ->sortBy(function($i) { 
        return $i['type'] === 'material' ? ($i['item']->pivot->order_number ?? 0) : 999;
    })
    ->values();

$currentIndex = 0; // TODO: Calculate based on progress
@endphp

<style>
.roadmap-progress {
    stroke: {{ $primaryColor }} !important;
}
.text-primary { color: {{ $primaryColor }} !important; }
.bg-primary { background-color: {{ $primaryColor }} !important; }
</style>

<!-- Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('user.package.my') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">WORLD</span>
        </div>
        <h1 class="text-xl font-bold text-gray-800">{{ $package->name }}</h1>
    </div>
</div>

<!-- Progress Stats -->
<div class="bg-white rounded-2xl p-4 mb-6 border border-gray-100">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white" style="background-color: {{ $primaryColor }}">
                <i class="ri-trophy-line text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Progress</p>
                <p class="text-lg font-bold text-gray-800">{{ $currentIndex }} / {{ count($allItems) }} Materi</p>
            </div>
        </div>
        <div class="text-right">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <i class="ri-fire-line text-orange-500"></i>
                <span>+{{ count($allItems) * 100 }} XP tersedia</span>
            </div>
        </div>
    </div>
</div>

<!-- Roadmap Container -->
<div class="relative min-h-[800px] pb-20">
    <!-- SVG Path Background -->
    <svg class="absolute inset-0 w-full h-full" preserveAspectRatio="none">
        <defs>
            <linearGradient id="pathGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" style="stop-color:{{ $primaryColor }};stop-opacity:0.3" />
                <stop offset="100%" style="stop-color:{{ $primaryColor }};stop-opacity:0.3" />
            </linearGradient>
        </defs>
        
        <!-- Main winding path -->
        @php
        $pathPoints = [];
        $nodeCount = max(count($allItems), 8);
        $startX = 15;
        $endX = 85;
        $yStep = 100 / ($nodeCount - 1);
        
        for ($i = 0; $i < $nodeCount; $i++) {
            $y = $i * $yStep;
            if ($i % 2 == 0) {
                $x = $startX;
            } else {
                $x = $endX;
            }
            if ($i == 0) $x = 50;
            if ($i == $nodeCount - 1) $x = 50;
            
            $pathPoints[] = [$x, $y];
        }
        
        $pathD = "M " . $pathPoints[0][0] . "% " . $pathPoints[0][1] . "%";
        for ($i = 1; $i < count($pathPoints); $i++) {
            $prev = $pathPoints[$i-1];
            $curr = $pathPoints[$i];
            $cp1x = $prev[0];
            $cp1y = $prev[1] + ($curr[1] - $prev[1]) * 0.5;
            $cp2x = $curr[0];
            $cp2y = $prev[1] + ($curr[1] - $prev[1]) * 0.5;
            $pathD .= " C $cp1x% $cp1y%, $cp2x% $cp2y%, $curr[0]% $curr[1]%";
        }
        @endphp
        
        <path d="{{ $pathD }}" 
              stroke="url(#pathGradient)" 
              stroke-width="12" 
              fill="none"
              stroke-linecap="round"
              class="roadmap-path"/>
        
        <!-- Progress overlay -->
        <path d="{{ $pathD }}" 
              class="roadmap-progress"
              stroke-width="12" 
              fill="none"
              stroke-linecap="round"
              stroke-dasharray="1000"
              stroke-dashoffset="{{ 1000 - (1000 * $currentIndex / max(count($allItems), 1)) }}"/>
    </svg>
    
    <!-- Nodes -->
    @foreach($allItems as $index => $itemData)
    @php
    $type = $itemData['type'];
    $item = $itemData['item'];
    $point = $pathPoints[$index] ?? [50, $index * (100 / max(count($allItems), 1))];
    $isLocked = $index > $currentIndex;
    $isCurrent = $index == $currentIndex;
    $isCompleted = $index < $currentIndex;
    
    if ($type === 'material') {
        $icon = 'ri-book-open-line';
        $color = $primaryColor;
    } else {
        $icon = 'ri-file-list-3-line';
        $color = $primaryColor;
    }
    
    if ($isLocked) {
        $color = '#d1d5db';
    }
    @endphp
    
    <div class="absolute transform -translate-x-1/2 -translate-y-1/2" 
         style="left: {{ $point[0] }}%; top: {{ $point[1] }}%;">
        
        <!-- Card -->
        <div class="relative {{ $index % 2 == 0 ? '-translate-x-full mr-8' : 'translate-x-full ml-8' }} w-48">
            <div class="bg-white rounded-xl p-4 shadow-lg border {{ $isCurrent ? 'border-emerald-400 ring-2 ring-emerald-100' : 'border-gray-100' }} {{ $isLocked ? 'opacity-70' : '' }}"
                 style="{{ $isCurrent ? 'border-color: ' . $primaryColor . ' !important; --tw-ring-color: ' . $primaryColor . '20 !important;' : '' }}">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white flex-shrink-0" style="background-color: {{ $color }}">
                        @if($isCompleted)
                        <i class="ri-check-line text-lg"></i>
                        @elseif($isLocked)
                        <i class="ri-lock-line text-lg"></i>
                        @else
                        <i class="{{ $icon }} text-lg"></i>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-800 text-sm truncate">{{ $item->title ?? $item->name }}</h4>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $type === 'material' ? 'Materi' : 'Tryout' }}</p>
                        @if(!$isLocked)
                        <div class="flex items-center gap-1 mt-2">
                            <i class="ri-fire-line text-orange-400 text-xs"></i>
                            <span class="text-xs text-orange-500 font-medium">+100 XP</span>
                        </div>
                        @endif
                    </div>
                </div>
                
                @if($isCurrent)
                <a href="{{ $type === 'material' ? route('user.material.show', $item->material_id) : route('user.tryout.lobby', ['id_package' => $package->package_id, 'id_tryout' => $item->tryout_id]) }}" 
                   class="mt-3 block w-full py-2 text-white text-center rounded-lg text-sm font-medium hover:opacity-90 transition-opacity"
                   style="background-color: {{ $primaryColor }}">
                    Mulai
                </a>
                @elseif($isCompleted)
                <span class="mt-2 block text-xs text-center" style="color: {{ $primaryColor }}">
                    <i class="ri-check-double-line mr-1"></i>Selesai
                </span>
                @else
                <span class="mt-2 block text-xs text-gray-400 text-center">
                    <i class="ri-lock-line mr-1"></i>Terbuka setelah selesai
                </span>
                @endif
            </div>
        </div>
        
        <!-- Node Circle -->
        <div class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2">
            <div class="w-14 h-14 rounded-full flex items-center justify-center text-white shadow-lg border-4 border-white {{ $isCurrent ? 'animate-pulse' : '' }}"
                 style="background-color: {{ $color }}">
                @if($isCompleted)
                <i class="ri-check-line text-xl"></i>
                @elseif($isLocked)
                <i class="ri-lock-line text-xl"></i>
                @else
                <i class="{{ $icon }} text-xl"></i>
                @endif
            </div>
            
            @if($isCurrent)
            <!-- Character avatar for current position -->
            <div class="absolute -top-8 left-1/2 transform -translate-x-1/2">
                <div class="w-8 h-8 bg-yellow-400 rounded-full flex items-center justify-center shadow-lg border-2 border-white">
                    <i class="ri-emotion-happy-line text-yellow-800"></i>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endforeach
    
    <!-- Boss at the end -->
    <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-8">
        <div class="rounded-2xl p-4 shadow-xl border-4 border-white text-white" style="background: linear-gradient(135deg, {{ $primaryColor }}, {{ $primaryColor }}dd);">
            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center mb-2">
                <i class="ri-trophy-line text-3xl text-white"></i>
            </div>
            <p class="font-bold text-center text-sm">BOSS</p>
            <p class="text-white/80 text-xs text-center">Final Challenge</p>
        </div>
    </div>
</div>

<!-- Bottom Action Bar -->
<div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 p-4 z-40 md:bottom-0 bottom-16">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white" style="background-color: {{ $primaryColor }}">
                <i class="ri-book-open-line"></i>
            </div>
            <div>
                <p class="text-xs text-gray-400">MULAI</p>
                <p class="font-medium text-gray-800 text-sm">Materi Belajar: {{ $allItems->first()['item']->title ?? 'Bilangan' }}</p>
            </div>
        </div>
        @if($allItems->count() > 0)
        @php $firstItem = $allItems->first(); @endphp
        <a href="{{ $firstItem['type'] === 'material' ? route('user.material.show', $firstItem['item']->material_id) : '#' }}" 
           class="px-6 py-2.5 text-white rounded-xl font-medium hover:opacity-90 transition-opacity flex items-center gap-2"
           style="background-color: {{ $primaryColor }}">
            Mulai Belajar
            <i class="ri-arrow-right-line"></i>
        </a>
        @endif
    </div>
</div>

<style>
.roadmap-path {
    animation: dash 30s linear infinite;
}
@keyframes dash {
    to { stroke-dashoffset: -100; }
}
</style>
@endsection
