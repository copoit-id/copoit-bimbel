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
        ->where('is_displayed', true)
        ->where('type_price', 'paid')
        ->get();
} elseif ($activeTab == 'gratis') {
    $packages = \App\Models\Package::where('is_active', true)
        ->where('status', 'active')
        ->where('is_displayed', true)
        ->whereIn('type_price', ['free_unconditional', 'free_conditional'])
        ->get();
} else {
    // Event tab
    $packages = \App\Models\Package::where('is_active', true)
        ->where('status', 'active')
        ->where('is_displayed', true)
        ->where(function($query) {
            $query->where('type_package', 'event')
                ->orWhere(function($q) {
                    $q->where('type_package', '!=', 'event')
                      ->where('start_date', '<=', now())
                      ->where('end_date', '>=', now());
                });
        })
        ->get();
}
@endphp

<!-- Packages Grid -->
@if($packages->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($packages as $package)
    @php
    $featureList = $package->features ? json_decode($package->features, true) : [];
    @endphp
    <div class="bg-white rounded-2xl border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all overflow-hidden">
        <!-- Package Image -->
        <div class="h-32 relative overflow-hidden" style="background: linear-gradient(135deg, {{ $primaryColor }}30 0%, {{ $primaryColor }}10 100%);">
            @if($package->image)
                @php
                    $thumbExt = strtolower(pathinfo($package->image, PATHINFO_EXTENSION));
                    $thumbIsVideo = in_array($thumbExt, ['mp4','webm','mov','m4v'], true);
                    $thumbUrl = Storage::url($package->image);
                @endphp
                @if($thumbIsVideo)
                <video src="{{ $thumbUrl }}" class="w-full h-full object-cover" controls preload="metadata" playsinline></video>
                @else
                <img src="{{ $thumbUrl }}" alt="{{ $package->name }}" class="w-full h-full object-cover">
                @endif
            @else
            <div class="w-full h-full flex items-center justify-center">
                <i class="ri-book-3-line text-4xl" style="color: {{ $primaryColor }}40"></i>
            </div>
            @endif

            <!-- Type Package Badge -->
            <span class="absolute top-3 left-3 px-2.5 py-1 bg-white/90 backdrop-blur-sm text-gray-700 text-xs rounded-full font-medium capitalize">
                <i class="ri-book-marked-line me-1"></i>{{ $package->type_package }}
            </span>

            <!-- Price Badge -->
            @if($package->type_price == 'paid')
            <div class="absolute top-3 right-3 px-3 py-1 bg-amber-500 text-white text-xs rounded-full font-bold">
                Rp {{ number_format($package->price, 0, ',', '.') }}
            </div>
            @elseif($package->type_price == 'free_conditional')
            <div class="absolute top-3 right-3 px-3 py-1 bg-orange-500 text-white text-xs rounded-full font-bold">
                Gratis*
            </div>
            @else
            <div class="absolute top-3 right-3 px-3 py-1 bg-green-500 text-white text-xs rounded-full font-bold">
                GRATIS
            </div>
            @endif
        </div>

        <!-- Content -->
        <div class="p-5">
            <h3 class="font-bold text-gray-800 mb-1">{{ $package->name }}</h3>
            <div class="text-sm text-gray-500 mb-4 line-clamp-2">{!! $package->description ?? 'Paket pembelajaran lengkap' !!}</div>

            <!-- Features -->
            @php
                $features = json_decode($package->features ?? '[]', true);
                $features = is_array($features) ? array_filter($features) : [];
            @endphp
            @if(!empty($features))
            <div class="space-y-1.5 mb-4">
                @foreach ($features as $feature)
                <div class="flex items-center text-sm text-gray-600">
                    <i class="ri-checkbox-circle-fill mr-2" style="color: {{ $primaryColor }}"></i>
                    <span>{{ $feature }}</span>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Action -->
            <div class="flex items-center justify-between pt-3 border-t">
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
                    @elseif($package->type_price === 'free_conditional')
                    <a href="{{ route('user.package.detail', $package->package_id) }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium text-white hover:opacity-90 transition-opacity"
                       style="background-color: {{ $primaryColor }}">
                        Ajukan
                    </a>
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
