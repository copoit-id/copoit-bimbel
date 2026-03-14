{{--
    Dashboard Activity/Recent Table Component
    
    Props:
    - title: string (default: 'Recent Activity')
    - tabs: array|null - [['id' => 'all', 'label' => 'All', 'count' => 12]]
    - activeTab: string (default: 'all')
    - showViewAll: boolean (default: true)
    - viewAllUrl: string (default: '#')
    - periodSelector: boolean (default: false)
    - columns: array (required) - ['Name', 'Date', 'Amount', 'Status']
--}}

@props([
    'title' => 'Recent Activity',
    'tabs' => [],
    'activeTab' => 'all',
    'showViewAll' => true,
    'viewAllUrl' => '#',
    'periodSelector' => false,
    'columns' => [],
])

<div class="bg-white rounded-xl border border-gray-200">
    {{-- Header --}}
    <div class="p-6 border-b border-gray-100">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
            
            <div class="flex items-center gap-3">
                @if($showViewAll)
                    <x-ui.button :href="$viewAllUrl" variant="ghost" size="sm" icon="ri-eye-line">
                        View all
                    </x-ui.button>
                @endif
                
                @if($periodSelector)
                    <div class="relative" x-data="{ open: false }">
                        <button 
                            @click="open = !open"
                            class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200 transition-colors"
                        >
                            <i class="ri-calendar-line"></i>
                            Last 30 days
                            <i class="ri-arrow-down-s-line ml-1"></i>
                        </button>
                        
                        <div 
                            x-show="open"
                            @click.away="open = false"
                            x-transition
                            class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-10"
                        >
                            <button class="w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-50">Last 7 days</button>
                            <button class="w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-50 bg-gray-50">Last 30 days</button>
                            <button class="w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-50">Last 3 months</button>
                            <button class="w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-50">Last year</button>
                        </div>
                    </div>
                @endif
                
                <x-ui.button variant="outline" size="sm" icon="ri-download-line">
                    Export
                </x-ui.button>
            </div>
        </div>
        
        {{-- Tabs --}}
        @if(!empty($tabs))
            <div class="flex items-center gap-1 mt-4 border-b border-gray-100">
                @foreach($tabs as $tab)
                    <button 
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $tab['id'] === $activeTab ? 'text-primary border-primary' : 'text-gray-500 border-transparent hover:text-gray-700' }}"
                    >
                        {{ $tab['label'] }}
                        @if(isset($tab['count']) && $tab['count'] > 0)
                            <span class="ml-1.5 px-2 py-0.5 text-xs rounded-full {{ $tab['id'] === $activeTab ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-600' }}">
                                {{ $tab['count'] }}
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif
    </div>
    
    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50/50">
                    @foreach($columns as $column)
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ $column }}
                        </th>
                    @endforeach
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>


