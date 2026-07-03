{{--
    Flash Alert Component
    
    Automatically displays session flash messages (success, error, warning, info)
    Auto-dismisses after 5 seconds
--}}

@if (session('success') || session('error') || session('warning') || session('info'))
@php
$type = session('success') ? 'success' : (session('error') ? 'error' : (session('warning') ? 'warning' : 'info'));
$messages = session('success') ?? session('error') ?? session('warning') ?? session('info');

$styles = [
    'success' => ['bg' => 'bg-green-light', 'text' => 'text-green', 'border' => 'border-green/20', 'icon' => 'ri-checkbox-circle-line'],
    'error' => ['bg' => 'bg-red-light', 'text' => 'text-red', 'border' => 'border-red/20', 'icon' => 'ri-error-warning-line'],
    'warning' => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-700', 'border' => 'border-yellow-200', 'icon' => 'ri-alert-line'],
    'info' => ['bg' => 'bg-blue-light', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'icon' => 'ri-information-line'],
];

$style = $styles[$type];
@endphp

<div 
    x-data="{ show: true }" 
    x-init="setTimeout(() => show = false, 5000)" 
    x-show="show" 
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-2"
    class="fixed top-4 right-4 sm:right-6 max-w-sm w-[calc(100%-2rem)] sm:w-full shadow-lg rounded-lg p-4 border {{ $style['bg'] }} {{ $style['border'] }} {{ $style['text'] }}"
    style="z-index: 2147483647;"
    role="alert"
>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i class="{{ $style['icon'] }} text-lg"></i>
            <span class="text-sm font-medium">{{ $messages }}</span>
        </div>
        <button 
            @click="show = false" 
            class="text-lg leading-none opacity-70 hover:opacity-100 transition-opacity"
            aria-label="Close"
        >
            <i class="ri-close-line"></i>
        </button>
    </div>
</div>
@endif
