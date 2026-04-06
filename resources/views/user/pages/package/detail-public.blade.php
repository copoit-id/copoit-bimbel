@extends('user.layout.new-user')

@section('title', $package->name)

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$isGuest = !Auth::check();
@endphp

<!-- Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('user.package.index') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Detail Paket</h1>
        <p class="text-gray-500 text-sm">Informasi lengkap paket pembelajaran</p>
    </div>
</div>

<!-- Package Hero -->
<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden mb-6">
    <!-- Cover Image -->
    <div class="h-48 relative overflow-hidden" style="background: linear-gradient(135deg, {{ $primaryColor }}30 0%, {{ $primaryColor }}10 100%);">
        @if($package->thumbnail)
        <img src="{{ asset('storage/' . $package->thumbnail) }}" alt="{{ $package->name }}" class="w-full h-full object-cover">
        @else
        <div class="w-full h-full flex items-center justify-center">
            <i class="ri-book-3-line text-8xl" style="color: {{ $primaryColor }}40"></i>
        </div>
        @endif
        
        @if($package->type_price === 'free_unconditional' || $package->type_price === 'free_conditional')
        <div class="absolute top-4 right-4 px-4 py-2 rounded-full text-sm font-bold bg-green-500 text-white">
            <i class="ri-gift-line mr-1"></i>GRATIS
        </div>
        @else
        <div class="absolute top-4 right-4 px-4 py-2 rounded-full text-sm font-bold text-white" style="background-color: {{ $primaryColor }}">
            Rp {{ number_format($package->price, 0, ',', '.') }}
        </div>
        @endif
    </div>
    
    <!-- Content -->
    <div class="p-6">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">{{ $package->name }}</h2>
                <div class="text-gray-600 mb-4 package-description">{!! $package->description ?? 'Paket pembelajaran lengkap untuk meningkatkan skill dan persiapan ujianmu.' !!}</div>
                
                <!-- Stats -->
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: {{ $primaryColor }}15">
                            <i class="ri-video-line" style="color: {{ $primaryColor }}"></i>
                        </div>
                        <span>{{ $package->materials->count() }} Materi</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: {{ $primaryColor }}15">
                            <i class="ri-file-list-3-line" style="color: {{ $primaryColor }}"></i>
                        </div>
                        <span>{{ $package->tryouts->count() }} Tryout</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: {{ $primaryColor }}15">
                            <i class="ri-time-line" style="color: {{ $primaryColor }}"></i>
                        </div>
                        <span>{{ $totalDuration }} Menit Total</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: {{ $primaryColor }}15">
                            <i class="ri-question-line" style="color: {{ $primaryColor }}"></i>
                        </div>
                        <span>{{ $totalQuestions }} Soal</span>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex flex-col gap-3 min-w-[200px]">
                @auth
                    @if($isOwned)
                    <a href="{{ route('user.package.show', $package->package_id) }}" 
                       class="px-6 py-3 rounded-xl text-center font-medium text-white hover:opacity-90 transition-opacity"
                       style="background-color: {{ $primaryColor }}">
                        <i class="ri-play-circle-line mr-2"></i>Mulai Belajar
                    </a>
                    @elseif($package->type_price === 'free_unconditional')
                    <form action="{{ route('user.event.join', $package->package_id) }}" method="POST" class="claim-form">
                        @csrf
                        <button type="submit" 
                                class="w-full px-6 py-3 rounded-xl text-center font-medium text-white hover:opacity-90 transition-opacity"
                                style="background-color: {{ $primaryColor }}">
                            <i class="ri-gift-line mr-2"></i>Klaim Paket Gratis
                        </button>
                    </form>
                    @elseif($package->type_price === 'free_conditional')
                    <a href="{{ route('login') }}" 
                       class="px-6 py-3 rounded-xl text-center font-medium text-white hover:opacity-90 transition-opacity"
                       style="background-color: {{ $primaryColor }}">
                        <i class="ri-gift-line mr-2"></i>Ajukan Akses Gratis
                    </a>
                    @else
                    <form action="{{ route('user.package.buy', $package->package_id) }}" method="POST" class="buy-form">
                        @csrf
                        <button type="submit" 
                                class="w-full px-6 py-3 rounded-xl text-center font-medium text-white hover:opacity-90 transition-opacity"
                                style="background-color: {{ $primaryColor }}">
                            <i class="ri-shopping-cart-line mr-2"></i>Beli Paket
                        </button>
                    </form>
                    @endif
                @else
                    @if($package->type_price === 'free_unconditional' || $package->type_price === 'free_conditional')
                    <a href="{{ route('login') }}" 
                       class="px-6 py-3 rounded-xl text-center font-medium text-white hover:opacity-90 transition-opacity"
                       style="background-color: {{ $primaryColor }}">
                        <i class="ri-gift-line mr-2"></i>Klaim Paket Gratis
                    </a>
                    @else
                    <a href="{{ route('login') }}" 
                       class="px-6 py-3 rounded-xl text-center font-medium text-white hover:opacity-90 transition-opacity"
                       style="background-color: {{ $primaryColor }}">
                        <i class="ri-shopping-cart-line mr-2"></i>Beli Paket
                    </a>
                    @endif
                    <p class="text-xs text-gray-500 text-center">
                        <i class="ri-information-line mr-1"></i>
                        Silakan masuk untuk melanjutkan
                    </p>
                @endauth
            </div>
        </div>
    </div>
