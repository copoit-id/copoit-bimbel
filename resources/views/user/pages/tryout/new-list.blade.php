@extends('user.layout.new-user')

@section('title', 'Tryout')

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
@endphp

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Tryout</h1>
        <p class="text-gray-500 mt-1">Uji kemampuanmu dengan tryout berkualitas</p>
    </div>
</div>

<!-- Stats Card -->
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
    @endphp
    <div class="bg-white rounded-2xl p-5 border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all">
        <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 {{ $isCompleted ? 'bg-green-100' : ($isInProgress ? 'bg-yellow-100' : 'bg-gray-100') }} rounded-xl flex items-center justify-center"
                 style="{{ !$isCompleted && !$isInProgress ? 'background-color: ' . $primaryColor . '20' : '' }}">
                <i class="ri-file-list-3-line text-xl {{ $isCompleted ? 'text-green-600' : ($isInProgress ? 'text-yellow-600' : '') }}"
                   style="{{ !$isCompleted && !$isInProgress ? 'color: ' . $primaryColor : '' }}"></i>
            </div>
            @if($isCompleted)
            <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">Selesai</span>
            @elseif($isInProgress)
            <span class="px-2.5 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full font-medium">Sedang dikerjakan</span>
            @else
            <span class="px-2.5 py-1 bg-gray-100 text-gray-600 text-xs rounded-full font-medium">Belum dikerjakan</span>
            @endif
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
            @if($tryout->start_date && $tryout->end_date)
            <div class="flex items-center text-sm text-gray-500">
                <i class="ri-calendar-line mr-2 text-gray-400"></i>
                <span>{{ $tryout->start_date->format('d M') }} - {{ $tryout->end_date->format('d M') }}</span>
            </div>
            @endif
        </div>
        
        @if($isCompleted)
        <div class="flex items-center justify-between pt-4 border-t">
            <div>
                <p class="text-xs text-gray-400">Skor</p>
                <p class="text-2xl font-bold" style="color: {{ $primaryColor }}">{{ $userAnswer->score ?? '-' }}</p>
            </div>
            <a href="{{ route('user.tryout.result', ['id_package' => $tryout->packages->first()?->package_id ?? 0, 'id_tryout' => $tryout->tryout_id]) }}" 
               class="px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition-opacity text-white"
               style="background-color: {{ $primaryColor }}">
                Lihat Hasil
            </a>
        </div>
        @else
        <a href="{{ route('user.tryout.lobby', ['id_package' => $tryout->packages->first()?->package_id ?? 0, 'id_tryout' => $tryout->tryout_id]) }}" 
           class="block w-full py-2.5 text-white text-center rounded-xl font-medium hover:opacity-90 transition-opacity"
           style="background-color: {{ $isInProgress ? '#f59e0b' : $primaryColor }}">
            {{ $isInProgress ? 'Lanjutkan' : 'Kerjakan' }}
        </a>
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
    <p class="text-gray-400 text-sm mb-6">Kamu belum memiliki akses ke tryout apapun.</p>
    <a href="{{ route('user.package.index') }}" class="inline-flex items-center px-6 py-3 text-white rounded-xl font-medium hover:opacity-90 transition-opacity" style="background-color: {{ $primaryColor }}">
        <i class="ri-store-3-line mr-2"></i>Lihat Paket
    </a>
</div>
@endif
@endsection
