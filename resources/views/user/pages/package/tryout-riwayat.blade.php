@extends('user.layout.new-user')

@section('title', 'Riwayat Tryout - ' . $tryout->name)

@section('content')
@php
$primaryColor = $clientBranding['primary_color'] ?? '#10b981';
@endphp

<!-- Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('user.package.tryout.list') }}" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <i class="ri-arrow-left-line text-xl text-gray-600"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Riwayat Tryout</h1>
        <p class="text-gray-500 text-sm">{{ $tryout->name }}</p>
    </div>
</div>

@if(count($attemptHistory) > 0)
<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
    <div class="divide-y divide-gray-100">
        @foreach($attemptHistory as $attempt)
        <div class="p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white" style="background-color: {{ $primaryColor }}">
                        <i class="ri-file-list-3-line"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">Percobaan {{ $loop->iteration }}</p>
                        <p class="text-xs text-gray-400">{{ $attempt['created_at']->format('d M Y, H:i') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold" style="color: {{ $primaryColor }}">{{ $attempt['score'] }}</p>
                    <span class="text-xs {{ $attempt['is_passed'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $attempt['is_passed'] ? 'Lulus' : 'Belum Lulus' }}
                    </span>
                </div>
            </div>
            
            <div class="grid grid-cols-3 gap-4 mt-4 pt-4 border-t border-gray-100">
                <div class="text-center">
                    <p class="text-xs text-gray-400 mb-1">Benar</p>
                    <p class="font-semibold text-green-600">{{ $attempt['correct_answers'] }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-400 mb-1">Salah</p>
                    <p class="font-semibold text-red-600">{{ $attempt['wrong_answers'] }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-400 mb-1">Durasi</p>
                    <p class="font-semibold text-gray-700">{{ $attempt['duration'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
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
