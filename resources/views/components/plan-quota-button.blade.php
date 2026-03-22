{{--
    Plan Quota Button Component
    
    Props:
    - feature: 'package' | 'user' | 'question_bank' | 'essay_ai'
    - href: string (URL untuk button aktif)
    - icon: string (icon class, default: 'ri-add-line')
    - disabledIcon: string (icon saat disabled, default: 'ri-lock-line')
    - label: string (button text)
    - variant: 'primary' | 'secondary' | 'outline' (default: 'primary')
    - size: 'sm' | 'md' | 'lg' (default: 'md')
    - tooltipPosition: 'auto' | 'top' | 'bottom' | 'left' | 'right' (default: 'auto')
--}}

@props([
    'feature' => 'package',
    'href' => '#',
    'icon' => 'ri-add-line',
    'disabledIcon' => 'ri-lock-line',
    'label' => 'Tambah',
    'variant' => 'primary',
    'size' => 'md',
    'tooltipPosition' => 'auto',
])

@php
// Get quota data from shared view or calculate fresh
$featureMethod = match($feature) {
    'package' => 'canCreatePackage',
    'user' => 'canRegisterUser',
    'question_bank' => 'canCreateQuestionBank',
    'essay_ai' => 'canUseEssayAI',
    default => 'canCreatePackage',
};
$quotaData = $planQuota[$feature] ?? \App\Services\PlanQuotaService::{$featureMethod}();
$isAllowed = $quotaData['allowed'] ?? false;
$current = $quotaData['current'] ?? 0;
$limit = $quotaData['limit'] ?? 0;
$reason = $quotaData['reason'] ?? 'Kuota terpenuhi';

// Size classes
$sizeClasses = [
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
];

// Variant classes for active state
$variantClasses = [
    'primary' => 'bg-primary text-white hover:bg-primary/90',
    'secondary' => 'bg-gray-100 text-gray-700 hover:bg-gray-200',
    'outline' => 'border-2 border-primary text-primary hover:bg-primary hover:text-white',
];

// Disabled classes
$disabledClasses = 'bg-gray-300 text-gray-500 cursor-not-allowed opacity-70';

$baseClasses = 'inline-flex items-center justify-center gap-2 font-medium rounded-lg transition-all duration-200 focus:outline-none';

// Tooltip position classes
$tooltipPositionClasses = match($tooltipPosition) {
    'top' => 'bottom-full left-1/2 -translate-x-1/2 mb-2',
    'bottom' => 'top-full left-1/2 -translate-x-1/2 mt-2',
    'left' => 'right-full top-1/2 -translate-y-1/2 mr-2',
    'right' => 'left-full top-1/2 -translate-y-1/2 ml-2',
    default => 'bottom-full left-1/2 -translate-x-1/2 mb-2', // auto/default = top
};

$tooltipArrowClasses = match($tooltipPosition) {
    'top' => 'top-full left-1/2 -translate-x-1/2 -mt-1 border-t-gray-800',
    'bottom' => 'bottom-full left-1/2 -translate-x-1/2 -mb-1 border-b-gray-800',
    'left' => 'left-full top-1/2 -translate-y-1/2 -ml-1 border-l-gray-800',
    'right' => 'right-full top-1/2 -translate-y-1/2 -mr-1 border-r-gray-800',
    default => 'top-full left-1/2 -translate-x-1/2 -mt-1 border-t-gray-800',
};
@endphp

@if($isAllowed)
    {{-- Button Active --}}
    <a href="{{ $href }}" 
       class="{{ $baseClasses }} {{ $sizeClasses[$size] }} {{ $variantClasses[$variant] }}">
        <i class="{{ $icon }}"></i>
        {{ $label }}
    </a>
@else
    {{-- Button Disabled dengan Tooltip --}}
    <div class="relative group inline-block" x-data="{ showTooltip: false }" 
         @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
        <button type="button" disabled
            class="{{ $baseClasses }} {{ $sizeClasses[$size] }} {{ $disabledClasses }}">
            <i class="{{ $disabledIcon }}"></i>
            {{ $label }}
        </button>
        
        {{-- Tooltip --}}
        <div x-show="showTooltip" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="absolute {{ $tooltipPositionClasses }} w-72 z-50"
             style="display: none;">
            <div class="bg-gray-800 text-white text-xs rounded-lg py-2 px-3 shadow-lg">
                <div class="flex items-center gap-2 mb-2">
                    <i class="ri-error-warning-line text-orange-400"></i>
                    <p class="font-medium">Kuota Plan Terpenuhi</p>
                </div>
                <p class="mb-2 text-gray-300">{{ $reason }}</p>
                @if($limit > 0)
                    <div class="pt-2 border-t border-gray-600">
                        <div class="flex justify-between text-xs mb-1">
                            <span>Penggunaan:</span>
                            <span class="font-medium">{{ $current }}/{{ $limit }}</span>
                        </div>
                        <div class="w-full bg-gray-600 rounded-full h-1.5">
                            <div class="bg-primary h-1.5 rounded-full transition-all duration-300" 
                                 style="width: {{ min(($current / $limit) * 100, 100) }}%"></div>
                        </div>
                    </div>
                @endif
                {{-- Arrow --}}
                <div class="absolute {{ $tooltipArrowClasses }} border-4 border-transparent"></div>
            </div>
        </div>
    </div>
@endif
