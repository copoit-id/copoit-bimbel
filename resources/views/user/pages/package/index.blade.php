@extends('user.layout.new-user')

@section('title', 'Paket')

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$activeTab = request('tab', 'berbayar');
@endphp

<style>
.tab-active {
    background-color: {{ $primaryColor }} !important;
    color: white !important;
}
</style>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Paket</h1>
        <p class="text-gray-500 mt-1">Pilih paket belajar yang sesuai dengan kebutuhanmu</p>
    </div>
    @if(auth()->check())
    <a href="{{ route('user.package.my') }}" class="px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition-opacity text-white" style="background-color: {{ $primaryColor }}">
        <i class="ri-road-map-line mr-1"></i>Paket Saya
    </a>
    @endif
</div>

<!-- Filter Tabs -->
<div class="flex gap-2 mb-6 overflow-x-auto pb-2">
    <a href="{{ route('user.package.index', ['tab' => 'berbayar']) }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap {{ $activeTab == 'berbayar' ? 'tab-active' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
        <i class="ri-vip-crown-line mr-1"></i>Berbayar
    </a>
    <a href="{{ route('user.package.index', ['tab' => 'gratis']) }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap {{ $activeTab == 'gratis' ? 'tab-active' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
        <i class="ri-gift-line mr-1"></i>Gratis
    </a>
    <a href="{{ route('user.package.index', ['tab' => 'event']) }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap {{ $activeTab == 'event' ? 'tab-active' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
        <i class="ri-calendar-event-line mr-1"></i>Event
    </a>
</div>

@php
if ($activeTab == 'berbayar') {
    $packages = \App\Models\Package::where('is_active', true)
        ->where('status', 'active')
        ->where('type_price', 'paid')
        ->get();
} elseif ($activeTab == 'gratis') {
    $packages = \App\Models\Package::where('is_active', true)
        ->where('status', 'active')
        ->whereIn('type_price', ['free_unconditional', 'free_conditional'])
        ->get();
} else {
    // Event tab
    $packages = \App\Models\Package::where('is_active', true)
        ->where('status', 'active')
        ->where('type_package', 'event')
        ->orWhere(function($q) {
            $q->where('type_package', '!=', 'event')
              ->where('start_date', '<=', now())
              ->where('end_date', '>=', now());
        })
        ->get();
}
@endphp

<!-- Packages Grid -->
@if($packages->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($packages as $package)
    <div class="bg-white rounded-2xl p-5 border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white" style="background-color: {{ $primaryColor }}">
                <i class="ri-package-line text-xl"></i>
            </div>
            @if($package->type_price == 'paid')
            <span class="px-2.5 py-1 bg-amber-100 text-amber-700 text-xs rounded-full font-medium">Premium</span>
            @else
            <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">Gratis</span>
            @endif
        </div>
        
        <h3 class="font-bold text-gray-800 mb-1">{{ $package->name }}</h3>
        <div class="text-sm text-gray-400 mb-4 line-clamp-2">{!! $package->description ?? 'Paket pembelajaran lengkap' !!}</div>
        
        <!-- Features -->
        <div class="space-y-2 mb-4">
            <div class="flex items-center text-sm text-gray-500">
                <i class="ri-book-open-line mr-2" style="color: {{ $primaryColor }}"></i>
                <span>{{ $package->materials->count() }} Materi</span>
            </div>
            <div class="flex items-center text-sm text-gray-500">
                <i class="ri-file-list-3-line mr-2" style="color: {{ $primaryColor }}"></i>
                <span>{{ $package->tryouts->count() }} Tryout</span>
            </div>
            <div class="flex items-center text-sm text-gray-500">
                <i class="ri-time-line mr-2" style="color: {{ $primaryColor }}"></i>
                <span>{{ $package->duration }} Hari Akses</span>
            </div>
        </div>
        
        <!-- Price & Action -->
        <div class="flex items-center justify-between pt-4 border-t">
            <div>
                @if($package->type_price == 'paid')
                <span class="text-lg font-bold" style="color: {{ $primaryColor }}">{{ $package->formatted_price }}</span>
                @else
                <span class="text-lg font-bold text-green-600">Gratis</span>
                @endif
            </div>
            
            @if(auth()->check())
                @php
                $hasAccess = auth()->user()->userPackageAccess()
                    ->where('package_id', $package->package_id)
                    ->where('status', 'active')
                    ->exists();
                @endphp
                
                @if($hasAccess)
                <a href="{{ route('user.package.show', $package->package_id) }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium text-white hover:opacity-90 transition-opacity"
                   style="background-color: {{ $primaryColor }}">
                    Buka
                </a>
                @elseif($package->type_price == 'paid')
                <button onclick="buyPackage({{ $package->package_id }})" 
                        class="px-4 py-2 rounded-lg text-sm font-medium text-white hover:opacity-90 transition-opacity"
                        style="background-color: {{ $primaryColor }}">
                    Beli
                </button>
                @else
                <button onclick="claimPackage({{ $package->package_id }})" 
                        class="px-4 py-2 rounded-lg text-sm font-medium text-white hover:opacity-90 transition-opacity"
                        style="background-color: {{ $primaryColor }}">
                    Ambil
                </button>
                @endif
            @else
            <a href="{{ route('login') }}" 
               class="px-4 py-2 rounded-lg text-sm font-medium text-white hover:opacity-90 transition-opacity"
               style="background-color: {{ $primaryColor }}">
                Lihat Detail
            </a>
            @endif
        </div>
    </div>
    @endforeach
</div>
@else
<div class="text-center py-16">
    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="ri-package-line text-4xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada paket</h3>
    <p class="text-gray-400 text-sm">Paket akan segera hadir. Stay tuned!</p>
</div>
@endif
@endsection

@section('scripts')
<script>
function buyPackage(packageId) {
    if (!confirm('Beli paket ini?')) return;
    // Implement buy logic
    window.location.href = '/user/paket-pembelian/' + packageId + '/buy';
}

function claimPackage(packageId) {
    if (!confirm('Ambil paket gratis ini?')) return;
    
    fetch('/user/event/' + packageId + '/join', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = '{{ route('user.package.my') }}';
        } else {
            alert(data.message || 'Gagal mengambil paket');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan. Silakan coba lagi.');
    });
}
</script>
@endsection
