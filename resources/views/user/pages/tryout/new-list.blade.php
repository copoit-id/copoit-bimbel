@extends('user.layout.new-user')

@section('title', 'Tryout')

@section('content')
@php
$user = auth()->user();
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
@endphp

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Tryout</h1>
        <p class="text-gray-500 mt-1">Jelajahi semua tryout yang tersedia</p>
    </div>
    @if($user)
    <a href="{{ route('user.package.my') }}?tab=tryouts" class="px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition-opacity text-white" style="background-color: {{ $primaryColor }}">
        <i class="ri-file-list-3-line mr-1"></i>Tryout Saya
    </a>
    @endif
</div>

@if($user)
<!-- Stats Card (hanya untuk user login) -->
<div class="rounded-2xl p-6 text-white mb-6" style="background: linear-gradient(135deg, {{ $primaryColor }}, {{ $primaryColor }}dd);">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-white/80 text-sm mb-1">Total Tryout Dikerjakan</p>
            <h2 class="text-3xl font-bold">{{ $tryouts->filter(function($t) { return $t->userAnswers->count() > 0; })->count() }}</h2>
        </div>
        <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
            <i class="ri-file-list-3-line text-3xl"></i>
        </div>
    </div>
</div>
@else
<!-- Guest Info Card -->
<div class="rounded-2xl p-6 text-white mb-6" style="background: linear-gradient(135deg, {{ $primaryColor }}, {{ $primaryColor }}dd);">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-white/80 text-sm mb-1">Ingin mengerjakan tryout?</p>
            <h2 class="text-xl font-bold">Login untuk akses penuh</h2>
        </div>
        <a href="{{ route('login') }}" class="px-4 py-2 bg-white rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors" style="color: {{ $primaryColor }}">
            Masuk Sekarang
        </a>
    </div>
</div>
@endif

<!-- Tryouts Grid -->
@if($tryouts->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($tryouts as $tryout)
    @php
    $userAnswer = $tryout->userAnswers->first();
    $totalQuestions = $tryout->getTotalQuestionsAttribute();
    $totalDuration = $tryout->getTotalDurationAttribute();
    $isCompleted = $userAnswer && $userAnswer->status === 'completed';
    $isInProgress = $userAnswer && $userAnswer->status === 'in_progress';
    $isForSale = $tryout->is_for_sale && $tryout->price > 0;
    @endphp
    <div class="bg-white rounded-2xl p-5 border border-gray-100 hover:shadow-md hover:-translate-y-0.5 transition-all">
        <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                 style="background-color: {{ $primaryColor }}20">
                <i class="ri-file-list-3-line text-xl" style="color: {{ $primaryColor }}"></i>
            </div>
            <div class="flex items-center gap-1">
                @if($isForSale)
                <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">
                    <i class="ri-shopping-cart-line mr-0.5"></i>Dijual
                </span>
                @else
                <span class="px-2.5 py-1 bg-blue-100 text-blue-700 text-xs rounded-full font-medium">
                    <i class="ri-folder-fill mr-0.5"></i>Paket
                </span>
                @endif
            </div>
        </div>

        <h3 class="font-bold text-gray-800 mb-2 line-clamp-2">{{ $tryout->name }}</h3>

        <div class="space-y-2 mb-4">
            <div class="flex items-center text-sm text-gray-500">
                <i class="ri-question-line mr-2 text-gray-400"></i>
                <span>{{ $totalQuestions }} Soal</span>
            </div>
            <div class="flex items-center text-sm text-gray-500">
                <i class="ri-time-line mr-2 text-gray-400"></i>
                <span>{{ $totalDuration }} Menit</span>
            </div>
            @if($isForSale)
            <div class="flex items-center text-sm text-gray-500">
                <i class="ri-money-dollar-circle-line mr-2 text-gray-400"></i>
                <span class="font-semibold" style="color: {{ $primaryColor }}">Rp {{ number_format($tryout->price, 0, ',', '.') }}</span>
            </div>
            @endif
        </div>

        @if(!$user)
            {{-- Guest - perlu login --}}
            <a href="{{ route('login') }}"
               class="flex items-center justify-center w-full py-2.5 rounded-xl text-sm font-medium border-2 hover:bg-gray-50 transition-colors"
               style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                <i class="ri-login-box-line mr-1"></i>
                Masuk untuk Akses
            </a>
        @elseif($isForSale)
            {{-- Tryout for individual sale --}}
            @if($tryout->has_access)
            <a href="{{ route('user.tryout.lobby', ['id_package' => $tryout->packages->first()?->package_id ?? 0, 'id_tryout' => $tryout->tryout_id]) }}"
               class="block w-full py-2.5 text-white text-center rounded-xl text-sm font-medium hover:opacity-90 transition-opacity"
               style="background-color: {{ $primaryColor }}">
                <i class="ri-play-circle-line mr-1"></i>Kerjakan
            </a>
            @else
            <form action="{{ route('user.individual-purchase.buy') }}" method="POST" class="w-full">
                @csrf
                <input type="hidden" name="type" value="tryout">
                <input type="hidden" name="id" value="{{ $tryout->tryout_id }}">
                <button type="submit" class="w-full py-2.5 rounded-xl text-sm font-medium text-white hover:opacity-90 transition-opacity"
                        style="background-color: {{ $primaryColor }}">
                    <i class="ri-shopping-cart-line mr-1"></i>
                    Beli Sekarang
                </button>
            </form>
            @endif
        @elseif($tryout->has_access)
            {{-- User has access via package - arahkan ke paket saya --}}
            <a href="{{ route('user.package.my') }}?tab=tryouts"
               class="flex items-center justify-center w-full py-2.5 rounded-xl text-sm font-medium border-2 hover:bg-gray-50 transition-colors"
               style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                <i class="ri-folder-open-line mr-1"></i>Lihat di Paket Saya
            </a>
        @else
            {{-- User doesn't have access --}}
            @if($tryout->access_via_package)
            <a href="{{ route('user.package.my') }}?tab=packages"
               class="flex items-center justify-center w-full py-2.5 rounded-xl text-sm font-medium border-2 hover:bg-gray-50 transition-colors"
               style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                <i class="ri-shopping-bag-line mr-1"></i>
                Dapatkan Akses
            </a>
            @else
            <button disabled class="flex items-center justify-center w-full py-2.5 rounded-xl text-sm font-medium bg-gray-100 text-gray-400 cursor-not-allowed">
                <i class="ri-lock-line mr-1"></i>
                Tidak Tersedia
            </button>
            @endif
        @endif
    </div>
    @endforeach
</div>
@else
<div class="text-center py-16">
    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="ri-file-list-3-line text-4xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada tryout</h3>
    <p class="text-gray-400 text-sm mb-6">Tryout akan segera tersedia.</p>
</div>
@endif
@endsection
