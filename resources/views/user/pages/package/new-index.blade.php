@extends('user.layout.new-user')

@section('title', 'Paket')

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
@endphp

<!-- Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('user.dashboard.index') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Paket</h1>
        <p class="text-gray-500 text-sm">Pilih paket yang sesuai dengan kebutuhanmu</p>
    </div>
</div>

<!-- Tabs -->
<div class="bg-white rounded-2xl p-2 mb-6 border border-gray-100 inline-flex">
    <a href="{{ route('user.package.index', ['tab' => 'paid']) }}" 
       class="px-6 py-2.5 rounded-xl font-medium transition-all {{ $tab === 'paid' ? 'text-white' : 'text-gray-600 hover:bg-gray-50' }}"
       style="{{ $tab === 'paid' ? 'background-color: ' . $primaryColor : '' }}">
        <i class="ri-vip-crown-line mr-2"></i>Berbayar
    </a>
    <a href="{{ route('user.package.index', ['tab' => 'free']) }}" 
       class="px-6 py-2.5 rounded-xl font-medium transition-all {{ $tab === 'free' ? 'text-white' : 'text-gray-600 hover:bg-gray-50' }}"
       style="{{ $tab === 'free' ? 'background-color: ' . $primaryColor : '' }}">
        <i class="ri-gift-line mr-2"></i>Gratis
    </a>
</div>

<!-- Packages Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($packages as $package)
    @php
    $isOwned = in_array((int) $package->package_id, $userOwnedPackageIds);
    @endphp
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all group">
        <!-- Package Image/Header -->
        <div class="h-32 relative overflow-hidden" style="background: linear-gradient(135deg, {{ $primaryColor }}20 0%, {{ $primaryColor }}10 100%);">
            @if($package->thumbnail)
            <img src="{{ asset('storage/' . $package->thumbnail) }}" alt="{{ $package->name }}" class="w-full h-full object-cover">
            @else
            <div class="w-full h-full flex items-center justify-center">
                <i class="ri-book-3-line text-6xl" style="color: {{ $primaryColor }}40"></i>
            </div>
            @endif
            
            @if($tab === 'paid')
            <div class="absolute top-3 right-3 px-3 py-1 rounded-full text-xs font-semibold" style="background-color: {{ $primaryColor }}; color: white;">
                Rp {{ number_format($package->price, 0, ',', '.') }}
            </div>
            @else
            <div class="absolute top-3 right-3 px-3 py-1 rounded-full text-xs font-semibold bg-green-500 text-white">
                GRATIS
            </div>
            @endif
        </div>
        
        <!-- Content -->
        <div class="p-5">
            <a href="{{ route('user.package.detail', $package->package_id) }}" class="block">
                <h3 class="font-bold text-lg text-gray-800 mb-2 hover:text-primary transition-colors">{{ $package->name }}</h3>
            </a>
            <div class="text-gray-500 text-sm mb-4 line-clamp-2">{!! $package->description ?? 'Paket belajar lengkap dengan materi dan tryout.' !!}</div>
            
            <!-- Features -->
            @if($package->features)
            <div class="space-y-1.5 mb-4">
                @foreach (json_decode($package->features) as $feature)
                <div class="flex items-center text-sm text-gray-600">
                    <i class="ri-checkbox-circle-fill mr-2 text-green-500"></i>
                    <span>{{ $feature }}</span>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="flex gap-2">
                <a href="{{ route('user.package.detail', $package->package_id) }}" 
                   class="flex-1 py-2.5 rounded-xl text-center font-medium border transition-all hover:bg-gray-50"
                   style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                    <i class="ri-eye-line mr-1"></i>Detail
                </a>
                @auth
                    @if($isOwned)
                    <a href="{{ route('user.package.show', $package->package_id) }}" 
                       class="flex-1 py-2.5 rounded-xl text-center font-medium text-white hover:opacity-90 transition-opacity"
                       style="background-color: {{ $primaryColor }}">
                        <i class="ri-play-circle-line mr-1"></i>Mulai
                    </a>
                    @elseif($tab === 'free')
                    <form action="{{ route('user.event.join', $package->package_id) }}" method="POST" class="claim-form flex-1">
                        @csrf
                        <button type="submit" 
                                class="w-full py-2.5 rounded-xl text-center font-medium text-white hover:opacity-90 transition-opacity"
                                style="background-color: {{ $primaryColor }}">
                            <i class="ri-gift-line mr-1"></i>Klaim
                        </button>
                    </form>
                    @else
                    <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST" class="buy-form flex-1">
                        @csrf
                        <button type="submit" 
                                class="w-full py-2.5 rounded-xl text-center font-medium text-white hover:opacity-90 transition-opacity"
                                style="background-color: {{ $primaryColor }}">
                            <i class="ri-shopping-cart-line mr-1"></i>Beli
                        </button>
                    </form>
                    @endif
                @else
                <a href="{{ route('login') }}" 
                   class="flex-1 py-2.5 rounded-xl text-center font-medium text-white hover:opacity-90 transition-opacity"
                   style="background-color: {{ $primaryColor }}">
                    <i class="ri-login-box-line mr-1"></i>Masuk
                </a>
                @endauth
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-full text-center py-12">
        <div class="w-24 h-24 rounded-full mx-auto mb-4 flex items-center justify-center" style="background-color: {{ $primaryColor }}10">
            <i class="ri-inbox-line text-4xl" style="color: {{ $primaryColor }}"></i>
        </div>
        <h3 class="font-semibold text-gray-700 mb-1">Belum ada paket</h3>
        <p class="text-gray-400 text-sm">Paket {{ $tab === 'paid' ? 'berbayar' : 'gratis' }} akan segera tersedia.</p>
    </div>
    @endforelse
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center backdrop-blur-sm">
    <div class="bg-white px-6 py-5 rounded-2xl shadow-xl flex items-center gap-3">
        <div class="animate-spin rounded-full h-8 w-8 border-2 border-gray-300 border-t-transparent" style="border-top-color: {{ $primaryColor }}"></div>
        <p class="text-sm font-medium text-gray-700">Memproses...</p>
    </div>
