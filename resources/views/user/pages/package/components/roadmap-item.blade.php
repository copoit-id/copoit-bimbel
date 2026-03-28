@php
$cardClass = $item['is_completed'] ? 'completed' : ($item['is_in_progress'] ? 'in-progress' : '');
$nodeClass = $item['is_completed'] ? 'completed' : ($item['is_in_progress'] ? 'in-progress' : '');
$nodeContent = $item['is_completed'] ? '<i class="ri-check-line"></i>' : $item['order'];
@endphp

<div class="timeline-row">
    {{-- Left Side --}}
    <div class="timeline-left">
        @if($item['is_left'])
            <a href="{{ $item['route'] }}" class="roadmap-card {{ $cardClass }}">
                <span class="card-status">{{ $item['status_text'] }}</span>
                
                <div class="card-icon">
                    <i class="{{ $item['icon'] }}"></i>
                </div>
                
                <div class="card-content">
                    <div class="card-unit">
                        <span class="unit-number">{{ $item['order'] }}</span>
                        <span>Unit {{ $item['order'] }}</span>
                    </div>
                    <div class="card-title">{{ $item['title'] }}</div>
                    <div class="card-subtitle">{{ $item['subtitle'] }}</div>
                    
                    @if($item['type'] === 'material' && ($item['is_in_progress'] || $item['is_completed']))
                        <div class="card-progress">
                            <div class="progress-bg">
                                <div class="progress-fill" style="width: {{ $item['progress_percent'] }}%"></div>
                            </div>
                            <div class="progress-text">{{ $item['progress_percent'] }}% selesai</div>
                        </div>
                    @endif
                </div>
                
                <div class="card-arrow">
                    <i class="ri-arrow-right-line"></i>
                </div>
            </a>
        @endif
    </div>
    
    {{-- Center Node --}}
    <div class="timeline-center">
        <div class="node-circle {{ $nodeClass }}">
            {!! $nodeContent !!}
        </div>
    </div>
    
    {{-- Right Side --}}
    <div class="timeline-right">
        @if(!$item['is_left'])
            <a href="{{ $item['route'] }}" class="roadmap-card {{ $cardClass }}">
                <span class="card-status">{{ $item['status_text'] }}</span>
                
                <div class="card-icon">
                    <i class="{{ $item['icon'] }}"></i>
                </div>
                
                <div class="card-content">
                    <div class="card-unit">
                        <span class="unit-number">{{ $item['order'] }}</span>
                        <span>Unit {{ $item['order'] }}</span>
                    </div>
                    <div class="card-title">{{ $item['title'] }}</div>
                    <div class="card-subtitle">{{ $item['subtitle'] }}</div>
                    
                    @if($item['type'] === 'material' && ($item['is_in_progress'] || $item['is_completed']))
                        <div class="card-progress">
                            <div class="progress-bg">
                                <div class="progress-fill" style="width: {{ $item['progress_percent'] }}%"></div>
                            </div>
                            <div class="progress-text">{{ $item['progress_percent'] }}% selesai</div>
                        </div>
                    @endif
                </div>
                
                <div class="card-arrow">
                    <i class="ri-arrow-right-line"></i>
                </div>
            </a>
        @endif
    </div>
</div>
