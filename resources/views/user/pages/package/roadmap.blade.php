@extends('user.layout.new-user')

@section('title', $package->name . ' - Roadmap')

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$hasItems = $roadmapItems->count() > 0;
@endphp

<!-- Header -->
<div class="bg-white rounded-2xl p-6 mb-8 border border-gray-100">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white text-2xl" style="background-color: {{ $primaryColor }}">
                <i class="ri-road-map-line"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-800">{{ $package->name }}</h1>
                <p class="text-sm text-gray-500">{{ $completedCount }}/{{ $totalItems }} item selesai</p>
            </div>
        </div>
        <div class="text-right">
            <div class="text-3xl font-bold" style="color: {{ $primaryColor }}">{{ $progressPercent }}%</div>
            <div class="text-sm text-gray-500">Complete</div>
        </div>
    </div>
    
    <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
        <div class="h-full rounded-full transition-all duration-700" style="width: {{ $progressPercent }}%; background-color: {{ $primaryColor }}"></div>
    </div>
</div>

@if($hasItems)
    <!-- Timeline Roadmap -->
    <div class="timeline-container" style="--primary-color: {{ $primaryColor }}; --progress-percent: {{ $progressPercent }}%;">
        @foreach($roadmapItems as $item)
            @include('user.pages.package.components.roadmap-item', ['item' => $item, 'primaryColor' => $primaryColor])
        @endforeach
    </div>
    
    <!-- Start Button -->
    @if($progressPercent < 100 && $nextItem)
        <a href="{{ $nextItem['route'] }}" class="start-fab">
            <span>🎓</span>
            <span>MULAI BELAJAR</span>
            <i class="ri-arrow-right-line"></i>
        </a>
    @endif
@else
    <!-- Empty State -->
    <div class="empty-state">
        <div class="empty-icon" style="background-color: {{ $primaryColor }}15; color: {{ $primaryColor }}">
            <i class="ri-inbox-line"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">Belum ada materi</h3>
        <p class="text-gray-500">Paket ini belum memiliki materi atau tryout.</p>
    </div>
@endif
@endsection

@section('styles')
<style>
/* Timeline Container */
.timeline-container {
    position: relative;
    padding: 2rem 0 4rem;
}

/* Timeline Line - Stops at first and last node */
.timeline-container::before {
    content: '';
    position: absolute;
    left: 50%;
    top: 24px;  /* Start at first node center */
    bottom: 24px;  /* End at last node center */
    width: 3px;
    background: #e5e7eb;
    transform: translateX(-50%);
    border-radius: 2px;
}

.timeline-container::after {
    content: '';
    position: absolute;
    left: 50%;
    top: 24px;
    width: 3px;
    height: calc(var(--progress-percent, 0%) - 48px);
    background: var(--primary-color, #10b981);
    transform: translateX(-50%);
    border-radius: 2px;
    transition: height 1s ease;
}

/* Timeline Row */
.timeline-row {
    display: flex;
    align-items: center;
    margin-bottom: 2rem;
    position: relative;
}

.timeline-row:last-child {
    margin-bottom: 0;
}

.timeline-left,
.timeline-right {
    flex: 1;
    width: 50%;
}

.timeline-left {
    padding-right: 3rem;
    display: flex;
    justify-content: flex-end;
}

.timeline-right {
    padding-left: 3rem;
    display: flex;
    justify-content: flex-start;
}

/* Center Node */
.timeline-center {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
}

.node-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: white;
    border: 3px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #9ca3af;
    font-size: 0.875rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.node-circle.completed {
    background: var(--primary-color, #10b981);
    border-color: var(--primary-color, #10b981);
    color: white;
}

.node-circle.in-progress {
    border-color: var(--primary-color, #10b981);
    color: var(--primary-color, #10b981);
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
}

/* Roadmap Card */
.roadmap-card {
    background: white;
    border: 1px solid #e5e7eb;  /* Thin border */
    border-radius: 16px;
    padding: 1.25rem;
    width: 100%;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    position: relative;
}

.roadmap-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 24px -8px rgba(0,0,0,0.12);
}

/* Card States */
.roadmap-card.completed {
    border-color: var(--primary-color, #10b981);
    border-left-width: 4px;
}

.roadmap-card.in-progress {
    border-color: var(--primary-color, #10b981);
    border-left-width: 4px;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.08);
}

/* Status Badge */
.card-status {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 0.25rem 0.625rem;
    border-radius: 12px;
    background: #f3f4f6;
    color: #6b7280;
}

.roadmap-card.completed .card-status {
    background: rgba(16, 185, 129, 0.1);
    color: var(--primary-color, #10b981);
}

.roadmap-card.in-progress .card-status {
    background: var(--primary-color, #10b981);
    color: white;
}

/* Card Icon */
.card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
    background: #f3f4f6;
    color: #6b7280;
}

.roadmap-card.completed .card-icon {
    background: rgba(16, 185, 129, 0.1);
    color: var(--primary-color, #10b981);
}

.roadmap-card.in-progress .card-icon {
    background: var(--primary-color, #10b981);
    color: white;
}

/* Card Content */
.card-content {
    flex: 1;
    min-width: 0;
    padding-right: 2rem;
}

.card-unit {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-bottom: 0.375rem;
}

.unit-number {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    color: #6b7280;
}

.roadmap-card.completed .unit-number,
.roadmap-card.in-progress .unit-number {
    background: var(--primary-color, #10b981);
    color: white;
}

.card-title {
    font-weight: 700;
    font-size: 1.0625rem;
    color: #1f2937;
    margin-bottom: 0.25rem;
    line-height: 1.4;
}

.card-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
}

/* Card Progress */
.card-progress {
    margin-top: 0.875rem;
}

.progress-bg {
    height: 5px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 0.25rem;
}

.progress-fill {
    height: 100%;
    background: var(--primary-color, #10b981);
    border-radius: 3px;
    transition: width 0.5s ease;
}

.progress-text {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
}

/* Card Arrow */
.card-arrow {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    color: #9ca3af;
    flex-shrink: 0;
    transition: all 0.2s ease;
}

.roadmap-card:hover .card-arrow {
    transform: translateX(3px);
}

.roadmap-card.completed .card-arrow {
    background: rgba(16, 185, 129, 0.1);
    color: var(--primary-color, #10b981);
}

.roadmap-card.in-progress .card-arrow {
    background: var(--primary-color, #10b981);
    color: white;
}

/* Start Button */
.start-fab {
    position: fixed;
    bottom: 1.5rem;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    padding: 0.875rem 1.75rem;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.9375rem;
    box-shadow: 0 8px 25px rgba(251, 191, 36, 0.4);
    display: flex;
    align-items: center;
    gap: 0.625rem;
    z-index: 50;
    text-decoration: none;
    transition: all 0.3s ease;
}

.start-fab:hover {
    transform: translateX(-50%) translateY(-2px);
    box-shadow: 0 12px 30px rgba(251, 191, 36, 0.5);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 20px;
    border: 2px dashed #e5e7eb;
}

.empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
    font-size: 2rem;
}

/* Mobile */
@media (max-width: 768px) {
    .timeline-container::before,
    .timeline-container::after {
        left: 20px;
    }
    
    .timeline-left {
        display: none;
    }
    
    .timeline-right {
        width: 100%;
        padding-left: 3.5rem;
        padding-right: 0;
    }
    
    .timeline-center {
        left: 20px;
    }
    
    .roadmap-card {
        padding: 1rem;
    }
}
</style>
@endsection
