@extends('user.layout.new-user')

@section('title', 'Riwayat Tryout - ' . $tryout->name)

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
$packageRouteId = $packageRouteId ?? ($package->package_id ?? 'free');
@endphp

<div class="mb-6 rounded-2xl border border-gray-100 bg-white p-5">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('user.package.my') }}?tab=tryouts" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                <i class="ri-arrow-left-line text-xl text-gray-600"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Riwayat Tryout</h1>
                <p class="text-gray-500 text-sm">{{ $tryout->name }}</p>
            </div>
        </div>
        <a href="{{ route('user.tryout.lobby', ['id_package' => $packageRouteId, 'id_tryout' => $tryout->tryout_id]) }}"
           class="inline-flex items-center justify-center px-4 py-2 text-white rounded-xl text-sm font-medium hover:opacity-90 transition-opacity"
           style="background-color: {{ $primaryColor }}">
            <i class="ri-play-circle-line mr-2"></i>Kerjakan Lagi
        </a>
    </div>
</div>

@if(count($attemptHistory) > 0)
@php
$totalAttempts = count($attemptHistory);
$bestScore = collect($attemptHistory)->max('score');
$passedAttempts = collect($attemptHistory)->where('is_passed', true)->count();
@endphp

<div class="grid grid-cols-1 gap-3 md:grid-cols-3 mb-5">
    <div class="rounded-2xl border border-primary/20 bg-primary/5 p-4">
        <p class="text-sm text-primary">Total Percobaan</p>
        <p class="mt-1 text-2xl font-bold text-primary">{{ $totalAttempts }}</p>
    </div>
    <div class="rounded-2xl border border-primary/20 bg-primary/5 p-4">
        <p class="text-sm text-primary">Nilai Terbaik</p>
        <p class="mt-1 text-2xl font-bold text-primary">{{ $bestScore }}</p>
    </div>
    <div class="rounded-2xl border border-primary/20 bg-primary/5 p-4">
        <p class="text-sm text-primary">Lulus</p>
        <p class="mt-1 text-2xl font-bold text-primary">{{ $passedAttempts }}</p>
    </div>
</div>

<div class="space-y-4">
    @foreach($attemptHistory as $index => $attempt)
    @php
    $attemptNumber = $loop->iteration;
    $isLatestAttempt = $index === 0;
    @endphp
    <div class="bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-md transition-all">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shrink-0" style="background-color: {{ $primaryColor }}">
                    <span class="font-bold text-sm">{{ $attemptNumber }}</span>
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold text-gray-900">Percobaan ke-{{ $attemptNumber }}</p>
                        @if($isLatestAttempt)
                        <span class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">Terbaru</span>
                        @endif
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $attempt['is_passed'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $attempt['is_passed'] ? 'Lulus' : 'Belum Lulus' }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        <i class="ri-calendar-line mr-1"></i>{{ $attempt['created_at']->format('d M Y, H:i') }}
                    </p>
                </div>
            </div>

            <div class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto lg:min-w-[560px] lg:justify-end">
                <div class="rounded-xl bg-gray-50 px-4 py-2 text-center sm:min-w-[92px]">
                    <p class="text-xs text-gray-400">Skor</p>
                    <p class="font-bold text-lg" style="color: {{ $primaryColor }}">{{ $attempt['score'] }}</p>
                </div>
                <div class="rounded-xl bg-green-50 px-4 py-2 text-center sm:min-w-[92px]">
                    <p class="text-xs text-gray-400">Benar</p>
                    <p class="font-semibold text-green-600">{{ $attempt['correct_answers'] }}</p>
                </div>
                <div class="rounded-xl bg-red-50 px-4 py-2 text-center sm:min-w-[92px]">
                    <p class="text-xs text-gray-400">Salah</p>
                    <p class="font-semibold text-red-600">{{ $attempt['wrong_answers'] }}</p>
                </div>
                <div class="rounded-xl bg-gray-50 px-4 py-2 text-center sm:min-w-[92px]">
                    <p class="text-xs text-gray-400">Kosong</p>
                    <p class="font-semibold text-gray-500">{{ $attempt['unanswered'] }}</p>
                </div>
                <div class="rounded-xl bg-gray-50 px-4 py-2 text-center sm:min-w-[110px]">
                    <p class="text-xs text-gray-400">Durasi</p>
                    <p class="font-semibold text-gray-700 text-sm">{{ $attempt['duration'] }}</p>
                </div>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row lg:flex-col xl:flex-row">
                <a href="{{ route('user.package.tryout.pembahasan', [$package->package_id ?? 0, $tryout->tryout_id, $attempt['attempt_token']]) }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium text-white hover:opacity-90 transition-opacity"
                   style="background-color: {{ $primaryColor }}">
                    <i class="ri-book-open-line mr-1.5"></i>Pembahasan
                </a>
                <a href="{{ route('user.tryout.result', ['id_package' => $package->package_id ?? 0, 'id_tryout' => $tryout->tryout_id]) }}?attempt={{ $attempt['attempt_token'] }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium border hover:bg-gray-50 transition-colors"
                   style="border-color: {{ $primaryColor }}; color: {{ $primaryColor }}">
                    <i class="ri-eye-line mr-1.5"></i>Detail
                </a>
                <a href="{{ route('user.package.tryout.ranking', ['id_package' => $package->package_id ?? 0, 'id_tryout' => $tryout->tryout_id]) }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                    <i class="ri-trophy-line mr-1.5"></i>Rank
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="text-center py-16">
    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="ri-file-list-3-line text-3xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-700 mb-2">Belum ada riwayat</h3>
    <p class="text-gray-400 text-sm mb-4">Kamu belum mengerjakan tryout ini.</p>
    <a href="{{ route('user.tryout.lobby', ['id_package' => $package->package_id ?? 0, 'id_tryout' => $tryout->tryout_id]) }}" 
       class="inline-flex items-center px-6 py-3 text-white rounded-xl font-medium hover:opacity-90 transition-opacity" 
       style="background-color: {{ $primaryColor }}">
        <i class="ri-play-circle-line mr-2"></i>Kerjakan Tryout
    </a>
</div>
@endif
@endsection
