@php
    $tryoutDetail = $tryout->tryoutDetails->first();
    $questionCount = 0;
    if ($tryoutDetail) {
        $questionCount = \App\Models\Question::where('tryout_detail_id', $tryoutDetail->tryout_detail_id)->count();
    }
    $primaryPackage = $tryout->primaryPackage ?? null;
    $packageId = $primaryPackage?->package_id ?? 'free';
    $packageName = $primaryPackage?->name;
    $userAttempts = $tryout->userAnswers->count();
    $lastAttempt = $tryout->userAnswers->sortByDesc('created_at')->first();
    $isUnlocked = !$tryout->is_premium || in_array($tryout->tryout_id, $unlockedTryoutIds, true);
    $hasPremiumAccess = Auth::user()?->hasGlobalPremiumAccess() ?? false;
@endphp

<div class="flex h-full flex-col bg-white px-5 py-5 shadow rounded-lg">
    <div>
        <div class="flex items-center justify-between text-xs font-semibold mb-2">
            <span class="px-3 py-1 rounded-full bg-gray-50 border border-gray-100 capitalize text-gray-600">
                {{ __($tryout->type_tryout) }}
            </span>
            @if($tryout->is_premium)
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-rose-50 text-rose-700 border border-rose-100"
                    title="{{ __('Premium') }}" aria-label="{{ __('Premium') }}">
                    <i class="ri-vip-crown-line text-sm"></i>
                </span>
            @endif
        </div>
        <p class="text-lg font-bold text-black">{{ $tryout->name }}</p>
        @if($packageName)
            <p class="text-xs text-gray-500 mt-1">{{ __('Paket') }}: {{ $packageName }}</p>
        @endif
        <div class="flex flex-col mt-4 gap-2 font-light text-sm">
            <span class="flex items-center justify-between">
                <p class="font-medium">{{ __('Jumlah Soal') }}:</p>
                <p class="font-light">{{ $questionCount }} {{ __('Soal') }}</p>
            </span>
            <span class="flex items-center justify-between">
                <p class="font-medium">{{ __('Durasi') }}:</p>
                <p class="font-light">{{ $tryoutDetail ? $tryoutDetail->duration : 0 }} {{ __('Menit') }}</p>
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
    </div>

    <div class="mt-auto flex items-center gap-2 font-light pt-4">
        @if($tryout->is_premium && !$hasPremiumAccess && !$isDevisadia)
            <button type="button"
                class="flex-1 min-w-0 flex justify-center bg-gray-200 text-gray-500 px-3 py-2 rounded-lg text-[13px] sm:text-sm leading-tight cursor-not-allowed truncate"
                disabled>
                {{ __('Premium - Hubungi Admin') }}
            </button>
        @elseif($isDevisadia && !$isUnlocked)
            <button type="button"
                class="flex-1 min-w-0 flex justify-center bg-gray-200 text-gray-500 px-3 py-2 rounded-lg text-[13px] sm:text-sm leading-tight cursor-not-allowed truncate"
                disabled>
                {{ __('Terkunci - Selesaikan Latihan') }}
            </button>
        @elseif(!$isUnlocked)
            <button type="button"
                class="flex-1 min-w-0 flex justify-center bg-gray-200 text-gray-500 px-3 py-2 rounded-lg text-[13px] sm:text-sm leading-tight cursor-not-allowed truncate"
                disabled>
                {{ __('Terkunci - Selesaikan Latihan') }}
            </button>
        @else
            @if($questionCount > 0)
                <a href="{{ route('user.tryout.lobby', ['id_package' => $packageId, 'id_tryout' => $tryout->tryout_id]) }}"
                    class="flex-1 min-w-0 flex justify-center bg-primary text-white px-3 py-2 rounded-lg text-[13px] sm:text-sm leading-tight hover:bg-primary/90 transition-colors truncate">
                    {{ __('Kerjakan') }}
                </a>
            @else
                <button type="button"
                    class="flex-1 min-w-0 flex justify-center bg-gray-400 text-white px-3 py-2 rounded-lg text-[13px] sm:text-sm leading-tight cursor-not-allowed truncate" disabled>
                    {{ __('Belum Ada Soal') }}
                </button>
            @endif

            @if($userAttempts > 0)
                <a href="{{ route('user.package.tryout.riwayat', ['id_package' => $packageId, 'id_tryout' => $tryout->tryout_id]) }}"
                    class="flex-1 min-w-0 flex justify-center border border-primary text-primary px-3 py-2 rounded-lg text-[13px] sm:text-sm leading-tight hover:bg-primary hover:text-white transition-colors truncate">
                    {{ __('Riwayat') }}
                </a>
            @endif

            <a href="{{ route('user.package.tryout.ranking', ['id_package' => $packageId, 'id_tryout' => $tryout->tryout_id]) }}"
                class="flex-none w-9 h-9 flex items-center justify-center border border-primary text-primary rounded-lg text-sm hover:bg-primary hover:text-white transition-colors">
                <i class="ri-bar-chart-2-fill"></i>
            </a>
        @endif
    </div>
</div>
