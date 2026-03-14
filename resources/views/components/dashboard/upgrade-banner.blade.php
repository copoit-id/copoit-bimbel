{{--
    Upgrade Plan Banner Component
    
    Props:
    - title: string (default: 'Unlock premium features')
    - description: string (default: 'Upgrade to Pro for unlimited analytics & real-time insights.')
    - buttonText: string (default: 'Upgrade Now')
    - buttonUrl: string (default: '#')
    - canClose: boolean (default: true)
    - storageKey: string (default: 'upgrade_banner_closed') - localStorage key
--}}

@props([
    'title' => 'Unlock premium features',
    'description' => 'Upgrade to Pro for unlimited analytics & real-time insights.',
    'buttonText' => 'Upgrade Now',
    'buttonUrl' => '#',
    'canClose' => true,
    'storageKey' => 'upgrade_banner_closed',
])

<div 
    x-data="{ 
        show: true,
        init() {
            const closed = localStorage.getItem('{{ $storageKey }}');
            if (closed === 'true') this.show = false;
        },
        close() {
            this.show = false;
            localStorage.setItem('{{ $storageKey }}', 'true');
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-4"
    class="relative overflow-hidden rounded-2xl border border-primary/30 bg-gradient-to-r from-primary to-primary/80 text-white p-6 mb-6"
>
    {{-- Background Pattern --}}
    <div class="absolute inset-0 opacity-10">
        <svg class="h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none">
            <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                <circle cx="1" cy="1" r="1" fill="currentColor"/>
            </pattern>
            <rect width="100" height="100" fill="url(#grid)"/>
        </svg>
    </div>
    
    <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                <i class="ri-vip-crown-line text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-white">{{ $title }}</h3>
                <p class="text-white/80 text-sm mt-0.5">{{ $description }}</p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <x-ui.button 
                :href="$buttonUrl" 
                variant="secondary" 
                class="!bg-white !text-primary hover:!bg-gray-100 whitespace-nowrap"
            >
                {{ $buttonText }}
            </x-ui.button>
            
            @if($canClose)
                <button 
                    @click="close()"
                    class="text-white/60 hover:text-white transition-colors p-1"
                >
                    <i class="ri-close-line text-xl"></i>
                </button>
            @endif
        </div>
    </div>
</div>
