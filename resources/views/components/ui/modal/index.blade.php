{{--
    Modal Component (Alpine.js required)
    
    Props:
    - name: string (required - unique identifier for the modal)
    - title: string | null
    - size: 'sm' | 'md' | 'lg' | 'xl' | 'full' (default: 'md')
    - centered: boolean (default: true)
    - static: boolean (prevent close on backdrop click, default: false)
    - showClose: boolean (default: true)
--}}

@props([
    'name',
    'title' => null,
    'size' => 'md',
    'centered' => true,
    'static' => false,
    'showClose' => true,
])

@php
$sizeClasses = [
    'sm' => 'max-w-md',
    'md' => 'max-w-lg',
    'lg' => 'max-w-2xl',
    'xl' => 'max-w-4xl',
    'full' => 'max-w-full mx-4',
];

$sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<div
    x-data="{ open: false }"
    x-modelable="open"
    x-modal-name="{{ $name }}"
    @keydown.escape.window="if (!{{ $static ? 'true' : 'false' }}) { open = false }"
    class="relative z-50"
    {{ $attributes }}
>
    {{-- Trigger Slot --}}
    @if(isset($trigger))
        <div @click="open = true">
            {{ $trigger }}
        </div>
    @endif

    {{-- Modal --}}
    <div
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
        style="display: none;"
    >
        {{-- Backdrop --}}
        <div 
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            @click="if (!{{ $static ? 'true' : 'false' }}) { open = false }"
        ></div>

        {{-- Modal Panel --}}
        <div class="flex min-h-full {{ $centered ? 'items-center' : 'items-start pt-10' }} justify-center p-4 text-center sm:p-0">
            <div
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 w-full {{ $sizeClass }}"
                @click.stop
            >
                {{-- Header --}}
                @if($title || $showClose)
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                        @if($title)
                            <h3 class="text-lg font-semibold text-gray-900" id="modal-title">
                                {{ $title }}
                            </h3>
                        @else
                            <div></div>
                        @endif
                        
                        @if($showClose)
                            <button
                                type="button"
                                @click="open = false"
                                class="text-gray-400 hover:text-gray-500 focus:outline-none"
                            >
                                <span class="sr-only">Close</span>
                                <i class="ri-close-line text-xl"></i>
                            </button>
                        @endif
                    </div>
                @endif

                {{-- Body --}}
                <div class="px-6 py-4">
                    {{ $slot }}
                </div>

                {{-- Footer --}}
                @if(isset($footer))
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                        {{ $footer }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