</div>

<!-- Contents -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Materials List -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Materials Section -->
        @if($package->materials->count() > 0)
        <div class="bg-white rounded-2xl p-6 border border-gray-100">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="ri-video-line" style="color: {{ $primaryColor }}"></i>
                Materi Pembelajaran
                <span class="ml-auto text-sm font-normal text-gray-500">{{ $package->materials->count() }} item</span>
            </h3>
            
            <div class="space-y-3">
                @foreach($package->materials as $index => $material)
                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-sm shrink-0" style="background-color: {{ $primaryColor }}">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <i class="{{ $material->type_icon ?? 'ri-file-text-line' }} text-gray-400"></i>
                            <h4 class="font-medium text-gray-800 truncate">{{ $material->title }}</h4>
                        </div>
                        <p class="text-sm text-gray-500 truncate">{{ $material->description ?? 'Materi pembelajaran' }}</p>
                    </div>
                    @auth
                        @if($isOwned)
                        <a href="{{ route('user.material.show', $material->material_id) }}" class="p-2 hover:bg-gray-200 rounded-lg transition-colors shrink-0" style="color: {{ $primaryColor }}">
                            <i class="ri-play-circle-line text-xl"></i>
                        </a>
                        @else
                        <i class="ri-lock-line text-gray-400 shrink-0"></i>
                        @endif
                    @else
                    <i class="ri-lock-line text-gray-400 shrink-0"></i>
                    @endauth
                </div>
                @endforeach
            </div>
        </div>
        @endif
        
        <!-- Tryouts Section -->
        @if($package->tryouts->count() > 0)
        <div class="bg-white rounded-2xl p-6 border border-gray-100">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="ri-file-list-3-line" style="color: {{ $primaryColor }}"></i>
                Tryout Latihan
                <span class="ml-auto text-sm font-normal text-gray-500">{{ $package->tryouts->count() }} tryout</span>
            </h3>
            
            <div class="space-y-3">
                @foreach($package->tryouts as $tryout)
                @php
                $tryoutDuration = 0;
                $tryoutQuestions = 0;
                foreach ($tryout->tryoutDetails as $detail) {
                    $tryoutDuration += $detail->duration;
                    $tryoutQuestions += \App\Models\Question::where('tryout_detail_id', $detail->tryout_detail_id)->count();
                }
                @endphp
                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white shrink-0" style="background-color: {{ $primaryColor }}">
                        <i class="ri-file-list-3-line text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-800">{{ $tryout->name }}</h4>
                        <div class="flex items-center gap-4 mt-1 text-sm text-gray-500">
                            <span><i class="ri-question-line mr-1"></i>{{ $tryoutQuestions }} Soal</span>
                            <span><i class="ri-time-line mr-1"></i>{{ $tryoutDuration }} Menit</span>
                        </div>
                    </div>
                    @auth
                        @if($isOwned)
                        <a href="{{ route('user.tryout.lobby', ['id_package' => $package->package_id, 'id_tryout' => $tryout->tryout_id]) }}" 
                           class="px-4 py-2 rounded-lg text-sm font-medium text-white hover:opacity-90 shrink-0"
                           style="background-color: {{ $primaryColor }}">
                            Kerjakan
                        </a>
                        @else
                        <span class="px-3 py-1 rounded-lg text-xs font-medium bg-gray-200 text-gray-500 shrink-0">
                            <i class="ri-lock-line mr-1"></i>Terlunci
                        </span>
                        @endif
                    @else
                    <span class="px-3 py-1 rounded-lg text-xs font-medium bg-gray-200 text-gray-500 shrink-0">
                        <i class="ri-lock-line mr-1"></i>Login untuk Akses
                    </span>
                    @endauth
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Benefits -->
        <div class="bg-white rounded-2xl p-6 border border-gray-100">
            <h3 class="font-bold text-gray-800 mb-4">Benefit Paket</h3>
            <ul class="space-y-3">
                <li class="flex items-start gap-3">
                    <i class="ri-check-line text-lg mt-0.5" style="color: {{ $primaryColor }}"></i>
                    <span class="text-sm text-gray-600">Akses materi pembelajaran lengkap</span>
                </li>
                <li class="flex items-start gap-3">
                    <i class="ri-check-line text-lg mt-0.5" style="color: {{ $primaryColor }}"></i>
                    <span class="text-sm text-gray-600">Tryout dengan pembahasan detail</span>
                </li>
                <li class="flex items-start gap-3">
                    <i class="ri-check-line text-lg mt-0.5" style="color: {{ $primaryColor }}"></i>
                    <span class="text-sm text-gray-600">Sertifikat penyelesaian</span>
                </li>
                <li class="flex items-start gap-3">
                    <i class="ri-check-line text-lg mt-0.5" style="color: {{ $primaryColor }}"></i>
                    <span class="text-sm text-gray-600">Akses 24/7 selama masa aktif</span>
                </li>
                <li class="flex items-start gap-3">
                    <i class="ri-check-line text-lg mt-0.5" style="color: {{ $primaryColor }}"></i>
                    <span class="text-sm text-gray-600">Update materi terbaru</span>
                </li>
            </ul>
        </div>
        
        <!-- Guest CTA -->
        @guest
        <div class="bg-gradient-to-br rounded-2xl p-6 text-white" style="background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $primaryColor }}dd 100%);">
            <h3 class="font-bold text-lg mb-2">Belum Punya Akun?</h3>
            <p class="text-white/80 text-sm mb-4">Daftar sekarang untuk mengakses paket pembelajaran dan mulai persiapan ujianmu!</p>
            <a href="{{ route('register') }}" class="block w-full py-3 rounded-xl text-center font-medium bg-white hover:bg-gray-100 transition-colors" style="color: {{ $primaryColor }}">
                Daftar Gratis
            </a>
            <a href="{{ route('login') }}" class="block w-full py-3 rounded-xl text-center font-medium text-white hover:text-white/80 transition-colors mt-2">
                Sudah punya akun? Masuk
            </a>
        </div>
        @endguest
    </div>
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center backdrop-blur-sm">
    <div class="bg-white px-6 py-5 rounded-2xl shadow-xl flex items-center gap-3">
        <div class="animate-spin rounded-full h-8 w-8 border-2 border-gray-300 border-t-transparent" style="border-top-color: {{ $primaryColor }}"></div>
        <p class="text-sm font-medium text-gray-700">Memproses...</p>
    </div>
</div>
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

@section('styles')
<style>
.package-description p { margin-bottom: 0.75rem; }
.package-description p:last-child { margin-bottom: 0; }
.package-description ul { list-style-type: disc; padding-left: 1.5rem; margin-bottom: 0.75rem; }
.package-description ol { list-style-type: decimal; padding-left: 1.5rem; margin-bottom: 0.75rem; }
.package-description a { color: #10b981; text-decoration: underline; }
.package-description strong { font-weight: 600; }
.package-description em { font-style: italic; }
.package-description h1, .package-description h2, .package-description h3 { font-weight: 700; margin-bottom: 0.5rem; }
.package-description h1 { font-size: 1.5rem; }
.package-description h2 { font-size: 1.25rem; }
.package-description h3 { font-size: 1.125rem; }
</style>
@endsection
@endsection
