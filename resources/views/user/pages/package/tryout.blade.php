@extends('user.layout.user')
@section('title', __('Tryout Paket'))
@section('content')
@php
    $premiumAccessIds = $premiumAccessIds ?? [];
    $practiceStats = $practiceStats ?? [];
    $unlockedTryoutIds = $practiceStats['unlocked_tryout_ids'] ?? [];
@endphp
<div class="dashboard">
    <x-page-desc title="{{ __('Tryout') }} - {{ $package->name }}" description="{{ __('Daftar tryout yang tersedia dalam paket ini') }}">
    </x-page-desc>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-6 text-gray-600">
        @forelse($tryouts as $tryout)
        @php
        $tryoutDetail = $tryout->tryoutDetails->first();
        $questionCount = 0;
        if ($tryoutDetail) {
        $questionCount = \App\Models\Question::where('tryout_detail_id', $tryoutDetail->tryout_detail_id)->count();
        }
        $userAttempts = $tryout->userAnswers->count();
        $lastAttempt = $tryout->userAnswers->sortByDesc('created_at')->first();
        $isUnlocked = !$tryout->is_premium || in_array($tryout->tryout_id, $unlockedTryoutIds, true);
        $hasPremiumAccess = in_array($tryout->tryout_id, $premiumAccessIds, true);
        $canAccess = $isUnlocked && (!$tryout->is_premium || $hasPremiumAccess);
        @endphp

        <div class="bg-white px-5 py-5 shadow rounded-lg flex h-full flex-col">
            <div class="flex flex-col gap-1 mb-4">
                <p class="text-lg font-bold text-black text-center mb-4">{{ $tryout->name }}</p>
                @if($tryout->is_premium)
                <span class="mx-auto inline-flex h-7 w-7 items-center justify-center rounded-full bg-rose-50 text-rose-700 border border-rose-100"
                    title="{{ __('Premium') }}" aria-label="{{ __('Premium') }}">
                    <i class="ri-vip-crown-line text-sm"></i>
                </span>
                @endif
                <span class="flex items-center justify-between">
                    <p class="font-medium">{{ __('Jumlah Soal') }}:</p>
                    <p class="font-light">{{ $questionCount }} {{ __('Soal') }}</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">{{ __('Durasi') }}:</p>
                    <p class="font-light">{{ $tryoutDetail ? $tryoutDetail->duration : 0 }} {{ __('Menit') }}</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">{{ __('Tipe') }}:</p>
                    <p class="font-light">{{ ucfirst($tryout->type_tryout) }}</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">{{ __('Dikerjakan') }}:</p>
                    <p class="font-light">{{ $userAttempts }} {{ __('Kali') }}</p>
                </span>
                @if($lastAttempt)
                <span class="flex items-center justify-between">
                    <p class="font-medium">{{ __('Skor Terakhir') }}:</p>
                    <p class="font-light {{ $lastAttempt->percentage >= 70 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($lastAttempt->percentage ?? 0, 1) }}%
                    </p>
                </span>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="mt-auto flex gap-2 font-light">
                @if($tryout->is_premium && !$hasPremiumAccess)
                <button
                    class="flex w-full justify-center bg-gray-200 text-gray-500 px-4 py-2 rounded-lg text-sm cursor-not-allowed"
                    disabled>
                    {{ __('Premium - Hubungi Admin') }}
                </button>
                @elseif(!$isUnlocked)
                <button
                    class="flex w-full justify-center bg-gray-200 text-gray-500 px-4 py-2 rounded-lg text-sm cursor-not-allowed"
                    disabled>
                    {{ __('Terkunci - Selesaikan Latihan') }}
                </button>
                @else
                    @if($questionCount > 0)
                    <a href="{{ route('user.tryout.lobby', ['id_package' => $package->package_id, 'id_tryout' => $tryout->tryout_id]) }}"
                        class="flex w-full justify-center bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-primary/90 transition-colors">
                        {{ __('Kerjakan') }}
                    </a>
                    @else
                    <button
                        class="flex w-full justify-center bg-gray-400 text-white px-4 py-2 rounded-lg text-sm cursor-not-allowed"
                        disabled>
                        {{ __('Belum Ada Soal') }}
                    </button>
                    @endif

                    @if($userAttempts > 0)
                    <a href="{{ route('user.package.tryout.riwayat', ['id_package' => $package->package_id, 'id_tryout' => $tryout->tryout_id]) }}"
                        class="flex w-full justify-center border border-primary text-primary px-4 py-2 rounded-lg text-sm hover:bg-primary hover:text-white transition-colors">
                        {{ __('Riwayat') }}
                    </a>
                    @endif

                    <a href="{{ route('user.package.tryout.ranking', ['id_package' =>$package->package_id, 'id_tryout' => $tryout->tryout_id]) }}"
                        class="flex justify-center border border-primary text-primary px-3 py-2 rounded-lg text-sm hover:bg-primary hover:text-white transition-colors">
                        <i class="ri-bar-chart-2-fill"></i>
                    </a>
                @endif
            </div>

            @if($lastAttempt && $lastAttempt->is_completed)
            <div class="mt-3">
                <a href="{{ route('user.package.tryout.pembahasan', ['id_package' => $package->package_id, 'id_tryout' => $tryout->tryout_id]) }}"
                    class="flex w-full justify-center bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition-colors">
                    {{ __('Lihat Pembahasan') }}
                </a>
            </div>
            @endif
        </div>
        @empty
        <div class="col-span-full text-center py-8">
            <i class="ri-file-list-line text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-500">{{ __('Belum ada tryout tersedia dalam paket ini') }}</p>
        </div>
        @endforelse
    </div>
</div>
@endsection

@section('scripts')

@endsection
@section('styles')
