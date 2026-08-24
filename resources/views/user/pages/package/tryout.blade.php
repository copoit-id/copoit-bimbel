@extends('user.layout.user')
@section('title', 'Tryout Paket')
@section('content')
<div class="dashboard">
    <x-page-desc title="Tryout - {{ $package->name }}" description="Daftar tryout yang tersedia dalam paket ini">
    </x-page-desc>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-6 text-gray-600">
        @forelse($tryouts as $tryout)
        @php
        $questionCount = $tryout->tryoutDetails->sum(fn ($detail) => $detail->questions->count());
        $totalDuration = $tryout->tryoutDetails->sum('duration');
        $completedAttempts = $tryout->completedAttemptCountForUser(auth()->id());
        $remainingAttempts = $tryout->remainingAttemptsForUser(auth()->id());
        $hasInProgressAttempt = $tryout->hasInProgressAttemptForUser(auth()->id());
        $isAttemptLimitReached = $tryout->hasReachedAttemptLimitForUser(auth()->id()) && ! $hasInProgressAttempt;
        $lastAttempt = $tryout->userAnswers->sortByDesc('created_at')->first();
        $tryoutIcon = $tryout->icon_class ?: 'ri-file-list-3-line';
        $showThumbnail = ($tryout->user_card_display ?? 'icon') === 'thumbnail' && filled($tryout->thumbnail_url);
        @endphp

        <div class="bg-white px-5 py-5 shadow rounded-lg flex flex-col justify-between">
            <div class="h-28 rounded-lg overflow-hidden mb-4 flex items-center justify-center bg-primary/10 text-primary">
                @if($showThumbnail)
                    <img src="{{ $tryout->thumbnail_url }}" alt="{{ $tryout->name }}" class="w-full h-full object-cover">
                @else
                    <i class="{{ $tryoutIcon }} text-5xl"></i>
                @endif
            </div>
            <div class="flex flex-col gap-1 mb-4">
                <p class="text-lg font-bold text-black text-center mb-4">{{ $tryout->name }}</p>
                <span class="flex items-center justify-between">
                    <p class="font-medium">Jumlah Soal:</p>
                    <p class="font-light">{{ $questionCount }} Soal</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">Durasi:</p>
                    <p class="font-light">{{ $totalDuration }} Menit</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">Tipe:</p>
                    <p class="font-light">{{ ucfirst($tryout->type_tryout) }}</p>
                </span>
                <span class="flex items-center justify-between">
                    <p class="font-medium">Dikerjakan:</p>
                    <p class="font-light">
                        {{ $completedAttempts }}{{ is_null($remainingAttempts) ? ' Kali' : '/' . ($tryout->max_attempts ?? 0) . ' Kali' }}
                    </p>
                </span>
                @if($lastAttempt)
                <span class="flex items-center justify-between">
                    <p class="font-medium">Skor Terakhir:</p>
                    <p class="font-light {{ ($lastAttempt->score ?? 0) >= 70 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($lastAttempt->score ?? 0, 1) }}
                    </p>
                </span>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2 font-light">
                @if($questionCount <= 0)
                <button
                    class="flex w-full justify-center bg-gray-400 text-white px-4 py-2 rounded-lg text-sm cursor-not-allowed"
                    disabled>
                    Belum Ada Soal
                </button>
                @elseif($isAttemptLimitReached)
                <a href="{{ route('user.tryout.result', ['id_package' => $package->package_id, 'id_tryout' => $tryout->tryout_id]) }}"
                    class="flex w-full items-center justify-center bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition-colors">
                    Sudah Dikerjakan
                </a>
                @else
                <a href="{{ route('user.tryout.lobby', ['id_package' => $package->package_id, 'id_tryout' => $tryout->tryout_id]) }}"
                    class="flex w-full justify-center bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-primary/90 transition-colors">
                    {{ $hasInProgressAttempt ? 'Lanjutkan' : 'Kerjakan' }}
                </a>
                @endif

                @if($completedAttempts > 0 || $hasInProgressAttempt)
                <a href="{{ route('user.package.tryout.riwayat', ['id_package' => $package->package_id, 'id_tryout' => $tryout->tryout_id]) }}"
                    class="flex w-full justify-center border border-primary text-primary px-4 py-2 rounded-lg text-sm hover:bg-primary hover:text-white transition-colors">
                    Riwayat
                </a>
                @endif

                @if($tryout->show_leaderboard)
                <a href="{{ route('user.package.tryout.ranking', ['id_package' =>$package->package_id, 'id_tryout' => $tryout->tryout_id]) }}"
                    class="flex justify-center border border-primary text-primary px-3 py-2 rounded-lg text-sm hover:bg-primary hover:text-white transition-colors">
                    <i class="ri-bar-chart-2-fill"></i>
                </a>
                @endif
            </div>

            @if($tryout->show_discussion && $lastAttempt && $lastAttempt->is_completed)
            <div class="mt-3">
                <a href="{{ route('user.package.tryout.pembahasan', ['id_package' => $package->package_id, 'id_tryout' => $tryout->tryout_id]) }}"
                    class="flex w-full justify-center bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition-colors">
                    Lihat Pembahasan
                </a>
            </div>
            @endif
        </div>
        @empty
        <div class="col-span-full text-center py-8">
            <i class="ri-file-list-line text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-500">Belum ada tryout tersedia dalam paket ini</p>
        </div>
        @endforelse
    </div>
</div>
@endsection

@section('scripts')

@endsection
@section('styles')