</div>

@if(session('success'))
<div id="successToast" class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-xl shadow-lg flex items-center gap-3 z-50">
    <i class="ri-check-line text-xl"></i>
    <span>{{ session('success') }}</span>
</div>
<script>
setTimeout(() => {
    document.getElementById('successToast')?.remove();
}, 3000);
</script>
@endif

@if(session('error'))
<div id="errorToast" class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-xl shadow-lg flex items-center gap-3 z-50">
    <i class="ri-close-circle-line text-xl"></i>
    <span>{{ session('error') }}</span>
</div>
<script>
setTimeout(() => {
    document.getElementById('errorToast')?.remove();
}, 3000);
</script>
@endif
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Handle claim form
    $('.claim-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const btn = form.find('button');
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="ri-loader-4-line animate-spin mr-2"></i>Memproses...');
        $('#loadingModal').removeClass('hidden').addClass('flex');
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                $('#loadingModal').addClass('hidden').removeClass('flex');
                if (response.success) {
                    // Redirect ke roadmap paket
                    window.location.href = response.redirect_url || '{{ route("user.package.my") }}';
                } else {
                    btn.prop('disabled', false).html(originalText);
                    alert(response.message || 'Terjadi kesalahan');
                }
            },
            error: function(xhr) {
                $('#loadingModal').addClass('hidden').removeClass('flex');
                btn.prop('disabled', false).html(originalText);
                const msg = xhr.responseJSON?.message || 'Terjadi kesalahan. Silakan coba lagi.';
                alert(msg);
            }
        });
    });
    
    // Handle buy form
    $('.buy-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const btn = form.find('button');
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="ri-loader-4-line animate-spin mr-2"></i>Memproses...');
        $('#loadingModal').removeClass('hidden').addClass('flex');
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                $('#loadingModal').addClass('hidden').removeClass('flex');
                if (response.redirect_url) {
                    window.location.href = response.redirect_url;
                } else if (response.success) {
                    window.location.href = '{{ route("user.package.riwayatPembelian") }}';
                } else {
                    btn.prop('disabled', false).html(originalText);
                    alert(response.message || 'Terjadi kesalahan');
                }
            },
            error: function(xhr) {
                $('#loadingModal').addClass('hidden').removeClass('flex');
                btn.prop('disabled', false).html(originalText);
                const msg = xhr.responseJSON?.message || 'Terjadi kesalahan. Silakan coba lagi.';
                alert(msg);
            }
        });
    });
});
</script>
@endsection
